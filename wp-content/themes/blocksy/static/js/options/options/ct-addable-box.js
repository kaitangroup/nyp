import {
	createElement,
	Component,
	useContext,
	createContext,
	useState,
	Fragment,
} from '@wordpress/element'
import classnames from 'classnames'
import {
	DndContext,
	closestCenter,
	PointerSensor,
	useSensor,
	useSensors,
} from '@dnd-kit/core'
import {
	restrictToVerticalAxis,
	restrictToParentElement,
} from '@dnd-kit/modifiers'
import {
	arrayMove,
	SortableContext,
	verticalListSortingStrategy,
} from '@dnd-kit/sortable'
import { useSortable } from '@dnd-kit/sortable'

import { __ } from 'ct-i18n'
import LayerControls from './ct-addable-box/LayerControls'

import OptionsPanel from '../OptionsPanel'
import { getValueFromInput } from '../helpers/get-value-from-input'
import { nanoid } from 'nanoid'

const valueWithUniqueIds = (value) =>
	value.map((singleItem) => ({
		...singleItem,

		...(singleItem.__id
			? {}
			: {
					__id: nanoid(),
				}),
	}))

const getDefaultState = () => ({
	currentlyPickedItem: null,
	isDragging: false,
	isOpen: false,
})

export const LayersContext = createContext(getDefaultState())

const { Provider, Consumer } = LayersContext

const SortableItem = ({ items, onChange, value, disabled }) => {
	const {
		attributes,
		listeners,
		setNodeRef,
		transform,
		transition,
		isDragging,
	} = useSortable({
		id: value.__id,
		disabled,
	})

	const style = {
		transform: transform
			? `translate3d(${transform.x}px, ${transform.y}px, 0)`
			: undefined,
		transition,
		zIndex: isDragging ? 9999999 : undefined,
		position: isDragging ? 'relative' : undefined,
	}

	return (
		<Consumer>
			{({
				option,
				isDragging: contextIsDragging,
				isOpen,
				parentValue,
			}) => (
				<li
					ref={setNodeRef}
					style={style}
					className={classnames('ct-layer', option.itemClass, {
						[`ct-disabled`]: !{ enabled: true, ...value }.enabled,
						[`ct-active`]:
							isOpen === value.__id &&
							(!contextIsDragging ||
								(contextIsDragging &&
									contextIsDragging !== isOpen)),
					})}>
					<LayerControls
						items={items}
						onChange={onChange}
						value={value}
						dragHandleProps={{
							...attributes,
							...listeners,
							style: {
								cursor: isDragging ? 'grabbing' : 'grab',
							},
						}}
					/>

					{isOpen === value.__id &&
						(!contextIsDragging ||
							(contextIsDragging &&
								contextIsDragging !== isOpen)) && (
							<div className="ct-layer-content">
								<OptionsPanel
									hasRevertButton={false}
									parentValue={parentValue}
									onChange={(key, newValue) => {
										onChange(
											items.map((l) =>
												l.__id === value.__id
													? {
															...l,
															[key]: newValue,
														}
													: l
											)
										)
									}}
									value={getValueFromInput(
										option['inner-options'],
										{
											...(option.value.filter(
												({ id }) => id === value.id
											).length > 1
												? option.value.filter(
														({ id }) =>
															value.id === id
													)[
														items
															.filter(
																({ id }) =>
																	id ===
																	value.id
															)
															.map(
																({ __id }) =>
																	__id
															)
															.indexOf(value.__id)
													]
												: {}),
											...value,
										}
									)}
									options={option['inner-options']}
								/>
							</div>
						)}
				</li>
			)}
		</Consumer>
	)
}

const Layers = ({ value, option, onChange, values }) => {
	const [state, setState] = useState(getDefaultState())

	const sensors = useSensors(useSensor(PointerSensor))

	const localOnChange = (v) => {
		onChange(v)
	}

	const addForId = (val = {}) => {
		localOnChange([
			...(value || []),
			{
				enabled: true,
				...getValueFromInput(option['inner-options'] || {}, {}),
				...val,
				__id: nanoid(),
			},
		])
	}

	const computedValue = valueWithUniqueIds(value)

	return (
		<Provider
			value={{
				...state,
				parentValue: values,
				addForId,
				option,

				removeForId: (idToRemove) =>
					localOnChange(
						valueWithUniqueIds(value).filter(
							({ __id: id }) => id !== idToRemove
						)
					),

				toggleOptionsPanel: (idToAdd) => {
					if (value.length > 0 && !value[0].__id) {
						localOnChange(computedValue)
					}

					setState((state) => ({
						...state,
						isOpen: state.isOpen === idToAdd ? false : idToAdd,
					}))
				},
			}}>
			<DndContext
				sensors={sensors}
				collisionDetection={closestCenter}
				modifiers={[restrictToVerticalAxis, restrictToParentElement]}
				onDragEnd={(event) => {
					const { active, over } = event

					if (!over) {
						return
					}

					if (active.id !== over.id) {
						const oldIndex = computedValue.findIndex(
							(item) => item.__id === active.id
						)
						const newIndex = computedValue.findIndex(
							(item) => item.__id === over.id
						)

						localOnChange(
							arrayMove(computedValue, oldIndex, newIndex)
						)
					}

					setState((state) => ({
						...state,
						isDragging: false,
					}))
				}}
				onDragStart={(event) => {
					const { active } = event

					if (value.length > 0 && !value[0].__id) {
						wp.customize &&
							wp.customize.previewer &&
							wp.customize.previewer.send(
								'ct:sync:refresh_partial',
								{
									shouldSkip: true,
								}
							)

						localOnChange(computedValue)
					}

					setState((state) => ({
						...state,
						isDragging: active.id,
					}))
				}}>
				<SortableContext
					items={computedValue.map((item) => item.__id)}
					strategy={verticalListSortingStrategy}>
					<Consumer>
						{({ option }) => (
							<ul className="ct-layers">
								{computedValue.map((value, index) => (
									<SortableItem
										key={value.__id}
										onChange={(v) => {
											localOnChange(v)
										}}
										value={value}
										items={computedValue}
										disabled={!!option.disableDrag}
									/>
								))}
							</ul>
						)}
					</Consumer>
				</SortableContext>
			</DndContext>

			<button
				className="ct-ui-button"
				onClick={(e) => {
					e.preventDefault()
					addForId()
				}}>
				{__('Add New Item', 'blocksy')}
			</button>
		</Provider>
	)
}

export default Layers
