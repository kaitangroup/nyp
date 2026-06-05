<?php

if (! isset($device)) {
	$device = 'desktop';
}

$trigger_type = blocksy_default_akg('mobile_menu_trigger_type', $atts, 'type-1');
$trigger_design = blocksy_default_akg('trigger_design', $atts, 'simple');

$trigger_label_value = blocksy_expand_responsive_value(
	blocksy_default_akg('trigger_label', $atts, __('Menu', 'blocksy'))
);

$trigger_label = blocksy_translate_dynamic(
	$trigger_label_value[$device],
	$panel_type . ':' . $section_id . ':' . $item_id . ':trigger_label'
);

$trigger_aria_label = $trigger_label;

if ('' === trim($trigger_aria_label)) {
	$trigger_aria_label = blocksy_translate_dynamic(
		__('Menu', 'blocksy'),
		$panel_type . ':' . $section_id . ':' . $item_id . ':trigger_label'
	);
}

$class = 'ct-header-trigger ct-toggle';

$visibility = blocksy_default_akg('header_trigger_visibility', $atts, [
	'tablet' => true,
	'mobile' => true,
]);

$class .= ' ' . blocksy_visibility_classes($visibility);

$label_class = 'ct-label';

$label_class .= ' ' . blocksy_visibility_classes(blocksy_akg('trigger_label_visibility', $atts,
	[
		'desktop' => false,
		'tablet' => false,
		'mobile' => false,
	]
));

$trigger_label_alignment = blocksy_expand_responsive_value(
	blocksy_akg('trigger_label_alignment', $atts, 'right')
);

$trigger_class = trim(
	'ct-icon ' .
	blocksy_visibility_classes(
		blocksy_akg('trigger_icon_visibility', $atts, [
			'desktop' => true,
			'tablet' => true,
			'mobile' => true,
		])
	)
);

$svg = blocksy_html_tag(
	'svg',
	[
		'class' => $trigger_class,
		'width' => '18',
		'height' => '14',
		'viewBox' => '0 0 18 14',
		'data-type' => $trigger_type,
		'aria-hidden' => 'true'
	],
	'
		<rect y="0.00" width="18" height="1.7" rx="1"/>
		<rect y="6.15" width="18" height="1.7" rx="1"/>
		<rect y="12.3" width="18" height="1.7" rx="1"/>
	'
);

$svg = apply_filters(
	'blocksy:header:trigger:svg',
	$svg,
	$atts,
	$trigger_class
);

?>

<button
	class="<?php echo esc_attr($class) ?>"
	data-toggle-panel="#offcanvas"
	aria-controls="offcanvas"
	data-design="<?php echo $trigger_design ?>"
	data-label="<?php echo $trigger_label_alignment[$device] ?>"
	aria-label="<?php echo $trigger_aria_label ?>"
	<?php echo blocksy_attr_to_html($attr) ?>>

	<span class="<?php echo $label_class ?>" aria-hidden="true"><?php echo $trigger_label ?></span>

	<?php echo $svg ?>
</button>
