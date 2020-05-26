<?php

namespace Cashbee\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Blacklist extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_id', 'name', 'mobile_number', 'type'
    ];
}