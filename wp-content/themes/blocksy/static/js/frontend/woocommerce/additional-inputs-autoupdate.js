let timeoutId = null

export const mount = (el, { event }) => {
	if (!el.closest('.ct-cart-auto-update')) {
		return
	}

	if (timeoutId) {
		clearTimeout(timeoutId)
	}

	timeoutId = setTimeout(function () {
		const updateCartButton = document.querySelector("[name='update_cart']")

		if (updateCartButton) {
			updateCartButton.click()
		}
	}, 300)
}
