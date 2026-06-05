import { useState, createElement, useMemo } from '@wordpress/element'
import { ColorPicker } from '@wordpress/components'
import { __ } from 'ct-i18n'
import classnames from 'classnames'

import { normalizeColor } from '../../helpers/normalize-color'
import InputWithValidCssExpression from '../../components/InputWithValidCssExpression'

import { CopyToClipboard } from 'react-copy-to-clipboard'
import { getComputedStyleValue } from './utils'

const ColorPickerIris = ({
	option,
	picker,
	onChange,
	value,
	value: { color, ...rest },
}) => {
	const [copied, setCopied] = useState(false)

	// The ColorPicker component from WordPress is not a real controlled
	// component, as it also persists some state inside. We should not try to
	// feed new colors into it while dragging, as it will really
	// confuse the component, because of the debounce inside it has, and likely
	// also some race condition is going on there.
	const [staticColor, setStaticColor] = useState(null)
	const [timeoutId, setTimeoutId] = useState(null)

	const calculatedColor = useMemo(() => getComputedStyleValue(color), [color])

	return (
		<div className="ct-gutenberg-color-picker">
			<ColorPicker
				color={staticColor !== null ? staticColor : calculatedColor}
				enableAlpha
				onChange={(color) => {
					onChange({ ...value, color: normalizeColor(color) })
				}}
				onMouseDown={() => {
					setStaticColor(calculatedColor)

					if (timeoutId) {
						clearTimeout(timeoutId)
						setTimeoutId(null)
					}
				}}
				onMouseUp={() => {
					setStaticColor(null)

					if (timeoutId) {
						clearTimeout(timeoutId)
					}

					setTimeoutId(
						setTimeout(() => {
							setStaticColor(null)
						}, 50)
					)
				}}
			/>

			{option.colorName && option.colorName({ picker })}

			<div className="ct-option-color-value">
				<InputWithValidCssExpression
					value={normalizeColor(color)}
					onChange={(color) => {
						onChange({ ...value, color: normalizeColor(color) })
					}}
					propertyToCheckAgainst="color"
				/>

				<CopyToClipboard
					text={normalizeColor(color)}
					onCopy={() => {
						setCopied(true)

						setTimeout(() => {
							setCopied(null)
						}, 3000)
					}}>
					<span
						className={classnames('ct-copy-color', {
							copied,
						})}
						data-tooltip-reveal="top">
						<svg
							width="12"
							height="12"
							fill="currentColor"
							viewBox="0 0 24 24">
							<path d="M20.7 7.6h-9.8c-1.8 0-3.3 1.5-3.3 3.3v9.8c0 1.8 1.5 3.3 3.3 3.3h9.8c1.8 0 3.3-1.5 3.3-3.3v-9.8c0-1.8-1.5-3.3-3.3-3.3zm1.1 13.1c0 .6-.5 1.1-1.1 1.1h-9.8c-.6 0-1.1-.5-1.1-1.1v-9.8c0-.6.5-1.1 1.1-1.1h9.8c.6 0 1.1.5 1.1 1.1v9.8zM5.5 15.3c0 .6-.5 1.1-1.1 1.1H3.3c-1.8 0-3.3-1.5-3.3-3.3V3.3C0 1.5 1.5 0 3.3 0h9.8c1.8 0 3.3 1.5 3.3 3.3v1.1c0 .6-.5 1.1-1.1 1.1-.6 0-1.1-.5-1.1-1.1V3.3c0-.6-.5-1.1-1.1-1.1H3.3c-.6 0-1.1.5-1.1 1.1v9.8c0 .6.5 1.1 1.1 1.1h1.1c.6 0 1.1.5 1.1 1.1z" />
						</svg>
						<i className="ct-tooltip">
							{copied
								? __('Copied', 'blocksy')
								: __('Copy', 'blocksy')}
						</i>
					</span>
				</CopyToClipboard>
			</div>

			{option.colorVariableName && option.colorVariableName({ picker })}
		</div>
	)
}

export default ColorPickerIris
