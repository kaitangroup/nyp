import { scrollLockManager } from './overlay/no-bounce'
import ctEvents from 'ct-events'
import { mount as mountMobileMenu } from './overlay/mobile-menu'

import { focusLockManager } from '../helpers/focus-lock'
import { whenTransitionEnds } from '../helpers/when-transition-ends'
import { isTouchDevice } from '../helpers/is-touch-device'
import { isIosDevice } from '../helpers/is-ios-device'
import { isModalTrigger } from '../helpers/is-modal-trigger'

const persistSettings = (settings) => {
	settings.container.__overlay_settings__ = settings
}

const getSettings = (settings) => {
	if (!settings.container) {
		throw new Error('No container provided')
	}

	return settings.container.__overlay_settings__ || {}
}

const clearSettings = (settings) => {
	settings.container.__overlay_settings__ = null
}

let windowClickListenerController = null

const showOffcanvas = (initialSettings) => {
	const settings = {
		onClose: () => {},
		container: null,
		focus: true,
		...getSettings(initialSettings)
	}
	;[
		...document.querySelectorAll(
			`[data-toggle-panel*="${settings.container.id}"]`
		),

		...document.querySelectorAll(`[href*="${settings.container.id}"]`)
	].map((trigger) => {
		trigger.setAttribute('aria-expanded', 'true')
	})

	if (settings.shouldBeInert) {
		settings.container.inert = false

		// aria-modal should be added only when modal is opened
		// https://developer.mozilla.org/en-US/docs/Web/Accessibility/ARIA/Reference/Attributes/aria-modal
		settings.container.setAttribute('aria-modal', 'true')
	}

	if (settings.focus) {
		setTimeout(() => {
			const maybeInput = settings.container.querySelector('input')

			if (maybeInput) {
				const end = maybeInput.value.length

				maybeInput.setSelectionRange(end, end)
				maybeInput.focus()
			}
		}, 200)
	}

	if (settings.container.querySelector('.ct-panel-content')) {
		settings.container
			.querySelector('.ct-panel-content')
			.addEventListener('click', (event) => {
				Array.from(settings.container.querySelectorAll('select')).map(
					(select) =>
						select.selectr && select.selectr.events.dismiss(event)
				)
			})
	}

	if (
		settings.clickOutside &&
		settings.container.querySelector('.ct-panel-content')
	) {
		settings.container.addEventListener(
			'click',
			settings.handleContainerClick
		)
	}

	const onKeyUp = (event) => {
		const { keyCode, target } = event

		if (keyCode !== 27) return
		event.preventDefault()

		document.body.hasAttribute('data-panel') && hideOffcanvas(settings)

		document.removeEventListener('keyup', onKeyUp)
	}

	document.addEventListener('keyup', onKeyUp)

	let maybeCloseButton =
		settings.container &&
		settings.container.querySelector('.ct-toggle-close')

	if (maybeCloseButton) {
		maybeCloseButton.addEventListener(
			'click',
			(event) => {
				event.preventDefault()
				hideOffcanvas(settings)
			},
			{ once: true }
		)

		if (!maybeCloseButton.hasEnterListener) {
			maybeCloseButton.hasEnterListener = true

			maybeCloseButton.addEventListener('keyup', (e) => {
				if (13 == e.keyCode) {
					e.preventDefault()
					hideOffcanvas(settings)
				}
			})
		}
	}

	if (
		settings.computeScrollContainer ||
		settings.container.querySelector('.ct-panel-content')
	) {
		const scrollContainer = settings.computeScrollContainer
			? settings.computeScrollContainer()
			: settings.container.querySelector('.ct-panel-content')

		scrollLockManager().disable(scrollContainer)

		if (isIosDevice()) {
			const observer = new MutationObserver((mutations) => {
				if (scrollContainer.isConnected) {
					return
				}

				scrollLockManager().enable()

				setTimeout(() => {
					// If panel is closed, we should not block the scroll
					if (!document.body.hasAttribute('data-panel')) {
						return
					}

					scrollLockManager().disable(
						settings.computeScrollContainer
							? settings.computeScrollContainer()
							: settings.container.querySelector(
									'.ct-panel-content'
								)
					)
				}, 1000)
			})

			observer.observe(settings.container, {
				childList: true,
				subtree: true
			})

			settings.container.__overlay_observer__ = observer
		}

		setTimeout(() => {
			focusLockManager().focusLockOn(
				settings.container.querySelector('.ct-panel-content')
					.parentNode,
				{
					focusOnMount: !settings.focus
				}
			)
		})
	}

	if ('AbortController' in window) {
		windowClickListenerController = new AbortController()
	}

	/**
	 * Add window event listener in the next frame. This allows us to freely
	 * propagate the current clck event up the chain -- without the modal
	 * getting closed.
	 */
	window.addEventListener('click', settings.handleWindowClick, {
		capture: true,
		signal: windowClickListenerController
			? windowClickListenerController.signal
			: undefined
	})

	ctEvents.trigger('ct:modal:opened', settings.container)
	ctEvents.trigger('blocksy:frontend:init')
	;[...settings.container.querySelectorAll('.ct-toggle-dropdown-mobile')].map(
		(arrow) => {
			mountMobileMenu(arrow)
		}
	)
}

const hideOffcanvas = (initialSettings, args = {}) => {
	const settings = {
		onClose: () => {},
		container: null,
		...getSettings(initialSettings)
	}

	args = {
		onlyUnmountEvents: false,
		shouldFocusOriginalTrigger: true,
		...args
	}

	if (settings.shouldBeInert) {
		settings.container.inert = true

		// aria-modal should be removed when modal is closed
		// https://developer.mozilla.org/en-US/docs/Web/Accessibility/ARIA/Reference/Attributes/aria-modal
		settings.container.removeAttribute('aria-modal')
	}

	if (!document.body.hasAttribute('data-panel')) {
		settings.container.classList.remove('active')
		settings.onClose()
		return
	}

	;[
		...document.querySelectorAll(
			`[data-toggle-panel*="${settings.container.id}"]`
		),

		...document.querySelectorAll(`[href*="${settings.container.id}"]`)
	].map((trigger, index) => {
		trigger.setAttribute('aria-expanded', 'false')

		if (args.shouldFocusOriginalTrigger && !isTouchDevice()) {
			if (!trigger.focusDisabled) {
				setTimeout(() => {
					if (index === 0) {
						trigger.focus()
					}
				}, 50)
			}

			trigger.focusDisabled = false
		}
	})

	if (args.onlyUnmountEvents) {
		scrollLockManager().enable()

		focusLockManager().focusLockOff(
			settings.container.querySelector('.ct-panel-content').parentNode
		)

		clearSettings(settings)
	} else {
		document.body.dataset.panel = `out`

		whenTransitionEnds(settings.container, () => {
			document.body.removeAttribute('data-panel')
			settings.container.classList.remove('active')

			scrollLockManager().enable()

			focusLockManager().focusLockOff(
				settings.container.querySelector('.ct-panel-content').parentNode
			)

			clearSettings(settings)

			ctEvents.trigger('ct:modal:closed', settings.container)
		})
	}

	if (settings.container.__overlay_observer__) {
		settings.container.__overlay_observer__.disconnect()
		settings.container.__overlay_observer__ = null
	}

	if (windowClickListenerController) {
		windowClickListenerController.abort()
	}

	settings.container.removeEventListener(
		'click',
		settings.handleContainerClick
	)

	settings.onClose()
}

export const handleClick = (e, settings) => {
	if (e && e.preventDefault) {
		e.preventDefault()
	}

	settings = {
		onClose: () => {},
		container: null,
		focus: false,
		clickOutside: true,
		isModal: false,
		computeScrollContainer: null,
		closeWhenLinkInside: false,

		shouldBeInert: !!settings.container.inert,

		handleContainerClick: (event) => {
			const isPanelHeadContent = event.target.closest('.ct-panel-actions')
			let isInsidePanelContent = event.target.closest('.ct-panel-content')
			let isPanelContentItself =
				[
					...settings.container.querySelectorAll('.ct-panel-content')
				].indexOf(event.target) > -1

			let maybeTarget = null

			if (event.target.matches('[data-toggle-panel],[href*="modal"]')) {
				maybeTarget = event.target
			}

			if (
				!maybeTarget &&
				event.target.closest('[data-toggle-panel],[href*="modal"]')
			) {
				maybeTarget = event.target.closest(
					'[data-toggle-panel],[href*="modal"]'
				)
			}

			// If target has the click listener, its likely that it will
			// trigger an overlay. We should close the panel in this case.
			if (
				maybeTarget &&
				maybeTarget.hasLazyLoadClickListener &&
				// This flow is not compatible with action buttons.
				!maybeTarget.matches('[data-button-state]')
			) {
				hideOffcanvas(settings)

				setTimeout(() => {
					maybeTarget.click()
				}, 650)
				return
			}

			if (
				(settings.isModal &&
					!isPanelContentItself &&
					isInsidePanelContent) ||
				(!settings.isModal &&
					(isPanelContentItself ||
						isInsidePanelContent ||
						isPanelHeadContent)) ||
				event.target.closest('[class*="select2-container"]') ||
				// Element was clicked upon but suddenly got removed from the DOM
				!event.target.closest('body') ||
				!event.target.closest('.ct-panel')
			) {
				return
			}

			if (window.getSelection().toString().length > 0) {
				return
			}

			document.body.hasAttribute('data-panel') && hideOffcanvas(settings)
		},
		handleWindowClick: (e) => {
			setTimeout(() => {
				if (
					settings.container.contains(e.target) ||
					e.target === document.body ||
					e.target.closest('[class*="select2-container"]') ||
					!e.target.closest('body') ||
					// If the click is inside the micro popup, we should not close the panel.
					e.target.closest('.ct-popup') ||
					// WooCommerce checkout triggers programmatic .click() on
					// payment method radio buttons after update_checkout AJAX
					// refreshes the order review. These synthetic clicks bubble
					// to window and would incorrectly close the offcanvas.
					(!e.isTrusted &&
						e.target.closest('form.woocommerce-checkout'))
				) {
					return
				}

				if (!document.body.hasAttribute('data-panel')) {
					return
				}

				hideOffcanvas(settings)
			})
		},
		...settings
	}

	persistSettings(settings)

	showOffcanvas(settings)

	if (settings.closeWhenLinkInside) {
		if (!settings.container.hasListener) {
			settings.container.hasListener = true

			settings.container.addEventListener('click', (event) => {
				if (!event.target) {
					return
				}

				let maybeA = event.target

				if (event.target.closest('a')) {
					maybeA = event.target.closest('a')
				}

				if (!maybeA.closest('.ct-panel')) {
					return
				}

				if (!maybeA.closest('.ct-panel').classList.contains('active')) {
					return
				}

				if (!maybeA.matches('a')) {
					return
				}

				if (maybeA.classList.contains('ct-overlay-skip')) {
					return
				}

				const modalsTriggers = [
					'.ct-offcanvas-trigger',
					'.ct-header-account',
					'[href="#ct-compare-modal"][data-behaviour="modal"]',
					'[data-shortcut="compare"][data-behaviour="modal"]'
				]

				const linkIsModalTrigger = isModalTrigger(maybeA)

				if (
					!maybeA.closest('nav[data-id*="menu"]') &&
					!maybeA.closest('[data-id*="text"]') &&
					!maybeA.closest('[data-id*="button"]') &&
					// If it will open a new overlay, we should not close the current one.
					!linkIsModalTrigger &&
					!maybeA.closest('.widget_nav_menu')
				) {
					return
				}

				const isLeftClick = event.button === 0

				// Links with target attribute (except _self) open in new tab/window
				const opensInNewContext =
					maybeA.target && maybeA.target !== '_self'

				// Ctrl+click (Windows/Linux) or Cmd+click (Mac) opens in new tab
				const newTabIntent =
					isLeftClick &&
					(event.ctrlKey || event.metaKey || opensInNewContext)

				// Do not close the offcanvas if the link is intended to open in a new tab.
				if (newTabIntent) {
					return
				}

				// regular | hash-link | modal
				let linkType = 'regular'

				let currentUrl = new URL(location.href)
				let nextUrl = null

				try {
					nextUrl = new URL(
						maybeA.getAttribute('href'),
						location.href
					)
				} catch (e) {
					console.error('Error parsing URLs', e, maybeA)
				}

				// Need to match even full URLs inside links: http://example.com/page#some-section
				// https://github.com/Creative-Themes/blocksy/issues/4694
				if (currentUrl && nextUrl) {
					currentUrl.hash = ''
					nextUrl.hash = ''

					if (currentUrl.toString() === nextUrl.toString()) {
						linkType = 'hash-link'
					}
				}

				if (linkIsModalTrigger) {
					linkType = 'modal'
				}

				// When a regular link is clicked, we should not hide the
				// offcanvas visually and should instead only clear out the
				// event listeners.
				//
				// The remainings of the offcanvas will be dropped visually
				// in the pageshow event when the back forward cache is detected.
				if (linkType === 'regular') {
					hideOffcanvas(settings, {
						onlyUnmountEvents: true,
						shouldFocusOriginalTrigger: false
					})
				}

				if (linkType === 'modal') {
					hideOffcanvas(settings, {
						shouldFocusOriginalTrigger: false
					})

					setTimeout(() => {
						maybeA.click()
					}, 500)
				}

				if (linkType === 'hash-link') {
					hideOffcanvas(settings, {
						shouldFocusOriginalTrigger: false
					})
				}
			})
		}
	}
}

ctEvents.on('ct:offcanvas:force-close', (settings) => hideOffcanvas(settings))

// Hide the remainings of the panel when the page is loaded
// from back/forward cache.
window.addEventListener('pageshow', (e) => {
	if (!event.persisted) {
		return
	}

	if (document.body.hasAttribute('data-panel')) {
		document.body.removeAttribute('data-panel')
	}

	const maybePanel = document.querySelector('.ct-panel.active')

	if (windowClickListenerController) {
		windowClickListenerController.abort()
		windowClickListenerController = null
	}

	scrollLockManager().enable()

	if (maybePanel) {
		maybePanel.classList.remove('active')

		focusLockManager().focusLockOff(
			maybePanel.querySelector('.ct-panel-content').parentNode
		)
	}
})

export const mount = (el, { event, focus = false }) => {
	handleClick(event, {
		isModal: true,
		container: document.querySelector(el.dataset.togglePanel || el.hash),
		clickOutside: true,
		focus
	})
}
