import { createElement, useState } from '@wordpress/element'
import {
	BlockControls,
	BlockAlignmentControl,
	AlignmentControl,
	__experimentalBlockAlignmentMatrixControl as BlockAlignmentMatrixControl,
	__experimentalBlockFullHeightAligmentControl as FullHeightAlignmentControl,
	__experimentalDuotoneControl as DuotoneControl,
} from '@wordpress/block-editor'
import TagNameDropdown from './TagNameDropdown'

import { fieldIsImageLike } from '../utils'
import { __ } from '@wordpress/i18n'
import {
	getColorsFromDuotonePreset,
	getDuotonePresetFromColors,
	useBlockSettings,
} from './utils'

const AlignmentControls = ({
	fieldDescriptor,
	attributes,
	attributes: {
		name,
		style,

		align,
		imageAlign,
		contentPosition,
		minimumHeight,

		viewType,
	},
	setAttributes,
}) => {
	const [prevMinimumHeight, setPrevMinimumHeight] = useState('')
	const isMinFullHeight = minimumHeight === '100vh'

	const settings = useBlockSettings(name)

	const duotonePalette = [
		...(settings.color.duotone?.custom ?? []),
		...(settings.color.duotone?.theme ?? []),
		...(settings.color.duotone?.default ?? []),
	]

	const colorPallete = [
		...(settings.color.palette?.custom ?? []),
		...(settings.color.palette?.theme ?? []),
		...(settings.color.palette?.default ?? []),
	]

	const duotoneStyle = style?.color?.duotone
	const duotonePresetOrColors = !Array.isArray(duotoneStyle)
		? getColorsFromDuotonePreset(duotoneStyle, duotonePalette)
		: duotoneStyle

	return (
		<BlockControls group="block">
			{!fieldIsImageLike(fieldDescriptor) ? (
				<>
					<AlignmentControl
						value={align}
						onChange={(newAlign) =>
							setAttributes({
								align: newAlign,
							})
						}
					/>
					<TagNameDropdown
						tagName={attributes.tagName}
						onChange={(tagName) => setAttributes({ tagName })}
					/>
				</>
			) : (
				<>
					<BlockAlignmentControl
						{...(fieldDescriptor.provider === 'wp' &&
						fieldDescriptor.id === 'author_avatar'
							? {
									controls: [
										'none',
										'left',
										'center',
										'right',
									],
							  }
							: {})}
						value={imageAlign}
						onChange={(newImageAlign) =>
							setAttributes({
								imageAlign: newImageAlign,
							})
						}
					/>

					{null && (
						<DuotoneControl
							duotonePalette={duotonePalette}
							colorPalette={colorPallete}
							// disableCustomDuotone
							// disableCustomColors
							value={duotonePresetOrColors}
							onChange={(newDuotone) => {
								const maybePreset = getDuotonePresetFromColors(
									newDuotone,
									duotonePalette
								)

								const newStyle = {
									...style,
									color: {
										...style?.color,
										duotone: maybePreset ?? newDuotone, // use preset or fallback to custom colors.
									},
								}
								setAttributes({ style: newStyle })
							}}
							settings={settings}
						/>
					)}

					{viewType === 'cover' ? (
						<>
							<BlockAlignmentMatrixControl
								label={__('Change content position')}
								value={contentPosition}
								onChange={(nextPosition) =>
									setAttributes({
										contentPosition: nextPosition,
									})
								}
							/>

							<FullHeightAlignmentControl
								isActive={isMinFullHeight}
								onToggle={() => {
									if (!isMinFullHeight) {
										setPrevMinimumHeight(minimumHeight)

										setAttributes({
											minimumHeight: '100vh',
											aspectRatio: 'auto',
										})
									} else {
										setAttributes({
											minimumHeight: prevMinimumHeight,
										})
									}
								}}
							/>
						</>
					) : null}
				</>
			)}
		</BlockControls>
	)
}

export default AlignmentControls
