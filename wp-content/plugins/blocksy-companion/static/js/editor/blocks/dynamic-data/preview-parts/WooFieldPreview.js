import { createElement, useRef } from '@wordpress/element'
import { useSelect } from '@wordpress/data'
import {
	useBlockProps,
	__experimentalUseBorderProps as useBorderProps,
} from '@wordpress/block-editor'
import { Spinner } from '@wordpress/components'

import classnames from 'classnames'

import ExcerptPreview from './wp/ExcerptPreview'
import DatePreview from './wp/DatePreview'
import CommentsPreview from './wp/CommentsPreview'
import AuthorPreview from './wp/AuthorPreview'
import TaxonomyPreview from './wp/TaxonomyPreview'
import PricePreview from './woo/PricePreview'
import StockPreview from './woo/StockPreview'
import RatingPreview from './woo/RatingPreview'
import SkuPreview from './woo/SkuPreview'
import BrandsPreview from './woo/BrandsPreview'

import { useBlockSupportsCustom } from '../hooks/use-block-supports-custom'
import AttributesPreview from './woo/AttributesPreview'

import ContentWithBeforeAndAfter from '../components/ContentWithBeforeAndAfter'

const WooFieldPreviewWithContext = ({
	fieldDescriptor,
	fieldsDescriptor,

	attributes,
	attributes: { align, tagName: TagName, before, after, fallback },

	postId,
	postType,

	termId,
	taxonomy,

	colors,
}) => {
	const ref = useRef(null)
	const shadowMutationRef = useRef(null)

	const { product, isLoading } =
		wc.wcBlocksSharedContext.useProductDataContext()

	const blockProps = useBlockProps({
		className: classnames('ct-dynamic-data', {
			[`has-text-align-${align}`]: align,
		}),
		ref,
	})

	const uniqueClass = blockProps.className
		.split(' ')
		.find((c) => c.startsWith('wp-elements-'))

	const previewData = useBlockSupportsCustom({
		fieldType: 'text',
		attributes,
		uniqueClass,
	})

	const borderProps = useBorderProps(attributes)

	if (isLoading) {
		return <Spinner />
	}

	let Component = null

	if (fieldDescriptor.id === 'price') {
		Component = PricePreview
	}

	if (fieldDescriptor.id === 'stock_status') {
		Component = StockPreview
	}

	if (fieldDescriptor.id === 'brands') {
		Component = BrandsPreview
	}

	if (fieldDescriptor.id === 'rating') {
		Component = RatingPreview
	}

	if (fieldDescriptor.id === 'sku') {
		Component = SkuPreview
	}

	if (fieldDescriptor.id === 'attributes') {
		Component = AttributesPreview
	}

	let css = ''

	if (previewData.css) {
		css += previewData.css
	}

	if (Component) {
		return (
			<TagName
				{...blockProps}
				{...borderProps}
				style={{
					...(blockProps.style || {}),
					...(borderProps.style || {}),
					...(previewData.style || {}),
				}}
				className={classnames(
					blockProps.className,
					borderProps.className,
					previewData.className
				)}>
				{css && <style>{css}</style>}

				<ContentWithBeforeAndAfter before={before} after={after}>
					<Component
						attributes={attributes}
						postId={postId}
						postType={postType}
						termId={termId}
						taxonomy={taxonomy}
						fallback={fallback}
						fieldsDescriptor={fieldsDescriptor}
						product={product}
					/>
				</ContentWithBeforeAndAfter>
			</TagName>
		)
	}

	return null
}

const WooFieldPreview = (props) => {
	const { fieldDescriptor, postId } = props

	const { withProductDataContext } = wc.wcBlocksSharedHocs

	return withProductDataContext(WooFieldPreviewWithContext)({
		...props,
		productId: postId,
	})
}

export default WooFieldPreview
