const isRtl = () => document.querySelector('html').dir === 'rtl'

export const transformValueForRtl = (value) => {
	return isRtl() ? value : value * -1
}
