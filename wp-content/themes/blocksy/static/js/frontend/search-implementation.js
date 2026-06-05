// @jsx h
import { h } from 'dom-chef'
import classnames from 'classnames'

import { loadStyle } from '../helpers'
import { setLoaderState } from './helpers/set-loader-state'
import { isIosDevice } from './helpers/is-ios-device'
import { whenTransitionEnds } from './helpers/when-transition-ends'

import { getRequestUrl } from './search/get-request-url'

let alreadyRunning = false

const productPricesAndStatusCache = {}

const decodeHTMLEntities = (string) => {
	var doc = new DOMParser().parseFromString(string, 'text/html')
	return doc.documentElement.textContent
}

const store = {}
let controller = null

const cachedFetch = (url, nonce = '') => {
	if (controller) {
		controller.abort('New request')
		controller = null
	}

	if (store[url]) {
		return new Promise((resolve) => {
			resolve(store[url])
			store[url] = store[url].clone()
		})
	}

	if ('AbortController' in window) {
		controller = new AbortController()
	}

	return fetch(url, {
		signal: controller.signal,
		headers: nonce
			? {
					'X-WP-Nonce': nonce
				}
			: {}
	}).then((response) => {
		store[url] = response.clone()

		controller = null

		return response
	})
}

const debounce = (fn, wait) => {
	var timeout

	return function () {
		if (!wait) {
			return fn.apply(this, arguments)
		}

		var context = this
		var args = arguments

		clearTimeout(timeout)

		timeout = setTimeout(function () {
			timeout = null
			return fn.apply(context, args)
		}, wait)
	}
}

const getPreviewElFor = ({
	hasThumbs,
	post: {
		// title: { rendered },
		title,
		url: href,
		_embedded = {},
		ct_featured_media,
		product_price = 0,
		product_status = '',
		placeholder_image = null
	}
}) => {
	const decodedTitle = decodeHTMLEntities(title)

	const defaultMediaDetails = {
		sizes: {
			thumbnail: {
				source_url: placeholder_image
			}
		}
	}

	const sizes =
		(ct_featured_media?.media_details || defaultMediaDetails).sizes || {}

	return (
		<a className="ct-search-item" role="option" key={href} {...{ href }}>
			{(ct_featured_media || placeholder_image) && hasThumbs && (
				<span
					{...{
						class: classnames({
							['ct-media-container']: true
						})
					}}>
					<img
						{...{
							src: sizes.thumbnail
								? sizes?.thumbnail.source_url
								: values(sizes).reduce(
										(currentSmallest, current) =>
											current.width <
											currentSmallest.width
												? current
												: currentSmallest,
										{
											width: 9999999999
										}
									).source_url || ct_featured_media.source_url
						}}
						style={{ aspectRatio: '1/1' }}
					/>
				</span>
			)}
			<span>
				{decodedTitle}

				{product_price || product_status ? (
					<span className="product-search-meta">
						{product_price ? (
							<small
								className="price"
								dangerouslySetInnerHTML={{
									__html: product_price
								}}
								key="price"
							/>
						) : null}
						{product_status ? (
							<small
								className="stock-status"
								dangerouslySetInnerHTML={{
									__html: product_status
								}}
								key="product-status"
							/>
						) : null}
					</span>
				) : null}
			</span>
		</a>
	)
}

export const mount = (formEl, args = {}) => {
	const maybeEl = formEl.querySelector('input[type="search"]')
	const maybeStatusEl = formEl.querySelector('[aria-live]')
	const searchResultsId = maybeEl?.getAttribute('aria-controls')

	const setExpandedState = (isExpanded) => {
		if (!maybeEl) {
			return
		}

		maybeEl.setAttribute('aria-expanded', isExpanded ? 'true' : 'false')
	}

	const closeResults = ({ announce = false } = {}) => {
		const maybeResultsEl = formEl.querySelector('.ct-search-results')

		fadeOutAndRemove(maybeResultsEl)
		setExpandedState(false)

		if (announce && maybeStatusEl) {
			maybeStatusEl.innerHTML =
				ct_localizations.search_live_results_closed
		}
	}

	const clickOutsideHandler = (e) => {
		let mode = { mode: 'inline', ...args }.mode

		if (mode === 'modal') {
			return
		}

		if (formEl.contains(e.target)) {
			return
		}

		closeResults()
	}

	const options = {
		postType: 'any',

		// inline | modal
		mode: 'inline',

		perPage: 5,

		...args
	}

	if (!maybeEl || !window.fetch) {
		return
	}

	let listener = debounce(async (e) => {
		document.removeEventListener('click', clickOutsideHandler)
		document.addEventListener('click', clickOutsideHandler)

		if (e.target.value.trim().length === 0) {
			closeResults()

			if (maybeStatusEl) {
				maybeStatusEl.innerHTML = ct_localizations.search_live_no_result
			}

			return
		}

		if (e.target.dataset?.minLength) {
			const minLength = parseInt(e.target.dataset.minLength, 10)

			if (e.target.value.trim().length < minLength) {
				return
			}
		}

		formEl.classList.add('ct-searching')
		setLoaderState(formEl, { enabled: true })

		const requestUrl = getRequestUrl({
			formEl,
			inputValue: e.target.value,
			perPage: options.perPage
		})

		const maybeNonce = formEl.querySelector('.ct-live-results-nonce')

		try {
			const response = await cachedFetch(
				requestUrl,
				maybeNonce ? maybeNonce.value : ''
			)

			let totalAmountOfPosts = parseInt(
				response.headers.get('X-WP-Total'),
				10
			)

			await loadStyle(ct_localizations.dynamic_styles.search_lazy)

			const posts = await response.json()

			if (
				(formEl.dataset.liveResults || '').indexOf('product_price') > -1
			) {
				const onlyProducts = posts.filter(
					(p) => p.subtype === 'product'
				)

				const maybeAllCached = onlyProducts.every(
					(post) => productPricesAndStatusCache[post.id]
				)

				if (!maybeAllCached) {
					const requestRestUrl = `${ct_localizations.rest_url}wc/store/products`

					const requestRestUrlParams = new URLSearchParams()
					requestRestUrlParams.append(
						'include',
						onlyProducts
							.filter((p) => !productPricesAndStatusCache[p.id])
							.map((p) => p.id)
							.sort()
							.join(',')
					)

					const productsResponse = await cachedFetch(
						`${requestRestUrl}?${requestRestUrlParams.toString()}`,
						maybeNonce ? maybeNonce.value : ''
					)

					const products = await productsResponse.json()

					products.forEach((product) => {
						productPricesAndStatusCache[product.id] = {
							price_html: product.price_html,
							is_in_stock: product.is_in_stock
						}
					})
				}

				posts.forEach((post) => {
					if (post.subtype !== 'product') {
						return
					}

					const matchedProduct = productPricesAndStatusCache[post.id]

					if (matchedProduct) {
						post.product_price = matchedProduct.price_html || ''
						post.product_status = matchedProduct?.is_in_stock
							? ct_localizations.search_live_stock_status_texts
									.instock
							: ct_localizations.search_live_stock_status_texts
									.outofstock
					}
				})
			}

			if (alreadyRunning) {
				return
			}

			alreadyRunning = true

			formEl.classList.remove('ct-searching')
			setLoaderState(formEl, { enabled: false })

			let itHadSearchResultsBefore =
				!!formEl.querySelector('.ct-search-results')

			let searchResults = formEl.querySelector('.ct-search-results')

			let { height: heightBeforeRemoval } = searchResults
				? searchResults.getBoundingClientRect()
				: 0

			if (
				searchResults &&
				!(e.target.value.trim().length === 0 || posts.length === 0)
			) {
				/**
				 * Should just quickly replace the list
				 * when results are available
				 */
				setExpandedState(false)
				searchResults && formEl.removeChild(searchResults)
			} else {
				if (e.target.value.trim().length === 0 || posts.length === 0) {
					closeResults()
				}
			}

			let searchResultsCountElLabel =
				ct_localizations.search_live_no_result

			if (posts.length > 0 && e.target.value.trim().length > 0) {
				searchResultsCountElLabel = (
					posts.length > 1
						? ct_localizations.search_live_many_results
						: ct_localizations.search_live_one_result
				).replace('%s', posts.length)
			}

			if (maybeStatusEl) {
				maybeStatusEl.innerHTML = searchResultsCountElLabel
			}

			if (posts.length > 0 && e.target.value.trim().length > 0) {
				let searchResultsEl = (
					<div
						id={searchResultsId}
						class="ct-search-results"
						role="listbox"
						aria-label={ct_localizations.search_live_results}>
						{posts
							.filter((post) => post?.id)
							.map((post) =>
								getPreviewElFor({
									post,
									hasThumbs:
										(
											formEl.dataset.liveResults || ''
										).indexOf('thumbs') > -1
								})
							)}

						{totalAmountOfPosts > options.perPage ? (
							<a
								className="ct-search-more"
								role="option"
								{...{
									href: ct_localizations.search_url.replace(
										/QUERY_STRING/,
										e.target.value
									)
								}}>
								{ct_localizations.show_more_text}
							</a>
						) : (
							[]
						)}
					</div>
				)

				formEl.appendChild(searchResultsEl)
				setExpandedState(true)

				if (!itHadSearchResultsBefore) {
					fadeIn(formEl.querySelector('.ct-search-results'))
				} else {
					let searchResults =
						formEl.querySelector('.ct-search-results')

					let { height: heightAfterReplace } =
						searchResults.getBoundingClientRect()

					if (heightBeforeRemoval !== heightAfterReplace) {
						searchResults.style.height = `${heightBeforeRemoval}px`
						searchResults.classList.add('ct-slide')

						requestAnimationFrame(() => {
							searchResults.style.height = `${heightAfterReplace}px`

							whenTransitionEnds(searchResults, () => {
								searchResults.removeAttribute('style')

								searchResults.classList.remove('ct-slide')
							})
						})
					}
				}

				if (formEl.querySelector('.ct-search-more')) {
					formEl
						.querySelector('.ct-search-more')
						.addEventListener('click', (e) => {
							e.preventDefault()
							formEl.submit()
						})
				}

				if (isIosDevice()) {
					if (options.mode === 'modal') {
						window.scrollTo(0, 0)
					}
				}
			}

			alreadyRunning = false
		} catch (error) {
			// Ignore aborts
			if (error.message === 'New request') {
				return
			}

			console.error('Error fetching search results', error)
		}
	}, 300)

	maybeEl.addEventListener('input', listener)

	const handleEscape = (e) => {
		if (e.key !== 'Escape') return

		const hasResults = !!formEl.querySelector('.ct-search-results')
		const hasValue = maybeEl.value.trim().length > 0

		if (hasResults || hasValue) {
			// First ESC: close results and clear input
			e.preventDefault()
			e.stopPropagation()
			closeResults({ announce: true })
			maybeEl.value = ''
		}

		// Second ESC (nothing to clear): let the event bubble
		// up to close the modal/overlay
	}

	// Overlay close listener uses keyup, so we intercept
	// the same event to stop it from bubbling
	maybeEl.addEventListener('keyup', handleEscape)

	maybeEl.addEventListener('focus', (e) => {
		listener(e)
	})

	if (maybeEl.value.length > 0) {
		listener({ target: maybeEl })
	}
}

function fadeOutAndRemove(el) {
	if (!el) return

	let { height } = el.getBoundingClientRect()

	el.classList.add('ct-fade-leave')
	el.style.height = `${height}px`

	requestAnimationFrame(() => {
		el.classList.remove('ct-fade-leave')
		el.classList.add('ct-fade-leave-active')
		el.style.height = 0

		whenTransitionEnds(
			el,
			() => el.parentNode && el.parentNode.removeChild(el)
		)
	})
}

function fadeIn(el) {
	el.classList.add('ct-fade-enter')

	let { height } = el.getBoundingClientRect()

	el.classList.add('ct-fade-leave')
	el.style.height = 0

	requestAnimationFrame(() => {
		el.style.height = `${height}px`
		el.classList.remove('ct-fade-enter')
		el.classList.add('ct-fade-enter-active')

		whenTransitionEnds(el, () => el.removeAttribute('style'))
	})
}

function values(obj) {
	var result = []

	if (typeof obj == 'object' || typeof obj == 'function') {
		var keys = Object.keys(obj)
		var len = keys.length

		for (var i = 0; i < len; i++) {
			result.push(obj[keys[i]])
		}

		return result
	}
}
