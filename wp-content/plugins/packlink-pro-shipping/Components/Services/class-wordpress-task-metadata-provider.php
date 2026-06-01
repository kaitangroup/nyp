<?php

namespace Packlink\WooCommerce\Components\Services;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecutor\Interfaces\Priority;
use Packlink\BusinessLogic\Tasks\Interfaces\BusinessTask;
use Packlink\BusinessLogic\Tasks\Interfaces\TaskMetadataProviderInterface;
use Packlink\BusinessLogic\Tasks\TaskExecutionConfig;

/**
 * WordPress-specific task metadata provider.
 *
 * Customizes execution configuration for different task types:
 * - SendDraftBusinessTask: HIGH priority, 'shipment-drafts' queue
 * - Other tasks: NORMAL priority, 'default' queue
 *
 * @package Packlink\WooCommerce\Components\Services
 */
class WordPress_Task_Metadata_Provider implements TaskMetadataProviderInterface
{
	const DEFAULT_QUEUE_NAME = 'default';

    /**
     * Get execution configuration for business task.
     *
     * Provides WordPress-specific configuration based on task type.
     *
     * @param BusinessTask $task Business task.
     *
     * @return TaskExecutionConfig Execution configuration.
     */
    public function getExecutionConfig(BusinessTask $task) : TaskExecutionConfig
    {
        /** @var Configuration $config */
        $config = ServiceRegister::getService(Configuration::CLASS_NAME);

        $queueName = $this->getQueueName($task);
        $priority = $this->getPriority($task);
        $context = $config->getContext();

        return new TaskExecutionConfig($queueName, $priority, $context);
    }

    /**
     * Get queue name for task.
     *
     * Different task types can use different queues for better organization.
     *
     * @param BusinessTask $task Business task.
     *
     * @return string Queue name.
     */
    private function getQueueName(BusinessTask $task)
    {
        if($task->getExecutionConfig() && $task->getExecutionConfig()->getQueueName()) {
            return $task->getExecutionConfig()->getQueueName();
        }

        return self::DEFAULT_QUEUE_NAME;
    }

    /**
     * Get priority for task.
     *
     * Different task types can have different priorities.
     *
     * @param BusinessTask $task Business task.
     *
     * @return int Priority (0-100).
     */
    private function getPriority(BusinessTask $task)
    {
        if (method_exists($task, 'getPriority')) {
            return $task->getPriority();
        }

        // Default to normal priority
        return Priority::NORMAL;
    }
}
