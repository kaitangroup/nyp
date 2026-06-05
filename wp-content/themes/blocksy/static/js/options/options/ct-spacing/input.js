import { createElement, Fragment } from '@wordpress/element'
import InputWithValidCssExpression from '../../components/InputWithValidCssExpression'

import { __ } from 'ct-i18n'

import cls from 'classnames'

import {
	SPACING_STATE_CUSTOM,
	SPACING_STATE_LINKED,
	SPACING_STATE_INDEPENDENT,
} from '../ct-spacing'

import { getNumericKeyboardEvents } from '../../helpers/getNumericKeyboardEvents'

const SpacingInput = ({ value, option, onChange, currentUnit }) => {
	if (value.state === SPACING_STATE_CUSTOM) {
		return (
			<span>
				<InputWithValidCssExpression
					type="text"
					placeholder=""
					value={value.custom}
					onChange={(v) => {
						onChange({
							...value,
							custom: v,
						})
					}}
					{...option.inputAttr}
					shouldPropagateEmptyValue={true}
				/>
			</span>
		)
	}

	const handleChange = (futureValue, args = {}) => {
		args = {
			sideIndex: 0,
			shouldClamp: false,

			...args,
		}

		let clampedValue = futureValue

		if (args.shouldClamp) {
			if (Object.keys(option).includes('min')) {
				clampedValue = Math.max(option.min, clampedValue)
			}

			if (Object.keys(option).includes('max')) {
				clampedValue = Math.min(option.max, clampedValue)
			}
		}

		if (value.state === SPACING_STATE_LINKED) {
			onChange({
				...value,
				values: value.values.map((v, i) => {
					if (v.value === 'auto') {
						return v
					}

					return {
						...v,
						value: clampedValue,
						unit: currentUnit,
					}
				}),
			})

			return
		}

		onChange({
			...value,
			values: value.values.map((v, i) => {
				if (i === args.sideIndex) {
					return {
						...v,
						value: clampedValue,
						unit: currentUnit,
					}
				}

				return v
			}),
		})
	}

	return (
		<Fragment>
			{['top', 'right', 'bottom', 'left'].map((side, index) => (
				<span key={side}>
					<input
						type="number"
						step={1}
						value={
							value.values[index].value === 'auto'
								? ''
								: value.values[index].value
						}
						onChange={({ target: { value: inputValue } }) => {
							handleChange(inputValue, {
								sideIndex: index,
							})
						}}
						onBlur={() => {
							let finalValue = value.values[index].value

							if (finalValue === 'auto' || finalValue === '') {
								return
							}

							handleChange(finalValue, {
								sideIndex: index,
								shouldClamp: true,
							})
						}}
						className={cls({
							inactive: value.values[index].value === 'auto',
						})}
						{...{
							placeholder:
								value.values[index].value === 'auto'
									? 'auto'
									: '',
							...option.inputAttr,
						}}
						{...getNumericKeyboardEvents({
							value: value.values[index].value,
							onChange: (inputValue) => {
								if (value.values[index].value === '') {
									return
								}

								handleChange(inputValue, {
									sideIndex: index,
									shouldClamp: true,
								})
							},
						})}
					/>

					<small>
						{
							{
								top: __('Top', 'blocksy'),
								bottom: __('Bottom', 'blocksy'),
								left: __('Left', 'blocksy'),
								right: __('Right', 'blocksy'),
							}[side]
						}
					</small>
				</span>
			))}
		</Fragment>
	)
}

export default SpacingInput
