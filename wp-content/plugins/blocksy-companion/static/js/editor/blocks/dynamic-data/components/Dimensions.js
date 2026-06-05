/**
 * WordPress dependencies
 */
import { createElement } from '@wordpress/element'
import { __ } from 'ct-i18n'
import {
	SelectControl,
	__experimentalUnitControl as UnitControl,
	__experimentalToolsPanel as ToolsPanel,
	__experimentalUseCustomUnits as useCustomUnits,
	__experimentalToolsPanelItem as ToolsPanelItem,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components'
import { useSetting } from '@wordpress/block-editor'
import Resolution from './Resolution'

const DimensionControls = ({
	clientId,
	attributes: {
		aspectRatio,
		imageFit,
		width,
		height,
		sizeSlug,
		viewType,
		minimumHeight,
	},
	setAttributes,
}) => {
	const defaultUnits = ['px', '%', 'vw', 'em', 'rem']
	const units = useCustomUnits({
		availableUnits: useSetting('spacing.units') || defaultUnits,
	})

	const onDimensionChange = (dimension, nextValue) => {
		const parsedValue = parseFloat(nextValue)
		/**
		 * If we have no value set and we change the unit,
		 * we don't want to set the attribute, as it would
		 * end up having the unit as value without any number.
		 */
		if (isNaN(parsedValue) && nextValue) return

		setAttributes({
			[dimension]: parsedValue < 0 ? '0' : nextValue,
		})
	}

	return (
		<ToolsPanel
			label={
				viewType === 'cover'
					? __('Block Settings', 'blocksy-companion')
					: __('Image Settings', 'blocksy-companion')
			}
			resetAll={() => {
				setAttributes({
					aspectRatio: 'auto',
					width: undefined,
					height: undefined,
					sizeSlug: undefined,
					minimumHeight: undefined,
				})
			}}>
			<ToolsPanelItem
				hasValue={() => !!aspectRatio}
				label={__('Aspect Ratio', 'blocksy-companion')}
				onDeselect={() => setAttributes({ aspectRatio: undefined })}
				resetAllFilter={() => ({
					aspectRatio: 'auto',
				})}
				isShownByDefault
				key={clientId}>
				<SelectControl
					__nextHasNoMarginBottom
					label={__('Aspect Ratio', 'blocksy-companion')}
					value={aspectRatio}
					options={[
						// These should use the same values as AspectRatioDropdown in @wordpress/block-editor
						{
							label: __('Original', 'blocksy-companion'),
							value: 'auto',
						},
						{
							label: __('Square', 'blocksy-companion'),
							value: '1',
						},
						{
							label: __('16:9', 'blocksy-companion'),
							value: '16/9',
						},
						{
							label: __('4:3', 'blocksy-companion'),
							value: '4/3',
						},
						{
							label: __('3:2', 'blocksy-companion'),
							value: '3/2',
						},
						{
							label: __('9:16', 'blocksy-companion'),
							value: '9/16',
						},
						{
							label: __('3:4', 'blocksy-companion'),
							value: '3/4',
						},
						{
							label: __('2:3', 'blocksy-companion'),
							value: '2/3',
						},
					]}
					onChange={(nextAspectRatio) =>
						setAttributes({
							aspectRatio: nextAspectRatio,
							minimumHeight: undefined,
						})
					}
				/>
			</ToolsPanelItem>

			{viewType !== 'cover' ? (
				<>
					<ToolsPanelItem
						style={{
							'grid-column': 'span 1 / auto',
						}}
						hasValue={() => !!width}
						label={__('Width', 'blocksy-companion')}
						onDeselect={() => setAttributes({ width: undefined })}
						resetAllFilter={() => ({
							width: undefined,
						})}
						isShownByDefault
						key={clientId}>
						<UnitControl
							label={__('Width', 'blocksy-companion')}
							labelPosition="top"
							value={width || ''}
							min={0}
							onChange={(nextWidth) =>
								onDimensionChange('width', nextWidth)
							}
							units={units}
						/>
					</ToolsPanelItem>

					<ToolsPanelItem
						style={{
							'grid-column': 'span 1 / auto',
						}}
						hasValue={() => !!height}
						label={__('Height', 'blocksy-companion')}
						onDeselect={() => setAttributes({ height: undefined })}
						resetAllFilter={() => ({
							height: undefined,
						})}
						isShownByDefault
						key={clientId}>
						<UnitControl
							label={__('Height', 'blocksy-companion')}
							labelPosition="top"
							value={height || ''}
							min={0}
							onChange={(nextHeight) =>
								onDimensionChange('height', nextHeight)
							}
							units={units}
						/>
					</ToolsPanelItem>

					<ToolsPanelItem
						hasValue={() => !!imageFit}
						label={__('Scale', 'blocksy-companion')}
						onDeselect={() =>
							setAttributes({ imageFit: undefined })
						}
						resetAllFilter={() => ({
							imageFit: 'cover',
						})}
						isShownByDefault
						key={clientId}>
						<ToggleGroupControl
							label={__('Scale', 'blocksy-companion')}
							value={imageFit}
							isBlock
							onChange={(nextImageFir) =>
								setAttributes({ imageFit: nextImageFir })
							}>
							<ToggleGroupControlOption
								key="cover"
								value="cover"
								label={__('Cover', 'blocksy-companion')}
							/>
							<ToggleGroupControlOption
								key="contain"
								value="contain"
								label={__('Contain', 'blocksy-companion')}
							/>
						</ToggleGroupControl>
					</ToolsPanelItem>
				</>
			) : null}

			{viewType === 'cover' ? (
				<ToolsPanelItem
					hasValue={() => !!minimumHeight}
					label={__('Minimum height')}
					onDeselect={() =>
						setAttributes({
							minimumHeight: undefined,
						})
					}
					isShownByDefault>
					<UnitControl
						__next40pxDefaultSize
						label={__('Minimum height')}
						labelPosition="top"
						value={minimumHeight || ''}
						onChange={(nextHeight) => {
							nextHeight =
								0 > parseFloat(nextHeight) ? '0' : nextHeight
							setAttributes({
								minimumHeight: nextHeight,
								aspectRatio: 'auto',
							})
						}}
						units={units}
					/>
				</ToolsPanelItem>
			) : (
				<Resolution
					sizeSlug={sizeSlug}
					onChange={(nextSizeSlug) =>
						setAttributes({ sizeSlug: nextSizeSlug })
					}
					clientId={clientId}
				/>
			)}
		</ToolsPanel>
	)
}

export default DimensionControls
