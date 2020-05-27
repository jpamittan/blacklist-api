<?php

namespace Cashbee\Jobs;

use Cashbee\Services\AI\StarDetect\FaceAPI as StarDetectFaceAPI;
use Cashbee\Services\AI\AdvanceAI\FaceAPI as AdvanceAIDetectFaceAPI;
use Cashbee\Services\AI\AdvanceAI\Blacklist as AdvanceAIBlacklistAPI;
use Cashbee\Models\{ BlacklistLog, Blacklist as BlacklistModel, ThirdPartyApiLog };

class ProcessThirdPartyBlacklist
{
    protected $stardetectfaceAPI;
    protected $advanceAIDetectFaceAPI;
    protected $advanceAIBlacklistAPI;
    protected $mobileNumber;
    protected $name;

    public function __construct($mobile_number, $name, $cCode, $idtype, $idNumber, $frontId, $bdate)
    {
        $this->stardetectfaceAPI      = new StarDetectFaceAPI($mobile_number, $name, $cCode, $idtype, $idNumber, $frontId, $bdate);
        $this->advanceAIDetectFaceAPI = new AdvanceAIDetectFaceAPI($mobile_number, $name, $cCode, $idtype, $idNumber, $frontId, $bdate);
        $this->advanceAIBlacklistAPI  = new AdvanceAIBlacklistAPI($mobile_number, $name, $cCode, $idtype, $idNumber, $frontId, $bdate);
        $this->mobileNumber           = $mobile_number;
        $this->name                   = $name;
    }

    public function handle()
    {
        $thirdPartyApiLogCheckForToday = ThirdPartyApiLog::whereServiceName('thirdparty-log')
            ->whereDate('created_at', date('Y-m-d'))
            ->whereMobileNumber($this->mobileNumber)
            ->first();

        if ($thirdPartyApiLogCheckForToday) {
            return false;
        }

        $response = $this->advanceAIBlacklistAPI->process();
        if (isset($response->data) &&
            ($response->data->hitIdNumber || $response->data->hitPhoneNumber || $response->data->hitNameAndBirthday)) {
            $this->_addCustomerToBlacklist('advance-ai-blacklist-api');
            $this->_addThirdPartyLog($response);
            $this->_addBlacklistLog($this->advanceAIBlacklistAPI->provideBlacklistLogData($response));
            return true;
        }

        $response = $this->advanceAIDetectFaceAPI->process();
        if (isset($response->data) && $response->data->hitCount > 0) {
            $this->_addCustomerToBlacklist('advance-ai-face-api');
            $this->_addThirdPartyLog($response);
            $this->_addBlacklistLog($this->advanceAIDetectFaceAPI->provideBlacklistLogData($response));
            return true;
        }

        $response = $this->stardetectfaceAPI->process();
        $starDetectorScore = isset($response['result'][0]) ? (int) $response['result'][0]['score'] : 0;
        if ($starDetectorScore > 0) {
            $this->_addCustomerToBlacklist('star-detect-face-api');
            $this->_addThirdPartyLog($response);
            $this->_addBlacklistLog($this->stardetectfaceAPI->provideBlacklistLogData($response));
            return true;
        }

        return false;
    }

    protected function _addBlacklistLog($data)
    {
        BlacklistLog::create($data);
    }

    protected function _addThirdPartyLog($response)
    {
        ThirdPartyApiLog::create([
            'mobile_number' => $this->mobileNumber,
            'type' => 'blacklist',
            'service_name' => 'thirdparty-log',
            'module_name' => 'thirdparty-check',
            'response_data' => $response
        ]);
    }

    protected function _addCustomerToBlacklist($typeName = '')
    {
        $mobileNumber = str_replace('+', '', $this->mobileNumber);
        $blacklist = BlacklistModel::whereMobileNumber($mobileNumbe)->first();
        if (! $blacklist) {
            $blacklist = new BlacklistModel();
        }
        $blacklist->mobile_number = $mobileNumbe;
        $blacklist->name = $this->name;
        $blacklist->type = $typeName;
        $blacklist->save();
    }
}
