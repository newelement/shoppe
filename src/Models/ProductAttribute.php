<?php

namespace Newelement\Shoppe\Models;
use Illuminate\Database\Eloquent\Model;

class ProductAttribute extends Model
{
    protected $fillable = [ 'name', 'values', 'slug' ];

    public function getValuesAttribute($value)
    {
        return implode(', ', json_decode($value, true));
    }

}
