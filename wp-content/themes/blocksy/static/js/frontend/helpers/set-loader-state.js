export const setLoaderState = (container, { enabled }) => {
	const anim = container.querySelector('.ct-ajax-loader animateTransform')

	if (!anim) return

	if (enabled) {
		anim.beginElement()
	} else {
		anim.endElement()
	}
}
