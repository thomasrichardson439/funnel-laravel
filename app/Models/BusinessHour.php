<?php

namespace App\Models;

use App\Models\Traits\HasOpenableHours;
use Illuminate\Database\Eloquent\Model;

class BusinessHour extends Model
{
    protected $guarded = ['id'];

    protected $dates = ['deleted_at'];

    public function business()
    {
    	return $this->belongsTo(Business::class);
    }

}
