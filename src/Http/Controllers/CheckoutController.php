<?php
namespace Newelement\Shoppe\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Newelement\Neutrino\Models\Page;
use Newelement\Shoppe\Traits\CartData;
use Newelement\Shoppe\Models\AddressBook;
use Newelement\Shoppe\Models\Customer;
use Newelement\Shoppe\Models\Order;
use Newelement\Shoppe\Models\OrderLine;
use Newelement\Shoppe\Models\PaymentType;
use Newelement\Shoppe\Traits\Transactions;
use Newelement\Shoppe\Traits\ShippingService;
use Newelement\Shoppe\Traits\TaxService;
use Illuminate\Support\Facades\Hash;
use Newelement\Shoppe\Events\OrderCreated;
use Newelement\Neutrino\Models\ActivityLog;
use Newelement\Shoppe\Models\Subscription;
use Newelement\Shoppe\Models\ShippingMethod;
use Newelement\Shoppe\Models\ShippingMethodClass;
use Newelement\Shoppe\Models\DiscountCode;
use \Carbon\Carbon;
use Auth;

class CheckoutController extends Controller
{
    use CartData, Transactions, ShippingService, TaxService;

    public function __construct()
    {}

    public function index(Request $request)
    {
        $cart = $this->getCartItems();
        $data = Page::where('slug', 'checkout')->first();
        $data->data_type = 'page';
        $data->items = $cart['items'];
        $data->sub_total = $cart['sub_total'];
        $data->shipping_type = $cart['shipping_type'];
        $data->minimum_order_amount = $cart['minimum_order_amount'];
        $data->shipping_rates = $cart['shipping_rates'];

        $paymentConnector = app('Payment');
        $shippingConnector = app('Shipping');
        $taxesConnector = app('Taxes');
        $inventoryConnector = app('Inventory');

        $data->payment_connector = $paymentConnector->connector_name;
        $data->tax_connector = $taxesConnector->connector_name;
        $data->shipping_connector = $shippingConnector->connector_name;
        $data->eligible_shipping = $cart['eligible_shipping'];

        $data->shipping_addresses = AddressBook::where(
                                    [
                                        'address_type' => 'shipping',
                                        'user_id' => auth()->user()? auth()->user()->id : 0
                                    ])
                                    ->orderBy('default', 'desc')
                                    ->orderBy('address', 'desc')
                                    ->get();

        $data->payment_types = [];

        if( getShoppeSetting('manage_stock') ){
            $checkStock = $inventoryConnector->checkCartStock($cart['items']);
            if( !$checkStock['success'] ){
                if( $request->ajax() ){
                    return response()->json(['success' => false, 'message' =>  $checkStock['message'] ], 500);
                } else {
                    return redirect()->back()->with('error', $checkStock['message'] );
                }
            }
        }

        $userId = auth()->check()? auth()->user()->id : -1;

        $customer = Customer::where('user_id', $userId )->first();
        if( $customer ){
            $paymentTypes = $paymentConnector->getStoredPaymentTypes($customer->customer_id);
            if( $paymentTypes['success'] ){
                $data->payment_types = $paymentTypes['items'];
            }
        }

        if( $request->ajax() ){
            return response()->json($data);
        } else {
            return view('shoppe::checkout', ['data' => $data]);
        }
    }

    public function applyDiscountCode(Request $request)
    {
        $discountCode = $request->discount_code;
        $checkCode = $this->checkDiscountCode($discountCode);
        if( !$checkCode['success'] ){
            return redirect()->back()->with('error', $checkCode['message']);
        }

        session();
    }

    public function checkDiscountCode($discountCode = false)
    {
        $success = true;
        $message = '';

        if( !$discountCode ){
            return [ 'success' => false, 'message' => 'Coupon code was empty.'];
        }

        // Does codes exist?
        $exist = DiscountCode::where('code', $discountCode)->exists();

        if( !$exist ){
            return [ 'success' => false, 'message' => 'The code you entered is not valid.'];
        }

        $cart = $this->getCartItems();
        $subTotal = $cart['sub_total'];
        $adjustedSubTotal = $subTotal;
        $discountAmount = 0.00;

        $code = DiscountCode::where('code', $discountCode)->first();

        // If so, has it expired?
        if( $code->expires_on ){
            $today = Carbon::now();
            if( $today > $code->expires_on ){
                return [ 'success' => false, 'message' => 'Sorry, the code you entered has expired.'];
            }
        }

        // Does it meet the min order requirement?
        if( $code->minimum_order_amount ){
            if( $code->minimum_order_amount > $subTotal ){
                return [ 'success' => false, 'message' => 'Sorry, the code you entered does not meet the minimum order amount.'];
            }
        }

        if( $code->amount_type === 'AMOUNT' &&  $subTotal < $code->amount ){
            return [ 'success' => false, 'message' => 'Sorry, the total cannot be less than the discounted amount.'];
        }

        // Check usage rules.
        $usageType = $code->type;

        // Is it once per customer?
        if( $usageType === 'ONCE_PER_CUSTOMER' && auth()->check() ){
            // Check for use
            $exists = Order::where(['user_id' => auth()->user()->id, 'discount_code_id' => $code->id ])->exists();
            if( $exists ){
                return [ 'success' => false, 'message' => 'Sorry, this code has already been used.'];
            }
        }

        // Is it a single use?
        if( $usageType === 'SINGLE' ){
            // Check for use
            $exists = Order::where(['discount_code_id' => $code->id ])->exists();
            if( $exists ){
                return [ 'success' => false, 'message' => 'Sorry, this code has already been used.'];
            }
        }

        // Is it unlimited?
        // CARRY ON ...

        // Is this a free shipping code?
        $freeShippingCode = $code->amount_type === 'FREE_SHIPPING'? true : false;
        // Is it an amount off code?
        $amountCode = $code->amount_type === 'AMOUNT'? true : false;
        // Is it a percent off code?
        $percentCode = $code->amount_type === 'PERCENT'? true : false;

        if( $amountCode || $percentCode ){
            // Calc discount amount
            if( $amountCode ){
                if( $subTotal >= $code->amount ){
                    $discountAmount = $code->amount;
                    $adjustedSubTotal = $subTotal - $discountAmount;
                }
            }

            // Calc percent amount
            if( $percentCode ){
                $amount = ( $subTotal/100 ) * $code->percent;
                $discountAmount = (float) number_format($amount, 2, '.', '');
                $adjustedSubTotal = $subTotal - $discountAmount;
            }

            // Return discount amount and discount message. like $10 off or 10% off.
            $discountMessage = $percentCode? $code->percent.'% off' : '$'.$code->amount.' off';
        }

        return [
            'success' => true,
            'message' => $discountMessage,
            'adjusted_sub_total' => $adjustedSubTotal,
            'discount_amount' => $discountAmount,
            'free_shipping' => $freeShippingCode
        ];
    }


    /*
    * PROCESS CHECKOUT
    *
    *
    */
    public function processCheckout(Request $request)
    {

        $paymentConnector = app('Payment');
        $shippingConnector = app('Shipping');
        $taxesConnector = app('Taxes');
        $inventoryConnector = app('Inventory');
        $cart = $this->getCartItems();
        $items = $cart['items'];
        $eligibleShipping = $cart['eligible_shipping'];
        $eligibleSubscription = $cart['eligible_subscription'];
        $checkout = [];

        //Validate initial data
        $validateArr = [
            'email' => 'required|email',
        ];

        if( $paymentConnector->connector_name === 'shoppe_stripe' ){
            $validateArr['saved_payment'] = 'required_without:token';
            $validateArr['token'] = 'required_without:saved_payment';
        }

        if( $eligibleShipping ){
            $validateArr['shipping_rate'] = 'required';
        }

        if( !$request->shipping_address_option ){
            $validateArr['shipping_name'] = 'required';
            $validateArr['shipping_address'] = 'required';
            $validateArr['shipping_city'] = 'required';
            $validateArr['shipping_state'] = 'required';
            $validateArr['shipping_zipcode'] = 'required';
            $validateArr['shipping_country'] = 'required';
        }

        $validatedData = $request->validate(
            $validateArr
        );

        $email = $request->email;
        $billing_name = $request->cc_name;
        $password = strlen($request->create_account_checkout) > 3? $request->create_account_checkout : false;
        $user = Customer::createOrGet( $billing_name, $email, $password );

        // Check stock
        if( getShoppeSetting('manage_stock') ){
            $checkStock = $inventoryConnector->checkCartStock($items);
            if( !$checkStock['success'] ){

                ActivityLog::insert([
                    'activity_package' => 'shoppe',
                    'activity_group' => 'cart.stock',
                    'content' => $checkStock['message'],
                    'log_level' => 5,
                    'created_by' => $user->id,
                    'created_at' => now()
                ]);

                if( $request->ajax() ){
                    return response()->json(['success' => false, 'message' =>  $checkStock['message'] ], 500);
                } else {
                    return back()->with('error', $checkStock['message'] );
                }
            }
        }

        $savedShipping = $request->shipping_address_option && $request->shipping_address_option !== 'new_shipping_address'? $request->shipping_address_option : false;
        $subTotal = $cart['sub_total'];
        $taxableTotal = $cart['taxable_total'];
        $subscriptionTotal = $cart['subscription_total'];
        $refId = sha1( uniqid().microtime().$subTotal.$email.env('APP_KEY') );
        $customerId = false;
        $plans = [];

        // Get request params
        $isStoredPayment = $request->saved_payment? true : false;
        $token = $request->token? $request->token : $request->saved_payment;
        $saveCard = $request->save_card? true : false;
        $shippingAmount = 0.00;

        // Start the checkout array
        $checkout['customer_name'] = $billing_name;
        $checkout['email'] = $email;
        $checkout['token'] = $token;
        $checkout['is_stored_payment'] = $isStoredPayment;
        $checkout['ref_id'] = $refId;
        $checkout['save_card'] = $saveCard;
        $checkout['description'] = $refId;
        $checkout['ref_id'] = $refId;
        $checkout['items'] = $cart['items'];
        $checkout['sub_total'] = $subTotal;
        $checkout['eligible_shipping'] = $eligibleShipping;
        $checkout['eligible_subscription'] = $eligibleSubscription;
        $checkout['taxable_total'] = $taxableTotal;
        $checkout['subscription_total'] = $subscriptionTotal;
        $checkout['shipping_amount'] = $shippingAmount;
        $checkout['shipping_service_id'] = $request->shipping_rate? $request->shipping_rate : false ;



        /*
        * Shipping
        *
        *
        */
        $this->savedShipping = $savedShipping;
        $this->eligibleShipping = $eligibleShipping;
        $this->email = $email;
        $this->user = $user;
        $shippingType = $cart['shipping_type'];

        $shippingAddress = $this->processShippingAddress($request);
        // SAVE CUSTOMER'S SHIPPING ADDRESS
        $this->saveShippingAddress();

        $checkout['shipping_address'] = $shippingAddress;
        $checkout['shipping_type'] = $cart['shipping_type'];
        $checkout['shipping_connector'] = $shippingConnector->connector_name;

        if( $shippingType === 'flat' ){
            $shippingRate = ShippingMethod::withTrashed()->find($request->shipping_rate);
            if( $shippingRate ){

                $classAmount = 0.00;
                $shippingMethodClass = ShippingMethodClass::where(['shipping_method_id' => $shippingRate->id])->first();
                if( $shippingMethodClass ){
                    if( $shippingMethodClass->calc_type === 'per_class' ){
                        foreach( $cart['shipping_classes'] as $cartShippingClass ){
                            if( $cartShippingClass === $shippingMethodClass->shipping_class_id ){
                                $classAmount += (float) $shippingMethodClass->amount;
                            }
                        }
                    } else if( $shippingMethodClass->calc_type === 'per_order' ) {
                        $flatClasses = array_unique($cart['shipping_classes']);
                        foreach( $flatClasses as $cartShippingClass ){
                            if( $cartShippingClass === $shippingMethodClass->shipping_class_id ){
                                $classAmount += (float) $shippingMethodClass->amount;
                            }
                        }
                    }
                }

                $calcShippingAmount = $shippingRate->amount + (float) $classAmount;

                $shippingAmount = $calcShippingAmount;
                $checkout['shipping_amount'] = $shippingAmount;
                $shippingCarrierService = $this->getShippingCarrierService($shippingRate->service_level);
                $checkout['shipping_carrier'] = $shippingCarrierService['carrier'];
                $checkout['shipping_service'] = $shippingCarrierService['service'];
                $checkout['shipping_service_id'] = $shippingRate->service_level;
                $checkout['shipping_method_id'] = $request->shipping_rate;
            }
        }


        if( $shippingType === 'estimated' && $cart['total_weight'] && $eligibleShipping ){
            $estimatedRate = $shippingConnector->getShippingRates( $checkout );

            if( !$estimatedRate['success'] ){

                ActivityLog::insert([
                    'activity_package' => 'shoppe',
                    'activity_group' => 'cart.shipping',
                    'content' => $estimatedRate['message'],
                    'log_level' => 5,
                    'created_by' => $user->id,
                    'created_at' => now()
                ]);

                if( $request->ajax() ){
                    return response()->json(['success' => false, 'message' => $estimatedRate['message'], 'failed_on' => 'shipping'], 500);
                } else {
                    return back()->with('error', $estimatedRate['message']);
                }
            }

            $checkout['shipping_amount'] = $estimatedRate['rates']['amount'];
            $checkout['shipping_carrier'] = $estimatedRate['rates']['carrier'];
            $checkout['shipping_service'] = $estimatedRate['rates']['service'];
            $checkout['shipping_service_id'] = $estimatedRate['rates']['service_id'];
            $checkout['shipping_est_days'] = $estimatedRate['rates']['estimated_days'];
            $checkout['shipping_object_id'] = isset($estimatedRate['rates']['object_id'])? $estimatedRate['rates']['object_id'] : null ;

            $shippingAmount = (float) $checkout['shipping_amount'];

        }

        $checkout['shipping_weight'] = $cart['total_weight'];
        $checkout['shipping_max_width'] = $cart['dimensions']['total']['width'];
        $checkout['shipping_max_height'] = $cart['dimensions']['total']['height'];
        $checkout['shipping_max_length'] = $cart['dimensions']['total']['length'];



        /*
        * Taxes
        *
        *
        */
        if( $taxableTotal > 0 && method_exists( $taxesConnector, 'getTaxes' ) ) {
            $taxes = $taxesConnector->getTaxes( $checkout );
            $checkout['tax_connector'] = $taxesConnector->connector_name;

            if( !$taxes['success'] ){

                ActivityLog::insert([
                    'activity_package' => 'shoppe',
                    'activity_group' => 'cart.taxes',
                    'content' => $taxes['message'],
                    'log_level' => 5,
                    'created_by' => $user->id,
                    'created_at' => now()
                ]);

                if( $request->ajax() ){
                    return response()->json(['success' => false, 'message' => $taxes['message'], 'failed_on' => 'taxes'], 500);
                } else {
                    return back()->with('error', $taxes['message']);
                }
            }
        }

        $checkout['tax_amount'] = $taxableTotal > 0? $taxes['tax_amount'] : 0.00;
        $checkout['tax_rate'] = $taxableTotal > 0? $taxes['tax_rate'] : 0;



        /*
        * Totals and Charge
        *
        *
        */
        if( $subscriptionTotal > 0 ){
            $subTotal = (float) $subTotal - (float) $subscriptionTotal;
        }

        $amount = (float) $subTotal + $shippingAmount + (float) $checkout['tax_amount'];
        $checkout['amount'] = $amount;

        // THE CHARGE
        $paymentConnector->email = strtolower($checkout['email']);

        if( $subTotal > 0 ){

            $charge = $paymentConnector->charge( $checkout );
            $checkout['payment_connector'] = $paymentConnector->connector_name;

            if( !$charge['success'] ){

                ActivityLog::insert([
                    'activity_package' => 'shoppe',
                    'activity_group' => 'cart.payment',
                    'content' => $charge['message'],
                    'log_level' => 5,
                    'created_by' => $user->id,
                    'created_at' => now()
                ]);

                if( $request->ajax() ){
                    return response()->json(['success' => false, 'message' => $charge['message'], 'failed_on' => 'payment'], 500);
                } else {
                    return back()->with('error', $charge['message']);
                }
            }

            $checkout['transaction_id'] = $charge['transaction_id'];

        }

        // Process a subscription if exists
        if( $eligibleSubscription && $subscriptionTotal > 0 ){
            $taxRates = getShoppeSetting('tax_rates');
            $checkout['tax_rates'] = $taxRates;

            $planIds = [];
            foreach( $items as $item ){
                if( $item->product->product_type === 'subscription' && $item->product->subscription_id ){
                    $planIds[] = $item->product->subscription_id;
                }
            }
                if( count($planIds) > 0 ){
                    $checkout['plan_ids'] = $planIds;
                    $subscription = $paymentConnector->createSubscription( $checkout );

                    if( $subscription['success'] ){

                        ActivityLog::insert([
                            'activity_package' => 'shoppe',
                            'activity_group' => 'cart.subscription',
                            'content' => 'Subscription created.',
                            'log_level' => 1,
                            'created_by' => $user->id,
                            'created_at' => now()
                        ]);

                        $checkout['transaction_id'] = $subscription['transaction_id'];

                    } else {

                        ActivityLog::insert([
                            'activity_package' => 'shoppe',
                            'activity_group' => 'cart.subscription',
                            'content' => $subscription['message'],
                            'log_level' => 5,
                            'created_by' => $user->id,
                            'created_at' => now()
                        ]);

                        if( $request->ajax() ){
                            return response()->json(['success' => false, 'message' =>  $subscription['message'] ], 500);
                        } else {
                            return redirect()->back()->with('error', $subscription['message'] );
                        }
                    }

                    $stripe_id = $subscription['payload']->customer;
                    $plan_name = '';
                    $subInsert = [];

                    foreach( $subscription['payload']->items->data as $subItem ){
                        $prod = $paymentConnector->getProduct($subItem->plan->product);
                        $subInsert[] = [
                            'user_id' => $user->id,
                            'stripe_id' => $subItem->subscription,
                            'stripe_plan' => $subItem->plan->id,
                            'name' => $prod['success']? $prod['payload']->name : 'NA',
                            'stripe_status' => $subscription['payload']->status,
                            'qty' => $subscription['payload']->quantity,
                            'trial_ends_at' => $subscription['payload']->trial_end? $subscription['payload']->trial_end : null,
                            'created_at' => now(),
                            'updated_at' => now()
                        ];
                    }

                    if( $subInsert ){
                        Subscription::insert($subInsert);
                    }

                }

        }



        /*
        * Create customer
        *
        *
        */
        if( $subTotal > 0 ){
            if( $saveCard ){
                $checkout['customer_id'] = $charge['customer_id'];
                $checkout['last_four'] = $charge['last_four'];
                $checkout['card_brand'] = $charge['card_brand'];
                $checkout['payment_type'] = $charge['payment_type'];
                $checkout['billing_id'] = $charge['billing_id'];
                $savedCustomer = Customer::saveCustomer( $checkout, $user );
                $savedPaymentType = PaymentType::savePayment( $checkout, $user );
            }

            if( $isStoredPayment ){
                $checkout['last_four'] = $charge['last_four'];
                $checkout['card_brand'] = $charge['card_brand'];
                $checkout['payment_type'] = $charge['payment_type'];
            }
        }



        /*
        * Insert order
        *
        *
        */
        try{
            $order = new Order;
            $order->ref_id = $refId;
            $order->user_id = Auth::check()? Auth::user()->id : $user->id;
            $order->transaction_id = isset($checkout['transaction_id'])? $checkout['transaction_id'] : null;
            $order->payment_connector = isset($checkout['payment_connector'])? $checkout['payment_connector'] : null;
            $order->shipping_connector = isset($checkout['shipping_connector'])? $checkout['shipping_connector'] : null;
            $order->tax_connector = isset($checkout['tax_connector'])? $checkout['tax_connector'] : null;
            $order->status = 1;
            $order->address_book_id = $savedShipping? $savedShipping : $address->id;
            $order->shipping_type = $checkout['shipping_type'];
            $order->carrier = isset( $checkout['shipping_carrier'] )? $checkout['shipping_carrier'] : null;
            $order->shipping_service = isset( $checkout['shipping_service'] )? $checkout['shipping_service'] : null;
            $order->shipping_id = isset( $checkout['shipping_service_id'] )? $checkout['shipping_service_id'] : null;
            $order->shipping_object_id = isset( $checkout['shipping_object_id'] )? $checkout['shipping_object_id'] : null;
            $order->shipping_method_id = isset( $checkout['shipping_method_id'] )? $checkout['shipping_method_id'] : null;
            $order->shipping_amount = isset( $checkout['shipping_amount'] )? $checkout['shipping_amount'] : 0.00;
            $order->shipping_weight = isset( $checkout['shipping_weight'] )? $checkout['shipping_weight'] : null;
            $order->shipping_max_width = isset( $checkout['shipping_max_width'] )? $checkout['shipping_max_width'] : null;
            $order->shipping_max_height = isset( $checkout['shipping_max_height'] )? $checkout['shipping_max_height'] : null;
            $order->shipping_max_length = isset( $checkout['shipping_max_length'] )? $checkout['shipping_max_length'] : null;
            $order->tax_amount = isset( $checkout['tax_amount'] )? $checkout['tax_amount'] : 0.00;
            $order->tax_rate = isset( $checkout['tax_rate'] )? $checkout['tax_rate'] : 0.00;
            if( $saveCard || $isStoredPayment ){
                $order->last_four = isset($checkout['last_four'])? $checkout['last_four'] : null;
                $order->card_brand = isset($checkout['card_brand'])? $checkout['card_brand'] : null;
                $order->payment_type = isset($checkout['payment_type'])? $checkout['payment_type'] : null;
            }
            $order->discount_code = isset( $checkout['discount_code'] )? $checkout['discount_code'] : null;
            $order->discount_amount = isset( $checkout['discount_amount'] )? $checkout['discount_amount'] : 0.00;
            $order->created_by = Auth::check()? auth()->user()->id : $user->id;
            $order->updated_by = Auth::check()? auth()->user()->id : $user->id;
            $order->save();

            ActivityLog::insert([
                'activity_package' => 'shoppe',
                'activity_group' => 'cart.order',
                'object_type' => 'order',
                'object_id' => $order->id,
                'content' => 'Order created',
                'log_level' => 0,
                'created_by' => $user->id,
                'created_at' => now()
            ]);

        } catch ( \Exception $e  ){

            ActivityLog::insert([
                'activity_package' => 'shoppe',
                'activity_group' => 'cart.order',
                'content' => $e->getMessage(),
                'log_level' => 5,
                'created_by' => $user->id,
                'created_at' => now()
            ]);

            if( $request->ajax() ){
                return response()->json(['success' => false, 'message' =>  $e->getMessage() ], 500);
            } else {
                return redirect()->back()->with('error', $e->getMessage() );
            }

        }

        // ORDER ID
        $checkout['order_id'] = $order->id;


        // INSERT ORDER LINES
        $lines = [];
        foreach( $items as $key => $item ){
            $lines[$key] = [
                'order_id' => $checkout['order_id'],
                'product_id' => $item->product->id,
                'variation_id' => $item->variation_id,
                'price' => $item->price,
                'qty' => $item->qty,
                'image' => $item->image,
                'variation' => $item->variationFormatted? $item->variationFormatted : null,
                'created_by' => Auth::check()? auth()->user()->id : $user->id,
                'updated_by' => Auth::check()? auth()->user()->id : $user->id,
            ];
        }

        try{
            $orderLines = new OrderLine;
            OrderLine::insert($lines);
        } catch ( \Exception $e ){

            ActivityLog::insert([
                'activity_package' => 'shoppe',
                'activity_group' => 'cart.order.line',
                'content' => $e->getMessage(),
                'log_level' => 5,
                'created_by' => $user->id,
                'created_at' => now()
            ]);

            if( $request->ajax() ){
                return response()->json(['success' => false, 'message' =>  $e->getMessage() ], 500);
            } else {
                return redirect()->back()->with('error', $e->getMessage() );
            }
        }

        // COMMIT TAX TRANSACTION
        if( $taxableTotal > 0 ){
            if( method_exists( $taxesConnector, 'createTransaction' ) ){
                $taxTransaction = $taxesConnector->createTransaction( $checkout );
                if( $taxTransaction['success'] ){
                    $order->tax_object_id = $taxTransaction['tax_object_id'];
                    $order->save();
                }
            }
        }

        // INSERT TRANSACTION LOG
        if( $subTotal > 0 ){
            $transArr = [
                'type' => 'debit',
                'amount' => $checkout['amount'],
                'order_id' => $checkout['order_id'],
                'transaction_id' => $checkout['transaction_id'],
                'notes' => 'Order created.',
                'transaction_on' => 'order',
                'user_id' => $user->id
            ];
            $this->createTransaction( $transArr );
        }

        /* INVOICE

        */


        if( $subscriptionTotal > 0 ){
            $transArr = [
                'type' => 'debit',
                'amount' => $subscriptionTotal + $checkout['tax_amount'],
                'order_id' => $checkout['order_id'],
                'transaction_id' => $checkout['transaction_id'],
                'notes' => 'Subscription created.',
                'transaction_on' => 'subscription',
                'user_id' => $user->id
            ];
            $this->createTransaction( $transArr );
        }

        if( getShoppeSetting('manage_stock') ){
            $inventoryConnector->removeStock( $items );
        }

        $checkout['order_complete_route'] = config('shoppe.slugs.order_complete', 'order-complete');

        // Empty the user's cart
        $this->deleteUserCart();

        event(new OrderCreated($order));

        if( $request->ajax() ){
            return response()->json($checkout);
        } else {
            return redirect()->route( 'shoppe.'.config('shoppe.slugs.order_complete', 'order-complete'), ['ref_id' => $refId ] );
        }
    }


    /*
    * ORDER COMPLETE / CHECKOUT SUCCESS
    *
    *
    *
    */
    public function checkoutSuccess($ref_id)
    {
        $order = Order::where('ref_id', $ref_id)->first();
        if( !$order ){
            abort(404);
        }
        return view('shoppe::checkout-complete', ['data' => $order]);
    }


    /*
    *
    *
    *
    *
    */
    public function getTaxes(Request $request)
    {
        $taxes = 0.00;
        $code = 200;
        $arr = [];
        $taxConnector = app('Taxes');
        $cart = $this->getCartItems();

        if( $cart['taxable_total'] <= 0 || !method_exists( $taxConnector, 'getTaxes' ) ){
            return response()->json(['taxes' => 0.00 , 'message' => 'Nothing to tax.' ]);
        }

        $shippingCost = $request->shipping;
        $shipping_address_id = $request->shipping_address_id;
        if( !$shipping_address_id ){
            $address = [
                'name' => $request->name,
                'street1' => $request->address,
                'street2' => $request->address2,
                'city' => $request->city,
                'state' => $request->state,
                'zip' => $request->zip,
                'country' => $request->country,
            ];
        }

        if( $shipping_address_id ){
            $shippingAddress = AddressBook::find($shipping_address_id);
            $address = [
                'name' => $shippingAddress->name,
                'street1' => $shippingAddress->address,
                'street2' => $shippingAddress->address2,
                'city' => $shippingAddress->city,
                'state' => $shippingAddress->state,
                'zip' => $shippingAddress->zipcode,
                'country' => $shippingAddress->country,
            ];
        }

        $arr['shipping_address'] = $address;
        $arr['items'] = $cart['items'];
        $arr['shipping_amount'] = $shippingCost;
        $arr['taxable_total'] = $cart['taxable_total'];

        $taxes = $taxConnector->getTaxes( $arr );

        $code = $taxes['success'] ? 200 : 500;

        return response()->json(['taxes' => isset($taxes['tax_amount'])? $taxes['tax_amount']: 0.00 , 'message' => $taxes['message'] ], $code);
    }


    /*
    *
    *
    *
    *
    */
    public function getShipping(Request $request)
    {
        $cart = $this->getCartItems();
        $code = 200;
        $checkout = [];
        $shoppeSettings = getShoppeSettings();

        $rates = [ 'rates' => [] ];

        if( !$cart['eligible_shipping'] ){
            return response()->json(['rates' => $rates, 'eligible_shipping' => $cart['eligible_shipping'] ], $code);
        }

        if( $cart['shipping_type'] === 'flat' ){
            $shippingRates = [];
            foreach( $cart['shipping_rates'] as $shippingRate ){
                $classAmount = 0.00;
                $shippingMethodClasses = ShippingMethodClass::where(['shipping_method_id' => $shippingRate->id])->get();
                foreach($shippingMethodClasses as $shippingMethodClass){
                    if( $shippingMethodClass->calc_type === 'per_class' ){
                        foreach( $cart['shipping_classes'] as $cartShippingClass ){
                            if( $cartShippingClass === $shippingMethodClass->shipping_class_id ){
                                $classAmount += (float) $shippingMethodClass->amount;
                            }
                        }
                    } else {
                        $flatClasses = array_unique($cart['shipping_classes']);
                        foreach( $flatClasses as $cartShippingClass ){
                            if( $cartShippingClass === $shippingMethodClass->shipping_class_id ){
                                $classAmount += (float) $shippingMethodClass->amount;
                            }
                        }
                    }
                }

                $shippingRate->amount = $shippingRate->amount + (float) $classAmount;
                $shippingRates[] = $shippingRate;
            }

            $rates = [ 'rates' => $shippingRates ];
        }

        if( $cart['shipping_type'] === 'estimated' ){

            $shipping_address_id = $request->shipping_address_id;
            if( !$shipping_address_id ){
                $address = [
                    'name' => $request->name,
                    'company_name' => $request->company_name,
                    'street1' => $request->address,
                    'street2' => $request->address2,
                    'city' => $request->city,
                    'state' => $request->state,
                    'zip' => $request->zip,
                    'country' => $request->country,
                    'email' => ''
                ];
            }

            if( $shipping_address_id ){
                $shippingAddress = AddressBook::find($shipping_address_id);
                $address = [
                    'name' => $shippingAddress->name,
                    'company_name' => $request->company_name,
                    'street1' => $shippingAddress->address,
                    'street2' => $shippingAddress->address2,
                    'city' => $shippingAddress->city,
                    'state' => $shippingAddress->state,
                    'zip' => $shippingAddress->zipcode,
                    'country' => $shippingAddress->country,
                    'email' => ''
                ];
            }

            $checkout['shipping_service_id'] = false;
            $checkout['shipping_address'] = $address;
            $shippingConnector = app('Shipping');
            $rates = $shippingConnector->getShippingRates( $checkout );

            if( !$rates['success'] ){
                $code = 500;
            }

        }

        return response()->json([
            'rates' => $rates,
            'shipping_type' => $cart['shipping_type'],
            'eligible_shipping' => $cart['eligible_shipping']
        ], $code);
    }

    private function getPlanItems($items)
    {
        foreach( $items as $item ){
            if( $item->product->product_type === 'subscription' && $item->product->subscription_id ){
                return $item->product->subscription_id;
            }
        }
        return false;
    }

    private function getShippingCarrierService($serviceId)
    {
        $carrier = '';
        $service = '';

        $serviceLevels = app('Shipping')->getServiceLevels();

        foreach( $serviceLevels as $serviceLevelGroups ){
            foreach( $serviceLevelGroups['levels'] as $key => $level ){
                if( $key === $serviceId ){
                    $carrier = $serviceLevelGroups['carrier'];
                    $service = $level;
                }
            }
        }

        return [
            'carrier' => $carrier,
            'service' => $service
        ];
    }
}
