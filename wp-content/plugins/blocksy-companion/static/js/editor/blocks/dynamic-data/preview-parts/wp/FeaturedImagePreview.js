import { createElement } from '@wordpress/element'

import { useSelect } from '@wordpress/data'
import { useEntityProp, store as coreStore } from '@wordpress/core-data'
import ImagePreview from './ImagePreview'

function getMediaSourceUrlBySizeSlug(media, slug) {
	return media?.media_details?.sizes?.[slug]?.source_url || media?.source_url
}

const FeaturedImagePreview = ({
	postType,
	postId,

	attributes,
	attributes: { sizeSlug },
}) => {
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

	return <ImagePreview attributes={attributes} url={maybeUrl} media={media} />
}

export default FeaturedImagePreview
