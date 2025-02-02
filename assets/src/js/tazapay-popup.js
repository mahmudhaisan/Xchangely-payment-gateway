jQuery(document).ready(function ($) {


    // Function to create and inject the modal container with order details in a table format
    function createPaymentModalContainer(orderData) {
        var orderItemsHTML = '';
        var subtotal = orderData.subtotal;
        var fees = orderData.fees;
        var total = orderData.total;

        // Generate HTML for order items in a table layout
        orderData.items.forEach(function (item) {
            orderItemsHTML += `
        <tr class="order-item">
            <td class="item-name">${item.name}</td>
            <td class="item-quantity">${item.quantity}</td>
            <td class="item-price">$${item.price}</td>
            <td class="item-link">
                <a href="${item.product_url}" target="_blank" class="btn btn-primary">View Product</a>
            </td>
        </tr>
    `;
        });

        // Check if overlay exists, if not create it
        if ($('#xchangely-overlay').length === 0) {
            $('body').append(`
                <div id="xchangely-overlay" class="overlay-container" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); display: flex; justify-content: center; align-items: center;">
            <div class="xchangely-popup-container" style="background: #fff; border-radius: 12px; max-width: 500px; width: 90%; box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);">
                    
                    <!-- Header -->
                    <div class="xchangely-popup-header" style="display: flex; justify-content: center; align-items: center; padding: 20px; border-bottom: 1px solid #ddd; position: relative;">
                        <img src="https://placehold.co/200x60" alt="Brand Logo" class="xchangely-logo" style="height: 50px;">
                        <button id="xchangely-close" class="btn-close" style="position: absolute; right: 15px; background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
                    </div>

                    <!-- Content -->
                    <div class="xchangely-popup-content" style="padding: 20px;">
                        <h2 style="text-align: center; font-size: 20px; margin-bottom: 10px;">Secure Checkout</h2>
                        <p style="text-align: center; font-size: 16px; margin-bottom: 20px;">Review your order details before proceeding with payment.</p>

                        <!-- Order Summary -->
                        <div class="xchangely-order-summary">
                            <h3 style="margin-bottom: 10px;">Order Details</h3>
                            <table class="order-table" style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                                <thead>
                                    <tr style="background: #f0f0f0;">
                                        <th style="padding: 10px; border-bottom: 1px solid #ddd;">Product</th>
                                        <th style="padding: 10px; border-bottom: 1px solid #ddd;">Qty</th>
                                        <th style="padding: 10px; border-bottom: 1px solid #ddd;">Price</th>
                                        <th style="padding: 10px; border-bottom: 1px solid #ddd;">View Product</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${orderItemsHTML}
                                </tbody>
                            </table>

                            <hr>

                            <div class="xchangely-total" style="margin-top: 15px;">
                                <div class="summary-item" style="display: flex; justify-content: space-between; padding: 5px 0;">
                                    <span class="summary-title">Subtotal:</span>
                                    <span class="summary-value">$${subtotal}</span>
                                </div>
                                <div class="summary-item" style="display: flex; justify-content: space-between; padding: 5px 0;">
                                    <span class="summary-title">Fees:</span>
                                    <span class="summary-value">$${fees}</span>
                                </div>
                                <div class="summary-item" style="display: flex; justify-content: space-between; font-weight: bold; padding: 10px 0;">
                                    <span class="summary-title">Total:</span>
                                    <span class="summary-value" style="color: #d9534f;"><strong>$${total}</strong></span>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Form -->
                        <form id="payment-form" style="margin-top: 20px;">
                            <div id="card-embed" style="padding: 20px; background: #f8f9fa; border-radius: 8px;">
                                <!-- Embed will create form here -->
                            </div>
                            <div id="error-message" style="color: red; margin-top: 10px;">
                                <!-- Display error message here -->
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            `);

            // Close modal when close button is clicked
            $('#xchangely-close').on('click', function () {
                $('#xchangely-overlay').fadeOut();
            });

            // Display the overlay modal
            $('#xchangely-overlay').fadeIn();
        }
    }





    // Function to fetch WooCommerce order details via AJAX
    function fetchOrderDetails() {

        // Get the full URL from the browser's address bar
        let url = window.location.href;
        // Check if the URL contains the 'xchangely-payment' part
        if (url.includes('#xchangely-payment:')) {
            // Extract the part after #xchangely-payment:
            let hash = url.split('#xchangely-payment:')[1];

            // Decode the hash part (it might contain HTML entities like &#038;)
            let decodedHash = decodeURIComponent(hash);

            console.log(decodedHash); // This should now correctly show the full query string with parameters
            var orderId = decodedHash.split('order_id=')[1]; // Get the part of the URL after 'order_id='        

        }


        if (!orderId) {
            console.error("Order ID is missing!");
            return;
        }

        console.log("Fetching order details for Order ID:", orderId);
        // console.log("WooCommerce AJAX URL:", wc_checkout_params.ajax_url);


        console.log(orderId);

        $.ajax({
            url: wc_checkout_params.ajax_url, // Ensure WooCommerce loads this
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'xchangely_get_order_details',
                order_id: orderId,
            },
            success: function (response) {
                console.log("AJAX Response:", response); // Log full response

                if (response.success) {
                    console.log("Order Details Data:", response.data);
                    createPaymentModalContainer(response.data);

                    $('#xchangely-overlay').fadeIn();
                } else {
                    console.error("Failed to fetch order details:", response.data);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error("AJAX Error:", textStatus, errorThrown);
                console.error("Response Text:", jqXHR.responseText);
            }
        });
    }


    async function embeed_card() {
        try {
            if (!document.getElementById("card-embed")) {
                console.warn("Card embed container not found, retrying in 500ms...");
                setTimeout(embeed_card, 1000);
                return;
            }

            const tazapay = await window.tazapay("pk_test_9NjxRctWhYSNk8cV4e9A");
            const embeds = tazapay.embeds();

            let configuration = {
                style: {},
                showLabels: true,
                hideErrors: false,
                layout: "two-rows",
                cvvMask: true,
                customPayButton: false,
            };

            const card = embeds.create("card", configuration);
            card.mount("card-embed");

            card.on("payButtonClick", function (d) {
                console.log(d);

                // This code block will run when the inbuilt "Pay" button is clicked.
            });


            console.log("Tazapay embed loaded successfully!");
        } catch (error) {
            console.error("Error embedding card:", error);
        }
    }








    // Function to open the payment modal
    function openPaymentModal(ajaxUrl) {


        // Show the overlay
        $('#xchangely-overlay').fadeIn();


        // Handle form submission
        $('#xchangely-payment-form').on('submit', function (event) {
            event.preventDefault();

            // Simulate payment processing (could be actual API integration here)
            setTimeout(function () {
                alert('Payment successful! Completing order...');

                // Redirect to WooCommerce AJAX URL for order completion
                window.location.href = ajaxUrl;

                // Hide modal after payment success
                $('#xchangely-overlay').fadeOut();
            }, 1000);
        });
    }




    let lastUrl = window.location.href; // Store the initial URL

    function checkHashForPayment() {

        let hash = decodeURIComponent(window.location.hash);
   
        if (hash.startsWith('#xchangely-payment:')) {
            let ajaxUrl = hash.split(':')[1];

            openPaymentModal(ajaxUrl);
            fetchOrderDetails();
            embeed_card();


        }
    }


    // Polling function to detect URL changes every 500ms
    setInterval(function () {
        let currentUrl = window.location.href; // Get current URL

        if (currentUrl !== lastUrl) {
            lastUrl = currentUrl; // Update stored URL
            console.log("URL changed:", currentUrl);
            checkHashForPayment(); // Run function when URL changes
        }
    }, 500); // Check every 500ms


    checkHashForPayment();





});
