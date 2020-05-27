<?php

namespace Cashbee\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = ['enabled', 'group', 'type', 'settings'];

    protected $casts = [
        'settings' => 'array'
    ];

    public function scopeGetAdvanceCredentials($query)
    {
        return $query
            ->where('group', 'credentials')
            ->where('type', 'advanced');
    }

    public function scopeGetAdvanceThirdPartyBlacklist($query)
    {
        return $query
            ->where('group', 'third_party_blacklist')
            ->where('type', 'advanced');
    }
}