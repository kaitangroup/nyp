import {
	createElement,
	Component,
	createPortal,
	useRef,
	createRef,
} from '@wordpress/element'
import PickerModal, { getNoColorPropFor } from './picker-modal'
import classnames from 'classnames'
import { __ } from 'ct-i18n'
import { normalizeCondition, matchValuesWithCondition } from 'match-conditions'

import usePopoverMaker from '../../helpers/usePopoverMaker'
import { getComputedStyleValue } from './utils'

import OutsideClickHandler from '../react-outside-click-handler'

const computePickerTitle = ({ picker, value, values }) => {
	if ((value || { color: '' }).color.indexOf('INHERIT') > -1) {
		return __('Inherited', 'blocksy')
	}

	if (picker.title !== picker.title.toString()) {
		return (
			Object.keys(picker.title).reduce((approvedTitle, currentTitle) => {
				if (approvedTitle) {
					return approvedTitle
				}

				if (
					matchValuesWithCondition(
						normalizeCondition(picker.title[currentTitle]),
						values
					)
				) {
					return currentTitle
				}

				return approvedTitle
			}, null) || Object.keys(picker.title)[0]
		)
	}

	return picker.title
}

export const resolveInherit = (picker, option, values, device) => {
	if (typeof picker.inherit === 'string') {
		if (picker.inherit.indexOf('self') > -1) {
			const currentValue =
				(option.responsive
					? values[option.id][device]
					: values[option.id]) || option.value
			const pickerToInheritFrom = picker.inherit.split(':')[1]

			let maybeNextValue =
				currentValue[pickerToInheritFrom]?.color || 'CT_CSS_SKIP_RULE'

			if (maybeNextValue.indexOf('CT_CSS_SKIP_RULE') > -1) {
				maybeNextValue = option.pickers.find(
					({ id }) => id === pickerToInheritFrom
				).inherit
			}

			return {
				background: maybeNextValue || '',
			}
		}

		return { background: picker.inherit }
	}

	let background = Object.keys(picker.inherit).reduce(
		(maybeResult, currentVar) => {
			if (maybeResult) {
				return maybeResult
			}

			if (
				matchValuesWithCondition(
					normalizeCondition(picker.inherit[currentVar]),
					picker.inherit_source === 'global'
						? Object.keys(picker.inherit[currentVar]).reduce(
								(current, key) => ({
									...current,
									[key]: wp.customize(key)(),
								}),
								{}
						  )
						: values
				)
			) {
				return currentVar
			}

			return maybeResult
		},
		null
	)

	if (!background) {
		return {}
	}

	return {
		background,
	}
}

const SinglePicker = ({
	option,
	value,
	onChange,
	picker,

	onOutsideClick,

	innerRef,

	containerRef,
	modalRef,

	onPickingChange,
	modalOpen,

	values,

	device,
}) => {
	const el = useRef()

	if (option.inline_modal) {
		return (
			<PickerModal
				containerRef={containerRef}
				option={option}
				onChange={onChange}
				picker={picker}
				value={value}
				inline_modal={!!option.inline_modal}
			/>
		)
	}

	return (
		<OutsideClickHandler
			useCapture={false}
			display="inline-block"
			disabled={!modalOpen}
			wrapperProps={{
				ref: (instance) => {
					el.current = instance

					if (innerRef) {
						innerRef.current = instance
					}
				},
			}}
			additionalRefs={[modalRef]}
			onOutsideClick={(e) => {
				if (
					(el.current.closest('.ct-color-picker-container') ===
						e.target.closest('.ct-color-picker-container') &&
						(e.target.closest('.ct-color-picker-single') ||
							e.target.matches('.ct-color-picker-single'))) ||
					el.current.closest('.ct-modal-tabs-content') === e.target
				) {
					return
				}

				onOutsideClick(e)
			}}
			className={classnames('ct-color-picker-single', {})}>
			<span tabIndex="0">
				<span
					tabIndex="0"
					className={classnames({
						[`ct-no-color`]:
							(value || {}).color === getNoColorPropFor(option),

						[`ct-color-inherit`]:
							(value || { color: '' }).color.indexOf('INHERIT') >
							-1,
					})}
					onClick={(e) => {
						if (option.skipModal) {
							return
						}

						e.stopPropagation()

						onPickingChange(el)
					}}
					data-tooltip-reveal="top"
					style={
						((value || {}).color || '').indexOf(
							getNoColorPropFor(option)
						) === -1
							? {
									background: getComputedStyleValue(
										(value || {}).color
									),
							  }
							: {
									...(picker.inherit &&
									(value || {}).color !==
										getNoColorPropFor(option)
										? resolveInherit(
												picker,
												option,
												values,
												device
										  )
										: {}),
							  }
					}>
					<i className="ct-tooltip">
						{computePickerTitle({ picker, value, values })}
					</i>

					{(value || { color: '' }).color.indexOf('INHERIT') > -1 && (
						<svg width="25" height="25" viewBox="0 0 30 30">
							<path d="M15 3c-3 0-5.7 1.1-7.8 2.9-.4.3-.5.9-.2 1.4.3.4 1 .5 1.4.2h.1C10.3 5.9 12.5 5 15 5c5.2 0 9.5 3.9 10 9h-3l4 6 4-6h-3.1C26.4 7.9 21.3 3 15 3zM4 10l-4 6h3.1c.5 6.1 5.6 11 11.9 11 3 0 5.7-1.1 7.8-2.9.4-.3.5-1 .2-1.4-.3-.4-1-.5-1.4-.2h-.1c-1.7 1.5-4 2.4-6.5 2.4-5.2 0-9.5-3.9-10-9h3L4 10z" />
						</svg>
					)}
				</span>
			</span>

			{option.afterPill &&
				option.afterPill({
					picker,
				})}
		</OutsideClickHandler>
	)
}

export default SinglePicker
