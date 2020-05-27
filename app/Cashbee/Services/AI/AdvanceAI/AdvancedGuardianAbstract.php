<?php

namespace Cashbee\Services\AI\AdvanceAI;

use Cashbee\Models\{ SystemSetting, ThirdPartyApiLog };
use Cashbee\Services\AI\AdvanceAI\{ EcommerceAccountDetection, FacebookAccountDetection, NumberOnlineTimeCheck, OCRGovernmentIDCheck, TeleStatusCheck };
use Zackyjack\AdvanceAI\CurlClient;

Abstract class AdvancedGuardianAbstract
{
    protected $api;
    protected $advancedAIEnabled;
    protected $responseData = [];
    protected $mobileNumber;
    protected $name;
    protected $countryCode;
    protected $identificationType;
    protected $identificationNumber;
    protected $frontOfIdCard;
    protected $birthdate;

    /**
     * Instantiate Advance AI credentials and CurlClient
     */
    public function __construct($mobile_number, $name, $cCode, $idtype, $idNumber, $frontId, $bdate)
    {
        $getAdvanceCredentials = SystemSetting::GetAdvanceCredentials()->get()->first();
        $this->api = new CurlClient(
            $getAdvanceCredentials->settings["host"],
            $getAdvanceCredentials->settings["access_key"],
            $getAdvanceCredentials->settings["secret_key"]
        );
        $this->advancedAIEnabled    = $getAdvanceCredentials->enabled;
        $this->mobileNumber         = $mobile_number;
        $this->name                 = $name;
        $this->countryCode          = $cCode;
        $this->identificationType   = $idtype;
        $this->identificationNumber = $idNumber;
        $this->frontOfIdCard        = $frontId;
        $this->birthdate            = $bdate;
    }

    public function processOtherAdvancedGuardianAPIs()
    {
        (new EcommerceAccountDetection(
            $this->mobileNumber,
            $this->name,
            $this->countryCode,
            $this->identificationType,
            $this->identificationNumber,
            $this->frontOfIdCard,
            $this->birthdate
        ))
            ->process()
            ->logData();

        (new FacebookAccountDetection(
            $this->mobileNumber,
            $this->name,
            $this->countryCode,
            $this->identificationType,
            $this->identificationNumber,
            $this->frontOfIdCard,
            $this->birthdate
        ))
            ->process()
            ->logData();

        (new NumberOnlineTimeCheck(
            $this->mobileNumber,
            $this->name,
            $this->countryCode,
            $this->identificationType,
            $this->identificationNumber,
            $this->frontOfIdCard,
            $this->birthdate
        ))
            ->process()
            ->logData();

        $ocrCheck = (new OCRGovernmentIDCheck(
            $this->mobileNumber,
            $this->name,
            $this->countryCode,
            $this->identificationType,
            $this->identificationNumber,
            $this->frontOfIdCard,
            $this->birthdate
        ))
            ->process();

        if (! empty($ocrCheck->responseData)) {
            $ocrCheck->logData();
        }

        (new TeleStatusCheck(
            $this->mobileNumber,
            $this->name,
            $this->countryCode,
            $this->identificationType,
            $this->identificationNumber,
            $this->frontOfIdCard,
            $this->birthdate
        ))
            ->process()
            ->logData();

        return true;
    }

    public function logData()
    {
        $moduleName = str_replace("Cashbee\Services\AI\AdvanceAI\\", "", get_class($this));
        $moduleName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $moduleName));

        ThirdPartyApiLog::create([
            'mobile_number' => $this->mobileNumber, 
            'type' => 'blacklist',
            'service_name' => 'advanced_guardian_api',
            'module_name' => $moduleName, 
            'response_data' => $this->responseData
        ]);
    }
}