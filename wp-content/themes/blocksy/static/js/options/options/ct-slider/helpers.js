export const hasUnitsList = ({ option }) =>
	option.units && option.units.length > 1

// Pillar of the ct-slider custom unit logic. Without this, we'd have to change
// the whole format of the slider.
export const getCurrentUnit = ({
	explicitCustom = false,

	forced_current_unit,
	option,

	value,
} = {}) => {
	if (forced_current_unit !== '__DEFAULT__') {
		if (explicitCustom) {
			return 'custom'
		}

		return ''
	}

	if (!hasUnitsList({ option })) {
		return ''
	}

	let defaultUnit = option.units ? option.units[0].unit : ''

	if (value === 'NaN' || value === '' || value === 'CT_CSS_SKIP_RULE') {
		return defaultUnit
	}

	let computedUnit = value
		.toString()
		.replace(/[0-9]/g, '')
		.replace(/\-/g, '')
		.replace(/\./g, '')
		.replace('CT_CSS_SKIP_RULE', '')

	let maybeActualUnit = option.units.find(({ unit }) => unit === computedUnit)

	const hasCustomUnit = option.units.find(
		({ unit, type }) => unit === '' && type === 'custom'
	)

	const hasEmptyNonCustomUnit = option.units.find(
		({ unit, type }) => unit === '' && type !== 'custom'
	)

	const isNumericValue = value.toString() === parseFloat(value).toString()

	if (computedUnit === '') {
		if (hasEmptyNonCustomUnit && isNumericValue) {
			return ''
		}

		if (hasCustomUnit) {
			if (explicitCustom) {
				return 'custom'
			}

			return ''
		}
	}

	if (maybeActualUnit) {
		return computedUnit
	}

	if (hasCustomUnit) {
		if (explicitCustom) {
			return 'custom'
		}

		return ''
	}

	return ''
}
