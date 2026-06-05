import {
	maybeAddLoadingState,
	maybeCleanupLoadingState,
	maybeAddErrors,
	actuallyInsertMessage,
	actuallyInsertError,
} from '../../account'

import { formPreSubmitHook } from '../hooks'
import { resetCaptchaFor } from '../captcha'

import { maybeMountTwoFactorForm } from '../integrations/two-factor'

import $ from 'jquery'

const maybeHandleJetpackProtect = (maybeLogin, content) => {
	const loginForm = maybeLogin.closest('.ct-login-form')

	if (!loginForm) {
		return true
	}

	let errorMessage = ct_localizations.login_generic_error_msg

	// 1st check if jetpack asks for math captcha
	const needToSolve =
		content.indexOf('jetpack_protect_process_math_form') !== -1

	if (needToSolve) {
		const maybeErrorMessages = content.match(/<h2.*?>(.*?)<\/h2>/)

		if (maybeErrorMessages && maybeErrorMessages.length > 1) {
			errorMessage = maybeErrorMessages[1]
		}

		actuallyInsertError(maybeLogin.closest('.ct-login-form'), errorMessage)

		const div = document.createElement('div')
		div.innerHTML = content
		const form = div.querySelector('form')

		const maybeExistingMathEl = loginForm.querySelector(
			'#jetpack_protect_answer'
		)

		if (form) {
			const maybeSubmit = form.querySelector(
				'input[type="submit"], button[type="submit"]'
			)
			if (maybeSubmit) {
				maybeSubmit.remove
			}

			if (maybeExistingMathEl) {
				maybeExistingMathEl.parentNode.remove()
			}

			const maybeRememberEl = loginForm.querySelector('.login-remember')
			if (maybeRememberEl) {
				maybeRememberEl.insertAdjacentElement(
					'beforebegin',

					...form.children
				)
			}
		}

		return false
	}

	// 2nd check if jetpack failed
	// weeek check for "Jetpack" and strong tag in the content
	if (
		content.indexOf('Jetpack') !== -1 &&
		content.indexOf('<strong>') !== -1
	) {
		const maybeErrorMessages = content.match(/<strong.*?>(.*?)<\/strong>/)

		if (maybeErrorMessages && maybeErrorMessages.length > 1) {
			errorMessage = maybeErrorMessages[1]
		}

		actuallyInsertError(maybeLogin.closest('.ct-login-form'), errorMessage)

		return false
	}

	return true
}

export const maybeHandleLoginForm = (el) => {
	let maybeLogin = el.querySelector('[name="loginform"]')

	if (!maybeLogin) {
		return
	}

	maybeLogin.addEventListener('submit', (e) => {
		e.preventDefault()

		if (window.ct_customizer_localizations) {
			return
		}

		maybeAddLoadingState(maybeLogin)

		let body = new FormData(maybeLogin)
		body.append('redirect_to', location.href)
		if (maybeLogin.querySelector('[name="username"]')) {
			body.append(
				'log',
				maybeLogin.querySelector('[name="username"]').value
			)
		}

		// let url = maybeLogin.action

		let url = `${ct_localizations.ajax_url}?action=blc_implement_user_login`

		if (window.WFLSVars && !maybeLogin.loginProceed) {
			body.append('action', 'wordfence_ls_authenticate')
			url = WFLSVars.ajaxurl

			formPreSubmitHook(maybeLogin).then(() => {
				fetch(url, {
					method: maybeLogin.method,
					body,
				})
					.then((response) => response.json())
					.then((res) => {
						maybeCleanupLoadingState(maybeLogin)
						let hasError = !!res.error

						const container = maybeLogin.closest('.ct-login-form')
						const form = maybeLogin
							.closest('.ct-login-form')
							.querySelector('form')

						if (hasError) {
							actuallyInsertError(container, res.error)
						}

						if (res.message) {
							actuallyInsertMessage(form, res.message)
						}

						if (res.login) {
							if (res.two_factor_required) {
								const wfltOverlay = maybeLogin.querySelector(
									'#wfls-prompt-overlay'
								)

								if (!wfltOverlay) {
									var overlay = $(
										'<div id="wfls-prompt-overlay"></div>'
									)
									var wrapper = $(
										'<div id="wfls-prompt-wrapper"></div>'
									)
									var label = $('<label for="wfls-token">')
									label.text('Wordfence 2FA Code' + ' ')
									label.append(
										$(
											'<a href="javascript:void(0)" class="wfls-2fa-code-help wfls-tooltip-trigger" title="The Wordfence 2FA Code can be found within the authenticator app you used when first activating two-factor authentication. You may also use one of your recovery codes."><i class="dashicons dashicons-editor-help"></i></a>'
										)
									)
									label = $('<p>').append(label)
									var field = $(
										'<p><input type="text" name="wfls-token" id="wfls-token" aria-describedby="wfls-token-error" class="input" value="" size="6" autocomplete="one-time-code"/></p>'
									)
									var remember = $(
										'<p class="wfls-remember-device-wrapper"><label for="wfls-remember-device"><input name="wfls-remember-device" type="checkbox" id="wfls-remember-device" value="1" /> </label></p>'
									)
									remember
										.find('label')
										.append('Remember for 30 days')
									var button = $(
										'<p class="submit"><input type="submit" name="wfls-token-submit" id="wfls-token-submit" class="button button-primary button-large"/></p>'
									)
									button
										.find('input[type=submit]')
										.val('Log In')
									wrapper.append(label)
									wrapper.append(field)
									if (parseInt(WFLSVars.allowremember)) {
										wrapper.append(remember)
									}
									wrapper.append(button)
									overlay.append(wrapper)
									$(form)
										.css('position', 'relative')
										.append(overlay)
									$('#wfls-token').focus()

									new $.Zebra_Tooltips(
										$('.wfls-tooltip-trigger')
									)
								}
							} else {
								const body = new FormData(maybeLogin)

								body.append('redirect_to', location.href)

								fetch(
									`${ct_localizations.ajax_url}?action=blc_implement_user_login`,
									{
										method: maybeLogin.method,
										body,
									}
								)
									.then((response) => response.json())
									.then(({ data: { html, redirect_to } }) => {
										location = redirect_to
									})
							}

							maybeLogin.loginProceed = true
						}

						if (
							!hasError ||
							(hasError &&
								maybeLogin
									.closest('.ct-login-form')
									.querySelector(
										'.ct-form-notification-error'
									)
									.innerHTML.indexOf('Captcha') === -1)
						) {
							resetCaptchaFor(
								maybeLogin.closest('.ct-login-form')
							)
						}
					})
			})

			return
		}

		formPreSubmitHook(maybeLogin).then(() => {
			fetch(url, {
				method: maybeLogin.method,
				body,
			})
				.then((response) => {
					if (response.status !== 200) {
						response.text().then((text) => {
							maybeCleanupLoadingState(maybeLogin)

							const jetpackMathResult = maybeHandleJetpackProtect(
								maybeLogin,
								text
							)

							if (!jetpackMathResult) {
								return
							}

							actuallyInsertError(
								maybeLogin.closest('.ct-login-form'),
								ct_localizations.login_generic_error_msg
							)
						})

						return
					}

					if (response.redirected && response.url) {
						return {
							data: {
								html: '',
								redirect_to: response.url,
							},
						}
					}

					return response.json()
				})
				.then(({ data: { html, redirect_to } }) => {
					const { doc, hasError } = maybeAddErrors(
						maybeLogin.closest('.ct-login-form'),
						html
					)

					if (!hasError) {
						if (!maybeMountTwoFactorForm(maybeLogin, doc)) {
							setTimeout(() => {
								location = redirect_to
							}, 2000)
						}
					} else {
						maybeCleanupLoadingState(maybeLogin)
					}

					if (
						!hasError ||
						(hasError &&
							maybeLogin
								.closest('.ct-login-form')
								.querySelector('.ct-form-notification-error')
								.innerHTML.indexOf('Captcha') === -1)
					) {
						resetCaptchaFor(maybeLogin.closest('.ct-login-form'))
					}
				})
		})
	})
}
