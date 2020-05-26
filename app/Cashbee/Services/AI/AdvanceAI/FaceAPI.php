<?php

namespace Cashbee\Services\AI\AdvanceAI;

use Cashbee\Models\Customer;

use Zackyjack\AdvanceAI\CurlClient;

class FaceAPI
{
    protected $api;

    public function __construct()
    {
        $host       = 'https://ph-api.advance.ai';
        $accessKey  = 'c14ea81a364e232d';
        $secretKey  = '91c4ded91071b197';

        $this->api = new CurlClient($host, $accessKey, $secretKey);
    }

    public function provideBlacklistLogData(Customer $customer, $response)
    {
        return [
            'source'            => 'advance_ai_faceapi',
            'source_response'   => $response,
            'blacklisted'       => $response->data->hitCount > 0,
            'score'             => $response->data->hitCount,
            'customer_id'       => $customer->id
        ];
    }

    public function process(Customer $customer)
    {
        $idImage = $customer->id_images()
            ->where('type', 'handheld_id_card')
            ->first();

        if (!$idImage) {
            echo 'No Face ID Image';
            return 'No Face ID Image';
        }

        $image      = file_get_contents($idImage->url);
        $filename   = rand(111111111, 999999999) . '.jpg';

        \Storage::disk('local')->put($filename, $image);

        $request_data = [
            'faceImage' => \Storage::disk('local')->getDriver()->getAdapter()->getPathPrefix() . '/' . $filename
        ];

        $result = $this->api->request('/ph/openapi/face-identity/v1/face-blacklist', ['imageType' => 'PHOTO_FACE'], $request_data);

        \Storage::disk('local')->delete($filename);

        return json_decode($result);
    }
}
