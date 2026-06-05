<?php

$prefix = 'eventkoi_event_single_';

$options = [
	'eventkoi_event_single_options' => [
		'type' => 'ct-options',
		'inner-options' => [
			blocksy_get_options('general/page-title', [
				'prefix' => 'eventkoi_event_single',
				'is_single' => true,
				'is_cpt' => true,
				'enabled_label' => blocksy_safe_sprintf(
					__('%s Title', 'blocksy'),
					'Event'
				),				
				'location_name' => __('Event Single', 'blocksy')
			]),

			blocksy_rand_md5() => [
				'type' => 'ct-title',
				'label' => __( 'Event Structure', 'blocksy' ),
			],

			blocksy_rand_md5() => [
				'title' => __( 'General', 'blocksy' ),
				'type' => 'tab',
				'options' => [
					blocksy_get_options('single-elements/structure', [
						'default_structure' => 'type-4',
						'prefix' => 'eventkoi_event_single',
					]),
				],
			],

			blocksy_rand_md5() => [
				'title' => __( 'Design', 'blocksy' ),
				'type' => 'tab',
				'options' => [

					blocksy_get_options('single-elements/structure-design', [
						'prefix' => 'eventkoi_event_single',
					])

				],
			],

		]
	]
];

