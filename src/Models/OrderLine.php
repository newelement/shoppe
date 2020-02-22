<?php

namespace Newelement\Shoppe\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderLine extends Model
{
    use SoftDeletes;

    public $statuses = [
        1 => 'New',
        2 => 'Hold',
        3 => 'Complete',
        4 => 'Refunded',
        86 => 'Canceled'
    ];

    public function product()
    {
        return $this->belongsTo('Newelement\Shoppe\Models\Product');
    }

    public function getStatusFormattedAttribute()
    {
        return $this->statuses[ $this->status ];
    }

}
