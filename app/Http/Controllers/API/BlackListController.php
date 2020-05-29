<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\BlacklistRequest;
use Cashbee\Services\AI\ThirdPartyBlacklistCheckService;
use Cashbee\Models\{ Blacklist as BlacklistModel };

class BlackListController extends Controller
{
    public function getBlacklist(string $mobileNumber): ?BlacklistModel
    {
        try {
            $response = BlacklistModel::whereMobileNumber($mobileNumber)->first();
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return $response;
    }

    public function getBlacklistByName(string $fname, string $lname): ?BlacklistModel
    {
        try {
            $response = BlacklistModel::Where('name', 'like', '%' . $fname . '%')
                ->where('name', 'like', '%' . $lname . '%')
                ->first();
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return $response;
    }

    public function checkBlacklist(BlacklistRequest $request): object
    {
        try {
            $response = (new ThirdPartyBlacklistCheckService(
                $request->get('mobile_number'),
                $request->get('name'),
                $request->get('country_code'),
                $request->get('identification_type'),
                $request->get('identification_number'),
                $request->get('front_of_id_card'),
                $request->get('birthdate')
            ))->handle();
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['result' => $response], 200);
    }
}
