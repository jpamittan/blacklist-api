<?php

namespace Cashbee\Services\AI\AdvanceAI;

use Zackyjack\AdvanceAI\CurlClient;

class FaceAPI
{
    protected $api;
    protected $mobileNumber;
    protected $name;
    protected $countryCode;
    protected $identificationType;
    protected $identificationNumber;
    protected $frontOfIdCard;
    protected $birthdate;

    public function __construct($mobile_number, $name, $cCode, $idtype, $idNumber, $frontId, $bdate)
    {
        $host      = 'https://ph-api.advance.ai';
        $accessKey = 'c14ea81a364e232d';
        $secretKey = '91c4ded91071b197';
        $this->api = new CurlClient($host, $accessKey, $secretKey);
        $this->mobileNumber         = $mobile_number;
        $this->name                 = $name;
        $this->countryCode          = $cCode;
        $this->identificationType   = $idtype;
        $this->identificationNumber = $idNumber;
        $this->frontOfIdCard        = $frontId;
        $this->birthdate            = $bdate;
    }

    public function provideBlacklistLogData($response)
    {
        return [
            'source'          => 'advance_ai_faceapi',
            'source_response' => $response,
            'blacklisted'     => $response->data->hitCount > 0,
            'score'           => $response->data->hitCount,
            'mobile_number'   => $this->mobileNumber
        ];
    }

    public function process()
    {
        $imageUrl = $this->frontOfIdCard;
        if (empty($imageUrl)) {
            return 'No Face ID Image';
        }
        $image    = file_get_contents($imageUrl);
        $filename = rand(111111111, 999999999) . '.jpg';
        \Storage::disk('local')->put($filename, $image);
        $request_data = [
            'faceImage' => \Storage::disk('local')->getDriver()->getAdapter()->getPathPrefix() . '/' . $filename
        ];
        $result = $this->api->request('/ph/openapi/face-identity/v1/face-blacklist', ['imageType' => 'PHOTO_FACE'], $request_data);
        \Storage::disk('local')->delete($filename);

        return json_decode($result);
    }
}
