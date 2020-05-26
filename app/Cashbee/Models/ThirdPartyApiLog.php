<?php

namespace Cashbee\Models;

use Illuminate\Database\Eloquent\Model;

class ThirdPartyApiLog extends Model
{
    protected $fillable = ['customer_id', 'type', 'service_name', 'module_name', 'response_data'];

    protected $casts = [
        'response_data' => 'array'
    ];
}