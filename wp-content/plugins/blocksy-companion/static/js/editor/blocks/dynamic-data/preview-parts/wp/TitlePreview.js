import { createElement } from '@wordpress/element'
import { withSelect } from '@wordpress/data'

import { __ } from 'ct-i18n'

import { useEntityProp } from '@wordpress/core-data'
import { toPlainText } from '../../utils'

const TitlePreview = ({
	attributes: { has_field_link, has_field_link_wrap_content },
	postId,
	postType,
}) => {
	const [rawTitle = '', setTitle, fullTitle] = useEntityProp(
		'postType',
		postType,
		'title',
		postId
	)

	if (!postId) {
		return 'Title'
	}

	if (!rawTitle) {
		return null
	}

	if (has_field_link === 'yes' && has_field_link_wrap_content === 'no') {
		return (
			<a href="#" rel="noopener noreferrer">
				{toPlainText(rawTitle)}
			</a>
		)
	}

	return toPlainText(rawTitle)
}

export default TitlePreview
