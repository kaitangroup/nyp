export const computeStartPositionForRowConfiguration = (rowConfiguration) => {
	return (
		rowConfiguration

			// Ignore rows with height 0
			.filter((row) => row.height !== 0)

			.reduce((result, row) => {
				if (result.indexOf(0) > -1) {
					return [...result, 0]
				}

				return [...result, row.sticky ? 0 : row.height]
			}, [])
			.reduce((sum, height) => sum + height, 0)
	)
}
