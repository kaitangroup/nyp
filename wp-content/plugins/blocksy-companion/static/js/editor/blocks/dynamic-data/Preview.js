import {
	createElement,
	useCallback,
	useState,
	useEffect,
} from '@wordpress/element'

import { __ } from 'ct-i18n'
import {
	useBlockProps,
	__experimentalUseBorderProps as useBorderProps,
} from '@wordpress/block-editor'
import classnames from 'classnames'

import WpFieldPreview from './preview-parts/WpFieldPreview'
import CustomFieldPreview from './preview-parts/CustomFieldPreview'
import WooFieldPreview from './preview-parts/WooFieldPreview'

const Preview = ({
	fieldDescriptor,
	fieldsDescriptor,

	isFieldMissing,

	postId,
	postType,

	termId,
	taxonomy,

	fieldsContext,

	termIcon,
	termImage,

	attributes,
	attributes: {
		tagName: TagName,

		align,
		field,

		before,
		after,
		fallback,
	},

	colors,
}) => {
	if (isFieldMissing) {
		const blockProps = useBlockProps({
			className: 'ct-block-notice components-notice is-warning',
		})

		return (
			<div {...blockProps}>
				{__('Field not found', 'blocksy-companion')}
			</div>
		)
	}

	// Likely the descriptor is still loading
	if (!fieldDescriptor) {
		return null
	}

	if (fieldDescriptor.provider === 'woo') {
		return (
			<WooFieldPreview
				fieldDescriptor={fieldDescriptor}
				attributes={attributes}
				postId={postId}
				postType={postType}
				colors={colors}
			/>
		)
	}

	if (fieldDescriptor.provider === 'wp') {
		return (
			<WpFieldPreview
				fieldsDescriptor={fieldsDescriptor}
				fieldDescriptor={fieldDescriptor}
				attributes={attributes}
				postId={postId}
				postType={postType}
				termId={termId}
				taxonomy={taxonomy}
				termImage={termImage}
				termIcon={termIcon}
				colors={colors}
			/>
		)
	}

	return (
		<CustomFieldPreview
			fieldDescriptor={fieldDescriptor}
			fieldsContext={fieldsContext}
			attributes={attributes}
		/>
	)
}

export default Preview
