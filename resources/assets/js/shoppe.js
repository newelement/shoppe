import axios from 'axios';
import postalCodes from 'postal-codes-js';

const HTTP = axios.create(axios.defaults.headers.common = {
    'X-Requested-With': 'XMLHttpRequest',
    'X-CSRF-TOKEN' : app.csrfToken,
    'Content-Type': 'multipart/form-data'
});

let $q = document.querySelector.bind(document),
    $$q = document.querySelectorAll.bind(document),
    modalMessage = '',
    thisVariation = {},
    checkoutErrors = [],
    checkoutError = false,
    currentStep = 1,
    shippingAddressOption = false,
    shippingAddressId = false,
    subTotal = 0.00,
    shipping = 0.00,
    taxes = 0.00,
    total = 0.00;

let $shoppeAddCartAlert = $q('#shoppe-product-alert'),
    $checkoutForm = document.getElementById('checkout-form'),
    $newPaymentTypeForm = document.getElementById('new-payment-type-form'),
    $checkoutErrorElement = document.getElementById('checkout-errors'),
    $shippingAddressFields = $$q('.shipping-address-field'),
    $shippingTaxesLoader = $q('.shipping-taxes-loader'),
    $shippingAddressForRates = $q('.enter-shipping-address-for-rates'),
    $checkoutStepsWrap = $q('.checkout-steps-wrap'),
    $shippingRatesList = $q('.shipping-rates-list'),
    $submitOrderBtn = $q('.submit-order-btn'),
    $sameAsShipping = $q('#same-as-shipping'),
    $newBillingAddress = $q('#new-billing-address'),
    $newPaymentType = $$q('input[name="saved_payment"]'),
    $checkoutAdvBtns = $$q('.checkout-adv-btn'),
    $checkoutPrevBtns = $$q('.checkout-prev-btn'),
    $checkoutFormSections = $$q('.checkout-form-section'),
    $checkoutSteps = $$q('.checkout-step'),
    $checkoutShippingReview = $q('#checkout-shipping-review'),
    $checkoutBillingReview = $q('#checkout-billing-review'),
    $checkoutMessages = $q('.checkout-messages'),
    $customerAddressEditToggle = $$q('.customer-address-edit-toggle'),
    $editCustomerAddressFields = $$q('.edit-customer-address-fields'),
    $toggleCustomerNewShippingAddress = $q('.toggle-customer-new-shipping-address'),
    $customerPaymentEditToggle = $$q('.customer-payment-edit-toggle'),
    $editCustomerPaymentFields = $$q('.edit-customer-payment-fields'),
    $toggleCustomerNewPayment = $q('.toggle-customer-new-payment');

let alertTypes = [
    'alert-primary',
    'alert-secondary',
    'alert-success',
    'alert-danger',
    'alert-warning',
    'alert-info'
];

function showShoppeAddCartMessage(type){
    $shoppeAddCartAlert.classList.remove(alertTypes.join(','));
    $shoppeAddCartAlert.classList.add('alert-'+type);
    $shoppeAddCartAlert.innerHTML = modalMessage;
    $shoppeAddCartAlert.classList.remove('d-none');
}

function closeShoppeAddCartMessage(){
    $shoppeAddCartAlert.classList.add('d-none');
    $shoppeAddCartAlert.classList.remove(alertTypes.join(','));
    $shoppeAddCartAlert.innerHTML = '';
}

function checkArrays( arrA, arrB ){
    if(arrA.length !== arrB.length) return false;
    var cA = arrA.slice().sort().join(",");
    var cB = arrB.slice().sort().join(",");
    return cA === cB;
}

function debounce(func, wait, immediate) {
    let timeout;
    return function executedFunction() {
        let context = this;
        let args = arguments;
        let later = function() {
            timeout = null;
            if (!immediate) func.apply(context, args);
        };
        let callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func.apply(context, args);
    };
};


/*
* SHIPPING AND TAXES
*
*
*/
let getShipping = async () => {
    shipping = 0.00;
    let formData = new FormData();

    if( shippingAddressOption && shippingAddressOption === 'new' ){

        formData.append('name', $q('#shipping-name').value );
        formData.append('address', $q('#shipping-address').value );
        formData.append('address2', $q('#shipping-address2').value);
        formData.append('city', $q('#shipping-city').value);
        formData.append('state', $q('#shipping-state').value);
        formData.append('zip', $q('#shipping-zip-code').value);
        formData.append('country', $q('#shipping-country').value);

    }

    if( shippingAddressOption && shippingAddressOption === 'saved' ){
        formData.append('shipping_address_id', shippingAddressId );
    }

    return HTTP.post('/api/shipping', formData)
    .then( response => {
        return response.data;
    })
    .catch(e => {
        console.log('shipping error', e);
        $shippingTaxesLoader.classList.remove('loading');
        logCheckoutError('shipping', e.message, false);
        checkoutError = true;
        document.getElementById('checkout-step-2-btn').classList.add('disabled');
    });
};

let getTaxes = async (shipping) => {
    taxes = 0.00;
    let formData = new FormData();
    formData.append('shipping', shipping );

    if( shippingAddressOption && shippingAddressOption === 'new' ){
        formData.append('name', $q('#shipping-name').value );
        formData.append('address', $q('#shipping-address').value );
        formData.append('address2', $q('#shipping-address2').value);
        formData.append('city', $q('#shipping-city').value);
        formData.append('state', $q('#shipping-state').value);
        formData.append('zip', $q('#shipping-zip-code').value);
        formData.append('country', $q('#shipping-country').value);
    }

    if( shippingAddressOption && shippingAddressOption === 'saved' ){
        formData.append('shipping_address_id', shippingAddressId );
    }

    return HTTP.post('/api/taxes', formData)
    .then( response => {
        return response.data.taxes;
    })
    .catch(e => {
        $shippingTaxesLoader.classList.remove('loading');
        console.log('taxes error', e.response.data.message);
        logCheckoutError('taxes', false, e.response.data.message);
        checkoutError = true;
        document.getElementById('checkout-step-2-btn').classList.add('disabled');
    });
};

function calcOrderTotal(){
    subTotal = $q('.sub-total').innerHTML;
    total = parseFloat(subTotal) + parseFloat(shipping) + parseFloat(taxes);
    $q('.sub-total').innerHTML = parseFloat(subTotal).toFixed(2);
    $q('.shipping').innerHTML = parseFloat(shipping).toFixed(2);
    $q('.taxes').innerHTML = parseFloat(taxes).toFixed(2);
    $q('.total').innerHTML = parseFloat(total).toFixed(2);
}

function validateShippingFields(){
    checkoutError = false;
    document.getElementById('checkout-step-2-btn').classList.remove('disabled');
    let hasShippingAddress = true;
    if($shippingTaxesLoader){
        $shippingTaxesLoader.classList.add('loading');
    }

    if( shippingAddressOption && shippingAddressOption === 'new'){
        $shippingAddressFields.forEach( function(el){
            if( !el.value.length  ){
                hasShippingAddress = false;
            }
        });
    }

    let zipCode = document.getElementById('shipping-zip-code');
    let countryCode = document.getElementById('shipping-country');
    if( zipCode && countryCode ){
        checkoutErrors = [];
        if( zipCode.value !== '' && countryCode.value !== '' ){
            let validPostalCode = postalCodes.validate(countryCode.value, zipCode.value);
            if( typeof validPostalCode !== 'boolean' ){
                hasShippingAddress = false;
                checkoutErrors.push('Please enter a valid zip/postal code.');
                zipCode.classList.add('border-danger');
                showCheckoutErrors();
                checkoutError = true;
                document.getElementById('checkout-step-2-btn').classList.add('disabled');
            }
        }
    }

    if( !shippingAddressOption ){
        hasShippingAddress = false;
    }

    if( hasShippingAddress  && currentStep === 1 ){

        $checkoutErrorElement.innerHTML = '';
        getShipping().then( (data) => {
            let rates = data.rates.rates;
            let rateItems = '';
            if( data.eligible_shipping ){
                if( rates.length ){
                    if( data.shipping_type === 'flat' ){
                        shipping = rates[0].amount;
                        $q('.shipping-service-summary').innerHTML = rates[0].title;
                        $shippingRatesList.innerHTML = '';
                        rates.forEach( (v, i) => {
                            let checked = i === 0? 'checked="checked"' : '';
                            rateItems += '<li class="rate-item">';
                                rateItems += '<input type="radio" name="shipping_rate" data-rate-service="'+v.title+'" id="rate-item-'+i+'" class="shipping-rates" data-rate="'+v.amount+'" data-rate-service-id="'+v.service_level+'" '+checked+' value="'+v.id+'">';
                                rateItems += '<label for="rate-item-'+i+'">';
                                    rateItems += '<div class="rate-inner"><span class="rate-service">'+v.title+'</span> &mdash; $<span class="rate-amount">'+v.amount+'</span>';
                                    rateItems += '<div class="rate-estimated-days">Estimated '+v.estimated_days+' shipping</div></div>';
                                rateItems += '</label>';
                            rateItems += '</li>';
                        });
                    } else if( data.shipping_type === 'estimated' ) {
                        shipping = rates[0].amount;
                        $q('.shipping-service-summary').innerHTML = rates[0].carrier+' '+rates[0].service;
                        $shippingRatesList.innerHTML = '';
                        rates.forEach( (v, i) => {
                            let checked = i === 0? 'checked="checked"' : '';
                            rateItems += '<li class="rate-item">';
                                rateItems += '<input type="radio" name="shipping_rate" id="rate-item-'+i+'" class="shipping-rates" data-rate-carrier="'+v.carrier+'" data-rate-service="'+v.service+'" data-rate="'+v.amount+'" data-rate-service-id="'+v.service_id+'" '+checked+' value="'+v.service_id+'">';
                                rateItems += '<label for="rate-item-'+i+'">';
                                    rateItems += '<div class="rate-inner"><span class="rate-service">'+v.carrier +' '+v.service+'</span> &mdash; $<span class="rate-amount">'+v.amount+'</span>';
                                    rateItems += '<div class="rate-estimated-days">Estimated '+v.estimated_days+' day shipping</div></div>';
                                rateItems += '</label>';
                            rateItems += '</li>';
                        });
                    }
                }
                $shippingRatesList.innerHTML = rateItems;
            }

            $q('.shipping').innerHTML = parseFloat(shipping).toFixed(2);

            let $shippingRates = $$q('.shipping-rates');
            if( $shippingRates.length ){
                $shippingRates.forEach( (el) =>  {
                    el.addEventListener( 'change', (e) => {
                        if( e.target.checked ){
                            if($shippingTaxesLoader){
                                $shippingTaxesLoader.classList.add('loading');
                            }
                            shipping = e.target.getAttribute('data-rate');
                            let carrier = e.target.getAttribute('data-rate-carrier');
                            let service = e.target.getAttribute('data-rate-service');

                            $q('.shipping-service-summary').innerHTML = carrier+' '+service;

                            getTaxes(shipping).then( (estTaxes) => {
                                taxes = estTaxes;
                                calcOrderTotal();
                                if($shippingTaxesLoader){
                                    $shippingTaxesLoader.classList.remove('loading');
                                }
                            });

                            calcOrderTotal();
                        }
                    });
                });
            }

            getTaxes(shipping).then( (estTaxes) => {
                taxes = estTaxes;
                calcOrderTotal();
                if($shippingTaxesLoader){
                    $shippingTaxesLoader.classList.remove('loading');
                }
            });

        });

    } else {
        if($shippingTaxesLoader){
            $shippingTaxesLoader.classList.remove('loading');
        }
    }
}

function togglePasswordVisibility(id) {
    var x = document.getElementById(id);
    if (x.type === "password") {
        x.type = "text";
    } else {
        x.type = "password";
    }
}

function showCheckoutErrors(){
    let messages = '';
    $checkoutMessages.innerHTML = '';

    checkoutErrors.forEach( (m) => {
        messages += m+'<br>';
    });

    if( checkoutErrors.length ){
        $checkoutMessages.innerHTML = messages;
    }
}

function validateShipping(){
    checkoutErrors = [];

    let $shippingOption = $q('[name="shipping_address_option"]:checked');

    if( typeof $shippingOption === 'undefined' || $shippingOption === null || !$shippingOption ){
        checkoutErrors.push('Please choose a shipping address option.');
    } else {

        if( $shippingOption.value === 'new_shipping_address' ){
            let reqFields = $$q('.new-shipping-address [data-required]');

            reqFields.forEach( (el) => {
                el.classList.remove('border-danger');
            });

            reqFields.forEach( (el) => {
                let message = el.getAttribute('data-required-message');
                if( el.value === '' ){
                    el.classList.add('border-danger');
                    checkoutErrors.push(message);
                }
            });

            let zipCode = document.getElementById('shipping-zip-code');
            let countryCode = document.getElementById('shipping-country');
            if( zipCode && countryCode ){
                if( zipCode.value !== '' && countryCode.value !== '' ){
                    let validPostalCode = postalCodes.validate(countryCode.value, zipCode.value);
                    if( typeof validPostalCode !== 'boolean' ){
                        checkoutErrors.push('Please enter a valid zip/postal code.');
                        zipCode.classList.add('border-danger');
                    }
                }
            }

        }
    }

    showCheckoutErrors();

    return checkoutErrors.length ? false : true;
}

function validateBilling(){
    let messages = [];
    let html = '';
    let $requireds = $$q('.billing-address [data-required]');
    $requireds.forEach( (el) => {
        el.classList.remove('border-danger');
    });
    if( $requireds.length ){
        $requireds.forEach( (el) => {
            if( el.value === '' ){
                el.classList.add('border-danger');
                let inputMessage = el.getAttribute('data-required-message');
                    messages.push(inputMessage);
                }
        });
    }

    if( messages.length ){
        messages.forEach( (mess) => {
            html += mess+'<br>';
        });
        $checkoutMessages.innerHTML = $checkoutMessages.innerHTML + html;
    }

    return messages.length ? true : false;
}

function setCheckoutStep(step){
    $checkoutFormSections.forEach( (el) => {
        el.classList.remove('current');
    });

    switch(step){
        case 1:
        // SHIPPING

            $checkoutSteps[1].classList.remove('current');
            $checkoutSteps[1].classList.remove('done');
            $checkoutSteps[0].classList.remove('done');
            $checkoutFormSections[0].classList.add('current');
            $q('#checkout-step-2-btn').classList.remove('visible-hide');
            $q('#checkout-submit-btn').classList.add('visible-hide');
            currentStep = 1;
            $checkoutForm.setAttribute('autocomplete', 'on');

        break;
        case 2:
        // PAYMENT

            $checkoutSteps[0].classList.add('done');
            $checkoutSteps[1].classList.add('current');
            $checkoutFormSections[1].classList.add('current');
            $q('#checkout-step-2-btn').classList.add('visible-hide');
            $q('#checkout-submit-btn').classList.remove('visible-hide');
            currentStep = 2;
            $checkoutForm.setAttribute('autocomplete', 'false');

        break;
    }
}

function showHideStepLoading(){
    $checkoutStepsWrap.classList.add('loading');
    setTimeout( () => {
        $checkoutStepsWrap.classList.remove('loading');
    }, 500);
}

function logCheckoutError(type, error, error2){
    checkoutErrors = [];
    let message = 'We are sorry, but our '+type+' service is having technical issues.';
    if( error2 ){
        message = error2;
    }
    checkoutErrors.push(message);
    showCheckoutErrors();
}

function showPlaceOrderLoader(){
    $q('#checkout-submit-btn').setAttribute('disabled', '');
    $q('#checkout-submit-btn .spinner-border-sm').classList.remove('hide');
}

function resetPlaceOrderLoader(){
    $q('#checkout-submit-btn').removeAttribute('disabled');
    $q('#checkout-submit-btn .spinner-border-sm').classList.add('hide');
}

function submitOrder(){
    // AJAX place order
    let formData = new FormData($checkoutForm);
    HTTP.post('/checkout', formData)
        .then( response => {
            window.location = '/'+response.data.order_complete_route+'/'+response.data.ref_id;
            resetPlaceOrderLoader();
        })
        .catch(e => {
            resetPlaceOrderLoader();
            console.log(e.response.data.message);
            $checkoutErrorElement.innerHTML = e.response.data.message;
    });
}


/*
* DOM LOAD EVENTS
*
*
*
*/
window.addEventListener('DOMContentLoaded', (e) => {

    let $productImageSelected = $q('.product-image-selected'),
    $newShippingAddress = $$q('input[name="shipping_address_option"]'),
    $productThumbs = $$q('.product-image-thumb'),
    $addCartBtn = $$q('.add-to-cart-btn'),
    $productAttributeList = $$q('.product-attribute-list'),
    $productImageLink = $q('.product-image-selected a'),
    $passwordToggles = $$q('.password-toggle'),
    $productPrice = $q('#price'),
    $productStock = $q('#stock'),
    $productPartNumber = $q('#mfg-part-number'),
    $variationId = $q('#variation-id');



    /*
    * STRIPE JS
    *
    *
    */
    if( typeof Stripe !== 'undefined' && app.payment_connector === 'shoppe_stripe' ){
        let stripe = Stripe(app.stripe_token);
        let elements = stripe.elements();
        let card = elements.create('card');
        card.mount('#card-element');

        function stripeTokenHandler(token) {
            let hiddenInput = document.createElement('input');
            hiddenInput.setAttribute('type', 'hidden');
            hiddenInput.setAttribute('name', 'token');
            hiddenInput.setAttribute('value', token.id);
            $checkoutForm.appendChild(hiddenInput);
            submitOrder();
        }

        function stripeTokenHandler2(token) {
            let hiddenInput = document.createElement('input');
            hiddenInput.setAttribute('type', 'hidden');
            hiddenInput.setAttribute('name', 'token');
            hiddenInput.setAttribute('value', token.id);
            $newPaymentTypeForm.appendChild(hiddenInput);
            $newPaymentTypeForm.submit();
        }

        if( $checkoutForm){
            $checkoutForm.addEventListener('submit', function(e) {
                showPlaceOrderLoader();
                e.preventDefault();
                $checkoutErrorElement.innerHTML = '';
                let error = validateBilling();
                console.log(error);
                if( !error ){

                    // Don't worry about creating a new card token if the user is using
                    // an existing card

                    if( $newPaymentType.length ){
                        let savedPaymentType = document.querySelector('input[name="saved_payment"]:checked').value;

                        if( savedPaymentType !== 'new_payment_type' ){
                            submitOrder();
                            return;
                        }
                    }

                    stripe.createToken(card).then(function(result) {
                        if (result.error) {
                            $checkoutErrorElement.innerHTML = $checkoutErrorElement.innerHTML + result.error.message;
                            resetPlaceOrderLoader();
                        } else {
                            stripeTokenHandler(result.token);
                        }
                    });

                } else {
                    resetPlaceOrderLoader();
                }
            });
        }

        if($newPaymentTypeForm){
            $newPaymentTypeForm.addEventListener('submit', function(e) {
                e.preventDefault();
                $checkoutErrorElement.innerHTML = '';
                stripe.createToken(card).then(function(result) {
                    if (result.error) {
                        $checkoutErrorElement.innerHTML = result.error.message;
                    } else {
                        stripeTokenHandler2(result.token);
                    }
                });
            });
        }
    }



    /*
    * PRODUCT SINGLE IMAGE GALLERY
    *
    *
    */
    if( $productThumbs ){
        $productThumbs.forEach(function(v){
            v.addEventListener('click', function(e){
                e.preventDefault();
                let medium = v.getAttribute('data-medium');
                let href = v.getAttribute('href');
                let currHeight = $productImageSelected.offsetHeight;
                $productImageSelected.style.minHeight = currHeight+'px';
                $productImageSelected.innerHTML = '<a href="'+href+'"><img src="'+medium+'" alt=""></a>';
                $q('.product-image-selected a').addEventListener('click', function(e) {
                    e.preventDefault();
                });
                $productThumbs.forEach(function(el){
                    el.classList.remove('active');
                });
                v.classList.add('active');
            });
        });
    }

    if( $productImageLink ){
        $productImageLink.addEventListener('click', function(e) {
            e.preventDefault();
        });
    }


    /*
    * PRODUCT SINGLE ADD CART BUTTON
    *
    *
    */
    if( $addCartBtn ){
        $addCartBtn.forEach(function(v){
            v.addEventListener('click', function(e){
                let $hasAttributes = v.getAttribute('data-has-attributes');
                if( $hasAttributes ){
                    $productAttributeList.forEach(function(v){
                        if( v.value === '' ){
                            modalMessage = 'Please choose all product options.';
                            showShoppeAddCartMessage('warning');
                            e.preventDefault();
                        } else {
                            $variationId.value = thisVariation.id;
                        }
                    });
                }
            });
        });
    }


    /*
    * PRODUCT ATTRIBUTES AND VARIATIONS
    *
    *
    */
    $productAttributeList.forEach( (v) => {
        thisVariation = {};
        v.addEventListener('change', (e) => {
            //console.log('CHANGE ', e);
            let chooseAllAttributes = true;
            let attrSet = [];
            $productAttributeList.forEach( (v) => {
                if( v.value === '' ){
                    chooseAllAttributes = false;
                }
                attrSet.push(v.value);
            });

            if ( chooseAllAttributes ){
                thisVariation = variations.filter( (obj) => {
                    if( checkArrays( attrSet, obj.attribute_values ) ){
                        return obj;
                    }
                })[0];

                console.log(thisVariation);

                if( typeof thisVariation !== 'undefined' ){
                    if( thisVariation.price ){
                        $productPrice.innerHTML = '$'+thisVariation.price;
                    }
                    if( Number.isInteger(thisVariation.stock) && $productStock ){
                        $productStock.innerHTML = thisVariation.stock;
                    }
                    if( thisVariation.mfg_part_number && $productPartNumber ){
                        $productPartNumber.innerHTML = thisVariation.mfg_part_number;
                    }
                    if( thisVariation.image ){
                        let $variationImage = $q('#variation-image-'+thisVariation.id);
                        let $variationImageLink = $q('#variation-image-'+thisVariation.id+' a');
                        $variationImageLink.click();
                    }
                }
            }
        });
    });


    /*
    * CHECKOUT EVENTS
    *
    *
    */
    let delayShippingFields = debounce(validateShippingFields, 500);

    if( $shippingAddressFields.length > 0 ){
        validateShippingFields();
        $shippingAddressFields.forEach( (el) => {
            el.addEventListener('blur', delayShippingFields);
            el.addEventListener('keyup', delayShippingFields);
        });
        $q('#shipping-state').addEventListener('change', delayShippingFields);
        $q('#shipping-country').addEventListener('change', delayShippingFields);
    }

    if( $sameAsShipping ){
        $sameAsShipping.addEventListener('change', (el) => {
            if( el.target.checked ){
                $q('.new-billing-address').style.display = 'none';
                $q('#billing-address').value = $q('#shipping-address').value;
                $q('#billing-address2').value = $q('#shipping-address2').value;
                $q('#billing-city').value = $q('#shipping-city').value;
                $q('#billing-state').value = $q('#shipping-state').value;
                $q('#billing-zip-code').value = $q('#shipping-zip-code').value;
                $q('#billing-country').value = $q('#shipping-country').value;
            } else {
                $q('.new-billing-address').style.display = 'block';
            }
        });
    }

    if( $newBillingAddress ){
        $newBillingAddress.addEventListener('change', (el) => {
            if( el.target.checked ){
                $q('.new-billing-address').style.display = 'block';
            } else {
                $q('.new-billing-address').style.display = 'none';
            }
        });
    }

    if( $newShippingAddress.length ){
        $newShippingAddress.forEach( (input) => {
            if( $newShippingAddress.length === 1 && input.checked && input.value === 'new_shipping_address' ){
                shippingAddressOption = 'new';
                shippingAddressId = false;
            };
            input.addEventListener('change', (el) => {
                if( el.target.checked && el.target.value === 'new_shipping_address' ){
                    $q('.new-shipping-address').style.display = 'block';
                    shippingAddressId = false;
                } else {
                    $q('.new-shipping-address').style.display = 'none';
                    shippingAddressId = el.target.value;
                    shippingAddressOption = 'saved';
                    validateShippingFields();

                    let shippingName = el.target.getAttribute('data-name');
                    let shippingCompanyName = el.target.getAttribute('data-company-name');
                    let shippingAddress = el.target.getAttribute('data-address');
                    let shippingAddress2 = el.target.getAttribute('data-address2');
                    let shippingCity = el.target.getAttribute('data-city');
                    let shippingState = el.target.getAttribute('data-state');
                    let shippingZip = el.target.getAttribute('data-zipcode');
                    let shippingCountry = el.target.getAttribute('data-country');

                    let shippingAddressHtml = '<h4>Shipping to</h4>';
                    shippingAddressHtml += shippingName+'<br>';
                    if( shippingCompanyName.length > 1 ){
                        shippingAddressHtml += shippingCompanyName+'<br>';
                    }
                    shippingAddressHtml += shippingAddress+'<br>';
                    if( shippingAddress2.length > 1 ){
                        shippingAddressHtml += shippingAddress2+'<br>';
                    }
                    shippingAddressHtml += shippingCity+', ';
                    shippingAddressHtml += shippingState+' ';
                    shippingAddressHtml += shippingZip+'<br>';
                    shippingAddressHtml += shippingCountry;

                    $q('.shipping-address-summary').innerHTML = shippingAddressHtml;
                    $q('.shipping-address-summary').classList.remove('hide-me');

                    if( $shippingRatesList ){
                        $shippingRatesList.innerHTML = '<li><svg focusable="false" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M460.115 373.846l-6.941-4.008c-5.546-3.202-7.564-10.177-4.661-15.886 32.971-64.838 31.167-142.731-5.415-205.954-36.504-63.356-103.118-103.876-175.8-107.701C260.952 39.963 256 34.676 256 28.321v-8.012c0-6.904 5.808-12.337 12.703-11.982 83.552 4.306 160.157 50.861 202.106 123.67 42.069 72.703 44.083 162.322 6.034 236.838-3.14 6.149-10.75 8.462-16.728 5.011z"></path></svg>  <span class="getting-rates">Getting rates ...</span></li>';
                    }

                }
            });
        });
    }

    if( $newPaymentType.length ){
        $newPaymentType.forEach( (input) => {
            input.addEventListener('change', (el) => {
                if( el.target.checked && el.target.value === 'new_payment_type' ){
                    $q('.payment-section-fields').classList.remove('hide');
                } else {
                    $q('.payment-section-fields').classList.add('hide');
                }
            });
        });
    }

    if($passwordToggles.length){
        $passwordToggles.forEach( (el) => {
            el.addEventListener('click', (e) => {
                e.preventDefault();
                let id = e.target.getAttribute('data-toggle-target');
                togglePasswordVisibility(id);
            });
        });
    }

    /*
    if( $submitOrderBtn ){
        $submitOrderBtn.addEventListener('click', (e) => {
            e.preventDefault();
        });
    }*/


    /*
    * CHECKOUT STEP BUTTONS
    *
    *
    */

    if( $checkoutAdvBtns.length ){
        $checkoutAdvBtns.forEach( (btn) => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                if( checkoutError ){
                    return false;
                }
                let step = parseInt(e.target.getAttribute('data-step'));
                switch(step){
                    case 1:
                        setCheckoutStep(step);
                    break;
                    case 2:
                        let vShipping = validateShipping();
                        showHideStepLoading();
                        if( vShipping ){
                            $checkoutFormSections.forEach( (el) => {
                                el.classList.remove('current');
                            });
                            setCheckoutStep(step);
                        }
                    break;
                }
            });
        });
    }

    if( $checkoutPrevBtns.length ){
        $checkoutPrevBtns.forEach( (btn) => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                let step = parseInt(e.target.getAttribute('data-step'));
                switch(step){
                    case 1:
                        showHideStepLoading();
                        setCheckoutStep(step);
                    break;
                    case 2:
                        showHideStepLoading();
                        setCheckoutStep(step);
                    break;
                }
            });
        });
    }

    if( $customerAddressEditToggle.length ){
        $customerAddressEditToggle.forEach( (el) => {
            el.addEventListener('click', (e) => {
                e.preventDefault();
                let href = e.target.hash.replace('#', '');
                let hidden = document.getElementById(href).classList.contains('hide');
                $editCustomerAddressFields.forEach( (el) => {
                    el.classList.add('hide');
                });
                if( hidden ){
                    document.getElementById(href).classList.remove('hide');
                } else {
                    document.getElementById(href).classList.add('hide');
                }

            });
        })
    }

    if($toggleCustomerNewShippingAddress){
        $toggleCustomerNewShippingAddress.addEventListener('click', (e) => {
            e.preventDefault();
            $q('.new-customer-shipping-address').classList.toggle('hide');
        });
    }

    if( $customerPaymentEditToggle.length ){
        $customerPaymentEditToggle.forEach( (el) => {
            el.addEventListener('click', (e) => {
                e.preventDefault();
                let href = e.target.hash.replace('#', '');
                let hidden = document.getElementById(href).classList.contains('hide');
                $editCustomerPaymentFields.forEach( (el) => {
                    el.classList.add('hide');
                });
                if( hidden ){
                    document.getElementById(href).classList.remove('hide');
                } else {
                    document.getElementById(href).classList.add('hide');
                }

            });
        })
    }

    if($toggleCustomerNewPayment){
        $toggleCustomerNewPayment.addEventListener('click', (e) => {
            e.preventDefault();
            $q('.new-customer-payment').classList.toggle('hide');
        });
    }


});
