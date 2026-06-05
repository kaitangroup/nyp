import './public-path'
import './events'

import ctEvents from 'ct-events'

import { watchLayoutContainerForReveal } from './frontend/animated-element'
import {
	onDocumentLoaded,
	handleEntryPoints,
	loadStyle,
	preloadAssetsForContent
} from './helpers'

import { getCurrentScreen } from './frontend/helpers/current-screen'
import { mountDynamicChunks } from './dynamic-chunks'

import { menuEntryPoints } from './frontend/entry-points/menus'
import { liveSearchEntryPoints } from './frontend/entry-points/live-search'

import { preloadClickHandlers } from './frontend/dynamic-chunks/click-trigger'
import { isTouchDevice } from './frontend/helpers/is-touch-device'

export const areWeDealingWithSafari = /apple/i.test(navigator.vendor)

/**
 * iOS hover fix
 */
document.addEventListener('click', (x) => 0)

import {
	fastOverlayHandleClick,
	fastOverlayMount
} from './frontend/fast-overlay'
// import { mount } from './frontend/social-buttons'

let allFrontendEntryPoints = [
	...menuEntryPoints,
	...liveSearchEntryPoints,

	{
		els: '[data-parallax]',
		load: () => import('./frontend/parallax/register-listener'),
		events: ['blocksy:parallax:init']
	},

	{
		els: '.flexy-container[data-flexy*="no"]',
		load: () => import('./frontend/flexy'),
		trigger: ['hover-with-touch']
	},

	{
		els: '.ct-share-box [data-network="pinterest"]',
		load: () => import('./frontend/social-buttons'),
		trigger: ['click']
	},

	{
		els: '.ct-share-box [data-network="clipboard"]',
		load: () => import('./frontend/social-buttons'),
		trigger: ['click']
	},

	{
		els: '.ct-media-container[data-media-id]:not([data-state*="hover"]), .ct-dynamic-media[data-media-id]:not([data-state*="hover"])',
		load: () => import('./frontend/lazy/video-on-click'),
		trigger: ['click', 'slight-mousemove', 'scroll']
	},

	{
		els: '.ct-media-container[data-media-id][data-state*="hover"], .ct-dynamic-media[data-media-id][data-state*="hover"]',
		load: () => import('./frontend/lazy/video-on-click'),
		trigger: ['click', 'hover-with-touch']
	},

	{
		els: '.ct-share-box [data-network]:not([data-network="pinterest"]):not([data-network="email"]):not([data-network="clipboard"])',
		load: () => import('./frontend/social-buttons'),
		trigger: ['hover'],
		condition: () =>
			!/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(
				navigator.userAgent
			)
	},

	{
		els: () => {
			const popperEls = [
				'.ct-language-switcher > .ct-active-language',
				'.ct-header-account[data-interaction="dropdown"] > .ct-account-item'
			]

			const maybeCart = document.querySelector(
				'.ct-header-cart > .ct-cart-content:not([data-count="0"])'
			)

			// Cart within offcanvas doesn't have a dropdown
			if (maybeCart && !maybeCart.closest('#offcanvas')) {
				popperEls.push('.ct-header-cart > .ct-cart-item')
			}

			return popperEls
		},
		load: () => import('./frontend/popper-elements'),
		trigger: ['hover-with-click']
	},

	{
		els: '.ct-back-to-top, .ct-shortcuts-bar [data-shortcut*="scroll_top"]',
		load: () => import('./frontend/back-to-top-link'),
		events: ['ct:back-to-top:mount'],
		trigger: ['scroll']
	},

	{
		els: '.ct-pagination[data-pagination="load_more"] .ct-load-more',
		load: () => import('./frontend/layouts/infinite-scroll'),
		trigger: [{ id: 'click', once: true }],
		mount: ({ mount, el, event }) =>
			mount(el.closest('.ct-pagination'), { event })
	},

	{
		els: '.ct-pagination[data-pagination="infinite_scroll"]',
		load: () => import('./frontend/layouts/infinite-scroll'),
		trigger: ['scroll']
	},

	{
		els: () => [
			...new Set(
				[...document.querySelectorAll('[data-reveal*="no"]')]
					.map((el) => el.closest('.entries, .products'))
					.filter((el) => !!el)
			)
		],
		load: () =>
			new Promise((r) => r({ mount: watchLayoutContainerForReveal }))
	},

	{
		els: [
			'.ct-has-link-overlay.is-layout-slider .flexy-item:has(> * > .ct-link-overlay)',
			'.ct-has-link-overlay:not(.is-layout-slider) *:has(> .ct-link-overlay)'
		],
		load: () => import('./frontend/link-overlay'),
		trigger: ['click']
	},

	{
		els: ['.ct-modal-action'],
		load: () => new Promise((r) => r({ mount: fastOverlayMount })),
		events: ['ct:header:update'],
		trigger: ['click']
	},

	{
		els: ['.ct-expandable-trigger'],
		load: () => import('./frontend/generic-accordion'),
		trigger: ['click']
	},

	{
		els: ['.ct-header-search'],
		load: () => new Promise((r) => r({ mount: fastOverlayMount })),
		mount: ({ mount, el, ...rest }) => {
			mount(el, {
				...rest,
				focus: true
			})
		},
		events: [],
		trigger: ['click']
	}
]

if (document.body.className.indexOf('woocommerce') > -1) {
	import('./frontend/woocommerce/main').then(({ wooEntryPoints }) => {
		allFrontendEntryPoints = [...allFrontendEntryPoints, ...wooEntryPoints]

		handleEntryPoints(allFrontendEntryPoints, {
			immediate: true,
			skipEvents: true
		})
	})
}

handleEntryPoints(allFrontendEntryPoints, {
	immediate: /comp|inter|loaded/.test(document.readyState)
})

const initOverlayTrigger = () => {
	;[
		...document.querySelectorAll('.ct-header-trigger'),
		...document.querySelectorAll('.ct-offcanvas-trigger')
	].map((menuToggle) => {
		if (menuToggle && !menuToggle.hasListener) {
			menuToggle.hasListener = true

			menuToggle.addEventListener('click', (event) => {
				event.preventDefault()

				if (!menuToggle.dataset.togglePanel && !menuToggle.hash) {
					return
				}

				let offcanvas = document.querySelector(
					menuToggle.dataset.togglePanel || menuToggle.hash
				)

				if (!offcanvas) {
					return
				}

				fastOverlayHandleClick(event, {
					container: offcanvas,
					closeWhenLinkInside: !menuToggle.closest('.ct-header-cart'),
					computeScrollContainer: () => {
						if (
							offcanvas.querySelector('.cart_list') &&
							!offcanvas.querySelector(
								'[data-id="cart"] .cart_list'
							)
						) {
							return offcanvas.querySelector('.cart_list')
						}

						if (
							getCurrentScreen() === 'mobile' &&
							offcanvas.querySelector(
								'[data-device="mobile"] > .ct-panel-content-inner'
							)
						) {
							return offcanvas.querySelector(
								'[data-device="mobile"] > .ct-panel-content-inner'
							)
						}

						return offcanvas.querySelector(
							'.ct-panel-content > .ct-panel-content-inner'
						)
					}
				})
			})
		}
	})
}

const mountIntegrations = (integrations) => {
	if (integrations.length > 0) {
		Promise.all(
			integrations
				.filter(({ check }) => check())
				.map(({ promise }) => promise())
		).then((integrations) => {
			integrations.map(({ mount }) => mount())
		})
	}
}

export const preloadLazyAssets = (userCall = true) => {
	loadStyle(ct_localizations.dynamic_styles.lazy_load)
	preloadClickHandlers()
	import('./frontend/handle-3rd-party-events')

	if (userCall) {
		ctEvents.trigger('blocksy:frontend:init')
	}
}

onDocumentLoaded(() => {
	document.body.addEventListener(
		'mouseover',
		() => {
			preloadLazyAssets(false)

			const maybeModalSearch = document.querySelector(
				'#search-modal .ct-search-form input'
			)

			if (maybeModalSearch && maybeModalSearch.value.trim().length > 0) {
				maybeModalSearch.dispatchEvent(
					new Event('input', { bubbles: true })
				)
			}
		},
		{ once: true, passive: true }
	)

	let inputs = [
		...document.querySelectorAll(
			'.comment-form [class*="comment-form-field"]'
		)
	]
		.reduce(
			(result, parent) => [
				...result,
				parent.querySelector('input,textarea')
			],
			[]
		)
		.filter((input) => input.type !== 'hidden' && input.type !== 'checkbox')

	const renderEmptiness = () => {
		inputs.map((input) => {
			input.parentNode.classList.remove('ct-not-empty')

			if (!input.value) {
				return
			}

			if (input.value.trim().length > 0) {
				input.parentNode.classList.add('ct-not-empty')
			}
		})
	}

	setTimeout(() => {
		renderEmptiness()
	}, 10)

	inputs.map((input) => input.addEventListener('input', renderEmptiness))

	mountDynamicChunks()

	setTimeout(() => {
		initOverlayTrigger()
	})

	mountIntegrations([
		{
			promise: () => import('./frontend/integration/litespeed'),
			check: () =>
				!![...document.childNodes].find((c) => {
					if (c.nodeType !== 8) {
						return false
					}

					return c.nodeValue.toLowerCase().includes('litespeed')
				})
		}
	])
})

let isPageLoad = true

ctEvents.on('blocksy:frontend:init', () => {
	handleEntryPoints(allFrontendEntryPoints, {
		immediate: true,
		skipEvents: true
	})

	mountDynamicChunks()

	initOverlayTrigger()

	// If assets detected in main container, preload them.
	// It will not cover assets for elements from drawer canvas, as those
	// will be opened later when corresponding items open.
	//
	// Also, need to do it on this first load because woo fragments may
	// already have updated the cart content before our main script runs,
	// so we need to make sure to cover those assets as well.
	preloadAssetsForContent(document.querySelector('#main-container'))

	if (isPageLoad) {
		isPageLoad = false
	} else {
		mountIntegrations([
			{
				promise: () => import('./frontend/integration/stackable'),
				check: () => true
			},

			{
				promise: () => import('./frontend/integration/greenshift'),
				check: () => !!window.gsInitTabs
			},

			{
				promise: () => import('./frontend/integration/cf7'),
				check: () => !!window.wpcf7
			},

			{
				promise: () => import('./frontend/integration/turnstile'),
				check: () => !!window.turnstile
			},

			{
				promise: () => import('./frontend/integration/elementor'),
				check: () => !!window.elementorFrontend
			},

			{
				promise: () =>
					import('./frontend/integration/elementor-premium-addons'),
				check: () => !!window.premiumWooProducts
			},

			{
				promise: () =>
					import(
						'./frontend/integration/advanced-product-fields-for-woocommerce'
					),
				check: () => !!window._wapf
			}
		])
	}
})

ctEvents.on(
	'ct:overlay:handle-click',
	({ e, href, container, options = {} }) => {
		fastOverlayHandleClick(e, {
			...(href
				? {
						container: document.querySelector(href)
					}
				: {}),

			...(container ? { container } : {}),
			...options
		})
	}
)

export {
	loadStyle,
	preloadAssetsForContent,
	handleEntryPoints,
	onDocumentLoaded
} from './helpers'
export { registerDynamicChunk, loadDynamicChunk } from './dynamic-chunks'
export { getCurrentScreen } from './frontend/helpers/current-screen'
