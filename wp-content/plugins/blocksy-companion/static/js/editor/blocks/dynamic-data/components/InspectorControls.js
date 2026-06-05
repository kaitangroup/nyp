import { createElement } from '@wordpress/element'
import { __ } from 'ct-i18n'

import { InspectorControls, useSettings } from '@wordpress/block-editor'
import {
	RangeControl,
	PanelBody,
	TextControl,
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components'
import { OptionsPanel } from 'blocksy-options'
import { useDispatch } from '@wordpress/data'

import { fieldIsCustomField, fieldIsImageLike } from '../utils'
import DimensionControls from './Dimensions'
import { CoverImageEdit } from './CoverImageControls'
import ColorsPanel from '../../../components/ColorsPanel'

import { getValueFromVariable } from '@wordpress/block-editor/src/components/global-styles/utils'

import {
	useBlockSettings,
	styleToAttributes,
	attributesToStyle,
	useColorsPerOrigin,
	useGradientsPerOrigin,
} from './utils'

export function setImmutably(object, path, value) {
	// Normalize path
	path = Array.isArray(path) ? [...path] : [path]

	// Shallowly clone the base of the object
	object = Array.isArray(object) ? [...object] : { ...object }

	const leaf = path.pop()

	// Traverse object from root to leaf, shallowly cloning at each level
	let prev = object
	for (const key of path) {
		const lvl = prev[key]
		prev = prev[key] = Array.isArray(lvl) ? [...lvl] : { ...lvl }
	}

	prev[leaf] = value

	return object
}

const computeValue = (attributes) => {
	return attributesToStyle({
		style: attributes.style,
		textColor: attributes.textColor,
	})
}

const DynamicDataInspectorControls = ({
	fieldDescriptor,
	fieldsDescriptor,
	linkFieldsChoices,

	attributes,
	setAttributes,

	options,
	fieldsChoices,

	clientId,

	name,
	__unstableParentLayout,

	taxonomies,

	postId,
	postType,
}) => {
	const { replaceInnerBlocks } = useDispatch('core/block-editor')

	const value = computeValue(attributes)

	const settings = useBlockSettings(name, __unstableParentLayout)

	const decodeValue = (rawValue) =>
		getValueFromVariable({ settings }, '', rawValue)

	const colors = useColorsPerOrigin(settings)

	const gradients = useGradientsPerOrigin(settings)

	const encodeColorValue = (colorValue) => {
		const allColors = colors.flatMap(
			({ colors: originColors }) => originColors
		)

		const colorObject = allColors.find(({ color }) => color === colorValue)

		return colorObject ? 'var:preset|color|' + colorObject.slug : colorValue
	}

	const encodeGradientValue = (gradientValue) => {
		const allGradients = gradients.flatMap(
			({ gradients: originGradients }) => originGradients
		)

		const gradientObject = allGradients.find(
			({ gradient }) => gradient === gradientValue
		)

		return gradientObject
			? 'var:preset|gradient|' + gradientObject.slug
			: gradientValue
	}

	const onChange = (newStyle) => {
		setAttributes(styleToAttributes(newStyle))
	}

	const linkColor = decodeValue(value?.elements?.link?.color?.text)

	const hoverLinkColor = decodeValue(
		value?.elements?.link?.[':hover']?.color?.text
	)

	const setLinkColorCustom = (newColor) => {
		onChange(
			setImmutably(
				value,
				['elements', 'link', 'color', 'text'],
				encodeColorValue(newColor)
			)
		)
	}

	const overlayColor = decodeValue(
		attributes?.style?.elements?.overlay?.color?.background
	)

	const overlayGradient = decodeValue(
		attributes?.style?.elements?.overlay?.color?.gradient
	)

	// Core does really a bad job of handling the color and gradient and they
	// do it with two callback, which leads to race conditions. This is really,
	// really bad.
	//
	// This is a workaround to handle the race condition.
	// Ideally, we should implement our own version of
	// __experimentalColorGradientSettingsDropdown.
	//
	// color | gradient
	let currentCb = null

	const setOverlayColor = (newColor) => {
		if (!currentCb) {
			currentCb = 'color'
		}

		// gradient was first. skipping color
		if (currentCb === 'gradient') {
			currentCb = null
			return
		}

		let newValue = setImmutably(
			value,
			['elements', 'overlay', 'color', 'background'],
			encodeColorValue(newColor)
		)

		// If we have a color, we should remove the gradient.
		if (newColor) {
			newValue = setImmutably(
				newValue,
				['elements', 'overlay', 'color', 'gradient'],
				encodeGradientValue(undefined)
			)
		}

		onChange(newValue)
	}

	const setOverlayGradient = (newGradient) => {
		if (!currentCb) {
			currentCb = 'gradient'
		}

		// color was first. skipping gradient
		if (currentCb === 'color') {
			return
		}

		let newValue = setImmutably(
			value,
			['elements', 'overlay', 'color', 'gradient'],
			encodeGradientValue(newGradient)
		)

		// If we have a gradient, we should remove the color.
		if (newGradient) {
			newValue = setImmutably(
				newValue,
				['elements', 'overlay', 'color', 'background'],
				encodeColorValue(undefined)
			)
		}

		onChange(newValue)
	}

	const textColor = decodeValue(value?.color?.text)

	const setTextColor = (newColor) => {
		let changedObject = setImmutably(
			value,
			['color', 'text'],
			encodeColorValue(newColor)
		)

		if (textColor === linkColor) {
			changedObject = setImmutably(
				changedObject,
				['elements', 'link', 'color', 'text'],
				encodeColorValue(newColor)
			)
		}

		onChange(changedObject)
	}

	const setLinkColor = (newColor) => {
		onChange(
			setImmutably(
				value,
				['elements', 'link', 'color', 'text'],
				encodeColorValue(newColor)
			)
		)
	}

	const setHoverLinkColor = (newColor) => {
		onChange(
			setImmutably(
				value,
				['elements', 'link', ':hover', 'color', 'text'],
				encodeColorValue(newColor)
			)
		)
	}

	const colorsPanelSettings =
		attributes.viewType === 'default' || !fieldIsImageLike(fieldDescriptor)
			? [
					...(attributes.has_field_link === 'yes'
						? [
								{
									colorValue: linkColor,
									label: __('Link', 'blocksy-companion'),
									enableAlpha: true,
									onColorChange: setLinkColor,
								},

								{
									colorValue: hoverLinkColor,
									label: __(
										'Link Hover',
										'blocksy-companion'
									),
									enableAlpha: true,
									onColorChange: setHoverLinkColor,
								},
						  ]
						: [
								{
									colorValue: textColor,
									label: __('Text', 'blocksy-companion'),
									enableAlpha: true,
									onColorChange: setTextColor,
								},
						  ]),
			  ]
			: [
					{
						colorValue: overlayGradient ? undefined : overlayColor,
						gradientValue: overlayGradient,
						label: __('Overlay', 'blocksy-companion'),
						enableAlpha: true,
						onColorChange: setOverlayColor,
						onGradientChange: (newValue) => {
							setOverlayGradient(newValue)
						},
						isShownByDefault: true,
						clearable: true,
					},
			  ]

	if (!fieldDescriptor) {
		return (
			<InspectorControls>
				<PanelBody>
					<OptionsPanel
						purpose="gutenberg"
						onChange={(optionId, optionValue) => {
							setAttributes({
								[optionId]: optionValue,
							})

							if (optionId === 'viewType' && !overlayColor) {
								setTimeout(() => {
									setOverlayColor('#000000')
								}, 50)
							}

							if (
								optionId === 'viewType' ||
								(optionId === 'field' &&
									(!fieldIsImageLike(optionValue) ||
										attributes.field ===
											'wp:author_avatar'))
							) {
								replaceInnerBlocks(clientId, [], false)
							}
						}}
						options={{
							field: {
								type: 'ct-select',
								label: __(
									'Content Source',
									'blocksy-companion'
								),
								value: '',
								search: true,
								searchPlaceholder: __(
									'Search for field',
									'blocksy-companion'
								),
								defaultToFirstItem: false,
								choices: fieldsChoices,
								purpose: 'default',
							},
						}}
						value={{
							...attributes,
						}}
						hasRevertButton={false}
					/>
				</PanelBody>
			</InspectorControls>
		)
	}

	return (
		<>
			<InspectorControls>
				<PanelBody>
					<OptionsPanel
						purpose="gutenberg"
						onChange={(optionId, optionValue) => {
							setAttributes({
								[optionId]: optionValue,
							})

							if (optionId === 'viewType' && !overlayColor) {
								setTimeout(() => {
									setOverlayColor('#000000')
								}, 50)
							}

							if (
								optionId === 'viewType' ||
								(optionId === 'field' &&
									(!fieldIsImageLike(optionValue) ||
										attributes.field ===
											'wp:author_avatar'))
							) {
								replaceInnerBlocks(clientId, [], false)
							}
						}}
						options={{
							field: {
								type: 'ct-select',
								label: __(
									'Content Source',
									'blocksy-companion'
								),
								value: '',
								search: true,
								searchPlaceholder: __(
									'Search for field',
									'blocksy-companion'
								),
								defaultToFirstItem: false,
								choices: fieldsChoices,
								purpose: 'default',
							},

							...(attributes.field === 'wp:archive_title'
								? {
										has_archive_prefix: {
											type: 'ct-switch',
											label: __(
												'Show archive label',
												'blocksy-companion'
											),
											value: 'no',
										},
								  }
								: {}),

							...(fieldIsCustomField(fieldDescriptor)
								? {
										has_field_link: {
											type: 'ct-switch',
											label: __(
												'Enable field link',
												'blocksy-companion'
											),
											value: 'no',
										},
										...(attributes.has_field_link === 'yes'
											? {
													link_source: {
														type: 'ct-select',
														label: __(
															'Link Source',
															'blocksy-companion'
														),
														value: '',
														search: true,
														searchPlaceholder: __(
															'Search for field',
															'blocksy-companion'
														),
														defaultToFirstItem: false,
														choices:
															linkFieldsChoices,
														purpose: 'default',
													},

													...(attributes?.link_source &&
													attributes.link_source !==
														''
														? {
																has_field_link_new_tab:
																	{
																		type: 'ct-switch',
																		label: __(
																			'Open in new tab',
																			'blocksy-companion'
																		),
																		value: 'no',
																	},

																has_field_link_rel:
																	{
																		type: 'text',
																		label: __(
																			'Link Rel',
																			'blocksy-companion'
																		),
																		value: '',
																	},
														  }
														: {}),
											  }
											: {}),
								  }
								: {}),

							...(attributes.field !== 'wp:author_avatar' &&
							fieldIsImageLike(fieldDescriptor)
								? {
										viewType: {
											type: 'ct-radio',
											label: __(
												'View Type',
												'blocksy-companion'
											),
											value: attributes.viewType,
											design: 'inline',
											purpose: 'gutenberg',
											divider: 'bottom:full',
											choices: {
												default: __(
													'Image',
													'blocksy-companion'
												),
												cover: __(
													'Cover',
													'blocksy-companion'
												),
											},
										},
								  }
								: {}),

							...(attributes.field === 'wp:terms' &&
							taxonomies &&
							taxonomies.length > 0
								? {
										taxonomy: {
											type: 'ct-select',
											label: __(
												'Taxonomy',
												'blocksy-companion'
											),
											value: '',
											design: 'inline',
											purpose: 'default',
											choices: taxonomies.map(
												({ name, slug }) => ({
													key: slug,
													value: name,
												})
											),
										},
								  }
								: {}),

							...(attributes.field === 'wp:term_image'
								? {
										imageSource: {
											type: 'ct-radio',
											label: __(
												'Image Source',
												'blocksy-companion'
											),
											value: attributes.imageSource,
											design: 'inline',
											purpose: 'gutenberg',
											divider: 'bottom',
											choices: {
												featured: __(
													'Image',
													'blocksy-companion'
												),
												icon: __(
													'Icon/Logo',
													'blocksy-companion'
												),
											},
										},
								  }
								: {}),

							...options,
						}}
						value={{
							...attributes,
							...(fieldsDescriptor &&
							fieldsDescriptor.has_taxonomies_customization
								? { has_taxonomies_customization: 'yes' }
								: {}),
						}}
						hasRevertButton={false}
					/>

					{fieldIsImageLike(fieldDescriptor) &&
						attributes.field !== 'wp:author_avatar' &&
						attributes.field !== 'wp:archive_image' &&
						attributes.viewType === 'default' && (
							<OptionsPanel
								purpose="gutenberg"
								onChange={(optionId, optionValue) => {
									setAttributes({
										[optionId]: optionValue,
									})
								}}
								options={{
									lightbox_condition: {
										type: 'ct-condition',
										condition: { has_field_link: 'no' },
										options: {
											lightbox: {
												type: 'ct-switch',
												label: __(
													'Expand on click',
													'blocksy-companion'
												),
												value: 'no',
											},
										},
									},

									...(attributes.field === 'wp:featured_image'
										? {
												videoThumbnail: {
													type: 'ct-switch',
													label: __(
														'Video thumbnail',
														'blocksy-companion'
													),
													value: 'no',
												},
										  }
										: {}),

									image_hover_effect: {
										label: __(
											'Image Hover Effect',
											'blocksy-companion'
										),
										type: 'ct-select',
										value: 'none',
										view: 'text',
										design: 'inline',
										divider: 'top:full',
										choices: {
											none: __(
												'None',
												'blocksy-companion'
											),
											'zoom-in': __(
												'Zoom In',
												'blocksy-companion'
											),
											'zoom-out': __(
												'Zoom Out',
												'blocksy-companion'
											),
										},
									},
								}}
								value={attributes}
								hasRevertButton={false}
							/>
						)}
				</PanelBody>

				{fieldIsImageLike(fieldDescriptor) &&
					attributes.field !== 'wp:author_avatar' && (
						<>
							<CoverImageEdit
								attributes={attributes}
								setAttributes={setAttributes}
								postId={postId}
								postType={postType}
							/>

							<DimensionControls
								clientId={clientId}
								attributes={attributes}
								setAttributes={setAttributes}
							/>
						</>
					)}

				{attributes.field === 'wp:author_avatar' && (
					<PanelBody>
						<RangeControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={__('Image size', 'blocksy-companion')}
							onChange={(newSize) =>
								setAttributes({
									avatar_size: newSize,
								})
							}
							min={5}
							max={500}
							initialPosition={attributes?.avatar_size}
							value={attributes?.avatar_size}
						/>
					</PanelBody>
				)}

				{attributes.field === 'woo:brands' && (
					<PanelBody>
						<RangeControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={__('Logo Size', 'blocksy-companion')}
							onChange={(newSize) =>
								setAttributes({
									brands_size: newSize,
								})
							}
							min={5}
							max={500}
							initialPosition={attributes?.brands_size}
							value={attributes?.brands_size}
						/>
						<RangeControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={__('Logo Gap', 'blocksy-companion')}
							onChange={(newGap) =>
								setAttributes({
									brands_gap: newGap,
								})
							}
							min={5}
							max={500}
							initialPosition={attributes?.brands_gap}
							value={attributes?.brands_gap}
						/>
					</PanelBody>
				)}

				{!fieldIsImageLike(fieldDescriptor) &&
					attributes.field !== 'woo:brands' && (
						<PanelBody>
							<OptionsPanel
								purpose="gutenberg"
								onChange={(optionId, optionValue) => {
									setAttributes({
										[optionId]: optionValue,
									})
								}}
								options={{
									before: {
										type: 'text',
										label: __(
											'Before',
											'blocksy-companion'
										),
										value: '',
									},

									after: {
										type: 'text',
										label: __('After', 'blocksy-companion'),
										value: '',
									},

									...(fieldDescriptor.provider !== 'wp' ||
									(fieldDescriptor.provider === 'wp' &&
										(fieldDescriptor.id === 'excerpt' ||
											fieldDescriptor.id === 'terms' ||
											fieldDescriptor.id === 'author'))
										? {
												fallback: {
													type: 'text',
													label: __(
														'Fallback',
														'blocksy-companion'
													),
													value: __(
														'Custom field fallback',
														'blocksy-companion'
													),
												},
										  }
										: {}),

									has_field_link_wrap_content_condition: {
										type: 'ct-condition',
										condition: { has_field_link: 'yes' },
										options: {
											has_field_link_wrap_content: {
												type: 'ct-switch',
												label: __(
													'Include Before/After in link',
													'blocksy-companion'
												),
												value: 'no',
											},
										},
									},
								}}
								value={attributes}
								hasRevertButton={false}
							/>
						</PanelBody>
					)}
			</InspectorControls>

			<InspectorControls
				group="color"
				resetAllFilter={() => {
					const fieldType = fieldIsImageLike(fieldDescriptor)
						? 'image'
						: 'text'

					if (fieldType === 'text') {
						setTextColor()
						setLinkColor()
						setHoverLinkColor()

						setTimeout(() => {
							const { link, ...rest } =
								attributes?.style?.elements

							const newStyle = {
								...attributes.style,

								elements: rest,
							}

							setAttributes({
								textColor: undefined,

								style: newStyle,
							})
						})
					}

					if (fieldType === 'image') {
						setTimeout(() => {
							setOverlayColor('#000000')
						}, 50)
					}
				}}>
				<ColorsPanel
					label={__('Colors', 'blocksy-companion')}
					panelId={clientId}
					settings={colorsPanelSettings}
					skipToolsPanel
					containerProps={{
						'data-field-type': fieldIsImageLike(fieldDescriptor)
							? `image:${attributes.viewType}`
							: 'text',
					}}
				/>

				{fieldIsImageLike(fieldDescriptor) &&
				attributes.viewType !== 'default' ? (
					<ToolsPanelItem
						hasValue={() => {
							// If there's a media background the dimRatio will be
							// defaulted to 50 whereas it will be 100 for colors.
							return attributes.dimRatio === undefined
								? false
								: attributes.dimRatio !== 50
						}}
						label={__('Overlay opacity')}
						onDeselect={() =>
							setAttributes({
								dimRatio: 50,
							})
						}
						resetAllFilter={() => ({
							dimRatio: 50,
						})}
						isShownByDefault
						panelId={clientId}>
						<RangeControl
							__nextHasNoMarginBottom
							label={__('Overlay opacity')}
							value={attributes.dimRatio}
							onChange={(newDimRatio) =>
								setAttributes({
									dimRatio: newDimRatio,
								})
							}
							min={0}
							max={100}
							step={10}
							required
							__next40pxDefaultSize
						/>
					</ToolsPanelItem>
				) : null}
			</InspectorControls>

			{attributes.field === 'wp:terms' && (
				<InspectorControls group="advanced">
					<TextControl
						__nextHasNoMarginBottom
						autoComplete="off"
						label={__('Term additional class', 'blocksy-companion')}
						value={attributes.termClass}
						onChange={(nextValue) => {
							setAttributes({
								termClass: nextValue,
							})
						}}
						help={__(
							'Additional class for term items. Useful for styling.',
							'blocksy-companion'
						)}
					/>
				</InspectorControls>
			)}
		</>
	)
}

export default DynamicDataInspectorControls
