import { createElement, createPortal } from '@wordpress/element'

import { __ } from 'ct-i18n'

import usePopoverMaker from '../../helpers/usePopoverMaker'
import { animated } from 'react-spring'

import BoxShadowOptions from './options'
import PickerModal from '../color-picker/picker-modal'

const BoxShadowModal = (props) => {
	const {
		currentView,

		modalStyles,

		option,
		value,
		onChange,

		modalRef,
		el,
		colorPickerEl,

		colorPicker,
	} = props

	const { styles, popoverProps } = usePopoverMaker({
		contentRef: modalRef,
		ref: el,
		defaultHeight: !option.hide_shadow_placement ? 507 : 437,
	})

	let view = null

	if (currentView === 'opts') {
		view = (
			<BoxShadowOptions
				{...props}
				onChange={(newValue) => {
					onChange({
						...newValue,
						inherit: false
					})
				}}
				style={{
					...styles,

					...modalStyles,
				}}
				popoverProps={popoverProps}
			/>
		)
	}

	if (currentView === 'color') {
		view = (
			<PickerModal
				style={{
					...styles,
					...modalStyles,
				}}
				option={{
					pickers: [colorPicker],
				}}
				onChange={(colorValue) => {
					onChange({
						...value,
						color: colorValue,
					})
				}}
				picker={colorPicker}
				el={colorPickerEl}
				value={value.color}
				wrapperProps={popoverProps}
				appendToBody={true}
			/>
		)
	}

	return createPortal(view, document.body)
}

export default BoxShadowModal
