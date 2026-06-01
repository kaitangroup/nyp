<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Components\Services;

use Packlink\BusinessLogic\Registration\RegistrationInfo;
use Packlink\BusinessLogic\Registration\RegistrationInfoService;
use Packlink\WooCommerce\Components\Utility\Shop_Helper;

/**
 * Class Registration_Info_Service
 *
 * @package Packlink\WooCommerce\Components\Services
 */
class Registration_Info_Service implements RegistrationInfoService {

	/**
	 * Returns registration data from the integration.
	 *
	 * @return RegistrationInfo User registration info.
	 */
	public function getRegistrationInfoData() {
		$registration_data = $this->get_registration_data();

		return new RegistrationInfo( $registration_data['email'], $registration_data['phone'], $registration_data['source'] );
	}

	/**
	 * Retrieves user registration data.
	 *
	 * @return array User registration data.
	 */
	protected function get_registration_data() {
		$result = array();

		$user_data        = Shop_Helper::get_admin_user_data( get_current_user_id() );
		$result['email']  = ! empty( $user_data['email'] ) ? $user_data['email'] : '';
		$result['phone']  = ! empty( $user_data['phone'] ) ? $user_data['phone'] : '';
		$result['source'] = get_home_url();

		return $result;
	}
}
