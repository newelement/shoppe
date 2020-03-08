<?php

namespace Newelement\Shoppe\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;

class OrderNote extends Model
{
    use SoftDeletes;


    public static function boot()
    {
        parent::boot();
        static::creating(function($model){
            if (!app()->runningInConsole()) {
               $user = Auth::user();
               $model->created_by = $user->id;
               $model->updated_by = $user->id;
           } else{
               $model->created_by = 1;
                   $model->updated_by = 1;
           }
        });

        static::updating(function($model){
            $user = Auth::user();
            $model->updated_by = $user->id;
        });

    }

    public function createdUser()
    {
        return $this->belongsTo('\Newelement\Neutrino\Models\User', 'created_by');
    }

    public function updatedUser()
    {
        return $this->belongsTo('\Newelement\Neutrino\Models\User', 'updated_by');
    }
}
