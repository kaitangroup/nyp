import { useMemo } from '@wordpress/element'
import { useSettings } from '@wordpress/block-editor'
import { _x } from 'ct-i18n'
import { paramCase } from 'change-case'
import { useInstanceId } from '@wordpress/compose'

import { getCSSRules } from '@wordpress/style-engine'

export function compileCSS(style, opts = {}) {
	const { additionalRules, ...options } = opts

	const rules = [...getCSSRules(style, options), ...(additionalRules || [])]

	// If no selector is provided, treat generated rules as inline styles to be returned as a single string.
	if (!options?.selector) {
		const inlineRules = []

		rules.forEach((rule) => {
			inlineRules.push(`${kebabCase(rule.key)}: ${rule.value};`)
		})

		return inlineRules.join(' ')
	}

	const groupedRules = rules.reduce((acc, rule) => {
		const { selector } = rule

		if (!selector) {
			return acc
		}

		if (!acc[selector]) {
			acc[selector] = []
		}

		acc[selector].push(rule)

		return acc
	}, {})

	const selectorRules = Object.keys(groupedRules).reduce(
		(acc, subSelector) => {
			acc.push(
				`${subSelector} { ${groupedRules[subSelector]
					.map((rule) => `${kebabCase(rule.key)}: ${rule.value};`)
					.join(' ')} }`
			)
			return acc
		},
		[]
	)

	return selectorRules.join('\n')
}

export function useBlockSettings(name, parentLayout) {
	const [
		backgroundImage,
		backgroundSize,
		customFontFamilies,
		defaultFontFamilies,
		themeFontFamilies,
		defaultFontSizesEnabled,
		customFontSizes,
		defaultFontSizes,
		themeFontSizes,
		customFontSize,
		fontStyle,
		fontWeight,
		lineHeight,
		textAlign,
		textColumns,
		textDecoration,
		writingMode,
		textTransform,
		letterSpacing,
		padding,
		margin,
		blockGap,
		defaultSpacingSizesEnabled,
		customSpacingSize,
		userSpacingSizes,
		defaultSpacingSizes,
		themeSpacingSizes,
		units,
		aspectRatio,
		minHeight,
		layout,
		borderColor,
		borderRadius,
		borderStyle,
		borderWidth,
		customColorsEnabled,
		customColors,
		customDuotone,
		themeColors,
		defaultColors,
		defaultPalette,
		defaultDuotone,
		userDuotonePalette,
		themeDuotonePalette,
		defaultDuotonePalette,
		userGradientPalette,
		themeGradientPalette,
		defaultGradientPalette,
		defaultGradients,
		areCustomGradientsEnabled,
		isBackgroundEnabled,
		isLinkEnabled,
		isTextEnabled,
		isHeadingEnabled,
		isButtonEnabled,
		shadow,
	] = useSettings(
		'background.backgroundImage',
		'background.backgroundSize',
		'typography.fontFamilies.custom',
		'typography.fontFamilies.default',
		'typography.fontFamilies.theme',
		'typography.defaultFontSizes',
		'typography.fontSizes.custom',
		'typography.fontSizes.default',
		'typography.fontSizes.theme',
		'typography.customFontSize',
		'typography.fontStyle',
		'typography.fontWeight',
		'typography.lineHeight',
		'typography.textAlign',
		'typography.textColumns',
		'typography.textDecoration',
		'typography.writingMode',
		'typography.textTransform',
		'typography.letterSpacing',
		'spacing.padding',
		'spacing.margin',
		'spacing.blockGap',
		'spacing.defaultSpacingSizes',
		'spacing.customSpacingSize',
		'spacing.spacingSizes.custom',
		'spacing.spacingSizes.default',
		'spacing.spacingSizes.theme',
		'spacing.units',
		'dimensions.aspectRatio',
		'dimensions.minHeight',
		'layout',
		'border.color',
		'border.radius',
		'border.style',
		'border.width',
		'color.custom',
		'color.palette.custom',
		'color.customDuotone',
		'color.palette.theme',
		'color.palette.default',
		'color.defaultPalette',
		'color.defaultDuotone',
		'color.duotone.custom',
		'color.duotone.theme',
		'color.duotone.default',
		'color.gradients.custom',
		'color.gradients.theme',
		'color.gradients.default',
		'color.defaultGradients',
		'color.customGradient',
		'color.background',
		'color.link',
		'color.text',
		'color.heading',
		'color.button',
		'shadow'
	)

	const rawSettings = useMemo(() => {
		return {
			background: {
				backgroundImage,
				backgroundSize,
			},
			color: {
				palette: {
					custom: customColors,
					theme: themeColors,
					default: defaultColors,
				},
				gradients: {
					custom: userGradientPalette,
					theme: themeGradientPalette,
					default: defaultGradientPalette,
				},
				duotone: {
					custom: userDuotonePalette,
					theme: themeDuotonePalette,
					default: defaultDuotonePalette,
				},
				defaultGradients,
				defaultPalette,
				defaultDuotone,
				custom: customColorsEnabled,
				customGradient: areCustomGradientsEnabled,
				customDuotone,
				background: isBackgroundEnabled,
				link: isLinkEnabled,
				heading: isHeadingEnabled,
				button: isButtonEnabled,
				text: isTextEnabled,
			},
			typography: {
				fontFamilies: {
					custom: customFontFamilies,
					default: defaultFontFamilies,
					theme: themeFontFamilies,
				},
				fontSizes: {
					custom: customFontSizes,
					default: defaultFontSizes,
					theme: themeFontSizes,
				},
				customFontSize,
				defaultFontSizes: defaultFontSizesEnabled,
				fontStyle,
				fontWeight,
				lineHeight,
				textAlign,
				textColumns,
				textDecoration,
				textTransform,
				letterSpacing,
				writingMode,
			},
			spacing: {
				spacingSizes: {
					custom: userSpacingSizes,
					default: defaultSpacingSizes,
					theme: themeSpacingSizes,
				},
				customSpacingSize,
				defaultSpacingSizes: defaultSpacingSizesEnabled,
				padding,
				margin,
				blockGap,
				units,
			},
			border: {
				color: borderColor,
				radius: borderRadius,
				style: borderStyle,
				width: borderWidth,
			},
			dimensions: {
				aspectRatio,
				minHeight,
			},
			layout,
			parentLayout,
			shadow,
		}
	}, [
		backgroundImage,
		backgroundSize,
		customFontFamilies,
		defaultFontFamilies,
		themeFontFamilies,
		defaultFontSizesEnabled,
		customFontSizes,
		defaultFontSizes,
		themeFontSizes,
		customFontSize,
		fontStyle,
		fontWeight,
		lineHeight,
		textAlign,
		textColumns,
		textDecoration,
		textTransform,
		letterSpacing,
		writingMode,
		padding,
		margin,
		blockGap,
		defaultSpacingSizesEnabled,
		customSpacingSize,
		userSpacingSizes,
		defaultSpacingSizes,
		themeSpacingSizes,
		units,
		aspectRatio,
		minHeight,
		layout,
		parentLayout,
		borderColor,
		borderRadius,
		borderStyle,
		borderWidth,
		customColorsEnabled,
		customColors,
		customDuotone,
		themeColors,
		defaultColors,
		defaultPalette,
		defaultDuotone,
		userDuotonePalette,
		themeDuotonePalette,
		defaultDuotonePalette,
		userGradientPalette,
		themeGradientPalette,
		defaultGradientPalette,
		defaultGradients,
		areCustomGradientsEnabled,
		isBackgroundEnabled,
		isLinkEnabled,
		isTextEnabled,
		isHeadingEnabled,
		isButtonEnabled,
		shadow,
	])

	return rawSettings
}

export function kebabCase(str) {
	let input = str?.toString?.() ?? ''

	// See https://github.com/lodash/lodash/blob/b185fcee26b2133bd071f4aaca14b455c2ed1008/lodash.js#L4970
	input = input.replace(/['\u2019]/, '')

	return paramCase(input, {
		splitRegexp: [
			/(?!(?:1ST|2ND|3RD|[4-9]TH)(?![a-z]))([a-z0-9])([A-Z])/g, // fooBar => foo-bar, 3Bar => 3-bar
			/(?!(?:1st|2nd|3rd|[4-9]th)(?![a-z]))([0-9])([a-z])/g, // 3bar => 3-bar
			/([A-Za-z])([0-9])/g, // Foo3 => foo-3, foo3 => foo-3
			/([A-Z])([A-Z][a-z])/g, // FOOBar => foo-bar
		],
	})
}

export function getColorClassName(colorContextName, colorSlug) {
	if (!colorContextName || !colorSlug) {
		return undefined
	}

	return `has-${kebabCase(colorSlug)}-${colorContextName}`
}

export const getColorObjectByAttributeValues = (
	colors,
	definedColor,
	customColor
) => {
	if (definedColor) {
		const colorObj = colors?.find((color) => color.slug === definedColor)

		if (colorObj) {
			return colorObj
		}
	}

	return {
		color: customColor,
	}
}

export const cleanEmptyObject = (object) => {
	if (
		object === null ||
		typeof object !== 'object' ||
		Array.isArray(object)
	) {
		return object
	}

	const cleanedNestedObjects = Object.entries(object)
		.map(([key, value]) => [key, cleanEmptyObject(value)])
		.filter(([, value]) => value !== undefined)
	return !cleanedNestedObjects.length
		? undefined
		: Object.fromEntries(cleanedNestedObjects)
}

export function styleToAttributes(style) {
	const textColorValue = style?.color?.text
	const textColorSlug = textColorValue?.startsWith('var:preset|color|')
		? textColorValue.substring('var:preset|color|'.length)
		: undefined
	const backgroundColorValue = style?.color?.background
	const backgroundColorSlug = backgroundColorValue?.startsWith(
		'var:preset|color|'
	)
		? backgroundColorValue.substring('var:preset|color|'.length)
		: undefined
	const gradientValue = style?.color?.gradient
	const gradientSlug = gradientValue?.startsWith('var:preset|gradient|')
		? gradientValue.substring('var:preset|gradient|'.length)
		: undefined
	const updatedStyle = { ...style }

	updatedStyle.color = {
		...updatedStyle.color,
		text: textColorSlug ? undefined : textColorValue,
		background: backgroundColorSlug ? undefined : backgroundColorValue,
		gradient: gradientSlug ? undefined : gradientValue,
	}

	return {
		style: cleanEmptyObject(updatedStyle),
		textColor: textColorSlug,
		backgroundColor: backgroundColorSlug,
		gradient: gradientSlug,
	}
}

export const attributesToStyle = (attributes) => {
	return {
		...attributes.style,
		color: {
			...attributes.style?.color,
			text: attributes.textColor
				? 'var:preset|color|' + attributes.textColor
				: attributes.style?.color?.text,
			background: attributes.backgroundColor
				? 'var:preset|color|' + attributes.backgroundColor
				: attributes.style?.color?.background,
			gradient: attributes.gradient
				? 'var:preset|gradient|' + attributes.gradient
				: attributes.style?.color?.gradient,
		},
	}
}

export function useColorsPerOrigin(settings) {
	const customColors = settings?.color?.palette?.custom
	const themeColors = settings?.color?.palette?.theme
	const defaultColors = settings?.color?.palette?.default
	const shouldDisplayDefaultColors = settings?.color?.defaultPalette

	return useMemo(() => {
		const result = []
		if (themeColors && themeColors.length) {
			result.push({
				name: _x(
					'Theme',
					'Indicates this palette comes from the theme.'
				),
				colors: themeColors,
			})
		}
		if (
			shouldDisplayDefaultColors &&
			defaultColors &&
			defaultColors.length
		) {
			result.push({
				name: _x(
					'Default',
					'Indicates this palette comes from WordPress.'
				),
				colors: defaultColors,
			})
		}
		if (customColors && customColors.length) {
			result.push({
				name: _x(
					'Custom',
					'Indicates this palette is created by the user.'
				),
				colors: customColors,
			})
		}
		return result
	}, [customColors, themeColors, defaultColors, shouldDisplayDefaultColors])
}

export function useGradientsPerOrigin(settings) {
	const customGradients = settings?.color?.gradients?.custom
	const themeGradients = settings?.color?.gradients?.theme
	const defaultGradients = settings?.color?.gradients?.default
	const shouldDisplayDefaultGradients = settings?.color?.defaultGradients

	return useMemo(() => {
		const result = []
		if (themeGradients && themeGradients.length) {
			result.push({
				name: _x(
					'Theme',
					'Indicates this palette comes from the theme.'
				),
				gradients: themeGradients,
			})
		}
		if (
			shouldDisplayDefaultGradients &&
			defaultGradients &&
			defaultGradients.length
		) {
			result.push({
				name: _x(
					'Default',
					'Indicates this palette comes from WordPress.'
				),
				gradients: defaultGradients,
			})
		}
		if (customGradients && customGradients.length) {
			result.push({
				name: _x(
					'Custom',
					'Indicates this palette is created by the user.'
				),
				gradients: customGradients,
			})
		}
		return result
	}, [
		customGradients,
		themeGradients,
		defaultGradients,
		shouldDisplayDefaultGradients,
	])
}

export function getInlineStyles(styles = {}) {
	const output = {}

	// The goal is to move everything to server side generated engine styles
	// This is temporary as we absorb more and more styles into the engine.
	getCSSRules(styles).forEach((rule) => {
		output[rule.key] = rule.value
	})

	return output
}

// Defines which element types are supported, including their hover styles or
// any other elements that have been included under a single element type
// e.g. heading and h1-h6.

const ELEMENTS = {
	link: 'a',
	overlay: '.wp-block-cover__background',
}

export function scopeSelector(scope, selector) {
	if (!scope || !selector) {
		return selector
	}

	const scopes = scope.split(',')
	const selectors = selector.split(',')

	const selectorsScoped = []

	scopes.forEach((outer) => {
		selectors.forEach((inner) => {
			selectorsScoped.push(`${outer.trim()} ${inner.trim()}`)
		})
	})

	return selectorsScoped.join(', ')
}

export const useElementCss = (
	style,
	attributes,
	fieldType,
	blockElementsContainerIdentifier
) => {
	const elementTypes = [{ elementType: 'link', pseudo: [':hover'] }]

	if (fieldType === 'image') {
		elementTypes.push({
			elementType: 'overlay',

			additionalRules: (selector, attributes) => {
				return [
					{
						selector,
						key: 'opacity',
						value: parseFloat(attributes.dimRatio) / 100,
					},
				]
			},
		})
	}

	// const blockElementsContainerIdentifier = useInstanceId([], 'wp-elements')

	const baseElementSelector = `.${blockElementsContainerIdentifier}`
	const blockElementStyles = style?.elements

	const styles = useMemo(() => {
		if (!blockElementStyles) {
			return
		}

		const elementCSSRules = []

		elementTypes.forEach(
			({ elementType, pseudo, elements, additionalRules }) => {
				const elementStyles = blockElementStyles?.[elementType]

				// Process primary element type styles.
				if (elementStyles) {
					const selector = scopeSelector(
						baseElementSelector,
						ELEMENTS[elementType]
					)

					const compiledCss = compileCSS(elementStyles, {
						selector,
						additionalRules: additionalRules
							? additionalRules(selector, attributes)
							: [],
					})

					elementCSSRules.push(compiledCss)

					// Process any interactive states for the element type.
					if (pseudo) {
						pseudo.forEach((pseudoSelector) => {
							if (elementStyles[pseudoSelector]) {
								elementCSSRules.push(
									compileCSS(elementStyles[pseudoSelector], {
										selector: scopeSelector(
											baseElementSelector,
											`${ELEMENTS[elementType]}${pseudoSelector}`
										),
									})
								)
							}
						})
					}
				}
			}
		)

		return elementCSSRules.length > 0 ? elementCSSRules.join('') : undefined
	}, [baseElementSelector, blockElementStyles, attributes])

	return {
		className: blockElementsContainerIdentifier,
		styles,
	}
}

export function getDuotonePresetFromColors(colors, duotonePalette) {
	if (!colors || !Array.isArray(colors)) {
		return
	}

	const preset = duotonePalette?.find((duotonePreset) => {
		return duotonePreset?.colors?.every(
			(val, index) => val === colors[index]
		)
	})

	return preset ? `var:preset|duotone|${preset.slug}` : undefined
}

export function getColorsFromDuotonePreset(duotone, duotonePalette) {
	if (!duotone) {
		return
	}
	const preset = duotonePalette?.find(({ slug }) => {
		return duotone === `var:preset|duotone|${slug}`
	})

	return preset ? preset.colors : undefined
}
