import { useEffect } from '@wordpress/element'
import { select } from '@wordpress/data'

export const getAttributes = () => {
	return {
		uniqueId: {
			type: 'string',
			default: '',
		},
	}
}

const shortenId = (clientId) => clientId.split('-')[0]

function isDuplicate({ attributes, blockType }) {
	const iframes = document.querySelectorAll('iframe')

	const allDocuments = [
		document,
		...Array.from(iframes)
			.filter(
				(iframe) =>
					!iframe.closest('.block-editor-block-preview__container')
			)
			.map((iframe) => iframe.contentDocument)
			.filter((cD) => cD),
	]

	const elements = allDocuments.flatMap((document) => {
		return [
			...document.querySelectorAll(
				`[data-id="${attributes.uniqueId}"][data-type="${blockType}"]`
			),
		]
	})

	if (elements.length > 1) {
		return true
	}

	return false
}

// Add support for this, otherwise IDs will be duplicated on clone
// https://github.com/WordPress/gutenberg/pull/38643
export const useUniqueId = ({
	attributes,
	clientId,
	setAttributes,
	blockType,
}) => {
	useEffect(() => {
		if (
			!attributes.uniqueId ||
			isDuplicate({ attributes, clientId, blockType })
		) {
			setAttributes({
				uniqueId: shortenId(clientId),
			})
		}
	}, [clientId])

	let uniqueId = attributes.uniqueId || shortenId(clientId)

	return {
		uniqueId,
		props: {
			'data-id': uniqueId,
		},
	}
}

export const useSaveUniqueId = (props) => {
	const { attributes, clientId } = props

	return {
		uniqueId: attributes.uniqueId || clientId || '',

		props: attributes.uniqueId
			? {
					'data-id': attributes.uniqueId,
			  }
			: {},
	}
}
