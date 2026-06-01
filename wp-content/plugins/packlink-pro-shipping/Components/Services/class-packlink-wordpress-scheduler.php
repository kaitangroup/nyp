<?php

namespace Packlink\WooCommerce\Components\Services;

use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Scheduler\DTO\ScheduleConfig;
use Packlink\BusinessLogic\Scheduler\Interfaces\SchedulerInterface;
use Packlink\BusinessLogic\Tasks\Interfaces\BusinessTask;
use Packlink\BusinessLogic\Tasks\Interfaces\TaskMetadataProviderInterface;
use Packlink\BusinessLogic\Tasks\TaskExecutionConfig;

class Packlink_WordPress_Scheduler implements SchedulerInterface
{

	const HOOK_NAME = 'packlink_execute_task';

	/**
	 * @inheritDoc
	 */
	public function scheduleWeekly(BusinessTask $businessTask, ScheduleConfig $config)
	{
		$this->assertSchedulerAvailable();

		$executionConfig = $this->getExecutionConfig($businessTask);
		$timestamp = $this->getNextWeeklyTimestamp($config);

		$this->scheduleAction(
			$businessTask,
			$executionConfig,
			$timestamp,
			$config->isRecurring(),
			WEEK_IN_SECONDS
		);
	}

	/**
	 * @inheritDoc
	 */
	public function scheduleDaily(BusinessTask $businessTask, ScheduleConfig $config)
	{
		$this->assertSchedulerAvailable();

		$executionConfig = $this->getExecutionConfig($businessTask);
		$daysOfWeek = $this->getDaysOfWeek($config);

		if (!empty($daysOfWeek)) {
			if ($config->isRecurring()) {
				foreach ($daysOfWeek as $dayOfWeek) {
					$timestamp = $this->getNextWeeklyTimestamp($config, (int)$dayOfWeek);
					$this->scheduleAction($businessTask, $executionConfig, $timestamp, true, WEEK_IN_SECONDS);
				}

				return;
			}

			$timestamp = $this->getNextDailyTimestamp($config, $daysOfWeek);
			$this->scheduleAction($businessTask, $executionConfig, $timestamp, false);

			return;
		}

		$timestamp = $this->getNextDailyTimestamp($config);
		$this->scheduleAction(
			$businessTask,
			$executionConfig,
			$timestamp,
			$config->isRecurring(),
			DAY_IN_SECONDS
		);
	}

	/**
	 * @inheritDoc
	 */
	public function scheduleHourly(BusinessTask $businessTask, ScheduleConfig $config)
	{
		$this->assertSchedulerAvailable();

		$executionConfig = $this->getExecutionConfig($businessTask);

		if (!$config->isRecurring()) {
			$timestamp = $this->getNextNonRecurringHourlyTimestamp($config);
			$this->scheduleAction($businessTask, $executionConfig, $timestamp, false);

			return;
		}

		$timestamp = $this->getNextHourlyTimestamp($config);
		$intervalSeconds = $this->getHourlyIntervalSeconds($config);

		$this->scheduleAction($businessTask, $executionConfig, $timestamp, true, $intervalSeconds);
	}

	/**
	 * Schedules Action Scheduler job for Packlink business task.
	 *
	 * @param BusinessTask         $businessTask
	 * @param TaskExecutionConfig  $executionConfig
	 * @param int                  $timestamp
	 * @param bool                 $recurring
	 * @param int|null             $intervalSeconds
	 *
	 * @return void
	 */
	private function scheduleAction(
		BusinessTask $businessTask,
		TaskExecutionConfig $executionConfig,
		int $timestamp,
		bool $recurring,
		int $intervalSeconds = null
	) {
		$payload = [
			[
			'task_data'  => $businessTask->toArray(),
			'task_class' => get_class($businessTask),
			'context'    => $executionConfig->getContext(),
			]
		];
		$args = array($payload);

		$group = (string)$executionConfig->getQueueName();
		$priority = $this->convertPriority($executionConfig->getPriority());

		// Avoid scheduling in the past (Action Scheduler may run immediately or behave unexpectedly)
		$timestamp = max(time() + 30, $timestamp);

		// De-dup: do not schedule the same hook+args+group more than once.
		if (function_exists('as_next_scheduled_action')) {
			$next = as_next_scheduled_action(self::HOOK_NAME, $args, $group);
			if (!empty($next)) {
				return;
			}
		}

		if ($recurring && $intervalSeconds !== null) {
			as_schedule_recurring_action(
				$timestamp,
				$intervalSeconds,
				self::HOOK_NAME,
				$args,
				$group,
				false,
				$priority
			);

			return;
		}

		as_schedule_single_action(
			$timestamp,
			self::HOOK_NAME,
			$args,
			$group,
			false,
			$priority
		);
	}

	/**
	 * Get execution configuration for business task.
	 *
	 * @param BusinessTask $businessTask
	 *
	 * @return TaskExecutionConfig
	 */
	private function getExecutionConfig(BusinessTask $businessTask): TaskExecutionConfig
	{
		$metadataProvider = ServiceRegister::getService(TaskMetadataProviderInterface::CLASS_NAME);


		return $metadataProvider->getExecutionConfig($businessTask);
	}

	/**
	 * @param ScheduleConfig $config
	 * @param int|null       $dayOfWeekOverride
	 *
	 * @return int
	 */
	private function getNextWeeklyTimestamp(ScheduleConfig $config, int $dayOfWeekOverride = null)
	{
		$now = $this->now();

		$dayOfWeek = $dayOfWeekOverride !== null ? $dayOfWeekOverride : (int)$config->getDayOfWeek();
		if ($dayOfWeek < 1 || $dayOfWeek > 7) {
			$dayOfWeek = (int)$now->format('N');
		}

		$hour = $config->getHour() !== null ? (int)$config->getHour() : (int)$now->format('H');
		$minute = $config->getMinute() !== null ? (int)$config->getMinute() : (int)$now->format('i');

		$candidate = clone $now;
		$candidate->setTime($hour, $minute, 0);

		$currentDay = (int)$candidate->format('N');
		$delta = ($dayOfWeek - $currentDay + 7) % 7;

		if ($delta === 0 && $candidate <= $now) {
			$delta = 7;
		}

		if ($delta > 0) {
			$candidate->modify('+' . $delta . ' days');
		}

		return $candidate->getTimestamp();
	}

	/**
	 * @param ScheduleConfig $config
	 * @param int[]|null     $daysOfWeek
	 *
	 * @return int
	 */
	private function getNextDailyTimestamp(ScheduleConfig $config, array $daysOfWeek = null)
	{
		$now = $this->now();

		$hour = $config->getHour() !== null ? (int)$config->getHour() : (int)$now->format('H');
		$minute = $config->getMinute() !== null ? (int)$config->getMinute() : (int)$now->format('i');

		if (!empty($daysOfWeek)) {
			$closest = null;

			foreach ($daysOfWeek as $dayOfWeek) {
				$timestamp = $this->getNextWeeklyTimestamp($config, (int)$dayOfWeek);
				$closest = $closest === null ? $timestamp : min($closest, $timestamp);
			}

			return $closest ?? $now->getTimestamp();
		}

		$candidate = clone $now;
		$candidate->setTime($hour, $minute, 0);

		if ($candidate <= $now) {
			$candidate->modify('+1 day');
		}

		return $candidate->getTimestamp();
	}

	/**
	 * @param ScheduleConfig $config
	 *
	 * @return int
	 */
	private function getNextNonRecurringHourlyTimestamp(ScheduleConfig $config)
	{
		if ($config->getDayOfWeek() !== null) {
			$timestamp = $this->getNextWeeklyTimestamp($config, (int)$config->getDayOfWeek());
			return $timestamp <= time() ? time() + 60 : $timestamp;
		}

		return $this->getNextDailyTimestamp($config);
	}

	/**
	 * @param ScheduleConfig $config
	 *
	 * @return int
	 */
	private function getNextHourlyTimestamp(ScheduleConfig $config)
	{
		$now = $this->now();
		$nowTs = $now->getTimestamp();

		$interval = new \DateInterval('PT' . $this->getHourlyIntervalHours($config) . 'H');

		$startTime = $this->getStartTime($nowTs, $config);
		$startTs = $startTime->getTimestamp();

		$endTime = clone $now;
		$endTime->setTimestamp($nowTs);
		$endTime->setTime($this->getEndHour($config), $this->getEndMinute($config), 0);
		$endTs = $endTime->getTimestamp();

		if ($nowTs <= $startTs) {
			return $startTs;
		}

		if ($nowTs === $endTs) {
			return $endTs;
		}

		while ($nowTs > $startTs) {
			if ($nowTs > $endTs || $startTs > $endTs) {
				$startTime = $this->getStartTime($nowTs, $config);
				$startTime->add(new \DateInterval('P1D'));

				return $startTime->getTimestamp();
			}

			$startTime->add($interval);
			$startTs = $startTime->getTimestamp();
		}

		if ($startTs > $endTs) {
			$startTime = $this->getStartTime($nowTs, $config);
			$startTime->add(new \DateInterval('P1D'));

			return $startTime->getTimestamp();
		}

		return $startTs;
	}

	/**
	 * @param int           $timestamp
	 * @param ScheduleConfig $config
	 *
	 * @return \DateTime
	 */
	private function getStartTime(int $timestamp, ScheduleConfig $config): \DateTime
	{
		$startTime = $this->now();
		$startTime->setTimestamp($timestamp);
		$startTime->setTime($this->getStartHour($config), $this->getStartMinute($config), 0);

		return $startTime;
	}

	/**
	 * @param ScheduleConfig $config
	 *
	 * @return int
	 */
	private function getStartHour(ScheduleConfig $config)
	{
		if ($config->getStartHour() !== null) {
			return (int)$config->getStartHour();
		}

		return $config->getHour() !== null ? (int)$config->getHour() : 0;
	}

	/**
	 * @param ScheduleConfig $config
	 *
	 * @return int
	 */
	private function getStartMinute(ScheduleConfig $config)
	{
		if ($config->getStartMinute() !== null) {
			return (int)$config->getStartMinute();
		}

		return $config->getMinute() !== null ? (int)$config->getMinute() : 0;
	}

	/**
	 * @param ScheduleConfig $config
	 *
	 * @return int
	 */
	private function getEndHour(ScheduleConfig $config)
	{
		return $config->getEndHour() !== null ? (int)$config->getEndHour() : 23;
	}

	/**
	 * @param ScheduleConfig $config
	 *
	 * @return int
	 */
	private function getEndMinute(ScheduleConfig $config)
	{
		return $config->getEndMinute() !== null ? (int)$config->getEndMinute() : 59;
	}

	/**
	 * @param ScheduleConfig $config
	 *
	 * @return mixed
	 */
	private function getHourlyIntervalHours(ScheduleConfig $config)
	{
		$interval = $config->getInterval() !== null ? (int)$config->getInterval() : 1;

		return max(1, $interval);
	}

	/**
	 * @param ScheduleConfig $config
	 *
	 * @return float|int
	 */
	private function getHourlyIntervalSeconds(ScheduleConfig $config)
	{
		return $this->getHourlyIntervalHours($config) * HOUR_IN_SECONDS;
	}

	/**
	 * @param ScheduleConfig $config
	 *
	 * @return int[]
	 */
	private function getDaysOfWeek(ScheduleConfig $config)
	{
		$daysOfWeek = $config->getDaysOfWeek();

		if (empty($daysOfWeek) && $config->getDayOfWeek() !== null) {
			$daysOfWeek = array($config->getDayOfWeek());
		}

		return array_values(array_filter($daysOfWeek, static function ($day) {
			$day = (int)$day;

			return $day >= 1 && $day <= 7;
		}));
	}

	/**
	 * Convert priority from infrastructure scale (0-100) to Action Scheduler scale (0-10).
	 *
	 * @param int $priority
	 *
	 * @return int
	 */
	private function convertPriority($priority)
	{
		$priority = (int)$priority;
		$p = (int)round($priority / 10);

		return max(0, min(10, $p));
	}

	/**
	 * @return \DateTime
	 *
	 * @throws \Exception
	 */
	private function now(): \DateTime
	{
		if (function_exists('wp_timezone')) {
			return new \DateTime('now', wp_timezone());
		}

		return new \DateTime('now', new \DateTimeZone('UTC'));
	}

	/**
	 * @return void
	 */
	private function assertSchedulerAvailable()
	{
		if (
			!function_exists('as_schedule_single_action') ||
			!function_exists('as_schedule_recurring_action') ||
			!function_exists('as_next_scheduled_action')
		) {
			throw new \RuntimeException('Action Scheduler not available. Please install WooCommerce.');
		}
	}
}
