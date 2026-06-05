import { createElement, RawHTML } from '@wordpress/element'

import { Spinner } from '@wordpress/components'

import { __ } from 'ct-i18n'
import { useTaxBlockData } from './hooks/use-tax-block-data'

const Preview = ({ attributes }) => {
	const { blockData } = useTaxBlockData({ attributes })

	if (!blockData || !blockData.block) {
		return <Spinner />
	}

	const isSlideshow = attributes.has_slideshow === 'yes'

	return (
		<>
			<RawHTML>{blockData.block}</RawHTML>
			{blockData && blockData.dynamic_styles && (
				<style>{blockData.dynamic_styles}</style>
			)}
			{blockData &&
				attributes.has_pagination === 'yes' &&
				!isSlideshow &&
				blockData.pagination_output && (
					<RawHTML>{blockData.pagination_output}</RawHTML>
				)}
		</>
	)
}

export default Preview
