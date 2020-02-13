<?php

namespace Newelement\Shoppe\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\User;

class Order extends Model
{
    use SoftDeletes;


    public function orderLines()
    {
        return $this->hasMany('Newelement\Shoppe\Models\OrderLine');
    }

}
