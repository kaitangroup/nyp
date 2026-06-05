export const mount = () => {
	setTimeout(() => {
		ctEvents.trigger('ct:header:responsive-menu:refresh')
	}, 300)
}
