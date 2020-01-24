<?php

namespace Newelement\Shoppe\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;

class Cart extends Model
{
    public function product()
    {
        return $this->belongsTo('\Newelement\Shoppe\Models\Product');
    }

    public function productVariation()
    {
        return $this->belongsTo('\Newelement\Shoppe\Models\ProductVariation');
    }
}
