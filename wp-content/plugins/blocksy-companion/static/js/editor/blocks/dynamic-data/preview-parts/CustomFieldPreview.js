import { createElement } from '@wordpress/element'
import useCustomFieldData from '../hooks/use-custom-field-data'

import CustomTextField from './custom/CustomTextField'
import CustomImageField from './custom/CustomImageField'

const CustomFieldPreview = ({
	fieldDescriptor,

	fieldsContext,

	attributes,
}) => {
	const { fieldData } = useCustomFieldData({
		fieldDescriptor,
		fieldsContext,
	})

	if (fieldDescriptor.type === 'image') {
		return (
			<CustomImageField
				fieldData={fieldData}
				fieldDescriptor={fieldDescriptor}
				attributes={attributes}
			/>
		)
	}

	return (
		<CustomTextField
			fieldData={fieldData}
			fieldDescriptor={fieldDescriptor}
			attributes={attributes}
		/>
	)
}

export default CustomFieldPreview
