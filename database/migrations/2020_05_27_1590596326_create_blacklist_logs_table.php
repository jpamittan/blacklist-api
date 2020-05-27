<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBlacklistLogsTable extends Migration
{
    public function up()
    {
        Schema::create('blacklist_logs', function (Blueprint $table) {
            $table->bigIncrements('id',20);
            $table->string('mobile_number');
            $table->string('score')->nullable()->default('NULL');
            $table->string('source');
            $table->text('source_response')->nullable()->default('NULL');
            $table->tinyInteger('blacklisted',1)->default('0');
            $table->timestamp('created_at')->nullable()->default('NULL');
            $table->timestamp('updated_at')->nullable()->default('NULL');
        });
    }

    public function down()
    {
        Schema::dropIfExists('blacklist_logs');
    }
}