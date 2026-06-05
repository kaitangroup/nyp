import { createElement } from '@wordpress/element'
import _ from 'underscore'
import { __ } from 'ct-i18n'
import classNames from 'classnames'
import { clamp, round } from './ct-slider'
import { getNumericKeyboardEvents } from '../helpers/getNumericKeyboardEvents'

const TimerNumberInput = ({
	value,
	option,
	option: {
		attr,
		step = 1,
		blockDecimal = true,
		decimalPlaces = 1,
		markAsAutoFor,
	},
	device,
	onChange,
	liftedOptionStateDescriptor,
}) => {
	const { liftedOptionState, setLiftedOptionState } =
		liftedOptionStateDescriptor

	const parsedValue =
		markAsAutoFor && markAsAutoFor.indexOf(device) > -1 ? 'auto' : value

	const min = !option.min && option.min !== 0 ? -Infinity : option.min
	const max = !option.max && option.max !== 0 ? Infinity : option.max

	return (
		<input
			type="number"
			value={
				liftedOptionState && liftedOptionState.isEmptyInput
					? ''
					: parsedValue
			}
			onBlur={(e) => {
				if (e?.nativeEvent?.relatedTarget?.matches('.ct-revert')) {
					return
				}

				setLiftedOptionState({
					isEmptyInput: false,
				})

				if (parseFloat(parsedValue)) {
					onChange(round(clamp(min, max, parsedValue), decimalPlaces))
				}
			}}
			onChange={({ target: { value } }) => {
				if (value.toString().trim() === '') {
					setLiftedOptionState({
						isEmptyInput: true,
					})
					return
				}

				setLiftedOptionState({
					isEmptyInput: false,
				})

				_.isNumber(parseFloat(value))
					? onChange(round(value, decimalPlaces))
					: parseFloat(value)
					? onChange(
							round(
								Math.min(parseFloat(value), max),
								decimalPlaces
							)
					  )
					: onChange(round(value, decimalPlaces))
			}}
			{...getNumericKeyboardEvents({
				blockDecimal,
				value: parsedValue,
				onChange: (value) => {
					onChange(round(clamp(min, max, value), decimalPlaces))
				},
			})}
		/>
	)
}

const TimerOption = ({ onChange, value, liftedOptionStateDescriptor }) => {
	if (typeof value !== 'object') {
		value = {
			days: value,
			hours: 0,
			minutes: 0,
		}
	}

	const { days, hours, minutes } = value

	return (
		<ul className={classNames('ct-option-time-picker', {})}>
			<li>
				<TimerNumberInput
					liftedOptionStateDescriptor={liftedOptionStateDescriptor}
					option={{
						min: 0,
						max: 100,
					}}
					value={days}
					onChange={(val) => {
						onChange({
							...value,
							days: val,
						})
					}}
				/>
				<div className="ct-option-description">
					{__('Days', 'blocksy')}
				</div>
			</li>

			<li>
				<TimerNumberInput
					liftedOptionStateDescriptor={liftedOptionStateDescriptor}
					option={{
						min: 0,
						max: 24,
					}}
					value={hours}
					onChange={(val) => {
						onChange({
							...value,
							hours: val,
						})
					}}
				/>
				<div className="ct-option-description">
					{__('Hours', 'blocksy')}
				</div>
			</li>

			<li>
				<TimerNumberInput
					liftedOptionStateDescriptor={liftedOptionStateDescriptor}
					option={{
						min: 0,
						max: 59,
					}}
					value={minutes}
					onChange={(val) => {
						onChange({
							...value,
							minutes: val,
						})
					}}
				/>
				<div className="ct-option-description">
					{__('Minutes', 'blocksy')}
				</div>
			</li>
		</ul>
	)
}

TimerOption.supportedPurposes = ['default', 'gutenberg']

export default TimerOption
