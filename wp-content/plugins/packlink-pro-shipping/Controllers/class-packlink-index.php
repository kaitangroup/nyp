<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Controllers;

/**
 * Class Packlink_Index
 *
 * @package Packlink\WooCommerce\Controllers
 */
class Packlink_Index extends Packlink_Base_Controller {
	/**
	 * Controller index action.
	 */
	public function index() {
		$controller_name = $this->get_param( 'packlink_pro_controller' );

		$class_name = '\Packlink\WooCommerce\Controllers\Packlink_' . $controller_name . '_Controller';
		if ( ! $this->validate_controller_name( $controller_name ) || ! class_exists( $class_name ) ) {
			status_header( 404 );
			nocache_headers();

			require get_404_template();

			exit();
		}

		/**
		 * Controller instance.
		 *
		 * @var Packlink_Base_Controller $controller
		 */
		$controller = new $class_name();
		$controller->process();
	}

	/**
	 * Validates controller name by checking whether it exists in the list of known controller names.
	 *
	 * @param string $controller_name Controller name from request input.
	 *
	 * @return bool
	 */
	private function validate_controller_name( $controller_name ) {
		$allowed_controllers = array(
			'Web_Hook',
			'Integration_Registration_Webhook',
			'Integration_Status',
			'Frontend',
			'Order_Overview',
			'Checkout',
			'Order_Details',
			'Debug',
			'Auto_Test',
			'Auto_Configure',
			'Module_State',
			'Login',
			'Regions',
			'Register',
			'Onboarding_State',
			'Parcel',
			'Warehouse',
			'My_Shipping_Services',
			'Shop_Shipping_Methods',
			'Configuration',
			'Order_Status',
			'Shipping_Service',
			'System_Info',
			'Edit_Service',
			'Shipping_Zones',
			'Support',
			'Manual_Sync',
			'Manual_Refresh_Service',
            'Cash_On_Delivery',
		);

		return in_array( $controller_name, $allowed_controllers, true );
	}
}

$controller = new Packlink_Index();
$controller->index();
