<?php

namespace Packlink\WooCommerce\Components\Utility;

use wpdb;

class Actions_Delete {
	/**
	 * Deletes all Packlink Action Scheduler actions and related logs
	 * from the current site.
	 *
	 * @param wpdb $wpdb WordPress database session.
	 */
	public static function delete_packlink_scheduled_actions( $wpdb ) {
		$hook = 'packlink_execute_task';

		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( $hook );
		}

		$actions_table = $wpdb->prefix . 'actionscheduler_actions';
		$logs_table    = $wpdb->prefix . 'actionscheduler_logs';
		$claims_table  = $wpdb->prefix . 'actionscheduler_claims';

		$action_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT action_id FROM {$actions_table} WHERE hook = %s",
				$hook
			)
		);

		if ( empty( $action_ids ) ) {
			return;
		}

		$placeholders = implode( ',', array_fill( 0, count( $action_ids ), '%d' ) );

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$logs_table} WHERE action_id IN ({$placeholders})",
				$action_ids
			)
		);

		$claim_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT claim_id FROM {$actions_table} WHERE hook = %s AND claim_id != 0",
				$hook
			)
		);

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$actions_table} WHERE action_id IN ({$placeholders})",
				$action_ids
			)
		);

		if ( ! empty( $claim_ids ) ) {
			$claim_placeholders = implode( ',', array_fill( 0, count( $claim_ids ), '%d' ) );

			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$claims_table} WHERE claim_id IN ({$claim_placeholders})",
					$claim_ids
				)
			);
		}
	}
}