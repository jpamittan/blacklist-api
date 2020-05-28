<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateThirdPartyApiLogsTable extends Migration
{
    public function up()
    {
        Schema::create('third_party_api_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('mobile_number')->nullable();
            $table->string('type')->default('blacklist');
            $table->string('service_name')->nullable();
            $table->string('module_name')->nullable();
            $table->text('response_data')->nullable();
            $table->timestamps(0);
        });
    }

    public function down()
    {
        Schema::dropIfExists('third_party_api_logs');
    }
}