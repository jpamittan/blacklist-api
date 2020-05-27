<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateThirdPartyApiLogsTable extends Migration
{
    public function up()
    {
        Schema::create('third_party_api_logs', function (Blueprint $table) {
            $table->bigIncrements('id',20);
            $table->string('mobile_number')->nullable()->default('NULL');
            $table->string('type')->default('blacklist');
            $table->string('service_name')->nullable()->default('NULL');
            $table->string('module_name')->nullable()->default('NULL');
            $table->text('response_data')->nullable()->default('NULL');
            $table->timestamp('created_at')->nullable()->default('NULL');
            $table->timestamp('updated_at')->nullable()->default('NULL');
        });
    }

    public function down()
    {
        Schema::dropIfExists('third_party_api_logs');
    }
}