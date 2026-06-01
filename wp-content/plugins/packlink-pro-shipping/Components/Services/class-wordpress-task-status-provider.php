<?php

namespace Packlink\WooCommerce\Components\Services;

use Logeecom\Infrastructure\TaskExecutor\Interfaces\TaskStatusProviderInterface;
use Logeecom\Infrastructure\TaskExecutor\Model\TaskStatus;

class WordPress_Task_Status_Provider implements TaskStatusProviderInterface
{
	/**
	 * Action Scheduler hook name used by Packlink tasks.
	 */
	const HOOK_NAME = 'packlink_execute_task';

	/**
	 * Max number of recent actions to inspect.
	 */
	const MAX_LOOKBACK = 50;
	/**
	 * Max inactivity before marking a running task as expired (seconds).
	 */
	const MAX_INACTIVITY_SECONDS = 60;

	/**
	 * @param string $type
	 * @param        $context
	 *
	 * @return TaskStatus
	 */
	public function getLatestStatus( string $type, $context = '' ) {
		$found  = $this->findLatestAction( $type, $context );
		if ( ! $found  ) {
			return new TaskStatus( TaskStatus::NOT_FOUND );
		}

		return new TaskStatus(
			$this->mapActionStatus( $found['action'], $found['id'], $found['store'] ),
			null
		);
	}

	public function getLatestStatusWithExpiration( string $type, $context = '' ) {
		$found = $this->findLatestAction( $type, $context );
		if ( ! $found ) {
			return new TaskStatus( TaskStatus::NOT_FOUND );
		}

		$status = $this->mapActionStatus( $found['action'], $found['id'], $found['store'] );
		if ( $status === TaskStatus::RUNNING && $this->isExpired( $found['action'] ) ) {
			return new TaskStatus( TaskStatus::EXPIRED, 'Task expired due to inactivity.' );
		}

		return new TaskStatus( $status, null );
	}

	/**
	 * Finds the latest Action Scheduler action for task type/context.
	 *
	 * @param string      $type
	 * @param string|null $context
	 *
	 * @return array|null
	 */
	private function findLatestAction( string $type, $context = '' ) {
		if ( ! function_exists( 'as_get_scheduled_actions' ) || ! class_exists( 'ActionScheduler_Store' ) ) {
			return null;
		}

		$statuses = $this->getActionSchedulerStatuses();
		$ids      = as_get_scheduled_actions(
			array(
				'hook'     => self::HOOK_NAME,
				'status'   => $statuses,
				'orderby'  => 'date',
				'order'    => 'DESC',
				'per_page' => self::MAX_LOOKBACK,
			),
			'ids'
		);

		if ( empty( $ids ) ) {
			return null;
		}

		$store = \ActionScheduler_Store::instance();
		foreach ( $ids as $id ) {
			$action = $store->fetch_action( $id );
			if ( ! $action ) {
				continue;
			}

			$args = $action->get_args();
			$payload = $this->extractPayload( $args );
			if ( ! $payload ) {
				continue;
			}

			if ( ! $this->matchesType($payload, $type) ) {
				continue;
			}

			if ( (string) ( $payload['context'] ?? '' ) !== (string) $context ) {
				continue;
			}

			return array(
				'id'     => $id,
				'action' => $action,
				'store'  => $store,
			);
		}

		return null;
	}

	private function matchesType(array $payload, string $type): bool
	{
		$taskClass = (string)($payload['task_class'] ?? '');
		if ($taskClass === '') {
			return false;
		}

		// 1) Exact match (FQCN)
		if ($taskClass === $type) {
			return true;
		}

		// 2) Short class name match (e.g. AutoTestBusinessTask)
		$short = $taskClass;
		if (strpos($short, '\\') !== false) {
			$parts = explode('\\', $short);
			$short = end($parts);
		}
		if ($short === $type) {
			return true;
		}

		return strpos($taskClass, $type) !== false;
	}

	/**
	 * Extracts task payload from Action Scheduler args.
	 *
	 * @param mixed $args
	 *
	 * @return array|null
	 */
	private function extractPayload( $args ) {
		if ( is_array( $args ) && isset( $args[0] ) && is_array( $args[0] ) ) {
			return $args[0];
		}

		if ( is_array( $args ) ) {
			return $args;
		}

		return null;
	}

	/**
	 * Maps Action Scheduler status to TaskStatus status.
	 *
	 * @param object $action
	 *
	 * @return string
	 */
	private function mapActionStatus( $action, $actionId, $store ) {
		$status = null;

		if ( is_object( $store ) && method_exists( $store, 'get_status' ) ) {
			try {
				$status = $store->get_status( $actionId );
			} catch ( \Exception $e ) {
				$status = null;
			}
		}

		// Fallback: some action objects have get_status()
		if ( ! is_string( $status ) || $status === '' ) {
			if (is_object($action) && method_exists($action, 'get_status')) {
				try {
					$status = $action->get_status();
				} catch (\Exception $e) {
					$status = null;
				}
			}
		}

		if ( $status === \ActionScheduler_Store::STATUS_RUNNING || $status === 'running' ) {
			$status = 'in-progress';
		}

		switch ( $status ) {
			case 'complete':
				return TaskStatus::COMPLETED;
			case 'failed':
				return TaskStatus::FAILED;
			case 'canceled':
				return TaskStatus::CANCELED;
			case 'in-progress':
				return TaskStatus::RUNNING;
			case 'pending':
			default:
				return TaskStatus::PENDING;
		}
	}

	/**
	 * Checks if a running action is expired due to inactivity.
	 *
	 * @param object $action
	 *
	 * @return bool
	 */
	private function isExpired( $action ) {
		$timestamp = $this->getActionTimestamp( $action );
		if ( ! $timestamp ) {
			return false;
		}

		return ( $timestamp + self::MAX_INACTIVITY_SECONDS ) < time();
	}

	/**
	 * Returns best-effort timestamp for action activity.
	 *
	 * @param object $action
	 *
	 * @return int|null
	 */
	private function getActionTimestamp( $action ) {
		if ( method_exists( $action, 'get_last_attempt_date' ) ) {
			$date = $action->get_last_attempt_date();
			$ts   = $this->dateToTimestamp( $date );
			if ( $ts ) {
				return $ts;
			}
		}

		if ( method_exists( $action, 'get_scheduled_date' ) ) {
			$date = $action->get_scheduled_date();
			return $this->dateToTimestamp( $date );
		}

		return null;
	}

	/**
	 * Convert Action Scheduler date object to timestamp.
	 *
	 * @param mixed $date
	 *
	 * @return int|null
	 */
	private function dateToTimestamp( $date ) {
		if ( $date && is_object( $date ) && method_exists( $date, 'getTimestamp' ) ) {
			return (int) $date->getTimestamp();
		}

		if ( is_string( $date ) ) {
			$ts = strtotime( $date );
			return $ts ? (int) $ts : null;
		}

		return null;
	}

	/**
	 * Returns Action Scheduler status list.
	 *
	 * @return array
	 */
	private function getActionSchedulerStatuses() {
		if ( class_exists( 'ActionScheduler_Store' ) ) {
			return array(
				\ActionScheduler_Store::STATUS_PENDING,
				\ActionScheduler_Store::STATUS_RUNNING,
				\ActionScheduler_Store::STATUS_COMPLETE,
				\ActionScheduler_Store::STATUS_FAILED,
				\ActionScheduler_Store::STATUS_CANCELED,
			);
		}

		return array( 'pending', 'in-progress', 'complete', 'failed', 'canceled' );
	}
}