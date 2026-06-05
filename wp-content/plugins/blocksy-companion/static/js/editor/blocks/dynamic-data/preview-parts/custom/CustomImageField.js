import { createElement } from '@wordpress/element'

import ImagePreview from '../wp/ImagePreview'

const CustomImageField = ({
	fieldData,

	attributes,
	attributes: { sizeSlug },
}) => {
	let maybeUrl = fieldData?.url

	if (fieldData?.full_url) {
		maybeUrl = fieldData.full_url
	}

	if (fieldData?.sizes?.[sizeSlug]) {
		if (typeof fieldData.sizes[sizeSlug] === 'string') {
			maybeUrl = fieldData.sizes[sizeSlug]
		} else {
			maybeUrl = fieldData.sizes[sizeSlug].url
		}
	}

	return <ImagePreview attributes={attributes} url={maybeUrl} />
}

export default CustomImageField
