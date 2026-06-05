import InfiniteScroll from 'infinite-scroll'
import { watchLayoutContainerForReveal } from '../animated-element'
import { setLoaderState } from '../helpers/set-loader-state'
import ctEvents from 'ct-events'

const generateQuerySelector = (el) => {
	let itemsToSkip = '.yit-wcan-container'

	const classesToSkip = [
		'active',
		'ct-active',
		'wpgb-enabled',
		'e-lazyloaded'
	]

	let parents = []

	let elem = el

	for (; elem && elem !== document; elem = elem.parentNode) {
		if (elem.matches(itemsToSkip)) {
			continue
		}

		parents.push(elem)
	}

	parents = parents.reverse()

	return parents
		.filter((el) => !el.matches('body, html'))
		.map((elForSelector) => {
			if (elForSelector === document.body) {
				return 'body'
			}

			let str = elForSelector.tagName

			if (elForSelector !== el) {
				str += elForSelector.id != '' ? '#' + elForSelector.id : ''
				str += elForSelector.dataset.target
					? `[data-target="${elForSelector.dataset.target}"]`
					: ''
			}

			if (elForSelector.className) {
				const classes = elForSelector.className.split(/\s/)

				for (let i = 0; i < classes.length; i++) {
					if (
						classes[i] &&
						classesToSkip.indexOf(classes[i]) === -1
					) {
						str += '.' + classes[i]
					}
				}
			}

			if (elForSelector === el) {
				const layoutIndex = [
					...elForSelector.parentNode.children
				].indexOf(elForSelector)

				if (layoutIndex > -1) {
					str += `:nth-child(${layoutIndex + 1})`
				}
			}

			return str
		})
		.join(' > ')
}

/**
 * Monkey patch imagesLoaded. We are using here another strategy for detecting
 * images loaded event.
 */
InfiniteScroll.imagesLoaded = (fragment, fn) => fn()
InfiniteScroll.Button.prototype.hide = () => {}

InfiniteScroll.prototype.onPageScroll = InfiniteScroll.throttle(function () {
	let distance = this.getBottomDistance()
	if (
		distance <= this.options.scrollThreshold &&
		distance > -this.options.scrollThreshold
	) {
		this.dispatchEvent('scrollThreshold')
	}
})

export const mount = (paginationContainer, { event } = {}) => {
	let layoutEl = [...paginationContainer.parentNode.children]
		.reduce(
			(a, c) => {
				return [...a, ...c.children]
			},
			[...paginationContainer.parentNode.children]
		)
		.find(
			(c) =>
				c.classList.contains('products') ||
				c.classList.contains('entries') ||
				c.classList.contains('ct-query-template-grid') ||
				c.classList.contains('ct-query-template-default')
		)

	if (!paginationContainer) {
		return
	}

	let paginationType = paginationContainer.dataset.pagination

	if (paginationType.indexOf('simple') > -1) return
	if (paginationType.indexOf('next_prev') > -1) return

	if (!paginationContainer.querySelector('.next')) return

	if (paginationContainer.infiniteScroll) {
		return
	}

	const paginationSelector =
		getAppendSelectorFor(layoutEl, {
			toAppend: '.ct-pagination'
		}) || '.ct-pagination'

	const nextEl = paginationContainer.querySelector('.next')

	let pathParam = `${paginationSelector} .next`

	if (nextEl && nextEl.href && nextEl.href.indexOf('paged=') > -1) {
		const parsedHref = new URL(nextEl.href)

		parsedHref.searchParams.set('paged', '{{#}}')

		pathParam = decodeURIComponent(parsedHref.toString())
	}

	let inf = new InfiniteScroll(layoutEl, {
		// debug: true,
		checkLastPage: `${paginationSelector} .next`,
		path: pathParam,
		append: getAppendSelectorFor(layoutEl),
		button:
			paginationType === 'load_more'
				? paginationContainer.querySelector('.ct-load-more')
				: null,

		outlayer: null,

		// For now, history push is fully disabled because of cases when the
		// view is not updated and we see outdated content on the page.
		//
		// https://github.com/Creative-Themes/blocksy/issues/4744
		//
		// history: 'push',

		scrollThreshold: paginationType === 'infinite_scroll' ? 400 : false,

		onInit() {
			this.on('load', (response) => {
				const helper = paginationContainer.querySelector(
					'.ct-load-more-helper'
				)

				helper.classList.remove('ct-loading')
				setLoaderState(helper, { enabled: false })

				setTimeout(() => {
					ctEvents.trigger('ct:infinite-scroll:load')
					ctEvents.trigger('blocksy:frontend:init')
					ctEvents.trigger('blocksy:parallax:init')
					if (window.jQuery) {
						jQuery(document.body).trigger(
							'wc_price_based_country_ajax_geolocation'
						)
					}

					document.dispatchEvent(
						new CustomEvent('cfvswVariationLoad', { detail: {} })
					)
				}, 100)
			})

			this.on('append', () => {
				watchLayoutContainerForReveal(layoutEl)
				;[...layoutEl.querySelectorAll('[srcset]')].forEach((el) => {
					const prev = el.srcset
					el.srcset = ''
					el.srcset = prev
				})
			})

			this.on('request', () => {
				const helper = paginationContainer.querySelector(
					'.ct-load-more-helper'
				)

				helper.classList.add('ct-loading')
				setLoaderState(helper, { enabled: true })
			})

			this.on('last', () => {
				paginationContainer.classList.add(
					!paginationContainer.querySelector('.ct-last-page-text')
						? 'ct-last-page-no-info'
						: 'ct-last-page'
				)
			})
		}
	})

	paginationContainer.infiniteScroll = inf

	if (paginationType === 'load_more' && event) {
		const loadMoreButton =
			paginationContainer.querySelector('.ct-load-more')

		if (loadMoreButton) {
			setTimeout(() => loadMoreButton.click())
		}
	}
}

function getAppendSelectorFor(layoutEl, args = {}) {
	args = {
		toAppend: 'default',
		...args
	}

	if (layoutEl.closest('.ct-posts-shortcode')) {
		if (layoutEl.classList.contains('products')) {
			return `${generateQuerySelector(
				layoutEl.closest('.ct-posts-shortcode')
			)} ${
				args.toAppend === 'default' ? '.products > li' : args.toAppend
			}`
		} else {
			return `${generateQuerySelector(
				layoutEl.closest('.ct-posts-shortcode')
			)} ${args.toAppend === 'default' ? '.entries > *' : args.toAppend}`
		}
	}

	if (layoutEl.closest('.wp-block-blocksy-query')) {
		const prefix = `.wp-block-blocksy-query[data-id="${
			layoutEl.closest('.wp-block-blocksy-query').dataset.id
		}"]`

		return `${prefix} ${
			args.toAppend === 'default'
				? `[class*="ct-query-template"] > *, ${prefix} .entries > *`
				: args.toAppend
		}`
	}

	if (layoutEl.classList.contains('products')) {
		const selector = generateQuerySelector(layoutEl, '[data-products]')

		if (args.toAppend !== 'default') {
			return null
		}

		// Used to be plain "li", but now we use a more generic selector since
		// content blocks can appear between products in archives.
		return `${selector} > *`
	}

	return `section > ${
		args.toAppend === 'default' ? '.entries > *' : args.toAppend
	}`
}
