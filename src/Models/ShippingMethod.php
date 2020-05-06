<?php

namespace Newelement\Shoppe\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShippingMethod extends Model
{
    use SoftDeletes;

    public function methodClasses()
    {
        return $this->hasMany('Newelement\Shoppe\Models\ShippingMethodClass');
    }

}
