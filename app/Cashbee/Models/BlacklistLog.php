<?php

namespace Cashbee\Models;

use Illuminate\Database\Eloquent\Model;

class BlacklistLog extends Model
{
    protected $fillable = [
        'customer_id', 'score', 'source', 'source_response', 'blacklisted'
    ];

    protected $casts = [
        'source_response' => 'array'
    ];
}
