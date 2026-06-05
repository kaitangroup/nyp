import { useMemo, useState, useEffect } from '@wordpress/element'

import { getStableJsonKey } from '@creative-themes/wordpress-helpers/get-stable-json-key'
import cachedFetch from '@creative-themes/wordpress-helpers/cached-fetch'

// TODO: maybe rename this hook to show that it can be used for something else
// other than custom fields.
//
// Potentially, termId can also be provided to get term data.
const useCustomFieldData = ({ fieldsContext, fieldDescriptor }) => {
	const [fieldData, setFieldData] = useState({})

	const { type, post_type, post_id } = fieldsContext
	const { provider, id, attributes, ...rest } = fieldDescriptor

	const requestDescriptor = useMemo(() => {
		const url = `${wp.ajax.settings.url}?action=blocksy_dynamic_data_block_custom_field_data`

		const body = {
			context: { type, post_type, post_id },

			field_provider: provider,
			field_id: id,

			// optional attributes
			attributes,
		}

		return {
			url,
			body,
			cacheKey: getStableJsonKey({ ...body, url }),
		}
	}, [type, post_type, post_id, provider, id, attributes])

	useEffect(() => {
		cachedFetch(requestDescriptor.url, requestDescriptor.body)
			.then((response) => response.json())
			.then(({ success, data }) => {
				if (!success) {
					return
				}

				setFieldData((prev) => ({
					...prev,
					[requestDescriptor.cacheKey]: data.field_data,
				}))
			})
	}, [requestDescriptor])

	let fieldDataResult = null

	if (fieldData[requestDescriptor.cacheKey]) {
		fieldDataResult = fieldData[requestDescriptor.cacheKey]
	}

	return {
		fieldData: fieldDataResult,
	}
}

export default useCustomFieldData
