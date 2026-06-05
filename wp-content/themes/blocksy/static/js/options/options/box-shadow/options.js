import { createElement } from '@wordpress/element'
import { animated } from 'react-spring'
import classnames from 'classnames'
import GenericOptionType from '../../GenericOptionType'
import { __ } from 'ct-i18n'
import Switch from '../ct-switch'

const BoxShadowOptions = ({
	option,
	value,
	onChange,

	hOffsetRef,
	vOffsetRef,
	blurRef,
	spreadRef,

	modalRef,

	style,
	popoverProps,
}) => {
	return (
		<animated.div
			style={style}
			{...popoverProps}
			className="ct-option-modal ct-box-shadow-modal"
			onClick={(e) => {
				e.preventDefault()
			}}
			ref={modalRef}>
			<div className="ct-shadow-trigger">
				<label>{__('Enable/Disable', 'blocksy')}</label>
				<Switch
					value={value.enable ? 'yes' : 'no'}
					onChange={() => {
						onChange({
							...value,
							enable: !value.enable,
						})
					}}
				/>
			</div>

			<div className="shadow-sliders">
				<GenericOptionType
					value={value.h_offset}
					values={value}
					id="h_offset"
					option={{
						id: 'h_offset',
						label: __('Horizontal Offset', 'blocksy'),
						type: 'ct-slider',
						steps: 'half',
						value: option.value.h_offset,
						min: -100,
						max: 100,
						design: 'compact',
						ref: hOffsetRef,
						// skipInput: true
					}}
					hasRevertButton={false}
					onChange={(newValue) =>
						onChange({
							...value,
							h_offset: newValue,
						})
					}
				/>

				<GenericOptionType
					value={value.v_offset}
					values={value}
					id="v_offset"
					option={{
						steps: 'half',
						id: 'v_offset',
						label: __('Vertical Offset', 'blocksy'),
						type: 'ct-slider',
						value: option.value.v_offset,
						min: -100,
						max: 100,
						design: 'compact',
						ref: vOffsetRef,
						// skipInput: true
					}}
					hasRevertButton={false}
					onChange={(newValue) =>
						onChange({
							...value,
							v_offset: newValue,
						})
					}
				/>

				<GenericOptionType
					value={value.blur}
					values={value}
					id="blur"
					option={{
						steps: 'positive',
						id: 'blur',
						label: __('Blur', 'blocksy'),
						type: 'ct-slider',
						value: option.value.blur,
						min: 0,
						max: 100,
						design: 'compact',
						ref: blurRef,
						// skipInput: true
					}}
					hasRevertButton={false}
					onChange={(newValue) => {
						onChange({
							...value,
							blur: newValue,
						})
					}}
				/>

				<GenericOptionType
					value={value.spread}
					values={value}
					id="spread"
					option={{
						steps: 'half',
						id: 'spread',
						label: __('Spread', 'blocksy'),
						type: 'ct-slider',
						value: option.value.spread,
						min: -100,
						max: 100,
						design: 'compact',
						ref: spreadRef,
						// skipInput: true
					}}
					hasRevertButton={false}
					onChange={(newValue) =>
						onChange({
							...value,
							spread: newValue,
						})
					}
				/>
			</div>

			{!option.hide_shadow_placement && (
				<ul className="ct-shadow-style">
					<li
						onClick={() =>
							onChange({
								...value,
								inset: false,
							})
						}
						className={classnames({
							active: !value.inset,
						})}>
						{__('Outline', 'blocksy')}
					</li>
					<li
						onClick={() =>
							onChange({
								...value,
								inset: true,
							})
						}
						className={classnames({
							active: value.inset,
						})}>
						{__('Inset', 'blocksy')}
					</li>
				</ul>
			)}
		</animated.div>
	)
}

export default BoxShadowOptions
