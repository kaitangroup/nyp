import {
	createElement,
	useCallback,
} from '@wordpress/element'
import { RichText } from '@wordpress/block-editor'
import { __ } from 'ct-i18n'

import { Spinner } from '@wordpress/components'
import useDynamicPreview from '../../hooks/useDynamicPreview'
import cls from 'classnames'

import { colors } from './colors'

const OVERWRITE_ATTRIBUTES = {
	enable_live_results: 'no',
	live_results_images: 'yes',
	searchBoxHeight: '',
	searchProductPrice: 'no',
	searchProductStatus: 'no',
	search_box_placeholder: __('Search', 'blocksy-companion'),
	taxonomy_filter_label: __('Select category', 'blocksy-companion'),
	search_through: { post: true, page: true, product: true, custom: true },
	taxonomy_filter_visibility: { desktop: true, tablet: true, mobile: false },

	...colors,
}

const clearInlineWhiteSpaceRef = (element) => {
	if (!element) {
		return
	}

	element.style.removeProperty('white-space')
}

const Preview = ({ attributes, setAttributes, buttonStyles }) => {
	const {
		search_box_button_text,
		search_box_placeholder,
		taxonomy_filter_label,
		buttonPosition,
		has_taxonomy_filter,
		buttonUseText,
		taxonomy_filter_visibility,
	} = attributes

	const hasVisibleTaxonomyFilter = has_taxonomy_filter === 'yes'
	const shouldWrapPseudoInput =
		buttonPosition === 'outside' && hasVisibleTaxonomyFilter
	const submitButtonType =
		buttonUseText === 'yes'
			? 'text'
			: buttonPosition === 'inside'
				? 'minimal:icon'
				: 'icon'

	const searchField = (
		<input
			type="search"
			value={search_box_placeholder}
			onChange={(e) => {
				setAttributes({
					search_box_placeholder: e.target.value,
				})
			}}
			placeholder="Search"
			name="s"
			autoComplete="off"
			title="Search for..."
			aria-label="Search for..."
		/>
	)

	const taxonomyField = hasVisibleTaxonomyFilter ? (
					<div
						className={cls('ct-fake-select', 'ct-select-taxonomy', {
							'ct-hidden-lg': !taxonomy_filter_visibility.desktop,
							'ct-hidden-md': !taxonomy_filter_visibility.tablet,
							'ct-hidden-sm': !taxonomy_filter_visibility.mobile,
						})}>
						<RichText
							tagName="span"
							ref={clearInlineWhiteSpaceRef}
							value={taxonomy_filter_label}
							placeholder="Select Category"
							allowedFormats={[]}
							onChange={(content) =>
								setAttributes({
									taxonomy_filter_label: content,
								})
							}
						/>
					</div>
				) : null

	const formatContent = useCallback(
		(content) => {
			const virtualContainer = document.createElement('div')
			virtualContainer.innerHTML = content

			const input = virtualContainer.querySelector('[type="search"]')

			if (input) {
				input.setAttribute('placeholder', search_box_placeholder)
			}

			const searchBox = virtualContainer.querySelector('.ct-search-box')

			searchBox.style = ''

			return virtualContainer.innerHTML
		},
		[search_box_placeholder, buttonPosition, buttonStyles]
	)

	const { isLoading } = useDynamicPreview(
		'search',
		{
			...attributes,
			...OVERWRITE_ATTRIBUTES,
		},
		formatContent
	)

	return isLoading ? (
		<Spinner />
	) : (
		<form
			role="search"
			method="get"
			onSubmit={(event) => {
				event.preventDefault()
			}}
			className="ct-search-form"
			data-form-controls={buttonPosition}
			data-taxonomy-filter={
				has_taxonomy_filter === 'yes' ? 'true' : 'false'
			}
			data-submit-button={submitButtonType}>
			<div
				className={cls('ct-search-form-inner', {
					'ct-pseudo-input': buttonPosition === 'inside',
				})}>
				{shouldWrapPseudoInput ? (
					<div className="ct-pseudo-input">
						{searchField}
						{taxonomyField}
					</div>
				) : (
					<>
						{searchField}
						{taxonomyField}
					</>
				)}

				<div
					className="wp-element-button"
					aria-label="Search button"
					style={buttonStyles}>
					{buttonUseText === 'yes' ? (
						<RichText
							tagName="span"
							identifier="search_box_button_text"
							ref={clearInlineWhiteSpaceRef}
							value={search_box_button_text}
							placeholder="Search"
							disableLineBreaks
							allowedFormats={[]}
							onChange={(content) =>
								setAttributes({
									search_box_button_text: content,
								})
							}
						/>
					) : (
						<svg
							className="ct-icon ct-search-button-content"
							aria-hidden="true"
							width="15"
							height="15"
							viewBox="0 0 15 15">
							<path d="M14.8,13.7L12,11c0.9-1.2,1.5-2.6,1.5-4.2c0-3.7-3-6.8-6.8-6.8S0,3,0,6.8s3,6.8,6.8,6.8c1.6,0,3.1-0.6,4.2-1.5l2.8,2.8c0.1,0.1,0.3,0.2,0.5,0.2s0.4-0.1,0.5-0.2C15.1,14.5,15.1,14,14.8,13.7z M1.5,6.8c0-2.9,2.4-5.2,5.2-5.2S12,3.9,12,6.8S9.6,12,6.8,12S1.5,9.6,1.5,6.8z" />
						</svg>
					)}
					<span className="ct-ajax-loader">
						<svg viewBox="0 0 24 24">
							<circle
								cx="12"
								cy="12"
								r="10"
								opacity="0.2"
								fill="none"
								stroke="currentColor"
								strokeMiterlimit="10"
								strokeWidth="2"></circle>

							<path
								d="m12,2c5.52,0,10,4.48,10,10"
								fill="none"
								stroke="currentColor"
								strokeLinecap="round"
								strokeMiterlimit="10"
								strokeWidth="2">
								<animateTransform
									attributeName="transform"
									attributeType="XML"
									type="rotate"
									dur="0.6s"
									from="0 12 12"
									to="360 12 12"
									repeatCount="indefinite"></animateTransform>
							</path>
						</svg>
					</span>
				</div>
			</div>
		</form>
	)
}

export default Preview
