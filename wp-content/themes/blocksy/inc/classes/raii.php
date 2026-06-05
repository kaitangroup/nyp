<?php

namespace Blocksy;

class RaiiPattern {
	private $callback = null;

	public function __construct($callback) {
		$this->callback = $callback;
	}

	public function __destruct() {
		if ($this->callback) {
			call_user_func($this->callback);
		}
	}

	/**
	 * Prevent PHP Object Injection attacks via unserialize().
	 *
	 * This class uses call_user_func() in __destruct(), making it a
	 * dangerous deserialization gadget. Blocking __unserialize() and
	 * __wakeup() ensures it cannot be instantiated through unserialize().
	 */
	public function __unserialize(array $data): void {
		throw new \LogicException('RaiiPattern cannot be unserialized.');
	}

	public function __wakeup(): void {
		throw new \LogicException('RaiiPattern cannot be unserialized.');
	}
}

