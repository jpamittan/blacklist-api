<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBlacklistsTable extends Migration
{
    public function up()
    {
        Schema::create('blacklists', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('mobile_number')->nullable();
            $table->string('name')->nullable();
            $table->string('identification_type')->nullable();
            $table->string('identification_number')->nullable();
            $table->date('birthdate')->nullable();
            $table->text('front_of_id_card')->nullable();
            $table->string('type')->nullable();
            $table->timestamps(0);
        });
    }

    public function down()
    {
        Schema::dropIfExists('blacklists');
    }
}