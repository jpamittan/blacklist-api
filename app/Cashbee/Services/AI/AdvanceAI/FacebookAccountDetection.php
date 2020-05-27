<?php

namespace Cashbee\Services\AI\AdvanceAI;

use Cashbee\Services\AI\AdvanceAI\AdvancedGuardianAbstract;

class FacebookAccountDetection extends AdvancedGuardianAbstract
{
    public function process()
    {
        $countryCode = $this->countryCode;
        $requestData = [
            'countryCode' => $countryCode,
            'number' => str_replace($countryCode, '', $this->mobileNumber)
        ];
        $response = $this->api->request('/ph/openapi/verification/v1/facebook-account-detection', $requestData);
        $this->responseData = json_decode($response);

        return $this;
    }
}