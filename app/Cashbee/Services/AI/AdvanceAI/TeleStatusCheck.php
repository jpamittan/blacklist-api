<?php

namespace Cashbee\Services\AI\AdvanceAI;

use Cashbee\Services\AI\AdvanceAI\AdvancedGuardianAbstract;

class TeleStatusCheck extends AdvancedGuardianAbstract
{
    public function process()
    {
        $countryCode = $this->countryCode;
        $requestData = [
            'countryCode' => $countryCode,
            'number' => str_replace($countryCode, '', $this->mobileNumber)
        ];
        $response = $this->api->request('/ph/openapi/verification/v1/tele-status-check', $requestData);
        $this->responseData = json_decode($response);

        return $this;
    }
}
