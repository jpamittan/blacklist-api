<?php

namespace Cashbee\Services\AI;

use Cashbee\Services\AI\StarDetect\FaceAPI as StarDetectFaceAPI;
use Cashbee\Services\AI\AdvanceAI\FaceAPI as AdvanceAIDetectFaceAPI;
use Cashbee\Services\AI\AdvanceAI\Blacklist as AdvanceAIBlacklistAPI;

use Cashbee\Models\{ Customer, BlacklistLog, Order, OrderLog, User, Blacklist as BlacklistModel, ThirdPartyApiLog };

class ThirdPartyBlacklistCheckService
{
    protected $stardetectfaceAPI;
    protected $advanceAIDetectFaceAPI;
    protected $advanceAIBlacklistAPI;

    protected $customer;
    protected $order;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Customer $customer, Order $order)
    {
        $this->stardetectfaceAPI        = new StarDetectFaceAPI;
        $this->advanceAIDetectFaceAPI   = new AdvanceAIDetectFaceAPI;
        $this->advanceAIBlacklistAPI    = new AdvanceAIBlacklistAPI($customer);

        $this->customer                 = $customer;
        $this->order                    = $order;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Check First if Already Rejected ...
        $this->order->refresh();
        if ($this->order->status->system_name == 'reject') {
            return false;
        }

        $thirdPartyApiLogCheckForToday = ThirdPartyApiLog::whereServiceName('cashbee-thirdparty-log')
            ->whereDate('created_at', date('Y-m-d'))
            ->whereCustomerId($this->customer->id)
            ->first();

        // If have record today ...
        // Do not proceed ...
        if ($thirdPartyApiLogCheckForToday) {
            return false;
        }

        ThirdPartyApiLog::create([
            'customer_id' => $this->customer->id,
            'type' => 'blacklist',
            'service_name' => 'cashbee-thirdparty-log',
            'module_name' => 'cashbee-thirdparty-check',
            'response_data' => []
        ]);

        // Advanced AI Blacklist ...
        $response = $this->advanceAIBlacklistAPI->process();
        if (isset($response->data) &&
            ($response->data->hitIdNumber || $response->data->hitPhoneNumber || $response->data->hitNameAndBirthday)) {
            // update to Reject ...
            $this->_addCustomerToBlacklist($this->customer, 'advance-ai-blacklist-api');
            $this->_addBlacklistLog($this->advanceAIBlacklistAPI->provideBlacklistLogData($response));
            return false;
        }

        $response = $this->advanceAIDetectFaceAPI->process($this->customer);
        if (isset($response->data) && $response->data->hitCount > 0) {
            // Update to Reject ...
            $this->_addCustomerToBlacklist($this->customer, 'advance-ai-face-api');
            $this->_addBlacklistLog($this->advanceAIDetectFaceAPI->provideBlacklistLogData($this->customer, $response));
            return false;
        }

        $response = $this->stardetectfaceAPI->process($this->customer);
        $starDetectorScore = isset($response['result'][0]) ? (int) $response['result'][0]['score'] : 0;
        if ($starDetectorScore > 0) {
            // Update to Reject ...
            $this->_addCustomerToBlacklist($this->customer, 'star-detect-face-api');
            $this->_addBlacklistLog($this->stardetectfaceAPI->provideBlacklistLogData($this->customer, $response));
            return false;
        }

        // No BlackList ...
        return true;
    }

    protected function _addBlacklistLog($data)
    {
        $aiRobotUser = User::whereEmail('ai-robot@cashjeep.ph')->first();

        $reason = 'Is Blacklisted on Third Party Source: ' . $data['source'];

        $lastOrderStatusId = $this->order->status_id;
        $this->order->setStatus('reject');
        $this->order->refresh();

        // Create BlackListLog ...
        BlacklistLog::create($data);

        OrderLog::create([
            'order_id' => $this->order->id,
            'previous_status_id' => $lastOrderStatusId,
            'current_status_id' => $this->order->status_id,
            'event_name' => "third_party_blacklist_reject_order",
            'operator_id' => $aiRobotUser->id,
            'remarks' => $reason,
            'reason' => $reason
        ]);
    }

    protected function _addCustomerToBlacklist(Customer $customer, $typeName = '')
    {
        $accountPhone = str_replace('+', '', $customer->account_phone);

        $blacklist = BlacklistModel::whereMobileNumber($accountPhone)->first();

        if (! $blacklist) {
            $blacklist = new BlacklistModel();
        }

        $blacklist->customer_id = $customer->id;
        $blacklist->mobile_number = $accountPhone;
        $blacklist->name = $customer->name;
        $blacklist->type = $typeName;
        $blacklist->save();
    }
}