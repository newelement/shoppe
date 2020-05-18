<?php

namespace Newelement\Shoppe\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiscountCode extends Model
{
    use SoftDeletes;

    protected $casts = [
        'expires_on' => 'datetime',
    ];

}
