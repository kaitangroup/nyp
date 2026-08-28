import {
	maybeAddLoadingState,
	maybeCleanupLoadingState,
	actuallyInsertError
} from '../../account'

/**
 * Handle Kadence Security (iThemes Security) login interstitials displayed
 * inside the Blocksy account modal.
 *
 * Kadence Security hooks into wp_login at priority -1000 and calls die()
 * after rendering its interstitial form. The form has name="itsec-{action}"
 * and submits back to wp-login.php. A successful submission returns an HTTP
 * redirect (followed by fetch), while a failure re-renders the form with an
 * #login-error element.
 *
 * @param {HTMLFormElement} loginFormEl  The original [name="loginform"] element.
 * @param {Document}        doc          Parsed HTML document from the response.
 * @returns {boolean} True if an interstitial form was found and mounted.
 */
export const maybeMountKadenceInterstitialForm = (loginFormEl, doc) => {
	const interstitialForm = doc.querySelector('form[name^="itsec-"]')

	if (!interstitialForm) {
		return false
	}

	// The 2FA onboarding wizard relies on complex page-level JS that doesn't
	// work when stripped out of its full-page context. Reload so the user gets
	// the native full-page experience instead.
	if (interstitialForm.name === 'itsec-2fa-on-board') {
		location.reload()
		return true
	}

	// Keep the login form as a sibling (same pattern as two-factor.js) so that
	// activateScreen() can reset state when the user switches to "Sign Up" and
	// back. On subsequent calls (error / next step) swap out the previous ITSEC
	// form in place; on the first call insert before the login form.
	const existing = loginFormEl.parentNode?.querySelector(
		'form[name^="itsec-"]'
	)
	if (existing) {
		existing.replaceWith(interstitialForm)
	} else {
		loginFormEl.insertAdjacentElement('beforebegin', interstitialForm)
	}

	interstitialForm.addEventListener('submit', (e) => {
		e.preventDefault()

		maybeAddLoadingState(interstitialForm)

		// Use getAttribute() rather than .action / .method to avoid a Chrome
		// quirk: when a form has a child input named "action", the HTMLFormElement
		// named-property getter shadows the IDL attribute, returning the
		// HTMLInputElement instead of the URL string.
		const actionUrl = interstitialForm.getAttribute('action')
		const actionMethod = interstitialForm.getAttribute('method') || 'post'

		fetch(actionUrl, {
			method: actionMethod,
			body: new FormData(interstitialForm)
		})
			.then((response) => {
				// Successful interstitial completion → Kadence redirects the user.
				if (response.redirected && response.url) {
					maybeCleanupLoadingState(interstitialForm)
					setTimeout(() => {
						location = response.url
					}, 500)
					return null
				}

				return response.text()
			})
			.then((html) => {
				if (html === null) {
					return
				}

				maybeCleanupLoadingState(interstitialForm)

				const parser = new DOMParser()
				const responseDoc = parser.parseFromString(html, 'text/html')

				// Surface any error message from the interstitial response.
				const maybeError = responseDoc.querySelector(
					'#login_error, #login-error'
				)

				if (maybeError) {
					actuallyInsertError(
						loginFormEl.closest('.ct-login-form'),
						maybeError.innerHTML
					)
				}

				// Re-mount the next (or same) interstitial step if present.
				if (
					!maybeMountKadenceInterstitialForm(
						loginFormEl,
						responseDoc
					) &&
					!maybeError
				) {
					// No more interstitial steps and no error – assume success.
					location.reload()
				}
			})
	})

	return true
}
