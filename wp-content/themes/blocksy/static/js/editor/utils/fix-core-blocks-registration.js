export const mountCoreBlocksFix = () => {
	// Register `core/post-title` and `core/query-pagination` blocks to make them
	// available for the ProductCollection block. Otherwise, the block editor will
	// crash when trying to render the inner blocks template of the ProductCollection
	// block.
	document.addEventListener('DOMContentLoaded', () => {
		if (
			!document.body.matches('.widgets-php') &&
			!document.body.matches('.wp-customizer')
		) {
			return
		}

		setTimeout(() => {
			if (window.wp && window.wp.blockLibrary) {
				window.wp.blockLibrary.registerCoreBlocks(
					window.wp.blockLibrary
						.__experimentalGetCoreBlocks()
						.filter(
							(b) =>
								b.name === 'core/post-title' ||
								b.name === 'core/query-pagination'
						)
				)
			}
		}, 1000)
	})
}
