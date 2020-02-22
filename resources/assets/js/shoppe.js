import axios from 'axios';

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
    shippingAddressOption = false,
    shippingAddressId = false,
    subTotal = 0.00,
    shipping = 0.00,
    taxes = 0.00,
    total = 0.00;

let $shoppeAddCartAlert = $q('#shoppe-product-alert'),
    $shippingAddressFields = $$q('.shipping-address-field'),
    $shippingTaxesLoader = $q('.shipping-taxes-loader'),
    $checkoutStepsWrap = $q('.checkout-steps-wrap'),
    $shippingRatesList = $q('.shipping-rates-list'),
    $submitOrderBtn = $q('.submit-order-btn'),
    $sameAsShipping = $q('#same-as-shipping'),
    $newBillingAddress = $q('#new-billing-address'),
    $newShippingAddress = $$q('input[name="shipping_address_option"]'),
    $checkoutAdvBtns = $$q('.checkout-adv-btn'),
    $checkoutPrevBtns = $$q('.checkout-prev-btn'),
    $checkoutFormSections = $$q('.checkout-form-section'),
    $checkoutSteps = $$q('.checkout-step'),
    $checkoutShippingReview = $q('#checkout-shipping-review'),
    $checkoutBillingReview = $q('#checkout-billing-review'),
    $checkoutMessages = $q('.checkout-messages');

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
        return response.data.rates;
    })
    .catch(e => {
        console.log('shipping error', e);
        $shippingTaxesLoader.classList.remove('loading');
        logCheckoutError('shipping', e.message);
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
        console.log('taxes error', e);
        $shippingTaxesLoader.classList.remove('loading');
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
    let hasShippingAddress = true;
    if($shippingTaxesLoader){
        $shippingTaxesLoader.classList.add('loading');
    }

    if( shippingAddressOption && shippingAddressOption === 'new' ){
        $shippingAddressFields.forEach( function(el){
            if( !el.value.length  ){
                hasShippingAddress = false;
            }
        });
    }

    if( !shippingAddressOption ){
        hasShippingAddress = false;
    }

    if( hasShippingAddress ){

        getShipping().then( (rates) => {

            shipping = rates.rates[0].amount;

            $shippingRatesList.innerHTML = '';
            let rateItems = '';
            rates.rates.forEach( (v, i) => {
                let checked = i === 0? 'checked="checked"' : '';
                rateItems += '<li class="rate-item">';
                    rateItems += '<label>';
                    rateItems += '<input type="radio" name="shipping_rate" class="shipping-rates" data-rate-carrier="'+v.carrier+'" data-rate-service="'+v.service+'" data-rate="'+v.amount+'" data-rate-service-id="'+v.service_id+'" '+checked+' value="'+v.service_id+'">';
                    rateItems += ' <span class="rate-service">'+v.carrier +' '+v.service+'</span> $<span class="rate-amount">'+v.amount+'</span>';
                    rateItems += '<div class="rate-estimated-days">Estimated '+v.estimated_days+' day shipping</div>';
                    rateItems += '</label>';
                rateItems += '</li>';
            });
            $shippingRatesList.innerHTML = rateItems;

            $q('.shipping').innerHTML = shipping;

            let $shippingRates = $$q('.shipping-rates');
            if( $shippingRates.length ){
                $shippingRates.forEach( (el) =>  {
                    el.addEventListener( 'change', (e) => {
                        if( e.target.checked ){
                            if($shippingTaxesLoader){
                                $shippingTaxesLoader.classList.add('loading');
                            }
                            shipping = e.target.getAttribute('data-rate');

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

function showCheckoutErrors(){
    let messages = '';
    $checkoutMessages.style.display = 'none';

    checkoutErrors.forEach( (m) => {
        messages += m+'<br>';
    });

    if( checkoutErrors.length ){
        $checkoutMessages.innerHTML = messages;
        $checkoutMessages.style.display = 'block';
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
        }
    }

    showCheckoutErrors();

    return checkoutErrors.length ? false : true;
}

function validatePayment(){
    checkoutErrors = [];

    let reqFields = $$q('#billing-address-fields [data-required]');

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

    /*
    $q('#billing-address').value;
    $q('#billing-address2').value
    $q('#billing-city').value
    $q('#billing-state').value
    $q('#billing-zip-code').value
    $q('#billing-country').value
    */
    showCheckoutErrors();

    return checkoutErrors.length ? false : true;
}

function validateReview(){
    checkoutErrors = [];

    showCheckoutErrors();
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
            $checkoutSteps[2].classList.remove('current');
            $checkoutSteps[2].classList.remove('done');
            $checkoutFormSections[0].classList.add('current');
            $q('#checkout-step-3-btn').classList.add('visible-hide');
            $q('#checkout-step-2-btn').classList.remove('visible-hide');
            $q('#checkout-submit-btn').classList.add('visible-hide');

        break;
        case 2:
        // PAYMENT

            $checkoutSteps[0].classList.add('done');
            $checkoutSteps[1].classList.add('current');
            $checkoutSteps[2].classList.remove('current');
            $checkoutSteps[1].classList.remove('done');
            $checkoutSteps[2].classList.remove('done');
            $checkoutFormSections[1].classList.add('current');
            $q('#checkout-step-2-btn').classList.add('visible-hide');
            $q('#checkout-step-3-btn').classList.remove('visible-hide');
            $q('#checkout-submit-btn').classList.add('visible-hide');

        break;
        case 3:
        // REVIEW

            $checkoutSteps[0].classList.add('done');
            $checkoutSteps[1].classList.add('done');
            $checkoutSteps[2].classList.add('current');
            $checkoutFormSections[2].classList.add('current');
            $q('#checkout-step-3-btn').classList.add('visible-hide');
            $q('#checkout-step-2-btn').classList.add('visible-hide');
            $q('#checkout-submit-btn').classList.remove('visible-hide');

        break;
    }
}

function showHideStepLoading(){
    $checkoutStepsWrap.classList.add('loading');
    setTimeout( () => {
        $checkoutStepsWrap.classList.remove('loading');
    }, 500);
}

function logCheckoutError(type, error){
    checkoutErrors = [];
    let message = 'We are sorry, but our '+type+' service is having technical issues.';
    checkoutErrors.push(message);
    showCheckoutErrors();
}

function buildCheckoutReview(){

    let shippingAddress = $q('#shipping-address').value+'<br>';
        shippingAddress += $q('#shipping-address2').value !== ''? $q('#shipping-address2').value+'<br>' : '';
        shippingAddress += $q('#shipping-city').value+' '+$q('#shipping-state').value+', '+$q('#shipping-zip-code').value+'<br>';
        shippingAddress += $q('#shipping-country').value;

    /*
    let billingAddress = $q('#billing-address').value+'<br>';
        billingAddress += $q('#billing-address2').value !== ''? $q('#billing-address2').value+'<br>' : '';
        billingAddress += $q('#billing-city').value+' '+$q('#billing-state').value+', '+$q('#billing-zip-code').value+'<br>';
        billingAddress += $q('#billing-country').value;
*/
    if( $checkoutShippingReview ){
        $checkoutShippingReview.innerHTML = shippingAddress;
    }
    /*
    if( $checkoutBillingReview ){
        $checkoutBillingReview.innerHTML = billingAddress;
    }*/

}


/*
* DOM LOAD EVENTS
*
*
*
*/
window.addEventListener('DOMContentLoaded', (e) => {

    let $productImageSelected = $q('.product-image-selected'),
    $productThumbs = $$q('.product-image-thumb'),
    $addCartBtn = $$q('.add-to-cart-btn'),
    $productAttributeList = $$q('.product-attribute-list'),
    $productImageLink = $q('.product-image-selected a'),
    $productPrice = $q('#price'),
    $productStock = $q('#stock'),
    $productPartNumber = $q('#mfg-part-number'),
    $variationId = $q('#variation-id');

    /*
    * STRIPE JS
    *
    *
    */
    if( typeof Stripe !== 'undefined' ){
        let stripe = Stripe(app.stripe_token);
        let elements = stripe.elements();
        let card = elements.create('card');
        card.mount('#card-element');

        function stripeTokenHandler(token) {
            // Insert the token ID into the form so it gets submitted to the server
            let form = document.getElementById('checkout-form');
            let hiddenInput = document.createElement('input');
            hiddenInput.setAttribute('type', 'hidden');
            hiddenInput.setAttribute('name', 'token');
            hiddenInput.setAttribute('value', token.id);
            form.appendChild(hiddenInput);
            form.submit();
        }

        let form = document.getElementById('checkout-form');
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            stripe.createToken(card).then(function(result) {
                if (result.error) {
                    let errorElement = document.getElementById('card-errors');
                    errorElement.textContent = result.error.message;
                } else {
                    stripeTokenHandler(result.token);
                }
            });
        });
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

                if( typeof thisVariation !== 'undefined' ){
                    if( thisVariation.price ){
                        $productPrice.innerHTML = '$'+thisVariation.price;
                    }
                    if( thisVariation.stock && $productStock ){
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
            input.addEventListener('change', (el) => {
                if( el.target.checked && el.target.value === 'new_shipping_address' ){
                    $q('.new-shipping-address').style.display = 'block';
                    shippingAddressOption = 'new';
                    shippingAddressId = false;
                } else {
                    $q('.new-shipping-address').style.display = 'none';
                    shippingAddressId = el.target.value;
                    shippingAddressOption = 'saved';
                    validateShippingFields();
                }
            });
        });
    }

    if( $submitOrderBtn ){
        $submitOrderBtn.addEventListener('click', (e) => {
            e.preventDefault();
            let messages = [];
            let html = '';
            let $checkOutMessages = $q('.checkout-messages');
            $checkOutMessages.style.display = 'none';
            $checkOutMessages.innerHTML = '';
            let $requireds = $$q('[data-required]');
            if( $requireds.length ){
                $requireds.forEach( (el) => {
                    if( el.value === '' ){
                        let inputMessage = el.getAttribute('data-required-message');
                        messages.push(inputMessage);
                    }
                });
            }

            if( messages.length ){
                messages.forEach( (mess) => {
                    html += '<li>'+mess+'</li>';
                });
                $checkOutMessages.innerHTML = html;
                $checkOutMessages.style.display = 'block';
            }

            if( !messages.length ){

            }

        });
    }


    /*
    * CHECKOUT STEP BUTTONS
    *
    *
    */

    if( $checkoutAdvBtns.length ){
        $checkoutAdvBtns.forEach( (btn) => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
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
                    case 3:
                        let vPayment = validatePayment();
                        showHideStepLoading();
                        if( vPayment ){
                            $checkoutFormSections.forEach( (el) => {
                                el.classList.remove('current');
                            });
                            setCheckoutStep(step);
                            buildCheckoutReview();
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


});
