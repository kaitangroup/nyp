const getRelevantInputs = (formEl) => {
	var inputs = jQuery()

	;[...formEl.querySelectorAll('input')].map((el) => {
		inputs = inputs.add(el)
	})

	return inputs
}

var wfls_init_captcha = function (actionCallback, formEl) {
	let log = getRelevantInputs(formEl)

	if (typeof grecaptcha === 'object') {
		grecaptcha.ready(function () {
			grecaptcha
				.execute(WFLSVars.recaptchasitekey, { action: 'login' })
				.then(function (token) {
					var tokenField = jQuery('#wfls-captcha-token')
					if (tokenField.length) {
						tokenField.val(token)
					} else {
						if (log.length) {
							tokenField = jQuery(
								'<input type="hidden" name="wfls-captcha-token" id="wfls-captcha-token" />'
							)
							tokenField.val(token)
							log.parent().append(tokenField)
						}
					}

					typeof actionCallback === 'function' && actionCallback(true)
				})
		})
	} else {
		var tokenField = jQuery('#wfls-captcha-token')

		if (tokenField.length) {
			tokenField.val('grecaptcha-missing')
		} else {
			if (log.length) {
				tokenField = jQuery(
					'<input type="hidden" name="wfls-captcha-token" id="wfls-captcha-token" />'
				)
				tokenField.val('grecaptcha-missing')
				log.parent().append(tokenField)
			}
		}

		typeof actionCallback === 'function' && actionCallback(true)
	}
}

const wpmudev_init_captcha = (cb, form) => {
	if ('v3_recaptcha' == WPDEF.options.version) {
		grecaptcha.ready(function () {
			grecaptcha
				.execute(WPDEF.options.siteKey, { action: 'WPDEF_reCaptcha' })
				.then(function (token) {
					document
						.querySelectorAll('.g-recaptcha-response')
						.forEach(function (elem) {
							elem.value = token
						})

					typeof cb === 'function' && cb(true)
				})
		})

		return
	}

	if (
		'v2_checkbox' == WPDEF.options.version ||
		'v2_invisible' == WPDEF.options.version
	) {
		let recaptchas = [
			...form.querySelectorAll('div[id^="wpdef_recaptcha_"]'),
		]

		recaptchas.forEach((container) => {
			if (container.innerHTML.trim() !== '') {
				return
			}

			const params = {
				sitekey: WPDEF.options.sitekey,
				theme: WPDEF.options.theme,
				size: WPDEF.options.size,

				callback: function () {
					typeof cb === 'function' && cb(true)
				},
			}

			grecaptcha.render(container.id, params)
		})
	}
}

export const maybeApplyWordfenceCaptcha = (cb, form) => {
	if (window.WFLSVars && parseInt(WFLSVars.useCAPTCHA)) {
		wfls_init_captcha(() => cb(), form)
		return true
	}

	return false
}

export const maybeApplyDefenderCaptcha = (cb, form) => {
	if (window.WPDEF && !window.turnstile) {
		wpmudev_init_captcha(() => cb(), form)
		return true
	}

	return false
}

export const resetCaptchaFor = (container) => {
	;[...container.querySelectorAll('.g-recaptcha, .anr_captcha_field')].map(
		(el) => {
			if (el.classList.contains('anr_captcha_field')) {
				grecaptcha.reset(
					parseFloat(
						el.firstElementChild.id.replace(
							'anr_captcha_field_',
							''
						)
					) - 1
				)
			} else {
				grecaptcha.reset(el.gID)
			}
		}
	)
}

export const reCreateCaptchaFor = (el) => {
	;[...el.querySelectorAll('.g-recaptcha, .anr_captcha_field')].map((el) => {
		if (el.gID) {
			return
		}

		el.innerHTML = ''
		el.gID = grecaptcha.render(el)
	})
}
