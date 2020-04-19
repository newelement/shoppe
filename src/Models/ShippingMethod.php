<?php

namespace Newelement\Shoppe\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingMethod extends Model
{

    public function methodClasses()
    {
        return $this->hasMany('Newelement\Shoppe\Models\ShippingMethodClass');
    }

}
