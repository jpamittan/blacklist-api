<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDefaultSettingsForBlacklist extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('system_settings')->insert([
            [
                'enabled' => 1,
                'group' => "third_party_blacklist",
                'type' => "advanced",
                'settings' => '{ "api_name": "/ph/openapi/verification/v2/blacklist-check", "id_type_mappings": [{ "SSS": [] }, { "GSIS": [] }, { "PHILHEALTH_ID": [] }, { "PRC": ["PRC ID (Professional Regulation Commission)"] }, { "UMID": ["UMID (Unified Multi-Purpose ID)"] }, { "TIN": [] }, { "DRIVER_LICENSE": ["Driver\'s License"] }, { "VOTERS_ID": [] }, { "PASSPORT": ["Passport (New)", "Passport (Old)", "Passport"] }, { "POSTAL_ID_PREMIUM": ["Postal ID"] }, { "STUDENT": [] } ] }'
            ],
            [
                'enabled' => 1,
                'group' => "credentials",
                'type' => "advanced",
                'settings' => '{ "host": "https://ph-api.advance.ai", "access_key": "c14ea81a364e232d", "secret_key": "91c4ded91071b197" }'
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('system_settings')
            ->where('group', 'third_party_blacklist')
            ->where('type', 'advanced')
            ->delete();

        DB::table('system_settings')
            ->where('group', 'credentials')
            ->where('type', 'advanced')
            ->delete();
    }
}
