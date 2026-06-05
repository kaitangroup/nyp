import { createElement } from '@wordpress/element'
import { useSelect } from '@wordpress/data'
import { __ } from 'ct-i18n'

import useCustomFieldData from '../../hooks/use-custom-field-data'

const BrandsPreview = ({ postId, attributes }) => {
	const { fieldData } = useCustomFieldData({
		fieldsContext: {
			type: 'post',
			post_type: 'product',
			post_id: postId,
		},

		fieldDescriptor: {
			provider: 'woo',
			id: 'brands',
		},
	})

	return (
		<div
			className="ct-product-brands"
			style={{
				'--product-brand-logo-size': `${attributes.brands_size}px`,
				'--product-brands-gap': `${attributes.brands_gap}px`,
			}}>
			{fieldData &&
				fieldData.map((brand) => {
					if (brand.image && brand.image.url) {
						return (
							<span
								key={brand.slug}
								className="ct-media-container">
								<img src={brand.image.url} alt={brand.name} />
							</span>
						)
					}

					return (
						<span
							key={brand.slug}
							dangerouslySetInnerHTML={{ __html: brand.name }}
						/>
					)
				})}
		</div>
	)
}

export default BrandsPreview
