<?php

namespace Cashbee\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use Cashbee\Services\AI\StarDetect\FaceAPI as StarDetectFaceAPI;
use Cashbee\Services\AI\AdvanceAI\FaceAPI as AdvanceAIDetectFaceAPI;
use Cashbee\Services\AI\AdvanceAI\Blacklist as AdvanceAIBlacklistAPI;

use Cashbee\Models\{BlacklistLog, Blacklist as BlacklistModel};

class ProcessThirdPartyBlacklist implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $stardetectfaceAPI;
    protected $advanceAIDetectFaceAPI;
    protected $advanceAIBlacklistAPI;

    protected $customer;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Customer $customer)
    {
        $this->stardetectfaceAPI        = new StarDetectFaceAPI;
        $this->advanceAIDetectFaceAPI   = new AdvanceAIDetectFaceAPI;
        $this->advanceAIBlacklistAPI    = new AdvanceAIBlacklistAPI;

        $this->customer                 = $customer;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Advanced AI Blacklist ...
        $response = $this->advanceAIBlacklistAPI->process($this->customer);
        if (isset($response->data) && $response->data->hit == 1) {
            // update to Reject ...
            $this->_addCustomerToBlacklist($this->customer, 'advance-ai-blacklist-api');
            $this->_addBlacklistLog($this->advanceAIBlacklistAPI->provideBlacklistLogData($this->customer, $response));
            return;
        }

        $response = $this->advanceAIDetectFaceAPI->process($this->customer);
        if (isset($response->data) && $response->data->hitCount > 0) {
            // Update to Reject ...
            $this->_addCustomerToBlacklist($this->customer, 'advance-ai-face-api');
            $this->_addBlacklistLog($this->advanceAIDetectFaceAPI->provideBlacklistLogData($this->customer, $response));
            return;
        }

        // $response['result'][0]['score']
        $response = $this->stardetectfaceAPI->process($this->customer);
        $starDetectorScore = isset($response['result'][0]) ? (int) $response['result'][0]['score'] : 0;
        if ($starDetectorScore > 0) {
            // Update to Reject ...
            $this->_addCustomerToBlacklist($this->customer, 'star-detect-face-api');
            $this->_addBlacklistLog($this->stardetectfaceAPI->provideBlacklistLogData($this->customer, $response));
            return;
        }

        // No BlackList ...
        return;
    }

    protected function _addBlacklistLog($data)
    {
        BlacklistLog::create($data);
    }

    protected function _addCustomerToBlacklist($id, $account_phone, $name, $typeName = '')
    {
        $accountPhone = str_replace('+', '', $account_phone);

        $blacklist = BlacklistModel::whereMobileNumber($accountPhone)->first();

        if (! $blacklist) {
            $blacklist = new BlacklistModel();
        }
        
        $blacklist->customer_id = $id;
        $blacklist->mobile_number = $accountPhone;
        $blacklist->name = $name;
        $blacklist->type = $typeName;
        $blacklist->save();
    }
}
