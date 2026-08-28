import { actuallyInsertError } from '../../account'

/**
 * Handle Jetpack Protect's math captcha challenge on non-200 login responses.
 *
 * Returns false if Jetpack intercepted (caller should stop processing),
 * true if Jetpack was not involved (caller should show a generic error).
 *
 * @param {HTMLFormElement} maybeLogin
 * @param {string}          content    Raw response text from wp-login.php
 * @returns {boolean}
 */
export const maybeHandleJetpackProtect = (maybeLogin, content) => {
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
