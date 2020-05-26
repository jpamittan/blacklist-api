<?php

namespace Cashbee\Services\AI\AdvanceAI;

use Cashbee\Services\AI\AdvanceAI\AdvancedGuardianAbstract;
use Cashbee\Models\{Customer};

class EcommerceAccountDetection extends AdvancedGuardianAbstract
{
    public function process()
    {
        $countryCode = $this->customer->getPhoneCountryCode();

        $requestData = [
            'countryCode' => $countryCode,
            'number' => str_replace($countryCode, '', $this->customer->account_phone)
        ];

        $response = $this->api->request('/ph/openapi/verification/v1/ecommerce-account-detection', $requestData);

        $this->responseData = json_decode($response);

        return $this;
    }
}
