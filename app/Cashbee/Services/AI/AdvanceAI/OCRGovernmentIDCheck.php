<?php

namespace Cashbee\Services\AI\AdvanceAI;

use Cashbee\Services\AI\AdvanceAI\AdvancedGuardianAbstract;
use Cashbee\Models\{ Customer };

class OCRGovernmentIDCheck extends AdvancedGuardianAbstract
{
    public function process()
    {
        // cardTypes 
        // UMID
        // SSS
        // TIN
        $cardType = $this->_getCardTypeFromCustomer($this->customer->identification_type);

        /**
         * If Advanced AI does not support the 
         */
        if (empty($cardType)) {
            echo "\n\nCard Type is not Supported\n\n";
            return $this;
        }

        $idImage = $this->customer->id_images()->where('type', 'front_of_id_card')->first();

        if (! $idImage) {
            echo 'No Face ID Image';
            return $this;
        }

        $image      = file_get_contents($idImage->url);
        $filename   = rand(111111111, 999999999) . '.jpg';

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