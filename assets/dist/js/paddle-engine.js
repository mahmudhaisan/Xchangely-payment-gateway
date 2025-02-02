jQuery(function($) {
    const form = $('form.checkout, form#order_review');

   const isSandbox = paddle_data.is_sandbox == 1 ? true : false;
    if(isSandbox){
        Paddle.Environment.set('sandbox');
        console.log('Paddle sandbox mode is enabled');
    }

    Paddle.Setup({
        vendor: parseInt(paddle_data.vendor),
        debug: isSandbox
    });


    $('form.checkout').on('click', ':submit', function(event) {
        return !invokeOverlayCheckout();
    });

    $('form#order_review').on('submit', function(event) {
        return !invokeOverlayCheckout();
    });

    $('#checkout_buttons button').on('click', function(event) {
        form.find('form#order_review').submit();
        return false;
    });

    function invokeOverlayCheckout() {
        if (isPaddlePaymentMethodSelected()) {
            Paddle.Spinner.show();
            getSignedCheckoutUrlViaAjax();
            return true;
        }
        return false;
    }

    function isPaddlePaymentMethodSelected() {
        return $('#payment_method_paddle').is(':checked');
    }

    function getSignedCheckoutUrlViaAjax() {
        $.ajax({
            dataType: 'json',
            method: 'POST',
            url: paddle_data.order_url,
            data: form.serializeArray(),
            success: function(response) {
                if (response.result === 'success') {
                    startPaddleCheckoutOverlay(response.checkout_url, response.email, response.country, response.postcode);
                } else {
                    handleErrorResponse(response);
                }
            },
            error: function(jqxhr, status) {
                console.error('Error during Ajax request:', status);
            }
        });
    }

    function startPaddleCheckoutOverlay(checkoutUrl, emailAddress, country, postCode) {
        Paddle.Checkout.open({
            email: emailAddress,
            country: country,
            postcode: postCode,
            override: checkoutUrl
        });
    }

    function handleErrorResponse(response) {
        if (response.reload === 'true') {
            window.location.reload();
            return;
        }

        $('.woocommerce-error, .woocommerce-message').remove();
        if (response.messages) {
            form.prepend(response.messages);
        }

        form.removeClass('processing').unblock();
        form.find('.input-text, select').blur();

        $('html, body').animate({
            scrollTop: (form.offset().top - 100)
        }, 1000);

        if (response.nonce) {
            form.find('#_wpnonce').val(response.nonce);
        }

        if (response.refresh === 'true') {
            $('body').trigger('update_checkout');
        }

        Paddle.Spinner.hide();
    }
});
