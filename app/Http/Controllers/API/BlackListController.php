<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Requests\BlacklistRequest;
use Cashbee\Jobs\ProcessThirdPartyBlacklist;
use Cashbee\Models\Blacklist;

class BlackListController extends Controller
{
    public function getBlacklist(string $mobileNumber)
    {
        try {
            $response = BlackList::whereMobileNumber($mobileNumber);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return $response;
    }

    public function checkBlacklist(BlacklistRequest $request)
    {
        try {
            $ProcessThirdPartyBlacklist = new ProcessThirdPartyBlacklist(
                $request->get('mobile_number'),
                $request->get('name'),
                $request->get('country_code'),
                $request->get('identification_type'),
                $request->get('identification_number'),
                $request->get('front_of_id_card'),
                $request->get('birthdate')
            );
            $response = $ProcessThirdPartyBlacklist->handle();
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['result' => $response], 200);
    }
}
