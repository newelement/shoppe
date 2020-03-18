<?php

namespace Newelement\Shoppe\Traits;

use Newelement\Shoppe\Models\AddressBook;

trait ShippingService
{

    public $savedShipping;
    public $eligibleShipping;
    public $email;
    public $user;
    public $shippingAddress;

    public function processShippingAddress($request)
    {
        $shippingAddress = false;

        if( $this->savedShipping ){
            $savedAddress = AddressBook::find($this->savedShipping);
            $shippingAddress = [
                'name' => $savedAddress->name,
                'company_name' => $savedAddress->company_name,
                'street1' => $savedAddress->address,
                'street2' => $savedAddress->address2,
                'city' => $savedAddress->city,
                'state' => $savedAddress->state,
                'zip' => $savedAddress->zipcode,
                'country' => $savedAddress->country,
                'email' => $this->email
            ];
        } else {
            $shippingAddress = [
                'name' => $request->shipping_name,
                'company_name' => $request->shipping_company_name,
                'street1' => $request->shipping_address,
                'street2' => $request->shipping_address2,
                'city' => $request->shipping_city,
                'state' => $request->shipping_state,
                'zip' => $request->shipping_zipcode,
                'country' => $request->shipping_country,
                'email' => $this->email
            ];
        }
        $this->shippingAddress = $shippingAddress;
        return $shippingAddress;
    }

    public function saveShippingAddress()
    {
        if( $this->eligibleShipping && !$this->savedShipping ){
            $address = AddressBook::checkExistingAddress($this->user, $this->shippingAddress, 'SHIPPING');
            if( !$address ){
                $address = new AddressBook;
                $address->user_id = $user->id;
                $address->address_type = 'SHIPPING';
                $address->name = $shippingAddress['name'];
                $address->company_name = $shippingAddress['company_name'];
                $address->address = $shippingAddress['street1'];
                $address->address2 = $shippingAddress['street2'];
                $address->city = $shippingAddress['city'];
                $address->state = $shippingAddress['state'];
                $address->zipcode = $shippingAddress['zip'];
                $address->country = $shippingAddress['country'];
                $address->save();
            }
        }
    }

    public function getShippingTotal()
    {

    }

}
