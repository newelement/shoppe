<?php

namespace Newelement\Shoppe\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Newelement\Searchable\SearchableTrait;

class ProductVariation extends Model
{
    use SoftDeletes;
    use SearchableTrait;

    protected $searchable = [
        'columns' => [
            'desc' => 7
        ],
    ];

    protected $fillable = [
            'product_id',
            'attribute_set',
            'image',
            'desc',
            'price',
            'sale_price',
            'sku',
            'mfg_part_number',
            'stock',
            'weight',
            'width',
            'height',
            'depth'
        ];

    public function getAttributeSetAttribute($value)
    {
        return json_decode($value);
    }

    public function getAttributeValuesAttribute($value)
    {
        return json_decode($value);
    }
}
