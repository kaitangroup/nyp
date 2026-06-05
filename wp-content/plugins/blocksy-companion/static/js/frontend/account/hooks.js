import {
	maybeApplyDefenderCaptcha,
	maybeApplyWordfenceCaptcha,
} from './captcha'

export const formPreSubmitHook = (form) =>
	new Promise((res) => {
		if (maybeApplyWordfenceCaptcha(res, form)) {
			return
		}

		if (maybeApplyDefenderCaptcha(res, form)) {
			return
		}

		res()
	})
