<?php

namespace Newelement\Shoppe\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\User;

class Customer extends Model
{
    use SoftDeletes;

    public static function saveCard( $checkout )
    {

    }

}
