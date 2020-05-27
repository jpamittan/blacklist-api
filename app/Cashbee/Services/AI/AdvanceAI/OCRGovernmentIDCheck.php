<?php

namespace Cashbee\Services\AI\AdvanceAI;

use Cashbee\Services\AI\AdvanceAI\AdvancedGuardianAbstract;

class OCRGovernmentIDCheck extends AdvancedGuardianAbstract
{
    public function process()
    {
        $cardType = $this->_getCardTypeFromCustomer($this->identificationType);
        if (empty($cardType)) {
            return $this;
        }
        $idImage = $this->frontOfIdCard;
        if (! $idImage) {
            return $this;
        }
        $image    = file_get_contents($idImage);
        $filename = rand(111111111, 999999999) . '.jpg';
        \Storage::disk('local')->put($filename, $image);
        $requestData = [
            'cardType' => $cardType,
            'ocrImage' => \Storage::disk('local')->getDriver()->getAdapter()->getPathPrefix() . '/' . $filename
        ];
        $result = $this->api->request('/ph/openapi/face-identity/v1/ocr-lite', $requestData);
        \Storage::disk('local')->delete($filename);
        $this->responseData = json_decode($result);

        return $this;
    }

    protected function _getCardTypeFromCustomer($customerCard)
    {
        $cardType = '';
        switch ($customerCard) {
            case "Driver’s License":
            case "Passport (New)":
            case "Passport (Old)":
            case "Postal ID":
                $cardType = '';
                break;

            case "UMID (Unified Multi-Purpose ID)":
                $cardType = 'UMID';
                break;

            default:
                $cardType = '';
                break;
        }

        return $cardType;
    }
}