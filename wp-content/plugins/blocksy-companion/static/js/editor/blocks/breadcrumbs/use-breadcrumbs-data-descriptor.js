import { useState, useEffect } from '@wordpress/element'

import { useSelect } from '@wordpress/data'
import { store as editorStore } from '@wordpress/editor'
import { store as coreStore } from '@wordpress/core-data'

import { __ } from 'ct-i18n'

import cachedFetch from '@creative-themes/wordpress-helpers/cached-fetch'

const useBreadcrumbsDataDescriptor = ({ postId, postType }) => {
	const [breadcrumbs, setBreadcrumbs] = useState(null)

	const { taxonomies } = useSelect((select) => {
		return {
			taxonomies: select(coreStore).getEntityRecords('root', 'taxonomy', {
				per_page: -1,
			}),
		}
	}, [])

	const visibleTaxonomies = (taxonomies ?? []).filter(
		(taxonomy) =>
			// In some circumstances .visibility can end up as undefined so optional chaining operator required.
			// https://github.com/WordPress/gutenberg/issues/40326
			taxonomy.types.includes(postType) && taxonomy.visibility?.show_ui
	)

	const taxonomyTerms = useSelect(
		(select) => {
			const { getEditedPostAttribute } = select(editorStore)

			return visibleTaxonomies.reduce((acc, taxonomy) => {
				const terms = getEditedPostAttribute(taxonomy.rest_base)

				acc[taxonomy.slug] = terms

				return acc
			}, {})
		},
		[visibleTaxonomies]
	)

	useEffect(() => {
		if (postId) {
			cachedFetch(
				`${wp.ajax.settings.url}?action=blocksy_blocks_retrieve_breadcrumbs_data_descriptor`,
				{
					post_id: postId,
					taxonomyTerms,
				}
			)
				.then((response) => response.json())
				.then(({ success, data }) => {
					setBreadcrumbs(data)
				})
		}
	}, [postId, taxonomyTerms])

	return {
		breadcrumbs,
	}
}

export default useBreadcrumbsDataDescriptor
