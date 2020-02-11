<?php

namespace Newelement\Shoppe\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cart extends Model
{
    use SoftDeletes;

    public function product()
    {
        return $this->belongsTo('\Newelement\Shoppe\Models\Product');
    }

    public function productVariation()
    {
        return $this->belongsTo('\Newelement\Shoppe\Models\ProductVariation');
    }
}
