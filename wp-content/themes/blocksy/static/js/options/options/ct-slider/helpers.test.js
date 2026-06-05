import { getCurrentUnit } from './helpers'

const DEFAULT_FORCED_UNIT = '__DEFAULT__'

const optionWithCustomOnly = {
	id: 'size',
	label: 'Font size',
	type: 'ct-slider',
	value: '15px',
	responsive: true,
	units: [
		{
			unit: 'px',
			min: 0,
			max: 200,
		},

		{
			unit: 'em',
			min: 0,
			max: 50,
		},

		{
			unit: 'rem',
			min: 0,
			max: 50,
		},

		{
			unit: 'pt',
			min: 0,
			max: 50,
		},

		{
			unit: 'vw',
			min: 0,
			max: 100,
		},

		{
			unit: '',
			type: 'custom',
		},
	],
}

const optionWithCustomAndEmpty = {
	id: 'line-height',
	label: 'Line height',
	type: 'ct-slider',
	value: '15px',
	responsive: true,
	units: [
		{
			unit: '',
			min: 0,
			max: 10,
		},

		{
			unit: 'px',
			min: 0,
			max: 100,
		},

		{
			unit: 'em',
			min: 0,
			max: 100,
		},

		{
			unit: 'pt',
			min: 0,
			max: 100,
		},

		{
			unit: '%',
			min: 0,
			max: 100,
		},

		{
			unit: '',
			type: 'custom',
		},
	],
}

test('it properly retrieves the current unit', () => {
	expect(
		getCurrentUnit({
			option: optionWithCustomOnly,
			value: '15px',
			forced_current_unit: DEFAULT_FORCED_UNIT,
		})
	).toBe('px')

	expect(
		getCurrentUnit({
			option: optionWithCustomOnly,
			value: '15',
			forced_current_unit: DEFAULT_FORCED_UNIT,
		})
	).toBe('')

	expect(
		getCurrentUnit({
			option: optionWithCustomOnly,
			value: '15as',
			forced_current_unit: DEFAULT_FORCED_UNIT,
		})
	).toBe('')

	expect(
		getCurrentUnit({
			option: optionWithCustomOnly,
			value: '15',
			forced_current_unit: DEFAULT_FORCED_UNIT,
			explicitCustom: true,
		})
	).toBe('custom')

	expect(
		getCurrentUnit({
			option: optionWithCustomOnly,
			value: '15as',
			forced_current_unit: DEFAULT_FORCED_UNIT,
			explicitCustom: true,
		})
	).toBe('custom')

	expect(
		getCurrentUnit({
			option: optionWithCustomOnly,
			value: 'var(--step-3)',
			forced_current_unit: DEFAULT_FORCED_UNIT,
			explicitCustom: true,
		})
	).toBe('custom')

	expect(
		getCurrentUnit({
			option: optionWithCustomOnly,
			value: 'var(--step-3)',
			forced_current_unit: DEFAULT_FORCED_UNIT,
		})
	).toBe('')
})

test('it properly retrieve custom for option with both empty unit and custom', () => {
	expect(
		getCurrentUnit({
			option: optionWithCustomAndEmpty,
			value: 'var(--step-3)',
			forced_current_unit: DEFAULT_FORCED_UNIT,
			explicitCustom: true,
		})
	).toBe('custom')

	expect(
		getCurrentUnit({
			option: optionWithCustomAndEmpty,
			value: 'var(--step-3)',
			forced_current_unit: DEFAULT_FORCED_UNIT,
		})
	).toBe('')

	expect(
		getCurrentUnit({
			option: optionWithCustomAndEmpty,
			value: '9p',
			forced_current_unit: DEFAULT_FORCED_UNIT,
			explicitCustom: true,
		})
	).toBe('custom')
})
