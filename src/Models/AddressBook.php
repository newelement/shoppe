<?php

namespace Newelement\Shoppe\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AddressBook extends Model
{
    public function user()
    {
        return $this->belongsTo('Newelement\Neutrino\Models\User');
    }
}
