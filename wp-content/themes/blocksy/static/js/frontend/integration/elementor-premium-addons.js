import $ from 'jquery'
import ctEvents from 'ct-events'

export const mount = () => {
	const premiumElements = document.querySelectorAll(
		'.premium-woocommerce[data-page-id]'
	)

	if (!premiumElements.length) {
		return
	}

	premiumElements.forEach((el) => {
		if (el.hasBlocksyListener) {
			return
		}

		el.hasBlocksyListener = true

		$(el).on('qv_loaded', () => {
			setTimeout(() => {
				ctEvents.trigger('blocksy:frontend:init')
			})
		})
	})
}
