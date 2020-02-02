import axios from 'axios';

const HTTP = axios.create(axios.defaults.headers.common = {
    'X-Requested-With': 'XMLHttpRequest',
    'X-CSRF-TOKEN' : app.csrfToken,
    'Content-Type': 'multipart/form-data'
});

let modalMessage = '',
thisVariation = {};

let $shoppeAddCartAlert = document.querySelector('#shoppe-product-alert'),
$shippingAddressFields = document.querySelectorAll('.shipping-address-field'),
$shippingTaxesLoader = document.querySelector('.shipping-taxes-loader'),
$shippingRatesList = document.querySelector('.shipping-rates-list'),
$submitOrderBtn = document.querySelector('.submit-order-btn'),
$sameAsShipping = document.querySelector('#same-as-shipping');

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

/*
* SHIPPING AND TAXES
*
*
*/
let getShipping = async () => {
    let shipping = 0.00;
    let formData = new FormData();
    formData.append('address', document.querySelector('#shipping-address').value );
    formData.append('address2', document.querySelector('#shipping-address2').value);
    formData.append('city', document.querySelector('#shipping-city').value);
    formData.append('state', document.querySelector('#shipping-state').value);
    formData.append('zip', document.querySelector('#shipping-zip-code').value);
    formData.append('country', document.querySelector('#shipping-country').value);

    return HTTP.post('/api/shipping', formData)
    .then( response => {
        return response.data.rates;
    })
    .catch(e => {
        console.log('shipping error', e);
    });
};

let getTaxes = async (shipping) => {
    let taxes = 0.00;
    let formData = new FormData();
    formData.append('shipping', shipping );
    formData.append('address', document.querySelector('#shipping-address').value );
    formData.append('address2', document.querySelector('#shipping-address2').value);
    formData.append('city', document.querySelector('#shipping-city').value);
    formData.append('state', document.querySelector('#shipping-state').value);
    formData.append('zip', document.querySelector('#shipping-zip-code').value);
    formData.append('country', document.querySelector('#shipping-country').value);

    return HTTP.post('/api/taxes', formData)
    .then( response => {
        return response.data.taxes;
    })
    .catch(e => {
        console.log('taxes error', e);
    });
};

function calcOrderTotal(){

}

function validateShippingFields(){
    let hasShippingAddress = true;
    if($shippingTaxesLoader){
        $shippingTaxesLoader.classList.add('loading');
    }
    console.log('validate shipping');
    $shippingAddressFields.forEach( function(el){
        if( !el.value.length  ){
            hasShippingAddress = false
        }
    });

    if( hasShippingAddress ){

        getShipping().then( (rates) => {

            let rate = rates[0].amount;

            $shippingRatesList.innerHTML = '';
            let rateItems = '';
            rates.forEach( (v, i) => {
                let checked = i === 0? 'checked="checked"' : '';
                rateItems += '<li class="rate-item">';
                    rateItems += '<label>';
                    rateItems += '<input type="radio" name="shipping_rate" data-rate-carrier="'+v.carrier+'" data-rate-service="'+v.service+'" data-rate-service-id="'+v.service_id+'" '+checked+' value="'+v.amount+'">';
                    rateItems += ' <span class="rate-service">'+v.carrier +' '+v.service+'</span> $<span class="rate-amount">'+v.amount+'</span>';
                    rateItems += '<div class="rate-estimated-days">Estimated '+v.estimated_days+' day shipping</div>';
                    rateItems += '</label>';
                rateItems += '</li>';
            });
            $shippingRatesList.innerHTML = rateItems;

            document.querySelector('.shipping').innerHTML = rate;

            getTaxes(rate).then( (taxes) => {

                document.querySelector('.taxes').innerHTML = taxes;

                calcOrderTotal();

                if($shippingTaxesLoader){
                    $shippingTaxesLoader.classList.remove('loading');
                }
            })
        });

    } else {
        if($shippingTaxesLoader){
            $shippingTaxesLoader.classList.remove('loading');
        }
    }
}

window.addEventListener('DOMContentLoaded', (e) => {

    let $productImageSelected = document.querySelector('.product-image-selected'),
    $productThumbs = document.querySelectorAll('.product-image-thumb'),
    $addCartBtn = document.querySelectorAll('.add-to-cart-btn'),
    $productAttributeList = document.querySelectorAll('.product-attribute-list'),
    $productImageLink = document.querySelector('.product-image-selected a'),
    $productPrice = document.querySelector('#price'),
    $productStock = document.querySelector('#stock'),
    $productPartNumber = document.querySelector('#mfg-part-number'),
    $variationId = document.querySelector('#variation-id');


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
                document.querySelector('.product-image-selected a').addEventListener('click', function(e) {
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
                        let $variationImage = document.querySelector('#variation-image-'+thisVariation.id);
                        let $variationImageLink = document.querySelector('#variation-image-'+thisVariation.id+' a');
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
    if( $shippingAddressFields.length > 0 ){
        validateShippingFields();
        $shippingAddressFields.forEach( (el) => {
            el.addEventListener('blur', validateShippingFields);
        });
    }

    if( $sameAsShipping ){
        $sameAsShipping.addEventListener('change', (el) => {
            console.log(el);
            if( el.target.checked ){
                document.querySelector('#billing-address').value = document.querySelector('#shipping-address').value;
                document.querySelector('#billing-address2').value = document.querySelector('#shipping-address2').value;
                document.querySelector('#billing-city').value = document.querySelector('#shipping-city').value;
                document.querySelector('#billing-state').value = document.querySelector('#shipping-state').value;
                document.querySelector('#billing-zip-code').value = document.querySelector('#shipping-zip-code').value;
                document.querySelector('#billing-country').value = document.querySelector('#shipping-country').value;
            }
        });
    }

    if( $submitOrderBtn ){
        $submitOrderBtn.addEventListener('click', (e) => {
            e.preventDefault();
            let messages = [];
            let html = '';
            let $checkOutMessages = document.querySelector('.checkout-messages');
            $checkOutMessages.style.display = 'none';
            $checkOutMessages.innerHTML = '';
            let $requireds = document.querySelectorAll('[data-required]');
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


});
