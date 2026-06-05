import { createElement, useState, useRef } from '@wordpress/element'
import OutsideClickHandler from './react-outside-click-handler'
import classnames from 'classnames'
import SingleColorPicker from './color-picker/single-picker'
import { __ } from 'ct-i18n'
import BoxShadowModal from './box-shadow/modal'

import { useSpringModal } from '../helpers/useSpringModal'

const BoxShadow = ({ value, option, onChange }) => {
	// null | color | opts
	const [currentView, setCurrentView] = useState(null)

	const [colorPickerEl, setColorPickerEl] = useState(null)

	const { modalOpen, modalStyles, openModal, closeModal } = useSpringModal({
		onClosed: () => {
			setCurrentView(null)
			setColorPickerEl(null)
		},
	})

	const el = useRef()
	const colorPicker = useRef()

	const hOffsetRef = useRef()
	const vOffsetRef = useRef()
	const blurRef = useRef()
	const spreadRef = useRef()

	const containerRef = useRef()
	const modalRef = useRef()

	return (
		<div ref={el} className={classnames('ct-box-shadow')}>
			<OutsideClickHandler
				useCapture={false}
				disabled={!modalOpen}
				className="ct-box-shadow-values"
				additionalRefs={[colorPicker, modalRef]}
				onOutsideClick={(e) => {
					closeModal()
				}}
				wrapperProps={{
					ref: containerRef,
					onClick: (e) => {
						e.preventDefault()

						if (modalOpen) {
							closeModal()
							return
						}

						setCurrentView('opts')

						openModal()
					},
				}}>
				<span>
					{value.inherit
						? __('Inherit', 'blocksy')
						: value.enable
						? __('Adjust', 'blocksy')
						: __('None', 'blocksy')}
				</span>
			</OutsideClickHandler>

			{value.enable && !value.inherit && (
				<SingleColorPicker
					innerRef={colorPicker}
					picker={{
						id: 'default',
						title: 'Initial',
					}}
					option={{
						pickers: [
							{
								id: 'default',
								title: 'Initial',
							},
						],
					}}
					modalRef={modalRef}
					containerRef={containerRef}
					onPickingChange={(el) => {
						if (!value.enable) {
							return
						}

						if (modalOpen) {
							closeModal()
							return
						}

						setCurrentView('color')
						setColorPickerEl(el)

						openModal()
					}}
					onChange={(colorValue) =>
						onChange({
							...value,
							color: colorValue,
						})
					}
					value={value.color}
				/>
			)}

			{modalOpen && (
				<BoxShadowModal
					currentView={currentView}
					modalStyles={modalStyles}
					option={option}
					value={value}
					onChange={onChange}
					modalRef={modalRef}
					el={el}
					colorPickerEl={colorPickerEl}
					colorPicker={{
						id: 'default',
						title: 'Initial',
					}}
				/>
			)}
		</div>
	)
}

export default BoxShadow
