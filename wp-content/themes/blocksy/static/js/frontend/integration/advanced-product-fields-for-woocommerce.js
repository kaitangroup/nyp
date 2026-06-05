import $ from 'jquery'

export const mount = () => {
	if (!window._wapf) {
		return
	}

	window._wapf($)
}
