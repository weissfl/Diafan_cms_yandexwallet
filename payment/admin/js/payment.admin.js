var $payment_type = $('select[name=yandexwallet_paymentType]');

function show_params(name) {
    $(".tr_payment[payment="+name+"]"+($payment_type.val()=='AC' ? ':not(:has(input[name=yandexwallet_secret]))' : '')).show();
}

show_params(
    $("select[name=payment]").change(function(){
        $(".tr_payment").hide();
        show_params(this.value);
    }).val()
);

var $wallet_secret = $('input[name=yandexwallet_secret]').parents('.tr_payment');
$payment_type.change(function() {
    if(this.value=='PC') {
        $wallet_secret.show();
    }
    else {
        $wallet_secret.hide();
    }
});

