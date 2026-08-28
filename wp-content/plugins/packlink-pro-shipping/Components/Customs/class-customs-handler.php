<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Components\Customs;

use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Country\CountryCodes;
use Packlink\BusinessLogic\Customs\CustomsService;
use Packlink\BusinessLogic\Customs\Models\CustomsMapping;
use Packlink\WooCommerce\Components\Services\Config_Service;
use Packlink\WooCommerce\Components\Services\Customs_Mapping_Service;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Customs_Handler
 *
 * Adds the customs data-capture fields WooCommerce lacks natively: HS code and country of origin on
 * products, and customer tax ID and company VAT on the admin customer profile. The captured meta is
 * later mapped into the core order objects at synchronization time (Shop_Order_Service).
 *
 * @package Packlink\WooCommerce\Components\Customs
 */
class Customs_Handler {

	/**
	 * Renders the HS code and country-of-origin fields in the product Shipping tab.
	 *
	 * Hook: woocommerce_product_options_shipping.
	 *
	 * @return void
	 */
	public function render_product_fields() {
		\woocommerce_wp_text_input(
			array(
				'id'          => Customs_Mapping_Service::PRODUCT_HS_CODE_META,
				'label'       => __( 'HS code', 'packlink-pro-shipping' ),
				'description' => __( 'Harmonized System code used on customs invoices (6 to 8 digits).', 'packlink-pro-shipping' ),
				'desc_tip'    => true,
				'type'        => 'text',
				'custom_attributes' => array(
					'pattern'   => '[0-9]{6,8}',
					'maxlength' => '8',
				),
			)
		);

		\woocommerce_wp_select(
			array(
				'id'      => Customs_Mapping_Service::PRODUCT_COUNTRY_OF_ORIGIN_META,
				'label'   => __( 'Country of origin', 'packlink-pro-shipping' ),
				'desc_tip' => true,
				'description' => __( 'Country where the product was manufactured, used on customs invoices.', 'packlink-pro-shipping' ),
				'options' => $this->get_country_options(),
			)
		);
	}

	/**
	 * Sanitizes and persists the product customs meta.
	 *
	 * Hook: woocommerce_process_product_meta.
	 *
	 * @param int $post_id Product post id.
	 *
	 * @return void
	 */
	public function save_product_fields( $post_id ) {
		$product = \wc_get_product( $post_id );

		if ( ! $product ) {
			return;
		}

		$hs_code = isset( $_POST[ Customs_Mapping_Service::PRODUCT_HS_CODE_META ] ) //phpcs:ignore WordPress.Security.NonceVerification.Missing
			? \sanitize_text_field( \wp_unslash( $_POST[ Customs_Mapping_Service::PRODUCT_HS_CODE_META ] ) ) //phpcs:ignore WordPress.Security.NonceVerification.Missing
			: '';
		$hs_code = \preg_replace( '/[^0-9]/', '', $hs_code );

		$country = isset( $_POST[ Customs_Mapping_Service::PRODUCT_COUNTRY_OF_ORIGIN_META ] ) //phpcs:ignore WordPress.Security.NonceVerification.Missing
			? \sanitize_text_field( \wp_unslash( $_POST[ Customs_Mapping_Service::PRODUCT_COUNTRY_OF_ORIGIN_META ] ) ) //phpcs:ignore WordPress.Security.NonceVerification.Missing
			: '';

		if ( '' !== $country && ! in_array( $country, CountryCodes::$countryCodes, true ) ) {
			$country = '';
		}

		$product->update_meta_data( Customs_Mapping_Service::PRODUCT_HS_CODE_META, $hs_code );
		$product->update_meta_data( Customs_Mapping_Service::PRODUCT_COUNTRY_OF_ORIGIN_META, $country );
		$product->save();
	}

	/**
	 * Adds a "Packlink customs" section with a single tax ID / VAT number text field to the admin
	 * customer profile. WooCommerce renders and saves the declared field automatically. One value
	 * serves both customs attributes: the core sends it as the receiver tax id or VAT number
	 * depending on the configured receiver user type.
	 *
	 * Hook: woocommerce_customer_meta_fields.
	 *
	 * @param array $fields Customer meta field sections.
	 *
	 * @return array
	 */
	public function add_customer_meta_fields( $fields ) {
		$fields['packlink_customs'] = array(
			'title'  => __( 'Packlink customs', 'packlink-pro-shipping' ),
			'fields' => array(
				Customs_Mapping_Service::USER_TAX_ID_META => array(
					'label'       => __( 'Tax ID / VAT number', 'packlink-pro-shipping' ),
					'description' => __( 'Used on customs invoices: sent as the customer tax ID for private persons or as the company VAT number for companies.', 'packlink-pro-shipping' ),
				),
			),
		);

		return $fields;
	}

	/**
	 * Seeds a default customs mapping the first time the plugin is installed/upgraded, pointing the
	 * mapping selects at the dedicated Packlink fields. Idempotent: never overwrites a merchant's
	 * saved mapping.
	 *
	 * @return void
	 */
	public static function seed_default_customs_mapping() {
		/**
		 * Configuration service.
		 *
		 * @var Config_Service $config
		 */
		$config = ServiceRegister::getService( Config_Service::CLASS_NAME );

		if ( $config->getCustomsMappings() ) {
			return;
		}

		$mapping = new CustomsMapping();

		$mapping->defaultReason           = 'purchase_or_sale';
		$mapping->defaultSenderTaxId      = '';
		$mapping->defaultReceiverUserType = CustomsService::PRIVATE_PERSON;
		$mapping->defaultReceiverTaxId    = '';
		$mapping->defaultTariffNumber     = '';
		$mapping->defaultCountry          = '';
		$mapping->mappingReceiverTaxId    = Customs_Mapping_Service::PREFIX_USER_META . Customs_Mapping_Service::USER_TAX_ID_META;
		$mapping->mappingTariffNumber     = Customs_Mapping_Service::PREFIX_PRODUCT_META . Customs_Mapping_Service::PRODUCT_HS_CODE_META;
		$mapping->mappingCountryOfOrigin  = Customs_Mapping_Service::PREFIX_PRODUCT_META . Customs_Mapping_Service::PRODUCT_COUNTRY_OF_ORIGIN_META;

		$config->setCustomsMappings( $mapping );
	}

	/**
	 * Builds the country-of-origin select options from the core supported-country list.
	 *
	 * @return array
	 */
	private function get_country_options() {
		$options = array( '' => __( '— Select —', 'packlink-pro-shipping' ) );

		foreach ( CountryCodes::$countryCodes as $code ) {
			$options[ $code ] = $code;
		}

		return $options;
	}
}
