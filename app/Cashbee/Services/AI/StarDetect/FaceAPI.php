<?php

namespace Cashbee\Services\AI\StarDetect;

use Cashbee\Models\{ Customer, SystemSetting };

use \Curl;

class FaceAPI
{
    // TEST
    // protected $apiURL = 'http://47.75.14.45/';

    // Live
    protected $apiURL = 'https://rest.star-detector.ph.starwin.tech/';

    // This will expire Oct 31, 2021
    protected $token = 'eyJhbGciOiJIUzUxMiJ9.eyJzdWIiOiJkOWJjYzhjOTJhZDM0MzQwYTFhMzc4YTQ2OGIzNzQwMCIsImV4cCI6MTYzNTY1MjM0MSwib3duZXJJZCI6NzZ9.Q-KWgf5r_S2OdbSDM12I_nNMA0vgyIX-7Kc2IB8RA3ahtsiqiKbynTjyrcKRPC4b9DYSljo_OFWPeiMWWs0X4g';

    protected $accessKey    = 'd9bcc8c92ad34340a1a378a468b37400';
    protected $accessSecret = '4387BF63E515227635D387222497244D7BFD5BAD8BD7ED0514894F5500BAEC94';

    public function __construct()
    {
        // $this->createToken();
    }

    public function createToken()
    {
        $signature = hash('sha256', $this->accessSecret . $this->accessKey);
        $signature = strtoupper($signature);

        $response = Curl::to($this->apiURL  . 'token?key=' . $this->accessKey . '&hours=17520' . '&signature=' . $signature)
            ->get();

        var_dump($response);
    }

    public function provideBlacklistLogData(Customer $customer, $response)
    {
        return [
            'source'            => 'stardetect_ai_faceapi',
            'source_response'   => $response,
            'blacklisted'       => isset($response['result'][0]),
            'score'             => isset($response['result'][0]) ? $response['result'][0]['score'] : 0,
            'customer_id'       => $customer->id
        ];
    }

    public function process(Customer $customer)
    {
        // $url = 'https://cashbee.s3.ap-southeast-1.amazonaws.com/customers/844/id_images/782225373.jpg';
        // $url = 'https://cashbee.s3.ap-southeast-1.amazonaws.com/customers/845/id_images/899598575.jpg';

        $setting = SystemSetting::whereType('thirdparty_blacklist_settings')->first();
        if (isset($setting->settings['stardetect_ai_faceapi']) && ! $setting->settings['stardetect_ai_faceapi']) {
            return [];
        }

        $idImage = $customer->id_images()
            ->where('type', 'handheld_id_card')
            ->first();

        if (!$idImage) {
            echo 'No Face ID Image';
            return 'No Face ID Image';
        }

        $imageUrl = $idImage->url;

        $image = file_get_contents($imageUrl);

        if (!$image) {
            echo 'ERROR';
            return 'ERROR';
        }

        $image = base64_encode($image);

        $response = Curl::to($this->apiURL  . 'pre-monitor/face')
            // Live Token 
            ->withHeader('X-AUTH-TOKEN: ' . $this->token)
            // Test Token
            // ->withHeader('X-AUTH-TOKEN: eyJhbGciOiJIUzUxMiJ9.eyJzdWIiOiI5MmQwOGUzNjgzNzQ0MTRiYjU4NDgxMzQwMDA0NjBmNSIsImV4cCI6MTg4MTkyMDg1Niwib3duZXJJZCI6Mn0.bBaUZuNyRygPJxVs5kHUyk-dBPz1P9Gg20rB1VoD7LBNVhuEFNFVExUcI4nDFuGqSl3QOvceAcv3Wm1YFTmoWA')
            ->withData(['photo' => $image])
            ->post();

        $response = json_decode($response, true);

        return $response;
    }
}
