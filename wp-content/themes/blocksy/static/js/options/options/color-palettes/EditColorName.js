import { useState, createElement, useRef, Fragment } from '@wordpress/element'
import { __, sprintf } from 'ct-i18n'

const EditColorName = ({ picker, currentPalette, onChange }) => {
	const [localValue, setLocalValue] = useState('__DEFAULT__')

	const { id, [picker.id]: currentColor, ...colors } = currentPalette

	const currentValue =
		localValue === '__DEFAULT__'
			? currentPalette[picker.id].title ||
			  sprintf(__('Color %s', 'blocksy'), picker.id.replace('color', ''))
			: localValue

	return (
		<input
			type="text"
			value={currentValue}
			onFocus={(e) => {
				e.target.select()
			}}
			onChange={(e) => {
				onChange('color', {
					...currentPalette,
					[picker.id]: {
						...currentPalette[picker.id],
						title: e.target.value,
					},
				})
			}}
		/>
	)
}

export default EditColorName
