<?php

namespace NYP\Services;

defined('ABSPATH') || exit;

/*
|--------------------------------------------------------------------------
| Planning Session
|--------------------------------------------------------------------------
|
| Handles temporary planning data before checkout.
| Data is stored in the WooCommerce session and later copied
| to the WooCommerce order during checkout.
|
*/

class PlanningSessionStorage
{
    /**
     * WooCommerce session key.
     */
    private const SESSION_KEY = 'nyp_planning_brief';

    /**
     * Get all planning data.
     */
    public function all(): array
    {
        if (!$this->hasSession()) {
            return [];
        }
        
        
        if (!WC()->session) {
            return [];
        }

        return (array) WC()->session->get(
            self::SESSION_KEY,
            []
        );
    }

    /**
     * Replace all planning data.
     */
    public function put(array $data): void
    {
        
        if (!WC()->session) {
            return;
        }

        WC()->session->set(
            self::SESSION_KEY,
            $data
        );
    }

    /**
     * Store a single field.
     */
    public function set(string $key, $value): void
    {
        $data = $this->all();

        $data[$key] = $value;

        $this->put($data);
    }

    /**
     * Get a single field.
     */
    public function get(string $key, $default = null)
    {
        $data = $this->all();

        return $data[$key] ?? $default;
    }

    /**
     * Determine whether planning data exists.
     */
    public function has(): bool
    {
        return !empty($this->all());
    }

    /**
     * Remove planning data.
     */
    public function clear(): void
    {
        if (!WC()->session) {
            return;
        }

        WC()->session->__unset(
            self::SESSION_KEY
        );
    }

    /**
 * Get the Planning Session ID.
 *
 * Generates a unique session ID on first use and
 * reuses it throughout the planning workflow.
 *
 * @return string
 */
public function getSessionId(): string
{
    
    $sessionId = $this->get(
        '_nyp_session_id'
    );

    if (!empty($sessionId)) {
        return $sessionId;
    }

    $sessionId = wp_generate_uuid4();

    $this->set(
        '_nyp_session_id',
        $sessionId
    );

    return $sessionId;
}
protected function hasSession(): bool
{
    return function_exists('WC')
        && WC()->session;
}

/**
 * Remove a single field.
 */
public function remove(string $key): void
{
    $data = $this->all();

    unset($data[$key]);

    $this->put($data);
}
}