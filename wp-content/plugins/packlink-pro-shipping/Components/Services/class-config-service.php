<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Components\Services;

use Logeecom\Infrastructure\Logger\Logger;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\WooCommerce\Components\Utility\Shop_Helper;

/**
 * Class Config_Service
 *
 * @package Packlink\WooCommerce\Components\Services
 */
class Config_Service extends Configuration {

	/**
	 * Minimal log level.
	 */
	const MIN_LOG_LEVEL = Logger::ERROR;

	const DEFAULT_FOOTER_HEIGHT      = 40;

	/**
	 * Singleton instance of this class.
	 *
	 * @var static
	 */
	protected static $instance;

	/**
	 * Retrieves integration name.
	 *
	 * @return string Integration name.
	 */
	public function getIntegrationName() {
		return 'WooCommerce';
	}

	/**
	 * Returns order draft source.
	 *
	 * @return string
	 */
	public function getDraftSource() {
		return 'module_woocommerce';
	}

	/**
	 * Gets the current version of the module/integration.
	 *
	 * @return string The version number.
	 */
	public function getModuleVersion() {
		return Shop_Helper::get_plugin_version();
	}

	/**
	 * Gets the name of the integrated e-commerce system.
	 * This name is related to Packlink API which can be different from the official system name.
	 *
	 * @return string The e-commerce name.
	 */
	public function getECommerceName() {
		return 'woocommerce_2';
	}

	/**
	 * Gets the current version of the integrated e-commerce system.
	 *
	 * @return string The version number.
	 */
	public function getECommerceVersion() {
		return \WooCommerce::instance()->version;
	}

	/**
	 * Returns current system identifier.
	 *
	 * @return string Current system identifier.
	 */
	public function getCurrentSystemId() {
		return (string) get_current_blog_id();
	}

	/**
	 * Returns web-hook callback URL for current system.
	 *
	 * @return string Web-hook callback URL.
	 */
	public function getWebHookUrl() {
		return Shop_Helper::get_controller_url( 'Web_Hook', 'index' );
	}

	/**
	 * Returns the integration registration webhook URL.
	 *
	 * @return string Registration webhook URL.
	 */
	public function getStatusUpdateUrl()
	{
		return Shop_Helper::get_controller_url('Integration_Registration_Webhook', 'index');
	}

	/**
	 * Sets database version for migration scripts
	 *
	 * @param string $database_version Database version.
	 */
	public function set_database_version( $database_version ) {
		update_option( 'PACKLINK_VERSION', $database_version );
	}

	/**
	 * Returns database version
	 *
	 * @return string
	 */
	public function get_database_version() {
		return get_option( 'PACKLINK_VERSION', '2.0.1' );
	}

	/**
	 * Returns default shipping method.
	 *
	 * @return ShippingMethod|null Shipping method.
	 */
	public function get_default_shipping_method() {
		$value = $this->getConfigValue( 'Default_Shipping' );

		return $value && is_array( $value ) ? ShippingMethod::fromArray( $value ) : null;
	}

	/**
	 * Saves default shipping method.
	 *
	 * @param ShippingMethod $shipping_method Shipping method.
	 */
	public function set_default_shipping_method( ShippingMethod $shipping_method = null ) {
		$this->saveConfigValue( 'Default_Shipping', $shipping_method ? $shipping_method->toArray() : null );
	}

	/**
	 * Retrieves footer height.
	 *
	 * @return int
	 */
	public function get_footer_height() {
		$height = $this->getConfigValue( 'Footer_Height' );

		return $height ?: self::DEFAULT_FOOTER_HEIGHT;
	}

	/**
	 * Saves footer height.
	 *
	 * @param int $height
	 */
	public function set_footer_height( $height ) {
		$this->saveConfigValue( 'Footer_Height', $height );
	}

	/**
	 * Returns whether manual synchronization is enabled.
	 *
	 * @return bool
	 */
	public function is_manual_sync_enabled() {
		$is_manual_sync_enabled = $this->getConfigValue( 'Manual_Sync' );

		return $is_manual_sync_enabled ?: false;
	}

	/**
	 * Saves whether manual synchronization is enabled.
	 *
	 * @param bool $manual_sync_enabled
	 */
	public function set_manual_sync_enabled( $manual_sync_enabled ) {
		$this->saveConfigValue( 'Manual_Sync', $manual_sync_enabled );
	}

}
