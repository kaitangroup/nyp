<?php
/**
 * Conditional logic for compatibility with WP
 */

if ( class_exists( 'WC_REST_Products_Controller' ) ) {
	class TikTokProductsController extends \WC_REST_Products_Controller {
		public function prepare_object( $object, $request ) {
			return $this->prepare_object_for_response( $object, $request );
		}
	}
} else {
	class TikTokProductsController {
		public function prepare_object( $object, $request ) {}
	}
}
