import $ from 'jquery'

let mounted = false

export const mount = (el, { event }) => {
	if (mounted || !$) {
		return
	}

	;['found_variation', 'reset_data'].forEach((eventName) => {
		$(el).on(eventName, (e, eventData) => {
			const stockContainer = el
				.closest('.product')
				.querySelector('.ct-woo-card-stock')

			if (!stockContainer) {
				return
			}

			if (eventData?.availability_html) {
				stockContainer.innerHTML = eventData.availability_html
			} else {
				stockContainer.innerHTML = ''
			}
		})
	})
}
