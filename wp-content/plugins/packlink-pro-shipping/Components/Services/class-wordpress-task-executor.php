<?php

namespace Packlink\WooCommerce\Components\Services;

use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecutor\Interfaces\TaskExecutorInterface;
use Packlink\BusinessLogic\Scheduler\DTO\ScheduleConfig;
use Packlink\BusinessLogic\Scheduler\Interfaces\SchedulerInterface;
use Packlink\BusinessLogic\Tasks\Interfaces\BusinessTask;
use Packlink\BusinessLogic\Tasks\Interfaces\TaskMetadataProviderInterface;
use Packlink\BusinessLogic\Tasks\TaskExecutionConfig;

class WordPress_Task_Executor implements TaskExecutorInterface {
	/**
	 * Action Scheduler hook name for Packlink tasks.
	 */
	const HOOK_NAME = 'packlink_execute_task';
	/**
	 * Internal scheduler hook (debug/compat).
	 */
	const SCHEDULER_HOOK_NAME = 'packlink_scheduler';
	/**
	 * Task payload keys.
	 */
	const ARG_TASK_DATA  = 'task_data';
	const ARG_TASK_CLASS = 'task_class';
	const ARG_CONTEXT    = 'context';
	/**
	 * Priority bounds for Action Scheduler (0..10).
	 */
	const PRIORITY_MIN = 0;
	const PRIORITY_MAX = 10;
	/**
	 * Delay bounds (seconds).
	 */
	const MAX_DELAY_SECONDS = 604800; // 7 days.
	/**
	 * Error messages.
	 */
	const ERR_AS_NOT_AVAILABLE         = 'Action Scheduler not available. Please install WooCommerce.';
	const ERR_INVALID_TASK_PAYLOAD     = 'Invalid task payload.';
	const ERR_INVALID_TASK_CLASS       = 'Invalid task class.';
	const ERR_MISSING_EXECUTION_CONFIG = 'Execution configuration not available.';
	const ERR_SCHEDULE_FAILED          = 'Task scheduling failed.';

	private $metadata_provider;
	private $scheduler;

	public function __construct() {

		$this->metadata_provider = ServiceRegister::getService( TaskMetadataProviderInterface::CLASS_NAME );

		$this->scheduler = ServiceRegister::getService( SchedulerInterface::CLASS_NAME );

	}

	/**
	 * Enqueue business task via Action Scheduler.
	 *
	 * Task will be executed asynchronously via wp-cron or real cron.
	 *
	 * Uses TaskMetadataProvider to get execution configuration.
	 *
	 * @param BusinessTask $businessTask Business task (e.g., SendDraftBusinessTask).
	 *
	 * @return void
	 */
	public function enqueue( BusinessTask $businessTask ) {
		// Check if Action Scheduler is available
		if ( ! function_exists( 'as_enqueue_async_action' ) ) {
			$this->logAndThrow( self::ERR_AS_NOT_AVAILABLE );
		}

		// Get execution configuration from metadata provider (BusinessLogic layer)
		$executionConfig = $this->getExecutionConfig( $businessTask );

		$payload = $this->buildPayload( $businessTask, $executionConfig );

		// Enqueue action with configuration
		$action_id = as_enqueue_async_action(
			self::HOOK_NAME,
			[ $payload ],
			(string) $executionConfig->getQueueName(),
			false,
			$this->convertPriority( $executionConfig->getPriority() )
		);

		if ( empty( $action_id ) ) {
			$this->logAndThrow( self::ERR_SCHEDULE_FAILED );
		}
	}

	/**
	 * Schedule delayed business task via Action Scheduler.
	 *
	 * Task will be executed after specified delay.
	 *
	 * Uses TaskMetadataProvider to get execution configuration.
	 *
	 * @param BusinessTask $businessTask Business task.
	 * @param int          $delaySeconds Delay in seconds before execution.
	 *
	 * @return void
	 */
	public function scheduleDelayed( BusinessTask $businessTask, int $delaySeconds ) {
		$delaySeconds = max( 0, (int) $delaySeconds );
		if ( $delaySeconds > self::MAX_DELAY_SECONDS ) {
			$delaySeconds = self::MAX_DELAY_SECONDS;
		}

		// Prefer SchedulerInterface when available so scheduling is centralized.
		if ( $this->scheduler instanceof SchedulerInterface ) {
			$timestamp = time() + $delaySeconds;

			$this->scheduler->scheduleHourly(
				$businessTask,
				new ScheduleConfig(
					(int) date( 'N', $timestamp ),
					(int) date( 'H', $timestamp ),
					(int) date( 'i', $timestamp ),
					false
				)
			);

			return;
		}

		// Check if Action Scheduler is available
		if ( ! function_exists( 'as_schedule_single_action' ) ) {
			$this->logAndThrow( self::ERR_AS_NOT_AVAILABLE );
		}

		// Get execution configuration from metadata provider (BusinessLogic layer)
		$executionConfig = $this->getExecutionConfig( $businessTask );
		if ( ! $executionConfig instanceof TaskExecutionConfig ) {
			$this->logAndThrow( self::ERR_MISSING_EXECUTION_CONFIG );
		}

		$payload = $this->buildPayload( $businessTask, $executionConfig );

		// Schedule action with configuration
		$action_id = as_schedule_single_action(
			time() + $delaySeconds,
			self::HOOK_NAME,
			[ $payload ],
			(string) $executionConfig->getQueueName(),
			false,
			$this->convertPriority( $executionConfig->getPriority() )
		);

		if ( empty( $action_id ) ) {
			$this->logAndThrow( self::ERR_SCHEDULE_FAILED );
		}
	}

	/**
	 * Get execution configuration for business task.
	 *
	 * Uses TaskMetadataProvider service (BusinessLogic layer) to get configuration.
	 *
	 * @param BusinessTask $businessTask Business task.
	 *
	 * @return TaskExecutionConfig Execution configuration.
	 */
	private function getExecutionConfig( BusinessTask $businessTask ) {
		if ( ! $this->metadata_provider instanceof TaskMetadataProviderInterface ) {
			$this->logAndThrow( self::ERR_MISSING_EXECUTION_CONFIG );
		}

		return $this->metadata_provider->getExecutionConfig( $businessTask );
	}

	/**
	 * Convert priority from infrastructure scale (0-100) to Action Scheduler scale (0-10).
	 *
	 * Infrastructure priorities:
	 * - Priority::LOW = 0-33
	 * - Priority::NORMAL = 34-66
	 * - Priority::HIGH = 67-100
	 *
	 * Action Scheduler priorities:
	 * - 0 = lowest
	 * - 10 = highest
	 *
	 * @param int $priority Priority from TaskExecutionConfig (0-100).
	 *
	 * @return int Priority for Action Scheduler (0-10).
	 */
	private function convertPriority( $priority ) {
		$priority = (int) $priority;
		$p        = (int) round( $priority / 10 );

		// Clamp to Action Scheduler allowed range
		return max( self::PRIORITY_MIN, min( self::PRIORITY_MAX, $p ) );
	}

	/**
	 * @return SchedulerInterface|null
	 */
	/**
	 * Register Action Scheduler callback for task execution.
	 *
	 * Call this method during plugin initialization to register the callback.
	 *
	 * @return void
	 */
	public function registerExecutionCallback() {
		add_action( 'init', function () {
			$executor = ServiceRegister::getService( TaskExecutorInterface::CLASS_NAME );
			$executor->registerExecutionCallback();
		} );
		add_action( self::HOOK_NAME, [ $this, 'executeTaskCallback' ], 10, 1 );
		add_action( self::SCHEDULER_HOOK_NAME, [ $this, 'runSc' ], 10, 1 );

	}

	public function runSc( $args ) {
		return $args['task_class'];
	}

	/**
	 * Action Scheduler callback for task execution.
	 *
	 * Deserializes business task and executes it.
	 * Handles yield-based progress tracking.
	 *
	 * @param array $args Task arguments from Action Scheduler.
	 *
	 * @return void
	 *
	 * @throws \Exception If task execution fails.
	 */
	public function executeTaskCallback( $args ) {
		if ( isset( $args[0] ) && is_array( $args[0] ) ) {
			$args = $args[0];
		}

		if ( ! is_array( $args ) || ! isset( $args[ self::ARG_TASK_CLASS ], $args[ self::ARG_TASK_DATA ] ) ) {
			$this->logAndThrow( self::ERR_INVALID_TASK_PAYLOAD );
		}

		$taskClass = $args[ self::ARG_TASK_CLASS ];
		$taskData  = $args[ self::ARG_TASK_DATA ];
		$context   = $args[ self::ARG_CONTEXT ] ?? '';

		if ( ! is_string( $taskClass ) || ! class_exists( $taskClass ) || ! is_callable( [
				$taskClass,
				'fromArray'
			] ) ) {
			$this->logAndThrow( self::ERR_INVALID_TASK_CLASS );
		}

		// Reconstruct business task
		/** @var BusinessTask $businessTask */
		$businessTask = call_user_func( [ $taskClass, 'fromArray' ], $taskData );

		// Execute task
		try {
			$result = $businessTask->execute();
		} catch ( \Throwable $e ) {
			Logger::logError( $e->getMessage(), 'Integration', array(
				'task_class' => $taskClass,
				'context'    => $context,
			) );
			throw $e;
		}

		if ( $result instanceof \Generator ) {
			$this->executeWithProgressTracking( $result );
		}

		// If not generator, task executed normally
	}

    /**
     * @param BusinessTask $businessTask
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function executeNow( BusinessTask $businessTask )
    {
        try {
            $result = $businessTask->execute();

            if ( $result instanceof \Generator ) {
                $this->executeWithProgressTracking( $result );
            }
        } catch ( \Throwable $e ) {
            Logger::logError( $e->getMessage(), 'Integration', [
                'task_class' => get_class($businessTask),
            ] );
            throw $e;
        }
    }

	/**
	 * Execute generator with progress tracking.
	 *
	 * Iterates over generator and handles yielded values:
	 * - null (yield;) → log keep-alive
	 * - int/float (yield 50;) → log progress
	 * - string (yield 'message';) → log message
	 *
	 * Note: Action Scheduler doesn't have reportProgress/reportAlive like QueueItem,
	 * so we just log the progress for debugging purposes.
	 *
	 * @param \Generator $generator Generator returned by business task.
	 *
	 * @return void
	 */
	private function executeWithProgressTracking( \Generator $generator ) {
		foreach ( $generator as $value ) {
			if ( $value === null ) {
				// yield; (no value) → keep-alive signal
				Logger::logDebug( 'Task keep-alive signal', 'Integration' );
			} elseif ( is_int( $value ) || is_float( $value ) ) {
				// yield 50; → report progress 50%
				Logger::logDebug( sprintf( 'Task progress: %s%%', $value ), 'Integration' );
			} elseif ( is_string( $value ) ) {
				// yield 'Processing...'; → log message
				Logger::logDebug( sprintf( 'Task message: %s', $value ), 'Integration' );
			}
		}
	}

	/**
	 * Build Action Scheduler payload with constraints.
	 *
	 * @param BusinessTask        $businessTask
	 * @param TaskExecutionConfig $executionConfig
	 *
	 * @return array
	 */
	private function buildPayload( BusinessTask $businessTask, TaskExecutionConfig $executionConfig ) {
		return array(
			self::ARG_TASK_DATA  => $businessTask->toArray(),
			self::ARG_TASK_CLASS => get_class( $businessTask ),
			self::ARG_CONTEXT    => (string) $executionConfig->getContext(),
		);
	}

	/**
	 * Log error and throw exception.
	 *
	 * @param string $message
	 *
	 * @return void
	 */
	private function logAndThrow( $message ) {
		Logger::logError( $message, 'Integration' );
		throw new \RuntimeException( $message );
	}
}
