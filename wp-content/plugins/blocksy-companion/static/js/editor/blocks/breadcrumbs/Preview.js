import { createElement } from '@wordpress/element'
import { Spinner } from '@wordpress/components'
import { useEntityProp } from '@wordpress/core-data'

import { __ } from 'ct-i18n'
import { toPlainText } from '../dynamic-data/utils'

const Preview = ({ breadcrumbs, postId, postType }) => {
	if (!breadcrumbs || !breadcrumbs.render) {
		return <Spinner />
	}

	const [rawTitle = ''] = useEntityProp('postType', postType, 'title', postId)

	return (
		<span
			dangerouslySetInnerHTML={{
				__html: breadcrumbs.render.replace(
					'__BLOCKSY_BREADCRUMBS_POST_TITLE__',
					toPlainText(rawTitle)
				),
			}}
		/>
	)
}

export default Preview
