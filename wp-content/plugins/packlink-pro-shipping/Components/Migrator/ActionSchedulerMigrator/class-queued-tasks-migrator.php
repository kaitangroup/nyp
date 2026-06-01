<?php

namespace Packlink\WooCommerce\Components\Migrator\ActionSchedulerMigrator;

use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecutor\Interfaces\TaskExecutorInterface;
use Packlink\BusinessLogic\Scheduler\DTO\ScheduleConfig;
use Packlink\BusinessLogic\Scheduler\Interfaces\SchedulerInterface;
use Packlink\BusinessLogic\ShipmentDraft\Utility\DraftStatus;
use Packlink\BusinessLogic\Tasks\BusinessTasks\SendDraftBusinessTask;
use Packlink\BusinessLogic\Tasks\BusinessTasks\UpdateShippingServicesBusinessTask;
use Packlink\WooCommerce\Components\Utility\Database;

class Queued_Tasks_Migrator {
	/**
	 * Runs all Action Scheduler migration steps.
	 */
	public function migrate() {
		Logger::logInfo('Migration started.');

		if ( ! function_exists( 'as_enqueue_async_action' ) ) {
			add_action( 'init', [ $this, 'migrate' ] );
			return;
		}

		$this->migrateDraftStatuses();
		$this->scheduleWeeklyUpdateShippingServices();
		$this->migrateQueuedItems();
		$this->cleanupLegacyQueueData();

		Logger::logInfo('Migration finished successfully.');
	}

	/**
	 * Schedules weekly UpdateShippingServicesBusinessTask.
	 */
	private function scheduleWeeklyUpdateShippingServices() {
		Logger::logInfo('Scheduling weekly UpdateShippingServicesBusinessTask.');

		if ( ! function_exists( 'as_schedule_recurring_action' ) ) {
			return;
		}

		/** @var SchedulerInterface $scheduler */
		$scheduler = ServiceRegister::getService( SchedulerInterface::CLASS_NAME );
		if ( ! $scheduler instanceof SchedulerInterface ) {
			return;
		}

		$schedule_config = new ScheduleConfig(
			rand( 1, 7 ),
			rand( 0, 5 ),
			rand( 0, 59 ),
			true
		);
		$scheduler->scheduleWeekly( new UpdateShippingServicesBusinessTask(), $schedule_config );
	}

	private function migrateQueuedItems() {
		Logger::logInfo('Migrating queued items.');

		global $wpdb;

		/** @var TaskExecutorInterface $taskExecutor */
		$taskExecutor = ServiceRegister::getService( TaskExecutorInterface::class );

		$table_name = $wpdb->prefix . Database::BASE_TABLE;
		$rows       = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT index_2, data FROM {$table_name} WHERE type = %s AND index_1 IN (%s, %s)",
				'QueueItem',
				'queued',
				'in_progress'
			),
			ARRAY_A
		);

		if ( empty( $rows ) ) {
			return;
		}

		foreach ( $rows as $row ) {
			$task_type = isset( $row['index_2'] ) ? (string) $row['index_2'] : '';
			if ( $task_type === '' ) {
				continue;
			}

			if ( $this->isTaskType( $task_type, 'SendDraftTask' ) ) {
				$order_id = $this->extractOrderIdFromQueueItemData( $row['data'] ?? null );
				if ( $order_id !== null && $order_id !== '' ) {
					$taskExecutor->enqueue( new SendDraftBusinessTask( (string) $order_id ) );
				}

				continue;
			}

			if ( $this->isTaskType( $task_type, 'UpdateShippingServicesTask' ) ) {
				$taskExecutor->enqueue( new UpdateShippingServicesBusinessTask() );
			}
		}
	}

	/**
	 * Migrates draft status to OrderShipmentDetails based on existing queued items.
	 */
	private function migrateDraftStatuses() {
		Logger::logInfo('Migration draft statuses started.');

		global $wpdb;

		$table_name = $wpdb->prefix . Database::BASE_TABLE;

		$order_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, data FROM {$table_name} WHERE type = %s",
				'OrderShipmentDetails'
			),
			ARRAY_A
		);

		foreach ( $order_rows as $row ) {
			if ( empty( $row['data'] ) ) {
				continue;
			}

			$data = json_decode( $row['data'], true );
			if ( ! is_array( $data ) ) {
				continue;
			}

			$has_draft_status = array_key_exists( 'draftStatus', $data ) && $data['draftStatus'] !== null && $data['draftStatus'] !== '';
			if ( $has_draft_status ) {
				continue;
			}

			$order_id  = $data['orderId'] ?? null;
			$reference = $data['reference'] ?? null;
			if ( empty( $order_id ) ) {
				continue;
			}

			$draft_status = null;

			if ( ! empty( $reference ) ) {
				$map_row = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT data FROM {$table_name} WHERE type = %s AND index_1 = %s LIMIT 1",
						'OrderSendDraftTaskMap',
						(string) $order_id
					),
					ARRAY_A
				);

				if ( empty( $map_row ) || empty( $map_row['data'] ) ) {
					$draft_status = DraftStatus::NOT_QUEUED;
				} else {
					$map_data = json_decode( $map_row['data'], true );
					$execution_id = is_array( $map_data ) ? ( $map_data['executionId'] ?? null ) : null;

					if ( empty( $execution_id ) ) {
						$draft_status = DraftStatus::DELAYED;
					} else {
						$queue_row = $wpdb->get_row(
							$wpdb->prepare(
								"SELECT data FROM {$table_name} WHERE type = %s AND id = %d LIMIT 1",
								'QueueItem',
								(int) $execution_id
							),
							ARRAY_A
						);

						if ( ! empty( $queue_row ) && ! empty( $queue_row['data'] ) ) {
							$queue_data = json_decode( $queue_row['data'], true );
							$draft_status = is_array( $queue_data ) && ! empty( $queue_data['status'] )
								? $queue_data['status']
								: DraftStatus::FAILED;
						} else {
							$draft_status = DraftStatus::FAILED;
						}
					}
				}
			} else {
				// No reference and no status assigned; do not enqueue, mark as not queued.
				$draft_status = DraftStatus::NOT_QUEUED;
			}

			if ( $draft_status === null ) {
				continue;
			}

			$data['draftStatus'] = $draft_status;

			$wpdb->update(
				$table_name,
				array( 'data' => wp_json_encode( $data ) ),
				array( 'id' => (int) $row['id'] )
			);
		}
	}

	/**
	 * Cleans up obsolete TaskRunner/Queue-related entities and configuration entries.
	 */
	private function cleanupLegacyQueueData() {
		Logger::logInfo('Cleaning up legacy queue data.');
		global $wpdb;

		$table_name = $wpdb->prefix . Database::BASE_TABLE;

		$types_to_delete = array(
			'OrderSendDraftTaskMap',
			'Process',
			'QueueItem',
			'Schedule'
		);
		$type_placeholders = implode( ',', array_fill( 0, count( $types_to_delete ), '%s' ) );
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table_name} WHERE type IN ({$type_placeholders})",
				$types_to_delete
			)
		);

		$task_runner_config_keys = array(
			'taskRunnerStatus',
			'taskRunnerWakeupDelay',
			'taskRunnerMaxAliveTime',
			'maxStartedTasksLimit',
			'maxTaskExecutionRetries',
			'maxTaskInactivityPeriod',
			'asyncStarterBatchSize',
		);
		$config_placeholders = implode( ',', array_fill( 0, count( $task_runner_config_keys ), '%s' ) );
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table_name} WHERE type = %s AND index_1 IN ({$config_placeholders})",
				array_merge( array( 'Configuration' ), $task_runner_config_keys )
			)
		);
	}

	/**
	 * Check if task type matches by short name or fully-qualified class name.
	 *
	 * @param string $task_type Task type from queue item.
	 * @param string $short_name Expected short class name.
	 *
	 * @return bool
	 */
	private function isTaskType( $task_type, $short_name ) {
		if ( $task_type === $short_name ) {
			return true;
		}

		return substr( $task_type, - strlen( $short_name ) ) === $short_name;
	}

	/**
	 * Extracts order id from serialized task payload.
	 *
	 * @param string|null $data Queue item data column (JSON).
	 *
	 * @return string|null
	 */
	private function extractOrderIdFromQueueItemData( $data ) {
		if ( empty( $data ) || ! is_string( $data ) ) {
			return null;
		}

		$decoded = json_decode( $data, true );
		if ( ! is_array( $decoded ) ) {
			return null;
		}

		$serialized = $decoded['serializedTask'] ?? '';
		if ( empty( $serialized ) || ! is_string( $serialized ) ) {
			return null;
		}

		if ( preg_match( '/"order_id";s:\d+:"([^"]+)"/', $serialized, $matches ) ) {
			return (string) $matches[1];
		}

		if ( preg_match( '/"orderId";s:\d+:"([^"]+)"/', $serialized, $matches ) ) {
			return (string) $matches[1];
		}

		return null;
	}
}
