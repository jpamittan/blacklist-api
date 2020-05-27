<?php

namespace Cashbee\Models;

use Illuminate\Database\Eloquent\Model;

class Blacklist extends Model
{
    protected $fillable = ['mobile_number', 'name', 'type'];
}