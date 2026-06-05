export const mount = () => {
	if (!window.turnstile) {
		return
	}

	if (window.WPDEF) {
		const widgets = document.querySelectorAll('div[id^="wpdef_turnstile_"]')
		if (widgets.length) {
			widgets.forEach((widget) => {
				turnstile.render(`#${widget.id}`, {
					sitekey: WPDEF.options.sitekey,
					theme: WPDEF.options.theme,
					size: WPDEF.options.size,
					language: WPDEF.options.lang,
					'response-field-name': 'wpdef-turnstile-response'
				})
			})

			return
		}
	}

	const forms = document.querySelectorAll('.ct-popup .cf-turnstile')

	if (!forms.length) {
		return
	}

	forms.forEach((form) => {
		turnstile.remove(form)
		turnstile.render(form)
		turnstile.reset(form)
	})
}
