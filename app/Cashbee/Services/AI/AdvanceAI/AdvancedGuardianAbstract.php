<?php

namespace Cashbee\Services\AI\AdvanceAI;

use Cashbee\Models\{ Customer, SystemSetting, ThirdPartyApiLog };
use Cashbee\Services\AI\AdvanceAI\{ EcommerceAccountDetection, FacebookAccountDetection, NumberOnlineTimeCheck, OCRGovernmentIDCheck, TeleStatusCheck };
use Zackyjack\AdvanceAI\CurlClient;

Abstract class AdvancedGuardianAbstract
{
    protected $api;
    protected $customer;
    protected $advancedAIEnabled;
    public $responseData = [];

    /**
     * Instantiate Advance AI credentials and CurlClient
     */
    public function __construct(Customer $customer)
    {
        $getAdvanceCredentials = SystemSetting::GetAdvanceCredentials()->get()->first();
        $this->api = new CurlClient(
            $getAdvanceCredentials->settings["host"],
            $getAdvanceCredentials->settings["access_key"],
            $getAdvanceCredentials->settings["secret_key"]
        );
        $this->customer = $customer;
        $this->advancedAIEnabled = $getAdvanceCredentials->enabled;
    }

    public function processOtherAdvancedGuardianAPIs(Customer $customer)
    {
        (new EcommerceAccountDetection($customer))
            ->process()
            ->logData();

        (new FacebookAccountDetection($customer))
            ->process()
            ->logData();

        (new NumberOnlineTimeCheck($customer))
            ->process()
            ->logData();

        $ocrCheck = (new OCRGovernmentIDCheck($customer))
            ->process();

        if (! empty($ocrCheck->responseData)) {
            $ocrCheck->logData();
        }

        (new TeleStatusCheck($customer))
            ->process()
            ->logData();

        return true;
    }

    public function logData()
    {
        $moduleName = str_replace("Cashbee\Services\AI\AdvanceAI\\", "", get_class($this));
        $moduleName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $moduleName));

        ThirdPartyApiLog::create([
            'customer_id' => $this->customer->id, 
            'type' => 'blacklist',
            'service_name' => 'advanced_guardian_api',
            'module_name' => $moduleName, 
            'response_data' => $this->responseData
        ]);
    }
}