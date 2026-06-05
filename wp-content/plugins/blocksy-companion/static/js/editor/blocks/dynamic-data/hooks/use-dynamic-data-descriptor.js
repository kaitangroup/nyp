import { useState, useEffect, useMemo } from '@wordpress/element'
import { __ } from 'ct-i18n'
import { getOptionsForBlock } from 'blocksy-options'
import cachedFetch from '@creative-themes/wordpress-helpers/cached-fetch'

import { useTaxonomies } from '../../query/edit/utils/utils'

import { useFieldsContext } from './use-fields-context'

const options = getOptionsForBlock('dynamic-data')

const wpFields = (args = {}) => {
	const { fieldsContext } = args

	let fields = []

	if (fieldsContext.type === 'term') {
		fields = [
			{
				id: 'term_title',
				label: __('Term Title', 'blocksy-companion'),
			},
			{
				id: 'term_description',
				label: __('Term Description', 'blocksy-companion'),
			},
			{
				id: 'term_image',
				label: __('Term Image', 'blocksy-companion'),
			},
			{
				id: 'term_count',
				label: __('Term Count', 'blocksy-companion'),
			},
		]
	}

	if (fieldsContext.type === 'all' || fieldsContext.type === 'post') {
		fields = [
			{
				id: 'title',
				label: __('Title', 'blocksy-companion'),
			},

			{
				id: 'excerpt',
				label: __('Excerpt', 'blocksy-companion'),
			},

			{
				id: 'date',
				label: __('Post Date', 'blocksy-companion'),
			},

			{
				id: 'comments',
				label: __('Comments', 'blocksy-companion'),
			},

			{
				id: 'terms',
				label: __('Terms', 'blocksy-companion'),
			},

			{
				id: 'author',
				label: __('Author', 'blocksy-companion'),
			},

			{
				id: 'featured_image',
				label: __('Featured Image', 'blocksy-companion'),
			},

			{
				id: 'author_avatar',
				label: __('Author Avatar', 'blocksy-companion'),
			},
		]
	}

	if (fieldsContext.type === 'all') {
		fields = [
			...fields,

			{
				id: 'archive_title',
				label: __('Archive Title', 'blocksy-companion'),
			},

			{
				id: 'archive_description',
				label: __('Archive Description', 'blocksy-companion'),
			},

			{
				id: 'archive_image',
				label: __('Archive Image', 'blocksy-companion'),
			},
		]
	}

	return {
		provider: 'wp',
		provider_label: 'WordPress',
		fields,
	}
}

const wooFields = ({ fieldsContext, taxonomies = [] }) => {
	const hasWoo = typeof window.wc !== 'undefined'

	if (!hasWoo) {
		return null
	}

	const shouldHaveWooFields =
		// Single product picked
		(fieldsContext.type === 'post' &&
			fieldsContext.post_type === 'product') ||
		// Product tab or size guide picked
		(fieldsContext.type === 'post_type' &&
			fieldsContext.post_type === 'product')

	if (!shouldHaveWooFields) {
		return null
	}

	const hasBrands = (taxonomies || []).find(
		({ slug }) => slug === 'product_brand'
	)

	return {
		provider: 'woo',
		provider_label: 'WooCommerce',

		fields: [
			{
				id: 'price',
				label: __('Price', 'blocksy-companion'),
			},
			{
				id: 'rating',
				label: __('Rating', 'blocksy-companion'),
			},
			{
				id: 'stock_status',
				label: __('Stock Status', 'blocksy-companion'),
			},
			{
				id: 'sku',
				label: __('SKU', 'blocksy-companion'),
			},
			{
				id: 'attributes',
				label: __('Attributes', 'blocksy-companion'),
			},
			...(hasBrands
				? [
						{
							id: 'brands',
							label: __('Brands', 'blocksy-companion'),
						},
				  ]
				: []),
		],
	}
}

const useDynamicDataDescriptor = ({ postId, postType, termId, taxonomy }) => {
	const [additionalFields, setAdditionalFields] = useState([])
	const [fullDescriptorLoaded, setFullDescriptorLoaded] = useState(false)

	const fieldsContext = useFieldsContext({ postId, postType, termId })

	let postTypeForTaxonomies = postType

	if (fieldsContext.type === 'post_type' && fieldsContext.post_type) {
		postTypeForTaxonomies = fieldsContext.post_type
	}

	if (fieldsContext.type === 'post' && fieldsContext.post_type) {
		postTypeForTaxonomies = fieldsContext.post_type
	}

	const taxonomies = useTaxonomies(postTypeForTaxonomies)

	useEffect(() => {
		cachedFetch(
			`${wp.ajax.settings.url}?action=blocksy_blocks_retrieve_dynamic_data_descriptor`,
			{
				context: fieldsContext,
			}
		)
			.then((response) => response.json())
			.then(({ success, data }) => {
				setAdditionalFields(data.fields)
				setFullDescriptorLoaded(true)
			})
	}, [fieldsContext])

	const fieldsDescriptor = {
		fields: [wpFields({ fieldsContext })],
	}

	const maybeWooFields = wooFields({ fieldsContext, taxonomies })

	if (maybeWooFields) {
		fieldsDescriptor.fields.push(maybeWooFields)
	}

	if (additionalFields.length > 0) {
		fieldsDescriptor.fields = [
			...fieldsDescriptor.fields,
			...additionalFields,
		]
	}

	const linkFieldsChoices = additionalFields.flatMap((p) =>
		p.fields
			.filter((f) => f.type === 'text')
			.map((f) => ({
				group: p.provider_label,
				key: `${p.provider}:${f.id}`,
				value: f.label,
			}))
	)

	return {
		fullDescriptorLoaded,
		fieldsDescriptor,
		linkFieldsChoices,
		options,
		fieldsChoices: fieldsDescriptor.fields.reduce(
			(acc, currentProvider) => [
				...acc,
				...currentProvider.fields
					.filter((field) => {
						if (
							currentProvider.provider !== 'wp' ||
							field.id !== 'terms'
						) {
							return true
						}

						return taxonomies && taxonomies.length > 0
					})
					.map((field) => ({
						group: currentProvider.provider_label,
						key: `${currentProvider.provider}:${field.id}`,
						value: field.label,
					})),
			],
			[]
		),
	}
}

export default useDynamicDataDescriptor
