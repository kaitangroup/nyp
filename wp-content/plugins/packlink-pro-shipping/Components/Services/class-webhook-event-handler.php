<?php

namespace Packlink\WooCommerce\Components\Services;

use Logeecom\Infrastructure\Logger\Logger;
use Packlink\BusinessLogic\WebHook\WebHookEventHandler;

/**
 * Class WebHook_Event_Handler
 *
 * @package Packlink\WooCommerce\Components\Services
 */
class WebHook_Event_Handler extends WebHookEventHandler
{
    /**
     * List of valid events that are handled by webhook handler.
     *
     * @var array
     */
    protected static $validEvents = array(
        'shipment.carrier.success',
        'shipment.carrier.fail',
        'shipment.label.ready',
        'shipment.label.fail',
        'shipment.tracking.update',
        'shipment.delivered',
        'shipment.carrier.delivered',
    );

    /**
     * Validates input and handles Packlink webhook event.
     *
     * @param string $input Request input.
     *
     * @return bool Result.
     */
    public function handle($input)
    {
        Logger::logDebug(
            'Webhook from Packlink received.',
            'Core',
            array('payload' => $input)
        );

        $payload = json_decode($input, false);

        if (!$this->validatePayload($payload)) {
            return false;
        }

        if ($this->checkAuthToken() && $this->shouldHandleEvent($payload->event)) {
            $this->handleEvent($payload->data);
        }

        return true;
    }

    /**
     * Checks if event should be handled further.
     *
     * @param string $eventName The name of the event.
     *
     * @return bool TRUE if the event handing should be done; otherwise, FALSE.
     */
    private function shouldHandleEvent($eventName)
    {
        return in_array(
            $eventName,
            array(
                'shipment.carrier.success',
                'shipment.delivered',
                'shipment.carrier.delivered',
                'shipment.label.ready',
                'shipment.tracking.update'
            ),
            true
        );
    }
}
