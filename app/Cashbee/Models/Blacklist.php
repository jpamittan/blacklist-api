<?php

namespace Cashbee\Models;

use Illuminate\Database\Eloquent\Model;

class Blacklist extends Model
{
    protected $fillable = [
        'mobile_number',
        'name',
        'identification_type',
        'identification_number',
        'birthdate',
        'front_of_id_card',
        'type'
    ];
}