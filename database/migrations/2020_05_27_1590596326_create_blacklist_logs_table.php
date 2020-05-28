<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBlacklistLogsTable extends Migration
{
    public function up()
    {
        Schema::create('blacklist_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('mobile_number');
            $table->string('score')->nullable();
            $table->string('source');
            $table->text('source_response')->nullable();
            $table->tinyInteger('blacklisted')->default('0');
            $table->timestamps(0);
        });
    }

    public function down()
    {
        Schema::dropIfExists('blacklist_logs');
    }
}