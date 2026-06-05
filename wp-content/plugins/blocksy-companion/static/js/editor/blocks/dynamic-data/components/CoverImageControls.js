import { createElement } from '@wordpress/element'

import {
	FocalPointPicker,
	ToggleControl,
	__experimentalToolsPanel as ToolsPanel,
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components'

import { useSelect } from '@wordpress/data'
import { useEntityProp, store as coreStore } from '@wordpress/core-data'
import {
	__experimentalUseCustomUnits as useCustomUnits,
	__experimentalUnitControl as UnitControl,
	__experimentalInputControlPrefixWrapper as InputControlPrefixWrapper,
	__experimentalVStack as VStack,
} from '@wordpress/components'

import { useSetting } from '@wordpress/block-editor'

import { __ } from '@wordpress/i18n'
import Resolution from './Resolution'

function getMediaSourceUrlBySizeSlug(media, slug) {
	return media?.media_details?.sizes?.[slug]?.source_url || media?.source_url
}

const ContentWidthIcon = () => (
	<svg
		xmlns="http://www.w3.org/2000/svg"
		viewBox="0 0 24 24"
		width="24"
		height="24"
		aria-hidden="true"
		focusable="false">
		<path d="M19 5.5H5V4h14v1.5ZM19 20H5v-1.5h14V20ZM5 9h14v6H5V9Z"></path>
	</svg>
)
const WideWidthIcon = () => (
	<svg
		xmlns="http://www.w3.org/2000/svg"
		viewBox="0 0 24 24"
		width="24"
		height="24"
		aria-hidden="true"
		focusable="false">
		<path d="M16 5.5H8V4h8v1.5ZM16 20H8v-1.5h8V20ZM5 9h14v6H5V9Z"></path>
	</svg>
)

const CoverImageEdit = ({
	clientId,
	postType,
	postId,
	attributes,
	setAttributes,
}) => {
	const {
		focalPoint,
		hasParallax,
		isRepeated,
		sizeSlug,

		allowCustomContentAndWideSize,
		contentSize,
		wideSize,
	} = attributes

	const [featuredImage, setFeaturedImage] = useEntityProp(
		'postType',
		postType,
		'featured_media',
		postId
	)

	const { media } = useSelect(
		(select) => {
			const { getMedia } = select(coreStore)

			return {
				media:
					featuredImage &&
					getMedia(featuredImage, {
						context: 'view',
					}),
			}
		},
		[featuredImage]
	)

	const maybeUrl = getMediaSourceUrlBySizeSlug(media, sizeSlug)

	const toggleParallax = () => {
		setAttributes({
			hasParallax: !hasParallax,
			...(!hasParallax ? { focalPoint: undefined } : {}),
		})
	}

	const toggleIsRepeated = () => {
		setAttributes({
			isRepeated: !isRepeated,
		})
	}

	const showFocalPointPicker = !hasParallax

	const defaultUnits = ['px', '%', 'vw', 'em', 'rem']
	const units = useCustomUnits({
		availableUnits: useSetting('spacing.units') || defaultUnits,
	})

	if (attributes.viewType !== 'cover') {
		return null
	}

	return (
		<>
			<ToolsPanel
				label={__('Layout')}
				resetAll={() => {
					setAttributes({
						hasParallax: false,
						focalPoint: undefined,
						isRepeated: false,
					})
				}}>
				<ToolsPanelItem
					label={__('Layout')}
					isShownByDefault
					hasValue={() => !allowCustomContentAndWideSize}
					onDeselect={() =>
						setAttributes({
							allowCustomContentAndWideSize: true,
							contentSize: undefined,
							wideSize: undefined,
						})
					}>
					<VStack
						spacing={4}
						className="block-editor-hooks__layout-constrained">
						<ToggleControl
							label={__('Inner blocks use content width')}
							checked={allowCustomContentAndWideSize}
							onChange={() => {
								setAttributes({
									allowCustomContentAndWideSize:
										!allowCustomContentAndWideSize,
								})
							}}
							help={
								allowCustomContentAndWideSize
									? __(
											'Nested blocks use content width with options for full and wide widths.'
									  )
									: __(
											'Nested blocks will fill the width of this container. Toggle to constrain.'
									  )
							}
						/>
						{allowCustomContentAndWideSize && (
							<>
								<UnitControl
									__next40pxDefaultSize
									label={__('Content width')}
									labelPosition="top"
									value={contentSize || wideSize || ''}
									onChange={(nextWidth) => {
										nextWidth =
											0 > parseFloat(nextWidth)
												? '0'
												: nextWidth
										setAttributes({
											contentSize: nextWidth,
										})
									}}
									units={units}
									prefix={
										<InputControlPrefixWrapper variant="icon">
											<ContentWidthIcon />
										</InputControlPrefixWrapper>
									}
								/>
								<UnitControl
									__next40pxDefaultSize
									label={__('Wide width')}
									labelPosition="top"
									value={wideSize || contentSize || ''}
									onChange={(nextWidth) => {
										nextWidth =
											0 > parseFloat(nextWidth)
												? '0'
												: nextWidth
										setAttributes({
											wideSize: nextWidth,
										})
									}}
									units={units}
									prefix={
										<InputControlPrefixWrapper variant="icon">
											<WideWidthIcon />
										</InputControlPrefixWrapper>
									}
								/>
								<p className="block-editor-hooks__layout-constrained-helptext">
									{__(
										'Customize the width for all elements that are assigned to the center or wide columns.'
									)}
								</p>
							</>
						)}
					</VStack>
				</ToolsPanelItem>
			</ToolsPanel>
			<ToolsPanel
				label={__('Image Settings', 'blocksy-companion')}
				resetAll={() => {
					setAttributes({
						hasParallax: false,
						focalPoint: undefined,
						isRepeated: false,
					})
				}}>
				{showFocalPointPicker && (
					<ToolsPanelItem
						label={__('Focal point')}
						isShownByDefault
						hasValue={() => !!focalPoint}
						onDeselect={() =>
							setAttributes({
								focalPoint: undefined,
							})
						}>
						<FocalPointPicker
							__nextHasNoMarginBottom
							label={__('Focal point')}
							url={maybeUrl}
							value={focalPoint}
							onDragStart={(newFocalPoint) =>
								setAttributes({
									focalPoint: newFocalPoint,
								})
							}
							onDrag={(newFocalPoint) =>
								setAttributes({
									focalPoint: newFocalPoint,
								})
							}
							onChange={(newFocalPoint) =>
								setAttributes({
									focalPoint: newFocalPoint,
								})
							}
						/>
					</ToolsPanelItem>
				)}
				<ToolsPanelItem
					label={__('Fixed background')}
					isShownByDefault
					hasValue={() => hasParallax}
					onDeselect={() =>
						setAttributes({
							hasParallax: false,
							focalPoint: undefined,
						})
					}>
					<ToggleControl
						__nextHasNoMarginBottom
						label={__('Fixed background')}
						checked={hasParallax}
						onChange={toggleParallax}
					/>
				</ToolsPanelItem>

				<ToolsPanelItem
					label={__('Repeated background')}
					isShownByDefault
					hasValue={() => isRepeated}
					onDeselect={() =>
						setAttributes({
							isRepeated: false,
						})
					}>
					<ToggleControl
						__nextHasNoMarginBottom
						label={__('Repeated background')}
						checked={isRepeated}
						onChange={toggleIsRepeated}
					/>
				</ToolsPanelItem>

				<Resolution
					sizeSlug={sizeSlug}
					onChange={(nextSizeSlug) =>
						setAttributes({ sizeSlug: nextSizeSlug })
					}
					clientId={clientId}
				/>
			</ToolsPanel>
		</>
	)
}

export { CoverImageEdit }
