<?php

// Check includes/class-wc-payment-gateway-wcpay.php save_upe_appearance_ajax()
// Available styles: https://docs.stripe.com/elements/appearance-api#supported-css-properties
add_filter('wcpay_elements_appearance', function ($appearance) {
	$appearance->rules->{'.Input'}->paddingTop = "6px";
	$appearance->rules->{'.Input--invalid'}->paddingTop = "6px";
	$appearance->rules->{'.Input'}->paddingBottom = "6px";
	$appearance->rules->{'.Input--invalid'}->paddingBottom = "6px";

	return $appearance;
});
