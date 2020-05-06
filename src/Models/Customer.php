<?php

namespace Newelement\Shoppe\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use App\User;
use Newelement\Neutrino\Models\Role;
use Auth;


class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'customer_id',
        'payment_connector'
    ];

    public static function saveCustomer( $checkout, $user )
    {
        $insert = self::updateOrCreate(
           ['user_id' => $user->id,],
           [ 'customer_id' => $checkout['customer_id'], 'payment_connector' => $checkout['payment_connector'] ]
        );

        return $insert;
    }

    public static function createOrGet( $name, $email, $password = false )
    {
        $userExists = User::where( 'email', $email )->first();

        if( !$userExists ){

            $newPassword = $password? $password : \Str::random(12);
            $role = Role::where('name', 'customer')->first();

            $user = new User;
            $user->name = $name;
            $user->email = strtolower($email);
            $user->password = Hash::make( $newPassword );
            $user->avatar = '/vendor/newelement/neutrino/images/default.png';
            $user->role_id = $role->id;
            $user->save();

        } else {
            $user = Auth::check()? Auth::user() : $userExists;
        }

        return $user;
    }

}
