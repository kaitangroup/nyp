import { useMemo } from '@wordpress/element'

import classnames from 'classnames'

import { useSettings } from '@wordpress/block-editor'

import {
	getColorClassName,
	styleToAttributes,
	getInlineStyles,
	useElementCss,
} from '../components/utils'

export const useBlockSupportsCustom = ({
	// text | image
	fieldType,
	attributes,

	uniqueClass,
}) => {
	const [userPalette, themePalette, defaultPalette] = useSettings(
		'color.palette.custom',
		'color.palette.theme',
		'color.palette.default'
	)

	const settingColors = useMemo(
		() => [
			...(userPalette || []),
			...(themePalette || []),
			...(defaultPalette || []),
		],
		[userPalette, themePalette, defaultPalette]
	)

	const { style } = attributes

	const elementCssData = useElementCss(
		style,
		attributes,
		fieldType,
		uniqueClass
	)

	if (fieldType === 'text') {
		let { backgroundColor, textColor } = attributes

		const backgroundClass = getColorClassName(
			'background-color',
			backgroundColor
		)

		const textClass = getColorClassName('color', textColor)

		const hasBackground = backgroundColor || style?.color?.background

		const hasBackgroundValue = backgroundColor || style?.color?.background

		return {
			className: classnames(textClass, elementCssData.className, {
				[backgroundClass]: !!backgroundClass,
				'has-background': hasBackground,
				'has-text-color': textColor || style?.color?.text,
				'has-link-color': style?.elements?.link?.color,
			}),

			style: getInlineStyles(style),

			css: elementCssData.styles,
		}
	}

	if (fieldType === 'image') {
	}

	return {
		className: classnames(elementCssData.className, {}),
		style: '',
		css: elementCssData.styles,
	}
}
