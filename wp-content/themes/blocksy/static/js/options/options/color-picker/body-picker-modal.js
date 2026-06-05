import {
	createElement,
	Component,
	createPortal,
	useRef,
	createRef,
} from '@wordpress/element'
import bezierEasing from 'bezier-easing'

import PickerModal from './picker-modal'
import usePopoverMaker from '../../helpers/usePopoverMaker'

import { resolveInherit } from './single-picker'

const BodyPickerModal = ({
	option,
	currentPicker,
	value,
	onChange,

	values,
	device,

	modalRef,

	modalSprings,
}) => {
	const { appendToBody = true } = option

	const { styles, popoverProps } = usePopoverMaker({
		contentRef: modalRef,
		ref: currentPicker.el || {},
		defaultHeight: 379,
		shouldCalculate: !option.inline_modal || appendToBody,
		id: option.id,
	})

	return createPortal(
		<PickerModal
			style={{
				...(appendToBody ? styles : {}),

				...modalSprings,
			}}
			option={option}
			onChange={onChange}
			picker={currentPicker.picker}
			el={currentPicker.el}
			value={value}
			inheritValue={
				currentPicker.picker.inherit
					? resolveInherit(
							currentPicker.picker,
							option,
							values,
							device
					  ).background
					: ''
			}
			wrapperProps={
				appendToBody
					? popoverProps
					: {
							ref: modalRef,
					  }
			}
			appendToBody={appendToBody}
		/>,
		appendToBody
			? document.body
			: currentPicker.el.current.closest('section').parentNode
	)
}

export default BodyPickerModal
