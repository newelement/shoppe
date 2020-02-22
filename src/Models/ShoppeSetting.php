<?php

namespace Newelement\Shoppe\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShoppeSetting extends Model
{
    use SoftDeletes;

    public function createdUser()
    {
        return $this->belongsTo('\Newelement\Neutrino\Models\User', 'created_by');
    }

    public function updatedUser()
    {
        return $this->belongsTo('\Newelement\Neutrino\Models\User', 'updated_by');
    }

}
