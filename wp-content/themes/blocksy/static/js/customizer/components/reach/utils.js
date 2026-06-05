let checkStyles = () => {}

export { checkStyles }

export let wrapEvent = (theirHandler, ourHandler) => (event) => {
	theirHandler && theirHandler(event)
	if (!event.defaultPrevented) {
		return ourHandler(event)
	}
}
