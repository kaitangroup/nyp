import { createElement, useEffect, useMemo } from '@wordpress/element'
import { __ } from 'ct-i18n'

import Preview from './Preview'

import AlignmentControls from './components/AlignmentControls'

import useDynamicDataDescriptor from './hooks/use-dynamic-data-descriptor'

import DynamicDataInspectorControls from './components/InspectorControls'

import { useTaxonomies } from '../query/edit/utils/utils'
import { useFieldsContext } from './hooks/use-fields-context'

const Edit = ({
	clientId,

	attributes,
	setAttributes,

	context,

	name,
	__unstableParentLayout,
}) => {
	const { postType, taxonomy } = context

	const {
		fieldsDescriptor,
		linkFieldsChoices,
		options,
		fieldsChoices,
		fullDescriptorLoaded,
	} = useDynamicDataDescriptor(context)

	const taxonomies = useTaxonomies(postType)

	const fieldDescriptor = useMemo(() => {
		if (!attributes.field || !fieldsDescriptor) {
			return null
		}

		const [provider, field] = attributes.field.split(':')

		const providerFields = fieldsDescriptor.fields.find(
			({ provider: p }) => p === provider
		)

		if (!providerFields) {
			return null
		}

		const maybeFieldDescriptor = providerFields.fields.find(
			({ id }) => id === field
		)

		if (!maybeFieldDescriptor) {
			return null
		}

		return {
			...maybeFieldDescriptor,
			provider: providerFields.provider,
		}
	}, [attributes.field, fieldsDescriptor])

	const isFieldMissing = useMemo(() => {
		if (!attributes.field || !fieldsDescriptor) {
			return false
		}

		const [provider, field] = attributes.field.split(':')

		let providerFields = fieldsDescriptor.fields.find(
			({ provider: p }) => p === provider
		)

		// If we found the provider, check for the field and return the result.
		if (providerFields) {
			const maybeFieldDescriptor = providerFields.fields.find(
				({ id }) => id === field
			)

			return !maybeFieldDescriptor
		}

		// No field is missing if the full descriptor is not loaded yet.
		if (!fullDescriptorLoaded) {
			return false
		}

		providerFields = fieldsDescriptor.fields.find(
			({ provider: p }) => p === provider
		)

		// If we still don't have the provider, the field is missing.
		if (!providerFields) {
			return true
		}

		const maybeFieldDescriptor = providerFields.fields.find(
			({ id }) => id === field
		)

		// If we don't have the field, it's missing.
		return !maybeFieldDescriptor
	}, [attributes.field, fieldDescriptor, fullDescriptorLoaded])

	if (isFieldMissing) {
		console.warn('Field is missing:', attributes.field)
	}

	useEffect(() => {
		if (attributes.field === 'wp:title' && taxonomy) {
			setAttributes({
				field: 'wp:term_title',
			})
		}
	}, [taxonomy, attributes.field])

	const fieldsContext = useFieldsContext(context)

	return (
		<>
			<AlignmentControls
				fieldDescriptor={fieldDescriptor}
				attributes={attributes}
				setAttributes={setAttributes}
			/>

			<Preview
				attributes={attributes}
				fieldsDescriptor={fieldsDescriptor}
				isFieldMissing={isFieldMissing}
				fieldDescriptor={fieldDescriptor}
				fieldsContext={fieldsContext}
				{...context}
			/>

			<DynamicDataInspectorControls
				options={options}
				fieldDescriptor={fieldDescriptor}
				attributes={attributes}
				setAttributes={setAttributes}
				fieldsChoices={fieldsChoices}
				clientId={clientId}
				fieldsDescriptor={fieldsDescriptor}
				linkFieldsChoices={linkFieldsChoices}
				taxonomies={taxonomies}
				{...context}
				name={name}
				__unstableParentLayout={__unstableParentLayout}
			/>
		</>
	)
}

export default Edit
