jQuery(document).ready(function(){

	var authKeyURL = jQuery('#woocommerce_paddle_enabled_sandbox').is(':checked') ? integrationData.sandbox_auth_url : integrationData.auth_url;

	jQuery('#woocommerce_paddle_enabled_sandbox').change(function() {
		if(this.checked) {
			authKeyURL = integrationData.sandbox_auth_url;
			jQuery('.open_paddle_popup').attr('href', authKeyURL);
		} else {
			authKeyURL = integrationData.auth_url;
			jQuery('.open_paddle_popup').attr('href', authKeyURL);
		}
	  });


	  jQuery('.open_paddle_popup').attr('href', authKeyURL);

});
