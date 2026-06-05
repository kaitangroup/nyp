import { useState, useEffect } from '@wordpress/element'
import { nanoid } from 'nanoid'

export function createTrigger() {
	var trigger = function () {
		trigger.id = nanoid()
		trigger.subscribers.forEach(function (subscriber) {
			subscriber()
		})
	}

	trigger.id = nanoid()
	trigger.subscribers = []

	trigger.subscribe = function (f) {
		trigger.subscribers.push(f)
	}

	trigger.unsubscribe = function (f) {
		trigger.subscribers.indexOf(f) >= 0 &&
			trigger.subscribers.splice(trigger.subscribers.indexOf(f), 1)
	}

	return trigger
}

export function useTrigger(trigger) {
	const state = useState(trigger.id)

	var update = function () {
		return state[1](trigger.id)
	}

	useEffect(function () {
		trigger.subscribe(update)

		return function () {
			return trigger.unsubscribe(update)
		}
	}, [])

	return state[0]
}
