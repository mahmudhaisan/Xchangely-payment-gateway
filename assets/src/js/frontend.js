// jQuery(function ($) {
//     // Hook into WooCommerce checkout submission
//     $(document).on('submit', 'form.checkout', function (event) {

//         event.preventDefault();
//         alert(12);

//         // Disable submit button to prevent duplicate clicks
//         let $submit = $('form.checkout #place_order');
//         $submit.prop('disabled', true);

//         // Get order data from WooCommerce AJAX
//         $.ajax({
//             type: 'POST',
//             url: wc_checkout_params.checkout_url,
//             data: $('form.checkout').serialize(),
//             success: function (response) {
//                 if (response.result === 'success' && response.tazapay_client_token) {
//                     openTazapayPopup(response.tazapay_client_token);
//                 } else {
//                     // Re-enable submit button if error
//                     alert('Failed to initiate Tazapay payment.');
//                     $submit.prop('disabled', false);
//                 }
//             },
//             error: function () {
//                 alert('Error processing payment.');
//                 $submit.prop('disabled', false);
//             }
//         });
//     });

//     // Function to open Tazapay popup
//     function openTazapayPopup(client_token) {
//         // Ensure Tazapay.js is loaded
//         if (!window.tazapay) {
//             alert('Tazapay SDK not loaded.');
//             return;
//         }

//         const tazapay = window.tazapay('pk_test_llBdBGEkC93bL1i5dHbG'); // Replace with live key

//         const embeds = tazapay.embeds();
//         let card = embeds.create("card", {
//             style: {},
//             layout: "two-rows",
//             cvvMask: true,
//         });

//         // Create a modal container
//         let modal = $('<div id="tazapay-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: center;"><div style="background: white; padding: 20px; border-radius: 10px;"><div id="card-embed"></div><button id="tazapay-pay" style="margin-top: 10px;">Pay Now</button></div></div>');
//         $('body').append(modal);

//         // Mount the card field inside the modal
//         card.mount("card-embed");

//         // Handle payment
//         $('#tazapay-pay').on('click', function () {
//             const details = {
//                 payment_method_details: {
//                     type: "card",
//                     card: { card: card },
//                 },
//                 success_url: window.location.href, // Redirect to same page after payment
//                 cancel_url: window.location.href, // Redirect to same page if failed
//             };

//             tazapay.confirmPayment(client_token, details)
//                 .then(response => {
//                     console.log('Payment success:', response);
//                     $('#tazapay-modal').remove(); // Close popup
//                     $('form.checkout').off('submit').submit(); // Proceed with WooCommerce order submission
//                 })
//                 .catch(error => {
//                     alert('Payment failed: ' + error.error);
//                     $('#tazapay-modal').remove(); // Close popup on failure
//                     $('form.checkout #place_order').prop('disabled', false);
//                 });
//         });
//     }
// });





