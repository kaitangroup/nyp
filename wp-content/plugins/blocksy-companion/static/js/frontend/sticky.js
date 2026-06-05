import ctEvents from 'ct-events'
import { getCurrentScreen } from 'blocksy-frontend'

import { computeShrink } from './sticky/shrink'
import { computeAutoHide } from './sticky/auto-hide'
import { computeFadeSlide } from './sticky/fade-slide'

import {
	getRowStickyHeight,
	getRowInitialMinHeight,
	maybeSetStickyHeightAnimated,
} from './sticky/shrink-utils'

import { clearShrinkCache } from './sticky/shrink-handle-middle-row'
import { clearLogoShrinkCache } from './sticky/shrink-handle-logo'

import { computeStartPositionForRowConfiguration } from './sticky/start-position'

export const setTransparencyFor = (deviceContainer, value = 'yes') => {
	Array.from(
		deviceContainer.querySelectorAll('[data-row][data-transparent-row]')
	).map((el) => {
		el.dataset.transparentRow = value
	})
}

var getParents = function (elem) {
	var parents = []

	for (; elem && elem !== document; elem = elem.parentNode) {
		parents.push(elem)
	}

	return parents
}

let cachedStartPosition = null
let cachedContainerInitialHeight = {}
let cachedHeaderInitialHeight = null
let cachedStickyContainerHeight = null
let forcedHeightSetForStickyContainer = false

const clearCache = () => {
	clearShrinkCache()
	clearLogoShrinkCache()

	cachedStartPosition = null
	cachedHeaderInitialHeight = null
	cachedStickyContainerHeight = null
	prevScrollY = null
	forcedHeightSetForStickyContainer = false
}

ctEvents.on('blocksy:sticky:compute', () => {
	setTimeout(() => {
		clearCache()
		compute()
	}, 100)
})

if (window.wp && wp.customize && wp.customize.selectiveRefresh) {
	let shouldSkipNext = false
	wp.customize.selectiveRefresh.bind(
		'partial-content-rendered',
		(placement) => {
			if (shouldSkipNext) {
				return
			}

			shouldSkipNext = true
			setTimeout(() => {
				clearCache()
				forcedHeightSetForStickyContainer = true
				compute()
				shouldSkipNext = false
			}, 500)
		}
	)
}

const getStartPositionFor = (stickyContainer) => {
	if (cachedStartPosition) {
		return cachedStartPosition
	}

	if (
		stickyContainer.dataset.sticky.indexOf('shrink') === -1 &&
		stickyContainer.dataset.sticky.indexOf('auto-hide') === -1
	) {
		// return stickyContainer.parentNode.getBoundingClientRect().height + 200
	}

	const headerRect = stickyContainer.closest('header').getBoundingClientRect()

	// Round to avoid floating point dust (e.g. -0.00006) at fractional DPRs
	// like 1.25 where headerRect.top + scrollY doesn't cancel out to exactly 0.
	let stickyOffset = Math.round(headerRect.top + scrollY)

	if (stickyOffset > 0) {
		let element = document.elementFromPoint(0, 3)

		if (element) {
			if (
				getParents(element)
					.map((el) => {
						let style = getComputedStyle(el)
						return style.position
					})
					.indexOf('fixed') > -1
			) {
				stickyOffset -= element.getBoundingClientRect().height
			}
		}
	}

	if (
		stickyContainer.dataset.sticky.indexOf('shrink') === -1 &&
		stickyContainer.dataset.sticky.indexOf('auto-hide') === -1
	) {
		stickyOffset += 200
	}

	const row = stickyContainer.parentNode

	const bodyComp = getComputedStyle(document.body)
	let maybeDynamicOffset = parseFloat(
		bodyComp.getPropertyValue('--header-sticky-offset') || 0
	)

	maybeDynamicOffset =
		maybeDynamicOffset +
		(parseFloat(bodyComp.getPropertyValue('--theme-frame-size')) || 0)

	const rowConfiguration = Array.from(row.parentNode.children)
		.filter(
			(el) =>
				el.dataset.row || el.classList.contains('ct-sticky-container')
		)
		.map((el) => {
			if (el.classList.contains('ct-sticky-container')) {
				return {
					sticky: true,
				}
			}

			return {
				height: el.getBoundingClientRect().height,
			}
		})

	const actualStartPositionForRows =
		computeStartPositionForRowConfiguration(rowConfiguration)

	let finalResult = actualStartPositionForRows + stickyOffset

	finalResult =
		finalResult > 0 ? finalResult - maybeDynamicOffset : finalResult

	let shouldWriteCache = true

	// Dont write cache until store notice is visible. Sometimes the store notice
	// appears too late and the sticky header is not positioned correctly.
	//
	// https://github.com/Creative-Themes/blocksy/issues/4617
	const maybeStoreNotice = document.querySelector('.woocommerce-store-notice')

	if (maybeStoreNotice) {
		shouldWriteCache = false

		if (maybeStoreNotice.offsetHeight > 0) {
			shouldWriteCache = true
		}
	}

	if (shouldWriteCache) {
		cachedStartPosition = finalResult
	}

	return finalResult
}

let prevScrollY = null

const compute = () => {
	if (prevScrollY === scrollY) {
		/*
		requestAnimationFrame(() => {
			compute()
		})
    */

		return
	}

	const stickyContainer = document.querySelector(
		`[data-device="${getCurrentScreen()}"] [data-sticky]`
	)

	if (!stickyContainer) {
		return
	}

	const currentScreenWithTablet = getCurrentScreen({ withTablet: true })

	let containerInitialHeight =
		cachedContainerInitialHeight[currentScreenWithTablet]

	const shouldSetHeight =
		!containerInitialHeight || forcedHeightSetForStickyContainer

	if (!containerInitialHeight || forcedHeightSetForStickyContainer) {
		cachedContainerInitialHeight[currentScreenWithTablet] = [
			...stickyContainer.querySelectorAll('[data-row]'),
		].reduce((res, row) => {
			return res + getRowInitialMinHeight(row)
		}, 0)

		containerInitialHeight =
			cachedContainerInitialHeight[currentScreenWithTablet]
	}

	if (shouldSetHeight) {
		forcedHeightSetForStickyContainer = false
		stickyContainer.parentNode.style.height = `${containerInitialHeight}px`
	}

	let startPosition = getStartPositionFor(stickyContainer, {})

	let headerInitialHeight = cachedHeaderInitialHeight

	if (headerInitialHeight === null) {
		const headerRect = stickyContainer
			.closest('[data-device]')
			.getBoundingClientRect()

		headerInitialHeight = headerRect.height
		cachedHeaderInitialHeight = headerInitialHeight
	}

	let stickyContainerHeight = cachedStickyContainerHeight

	const stickyComponents = stickyContainer.dataset.sticky
		.split(':')
		.filter((c) => c !== 'yes' && c !== 'no' && c !== 'fixed')

	if (!stickyContainerHeight) {
		stickyContainerHeight = [
			...stickyContainer.querySelectorAll('[data-row]'),
		].reduce((res, row) => res + getRowStickyHeight(row), 0)
		cachedStickyContainerHeight = parseInt(stickyContainerHeight)

		maybeSetStickyHeightAnimated(() => {
			return stickyComponents.indexOf('auto-hide') === -1
				? // case when content is forcing the initial height to be bigger
				  stickyContainerHeight >
				  [...stickyContainer.querySelectorAll('[data-row]')].reduce(
						(res, row) => res + getRowInitialMinHeight(row),
						0
				  )
					? `${stickyContainerHeight}px`
					: `${[
							...stickyContainer.querySelectorAll('[data-row]'),
					  ].reduce(
							(res, row) => res + getRowStickyHeight(row),
							0
					  )}px`
				: '0px'
		})
	}

	let isSticky =
		(startPosition > 0 && Math.abs(window.scrollY - startPosition) < 5) ||
		window.scrollY > startPosition

	if (stickyComponents.indexOf('shrink') > -1) {
		isSticky =
			startPosition > 0
				? window.scrollY >= startPosition
				: window.scrollY > 0
	}

	setTimeout(() => {
		if (isSticky && document.body.dataset.header.indexOf('shrink') === -1) {
			document.body.dataset.header = `${document.body.dataset.header}:shrink`
		}

		if (!isSticky && document.body.dataset.header.indexOf('shrink') > -1) {
			document.body.dataset.header = document.body.dataset.header.replace(
				':shrink',
				''
			)
		}
	}, 300)

	let currentScrollY = scrollY

	if (stickyComponents.indexOf('shrink') > -1) {
		computeShrink({
			stickyContainer,
			stickyContainerHeight,

			containerInitialHeight,
			isSticky,
			startPosition,
			stickyComponents,
		})
	}

	if (stickyComponents.indexOf('auto-hide') > -1) {
		computeAutoHide({
			stickyContainer,
			isSticky,
			startPosition,
			stickyComponents,

			containerInitialHeight,
			stickyContainerHeight,

			headerInitialHeight,

			currentScrollY,
			prevScrollY,
		})
	}

	if (
		stickyComponents.indexOf('slide') > -1 ||
		stickyComponents.indexOf('fade') > -1
	) {
		computeFadeSlide({
			stickyContainer,
			isSticky,
			startPosition,
			stickyComponents,
		})
	}

	prevScrollY = currentScrollY
}

export const mountStickyHeader = () => {
	if (!document.querySelector('header [data-sticky]')) {
		return
	}

	var prevWidth = window.width

	window.addEventListener(
		'resize',
		(event) => {
			if (window.width === prevWidth) {
				return
			}

			prevWidth = window.width

			clearCache()
			compute(event)
			ctEvents.trigger('ct:header:update')
		},
		false
	)

	window.addEventListener('orientationchange', (event) => {
		clearCache()
		compute(event)
		ctEvents.trigger('ct:header:update')
	})

	window.addEventListener('scroll', compute, false)
	window.addEventListener('load', compute, false)

	compute()
}
