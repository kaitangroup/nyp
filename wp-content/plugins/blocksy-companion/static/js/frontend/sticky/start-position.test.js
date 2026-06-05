import { computeStartPositionForRowConfiguration } from './start-position'

describe('computeStartPositionForRowConfiguration', () => {
	test('should compute start position for row configuration', () => {
		const rowConfiguration = [
			{ sticky: true },
			{ height: 50 },
			{ height: 50 },
		]

		expect(computeStartPositionForRowConfiguration(rowConfiguration)).toBe(
			0
		)

		expect(
			computeStartPositionForRowConfiguration([
				{ height: 50 },
				{ sticky: true },
				{ height: 50 },
			])
		).toBe(50)

		expect(
			computeStartPositionForRowConfiguration([
				{ height: 50 },
				{ height: 50 },
				{ sticky: true },
			])
		).toBe(100)

		expect(
			computeStartPositionForRowConfiguration([
				{ sticky: true },
				{ height: 50 },
			])
		).toBe(0)
	})

	// https://github.com/Creative-Themes/blocksy/issues/4632
	test('should handle rows with height 0 that come before the sticky row and also have some row with real height right after', () => {
		expect(
			computeStartPositionForRowConfiguration([
				// Top row has 0 height
				{ height: 0 },

				// Middle row has some height
				{ height: 50 },

				// Bottom row is sticky
				{ sticky: true },
			])
		).toBe(50)
	})
})
