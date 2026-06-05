<?php

$mobile_menu_type = blocksy_default_akg('mobile_menu_type', $atts, 'type-1');

$mobile_menu_interactive = blocksy_akg('mobile_menu_interactive', $atts, 'yes');

if ($mobile_menu_interactive === 'yes') {
	$attr['data-interaction'] = 'click';
	$attr['data-toggle-type'] = blocksy_akg(
		'mobile_menu_toggle_shape',
		$atts,
		'type-1'
	);
}

$attr['data-submenu-dots'] = blocksy_akg('mobile_menu_submenu_dots', $atts, 'yes');


ob_start();

$menu_args = [];

$menu = blocksy_default_akg('menu', $atts, 'blocksy_location');

if (! wp_get_nav_menu_object($menu) && $menu !== 'blocksy_location') {
	$menu = 'blocksy_location';
}

$menu_object = null;

if ($menu === 'blocksy_location') {
	$theme_locations = get_nav_menu_locations();

	$menu_object = wp_get_nav_menu_object('');

	if (isset($theme_locations[$location])) {
		$menu_object = get_term($theme_locations[$location], 'nav_menu');
	}
} else {
	$menu_args['menu'] = $menu;

	$menu_object = wp_get_nav_menu_object($menu);
}

$menu_args['child_indicator_wrapper'] = 'yes';

$menu_args['child_indicator_type'] = $mobile_menu_type;

if ($mobile_menu_interactive !== 'yes') {
	$menu_args['child_indicator_type'] = 'skip';
}

add_filter(
	'walker_nav_menu_start_el',
	'blocksy_handle_nav_menu_start_el',
	10, 4
);

add_filter(
	'nav_menu_item_title',
	'blocksy_handle_nav_menu_item_title',
	10, 4
);

$mobile_menu_interactive_auto_expand = blocksy_default_akg(
	'mobile_menu_interactive_auto_expand',
	$atts,
	'no'
);

$mobile_menu_interactive = blocksy_akg(
	'mobile_menu_interactive',
	$atts,
	'yes'
);

wp_nav_menu($menu === 'blocksy_location' ? array_merge([
	'container' => false,
	'menu_class' => false,
	'fallback_cb' => 'blocksy_main_menu_fallback',
	'blocksy_advanced_item' => true,
	'blocksy_should_add_dropdown_active' => $mobile_menu_interactive_auto_expand === 'yes' && $mobile_menu_interactive === 'yes',
	'theme_location' => $location,
], $menu_args) : array_merge([
	'container' => false,
	'menu_class' => false,
	'fallback_cb' => 'blocksy_main_menu_fallback',
	'blocksy_advanced_item' => true,
	'blocksy_should_add_dropdown_active' => $mobile_menu_interactive_auto_expand === 'yes' && $mobile_menu_interactive === 'yes',
], $menu_args));

remove_filter(
	'nav_menu_item_title',
	'blocksy_handle_nav_menu_item_title',
	10, 4
);

remove_filter(
	'walker_nav_menu_start_el',
	'blocksy_handle_nav_menu_start_el',
	10, 4
);

$menu_output = ob_get_clean();

$class = 'mobile-menu menu-container';

if (strpos($menu_output, 'sub-menu')) {
	$class .= ' has-submenu';
}

$aria_label = '';

if ($menu_object && isset($menu_object->name)) {
	$aria_label = 'aria-label="' . esc_attr($menu_object->name) . '"';
}

?>

<nav
	class="<?php echo $class ?>"
	<?php echo blocksy_attr_to_html($attr) ?>
	<?php echo $aria_label ?>>

	<?php echo $menu_output ?>
</nav>

