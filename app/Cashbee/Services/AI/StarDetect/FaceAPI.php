<?php

namespace Cashbee\Services\AI\StarDetect;

use Cashbee\Models\SystemSetting;
use \Curl;

class FaceAPI
{
    protected $apiURL       = 'https://rest.star-detector.ph.starwin.tech/';
    // This will expire Oct 31, 2021
    protected $token        = 'eyJhbGciOiJIUzUxMiJ9.eyJzdWIiOiJkOWJjYzhjOTJhZDM0MzQwYTFhMzc4YTQ2OGIzNzQwMCIsImV4cCI6MTYzNTY1MjM0MSwib3duZXJJZCI6NzZ9.Q-KWgf5r_S2OdbSDM12I_nNMA0vgyIX-7Kc2IB8RA3ahtsiqiKbynTjyrcKRPC4b9DYSljo_OFWPeiMWWs0X4g';
    protected $accessKey    = 'd9bcc8c92ad34340a1a378a468b37400';
    protected $accessSecret = '4387BF63E515227635D387222497244D7BFD5BAD8BD7ED0514894F5500BAEC94';
    protected $mobileNumber;
    protected $name;
    protected $countryCode;
    protected $identificationType;
    protected $identificationNumber;
    protected $frontOfIdCard;
    protected $birthdate;

    public function __construct($mobile_number, $name, $cCode, $idtype, $idNumber, $frontId, $bdate)
    {
        $this->mobileNumber         = $mobile_number;
        $this->name                 = $name;
        $this->countryCode          = $cCode;
        $this->identificationType   = $idtype;
        $this->identificationNumber = $idNumber;
        $this->frontOfIdCard        = $frontId;
        $this->birthdate            = $bdate;
    }

    public function createToken()
    {
        $signature = hash('sha256', $this->accessSecret . $this->accessKey);
        $signature = strtoupper($signature);
        $response = Curl::to($this->apiURL  . 'token?key=' . $this->accessKey . '&hours=17520' . '&signature=' . $signature)->get();
        var_dump($response);
    }

    public function provideBlacklistLogData($response)
    {
        return [
            'source'          => 'stardetect_ai_faceapi',
            'source_response' => $response,
            'blacklisted'     => isset($response['result'][0]),
            'score'           => isset($response['result'][0]) ? $response['result'][0]['score'] : 0,
            'mobile_number'   => $this->mobileNumber
        ];
    }

    public function process()
    {
        $setting = SystemSetting::whereType('thirdparty_blacklist_settings')->first();
        if (isset($setting->settings['stardetect_ai_faceapi']) && ! $setting->settings['stardetect_ai_faceapi']) {
            return [];
        }
        $imageUrl = $this->frontOfIdCard;
        if (empty($imageUrl)) {
            return 'No Face ID Image';
        }
        $image = file_get_contents($imageUrl);
        if (empty($image)) {
            return 'ERROR';
        }
        $image = base64_encode($image);
        $response = Curl::to($this->apiURL  . 'pre-monitor/face')
            ->withHeader('X-AUTH-TOKEN: ' . $this->token)
            ->withData(['photo' => $image])
            ->post();
        $response = json_decode($response, true);

        return $response;
    }
}
