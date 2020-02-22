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

    public static function checkExistingAddress($user, $shippingAddress, $type)
    {
        $addresses = self::where([ 'user_id' => $user->id, 'address_type' => $type ])->get();

        foreach( $addresses as $address ){
            if(
                self::sanitizeAddress($shippingAddress['street1']) === self::sanitizeAddress($address->address) &&
                self::sanitizeAddress($shippingAddress['street2']) === self::sanitizeAddress($address->address2) &&
                self::sanitizeAddress($shippingAddress['city']) === self::sanitizeAddress($address->city) &&
                self::sanitizeAddress($shippingAddress['zip']) === self::sanitizeAddress($address->zipcode) &&
                self::sanitizeAddress($shippingAddress['country']) === self::sanitizeAddress($address->country)
            ){
                return $address;
            }
        }

        return false;
    }

    public static function sanitizeAddress($string)
    {
        $string = trim($string);
        $string = str_replace(' ', '', $string);
        $string = strtoupper($string);
        return $string;
    }

}
