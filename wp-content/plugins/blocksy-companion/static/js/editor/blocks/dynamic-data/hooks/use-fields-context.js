import { useMemo } from '@wordpress/element'

const computeContext = ({ termId, postId, postType }) => {
	const specialPostTypes = [
		'ct_thank_you_page',
		'ct_content_block',
		'ct_product_tab',
		'ct_size_guide',
	]

	let context = {
		type: 'all',
	}

	if (postId) {
		context = {
			type: 'post',
			post_id: postId,
			post_type: postType,
		}
	}

	const currentPostType = (
		[...document.body.classList].find((c) => c.startsWith('post-type-')) ||
		''
	).replace('post-type-', '')

	if (currentPostType) {
		// Maybe make detection based on PreviewedPostsSelect.js presence
		const isSpecialPostType = specialPostTypes.includes(currentPostType)

		// We should still use global context for special post types,
		// with small exceptions.
		const thereIsNoPostInContext =
			isSpecialPostType && postType === currentPostType

		if (thereIsNoPostInContext) {
			context = {
				type: 'all',
			}

			if (postType === 'ct_product_tab' || postType === 'ct_size_guide') {
				context = {
					type: 'post_type',
					post_type: 'product',
				}
			}
		}
	}

	if (termId) {
		context = {
			type: 'term',
			term_id: termId,
		}
	}

	return context
}

export const useFieldsContext = ({ termId, postId, postType }) => {
	const context = useMemo(
		() => computeContext({ termId, postId, postType }),
		[termId, postId, postType]
	)

	return context
}
