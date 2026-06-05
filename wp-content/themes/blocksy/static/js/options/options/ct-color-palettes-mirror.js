import {
	createContext,
	useContext,
	createElement,
	createPortal,
	useRef,
	Fragment,
	useState,
	useEffect,
} from '@wordpress/element'
import OptionsPanel from '../OptionsPanel'
import { __ } from 'ct-i18n'
import classnames from 'classnames'
import PalettePreview from './color-palettes/PalettePreview'
import ColorPalettesModal from './color-palettes/ColorPalettesModal'

import usePopoverMaker from '../helpers/usePopoverMaker'
import OutsideClickHandler from './react-outside-click-handler'
import bezierEasing from 'bezier-easing'

import Overlay from '../../customizer/components/Overlay'

import { Dropdown } from '@wordpress/components'
import { ColorPalettesContext } from './ct-color-palettes-picker'

import storeName, { getStore } from './color-palettes/store'
import { useSelect, useDispatch } from '@wordpress/data'

const ColorPalettesMirror = ({ option, value, values, onChange }) => {
	const { isEditingPalettes, setIsEditingPalettes, customPalettes } =
		useContext(ColorPalettesContext)
	const colorPalettesWrapper = useRef()

	// Dont persist the palettes in the database.
	const { palettes, current_palette, ...properValue } = value

	const computedValue = Object.keys(values.colorPalette).reduce(
		(finalValue, currentId) => ({
			...finalValue,
			...(currentId.indexOf('color') === 0
				? {
						[currentId]: value[currentId]
							? value[currentId]
							: values.colorPalette[currentId],
				  }
				: {}),
		}),
		{}
	)

	return (
		<div className="ct-color-palette-preview">
			<PalettePreview
				currentPalette={computedValue}
				option={option}
				onChange={(optionId, optionValue) => {
					onChange(optionValue)
				}}
			/>

			<Overlay
				items={isEditingPalettes}
				className={classnames(
					'ct-admin-modal ct-color-palettes-modal',
					{
						'ct-no-tabs': (customPalettes || []).length === 0,
						'ct-has-tabs': (customPalettes || []).length > 0,
					}
				)}
				onDismiss={() => setIsEditingPalettes(false)}
				render={() => (
					<ColorPalettesModal
						onChange={(value) => {
							onChange(value)
						}}
						setIsEditingPalettes={setIsEditingPalettes}
						value={properValue}
						option={option}
					/>
				)}
			/>
		</div>
	)
}

ColorPalettesMirror.MetaWrapper = ({ getActualOption }) => {
	const [isEditingPalettes, setIsEditingPalettes] = useState(false)

	// Initialize the store lazily
	const store = getStore()

	const { fetchCustomPalettes, syncCustomPalettes } = store
		? useDispatch(storeName)
		: { fetchCustomPalettes: () => {}, syncCustomPalettes: () => {} }

	const { customPalettes } = store
		? useSelect((select) => {
				const s = select(storeName)
				return {
					customPalettes: s.getCustomPalettes(),
				}
		  }, [])
		: { customPalettes: [] }

	useEffect(() => {
		if (store && fetchCustomPalettes) {
			fetchCustomPalettes()
		}
	}, [store])

	return (
		<ColorPalettesContext.Provider
			value={{
				customPalettes,
				setCustomPalettes: syncCustomPalettes,
				isEditingPalettes,
				setIsEditingPalettes,
			}}>
			{getActualOption()}
		</ColorPalettesContext.Provider>
	)
}

ColorPalettesMirror.LabelToolbar = ({ option, value, onChange }) => {
	const { setIsEditingPalettes, customPalettes, setCustomPalettes } =
		useContext(ColorPalettesContext)

	if (!option.palettes) {
		return null
	}

	const canSave = ![...option.palettes, ...(customPalettes || [])].find(
		(palette) => {
			const actualColors = Object.keys(value).reduce(
				(finalValue, currentId) => ({
					...finalValue,
					...(currentId.indexOf('color') === 0
						? {
								[currentId]: value[currentId].color,
						  }
						: {}),
				}),
				{}
			)

			const paletteColors = Object.keys(palette).reduce(
				(finalValue, currentId) => ({
					...finalValue,
					...(currentId.indexOf('color') === 0
						? {
								[currentId]: palette[currentId].color,
						  }
						: {}),
				}),
				{}
			)

			if (
				Object.keys(actualColors).length !==
				Object.keys(paletteColors).length
			) {
				return false
			}

			return Object.keys(actualColors).every((key) => {
				return actualColors[key] === paletteColors[key]
			})
		}
	)

	return (
		<Fragment>
			<Dropdown
				contentClassName="ct-options-popover"
				popoverProps={{ placement: 'bottom-start', offset: 3 }}
				renderToggle={({ isOpen, onToggle }) => (
					<span
						className="ct-more-options-trigger"
						data-tooltip-reveal="top">
						<button
							className="components-button components-dropdown-menu__toggle is-small has-icon"
							onClick={(e) => {
								e.preventDefault()
								onToggle()
							}}>
							<svg
								viewBox="0 0 24 24"
								width="24"
								height="24"
								fill="currentColor">
								<path d="M13 19h-2v-2h2v2zm0-6h-2v-2h2v2zm0-6h-2V5h2v2z"></path>
							</svg>
						</button>

						<i className="ct-tooltip">
							{__('Advanced', 'blocksy')}
						</i>
					</span>
				)}
				renderContent={({ onClose }) => (
					<div className="components-dropdown-menu__menu">
						<div className="components-menu-group">
							<button
								className="components-button components-menu-item__button"
								onClick={(e) => {
									e.preventDefault()
									setIsEditingPalettes(true)
									onClose()
								}}>
								<span className="components-menu-item__item">
									{__('Color Palettes', 'blocksy')}
								</span>
							</button>

							<button
								className="components-button components-menu-item__button"
								disabled={!canSave}
								onClick={(e) => {
									e.preventDefault()
									onClose()

									// Dont persist the palettes in the database.
									const {
										palettes,
										current_palette,
										...properValue
									} = value

									setCustomPalettes([
										...customPalettes,
										properValue,
									])
								}}>
								<span className="components-menu-item__item">
									{__('Save Palette', 'blocksy')}
								</span>
							</button>
						</div>
					</div>
				)}
			/>
		</Fragment>
	)
}

export default ColorPalettesMirror
