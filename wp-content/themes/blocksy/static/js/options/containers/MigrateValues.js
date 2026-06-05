import { createElement, useEffect } from '@wordpress/element'
import OptionsPanel from '../OptionsPanel'

const popupNewCloseActions = (valueDescriptor) => {
	const newValue = {
		...valueDescriptor.value,
	}

	let didChange = false

	if (newValue.close_button_type === 'none') {
		newValue.close_button_type = 'outside'
		newValue.popup_close_button = 'no'

		didChange = true
	}

	if (newValue.popup_additional_close_strategy) {
		newValue.popup_custom_close = 'yes'
		newValue.popup_custom_close_strategy =
			newValue.popup_additional_close_strategy

		newValue.popup_custom_close_button_selector =
			newValue.aditional_close_button_click_selector

		newValue.popup_custom_close_action_delay =
			newValue.popup_additional_close_submit_delay

		delete newValue.popup_additional_close_strategy
		delete newValue.aditional_close_button_click_selector
		delete newValue.popup_additional_close_submit_delay

		didChange = true
	}

	return {
		value: newValue,
		didChange,
	}
}

// When adding new migration, also implement same key in:
// - inc/helpers/options.php
const migrations = {
	popups_new_close_actions: popupNewCloseActions,
}

const MigrateValues = ({
	renderingChunk,
	value,
	onChange,
	onChangeMultiple,
	purpose,
	parentValue,
	hasRevertButton,
}) => {
	useEffect(() => {
		const allMigrations = renderingChunk.flatMap((migrationOption) => {
			return migrationOption.migrations || []
		})

		const migratedValueDescriptor = allMigrations.reduce(
			(valueDescriptor, migration) => {
				const migrationFn = migrations[migration]

				if (!migrationFn) {
					return valueDescriptor
				}

				const migrationResult = migrationFn(valueDescriptor)

				return {
					value: migrationResult.value,
					didChange:
						migrationResult.didChange || valueDescriptor.didChange,
				}
			},
			{
				value,
				didChange: false,
			}
		)

		if (migratedValueDescriptor.didChange) {
			onChangeMultiple(migratedValueDescriptor.value, {
				deleteNonExistent: true,
			})
		}
	}, [])

	return renderingChunk.map((migrationOption) => {
		return (
			<OptionsPanel
				purpose={purpose}
				key={migrationOption.id}
				onChange={onChange}
				options={migrationOption.options}
				value={value}
				hasRevertButton={hasRevertButton}
				parentValue={parentValue}
			/>
		)
	})
}

export default MigrateValues
