<?php

namespace Cashbee\Services\AI\AdvanceAI;

use Cashbee\Services\AI\AdvanceAI\AdvancedGuardianAbstract;

class NumberOnlineTimeCheck extends AdvancedGuardianAbstract
{
    public function process()
    {
        $requestData = [
            'phoneNumber' => $this->mobileNumber
        ];
        $response = $this->api->request('/ph/openapi/verification/v1/online-time-check', $requestData);
        $this->responseData = json_decode($response);

        return $this;
    }
}
