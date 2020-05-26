<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Cashbee\Jobs\ProcessThirdPartyBlacklist;
use Cashbee\Models\Blacklist;


class BlackListController extends Controller
{
    public function getBlacklist(Request $request)
    {
        try {

            $response = BlackList::whereMobileNumber($request->get('phoneNumber'));

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return $response;
    }

    public function checkBlacklist(Request $request)
    {
        try {

            $response = ProcessThirdPartyBlacklist::dispatch($request->get('phoneNumber'));

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return $response;
    }
}
