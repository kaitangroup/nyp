import { createElement } from '@wordpress/element'
import { useSelect } from '@wordpress/data'
import { store as coreStore } from '@wordpress/core-data'
import ImagePreview from './ImagePreview'

function getMediaSourceUrlBySizeSlug(media, slug) {
	return media?.media_details?.sizes?.[slug]?.source_url || media?.source_url
}

const TermImagePreview = ({
	termImage,
	termIcon,

	attributes,
	attributes: { sizeSlug, imageSource },
}) => {
	const maybeImageId =
		imageSource === 'icon'
			? termIcon?.attachment_id
			: termImage?.attachment_id

	const { media } = useSelect(
		(select) => {
			const { getMedia } = select(coreStore)

			return {
				media:
					maybeImageId &&
					getMedia(maybeImageId, {
						context: 'view',
					}),
			}
		},
		[maybeImageId]
	)

	const maybeUrl = getMediaSourceUrlBySizeSlug(media, sizeSlug)

	return <ImagePreview attributes={attributes} url={maybeUrl} />
}

export default TermImagePreview
