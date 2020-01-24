<?php

namespace Newelement\Shoppe\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariationAttribute extends Model
{
    protected $fillable = [
            'product_id',
            'attribute_id',
            'values'
        ];

    public function getProductAttribute()
    {
        return $this->hasOne('\Newelement\Shoppe\Models\ProductAttribute', 'id', 'attribute_id');
    }

    public function jsonValues()
    {
        return json_decode( $this->values );
    }
}
