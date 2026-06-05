import {
	createElement,
	Component,
	Fragment,
	createContext,
	useRef,
	useContext,
	useState,
} from '@wordpress/element'
import SinglePicker from './color-picker/single-picker'
import { normalizeCondition, matchValuesWithCondition } from 'match-conditions'

import BodyPickerModal from './color-picker/body-picker-modal'

import { useSpringModal } from '../helpers/useSpringModal'

const ColorPicker = ({
	option,
	values,
	value: internalValue,
	onChange: onInternalChange,
	device,
}) => {
	const value = internalValue
	const onChange = onInternalChange

	const [currentPicker, setCurrentPicker] = useState({
		picker: null,
		el: {
			current: null,
		},
	})

	const { modalOpen, modalStyles, openModal, closeModal } = useSpringModal({
		onClosed: () => {
			setCurrentPicker({
				picker: null,
				el: {
					current: null,
				},
			})
		},
	})

	const containerRef = useRef()
	const modalRef = useRef()

	return (
		<Fragment>
			<div ref={containerRef} className="ct-color-picker-container">
				{option.pickers
					.filter(
						(picker) =>
							!picker.condition ||
							matchValuesWithCondition(
								normalizeCondition(picker.condition),
								picker.condition_source === 'global'
									? Object.keys(picker.condition).reduce(
											(current, key) => ({
												...current,
												[key]: wp.customize(key)(),
											}),
											{}
									  )
									: values
							)
					)
					.map((picker) => (
						<SinglePicker
							containerRef={containerRef}
							device={device}
							picker={picker}
							key={picker.id}
							option={option}
							modalRef={modalRef}
							values={values}
							modalOpen={modalOpen}
							onOutsideClick={() => {
								closeModal()
							}}
							onPickingChange={(el) => {
								if (
									currentPicker.picker &&
									currentPicker.picker.id === picker.id
								) {
									closeModal()
									return
								}

								setCurrentPicker({
									picker,
									el,
								})

								openModal()
							}}
							onChange={(newPicker) => {
								onChange({
									...value,
									[picker.id]: newPicker,
								})
							}}
							value={value[picker.id] || option.value[picker.id]}
						/>
					))}
			</div>

			{modalOpen && (
				<BodyPickerModal
					option={option}
					currentPicker={currentPicker}
					values={values}
					value={
						currentPicker.picker
							? value[currentPicker.picker.id] ||
							  option.value[currentPicker.picker.id]
							: null
					}
					device={device}
					modalRef={modalRef}
					onChange={(newPicker) => {
						onChange({
							...value,
							[currentPicker.picker.id]: newPicker,
						})
					}}
					modalSprings={modalStyles}
				/>
			)}
		</Fragment>
	)
}

export default ColorPicker
