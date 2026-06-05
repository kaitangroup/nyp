import { createReduxStore, register } from '@wordpress/data'

const STORE_NAME = 'ct/color-palette-store'

const DEFAULT_STATE = {
	isEditingPalettes: false,
	customPalettes: [],
	loading: false,
}

const actions = {
	setCustomPalettes(palettes) {
		return { type: 'SET_CUSTOM_PALETTES', palettes }
	},

	setLoading(loading) {
		return { type: 'SET_LOADING', loading }
	},

	// --- Async actions ---
	*fetchCustomPalettes() {
		yield actions.setLoading(true)

		const response = yield {
			type: 'FETCH_FROM_SERVER',
			url: `${window.ajaxurl}?action=blocksy_get_custom_palettes`,
			method: 'POST',
		}

		if (response?.data?.palettes) {
			yield actions.setCustomPalettes(response.data.palettes)
		}

		yield actions.setLoading(false)
	},

	*syncCustomPalettes(palettes) {
		yield actions.setLoading(true)

		yield {
			type: 'POST_TO_SERVER',
			url: `${window.ajaxurl}?action=blocksy_sync_custom_palettes`,
			body: JSON.stringify({ palettes }),
			method: 'POST',
		}

		yield actions.setCustomPalettes(palettes)
		yield actions.setLoading(false)
	},
}

const storeConfig = {
	reducer(state = DEFAULT_STATE, action) {
		switch (action.type) {
			case 'SET_CUSTOM_PALETTES':
				return { ...state, customPalettes: action.palettes }

			case 'SET_LOADING':
				return { ...state, loading: action.loading }

			default:
				return state
		}
	},

	actions,

	selectors: {
		getCustomPalettes: (state) => state.customPalettes,
		isLoading: (state) => state.loading,
	},

	// --- Controls tell WP data how to handle async effects ---
	controls: {
		FETCH_FROM_SERVER({ url, method }) {
			return fetch(url, {
				method,
				headers: {
					Accept: 'application/json',
					'Content-Type': 'application/json',
				},
				body: JSON.stringify({}),
			}).then((r) => r.json())
		},

		POST_TO_SERVER({ url, body, method }) {
			return fetch(url, {
				method,
				headers: {
					Accept: 'application/json',
					'Content-Type': 'application/json',
				},
				body,
			}).then((r) => r.json())
		},
	},
}

let storeInstance = null
let isRegistered = false

/**
 * Get the color palette store instance with lazy registration.
 * This ensures wp.data is available before attempting to register the store.
 *
 * @returns {Object|null} The store instance or null if wp.data is not available
 */
export const getStore = () => {
	// Check if wp.data is available
	if (typeof window.wp === 'undefined' || !window.wp.data) {
		console.warn(
			'wp.data is not available. Color palette store cannot be initialized.'
		)
		return null
	}

	// Return cached instance if already registered
	if (isRegistered && storeInstance) {
		return storeInstance
	}

	// Create and register the store
	try {
		storeInstance = createReduxStore(STORE_NAME, storeConfig)
		register(storeInstance)
		isRegistered = true
		return storeInstance
	} catch (error) {
		console.error('Failed to register color palette store:', error)
		return null
	}
}

// For backward compatibility, export STORE_NAME
export { STORE_NAME }

// Default export returns the store name for use with useSelect/useDispatch
export default STORE_NAME
