<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Center extends Model
{
    protected $fillable = [
        'center_name', 'center_code', 'branch_name',
        'meeting_day', 'meeting_time', 'field_officer',
    ];

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }
}
