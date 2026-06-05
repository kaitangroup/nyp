import { createElement } from '@wordpress/element'
import { __ } from 'ct-i18n'
import {
	SelectControl,
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components'

import { useSelect } from '@wordpress/data'
import { store as blockEditorStore } from '@wordpress/block-editor'

const DEFAULT_SIZE = 'full'

const Resolution = ({ sizeSlug, onChange, clientId }) => {
	const imageSizes = useSelect(
		(select) => select(blockEditorStore).getSettings().imageSizes,
		[]
	)
	const imageSizeOptions = imageSizes.map(({ name, slug }) => ({
		value: slug,
		label: name,
	}))

	if (!imageSizeOptions.length) {
		return null
	}

	return (
		<ToolsPanelItem
			hasValue={() => !!sizeSlug}
			label={__('Resolution', 'blocksy-companion')}
			onDeselect={() => onChange(undefined)}
			resetAllFilter={() => ({
				sizeSlug: undefined,
			})}
			isShownByDefault={false}
			key={clientId}>
			<SelectControl
				__nextHasNoMarginBottom
				label={__('Resolution', 'blocksy-companion')}
				value={sizeSlug || DEFAULT_SIZE}
				options={imageSizeOptions}
				onChange={(nextSizeSlug) => onChange(nextSizeSlug)}
				help={__(
					'Select the size of the source image.',
					'blocksy-companion'
				)}
			/>
		</ToolsPanelItem>
	)
}

export default Resolution
