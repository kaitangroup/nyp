<?php
namespace NYP\Modules\Intake;
class IntakeOrderStorage {
    public function register(): void {

        add_action(
            'template_redirect',
            [$this, 'saveProjectInfo']
        );
    }

    public function saveProjectInfo(): void
{
if (!isset($_POST['nyp_nonce'])) {
return;
}


if (
    !wp_verify_nonce(
        sanitize_text_field(
            wp_unslash($_POST['nyp_nonce'])
        ),
        'nyp_save_project_info'
    )
) {
    return;
}

$orderId = absint($_POST['order_id'] ?? 0);

$order = wc_get_order($orderId);

if (!$order) {
    return;
}

$fields = [

    // Section 1
    '_nyp_project_name'         => 'project_name',
    '_nyp_reference_number'     => 'reference_number',
    '_nyp_studio_contact_person' => 'studio_contact_person',

    '_nyp_planning_category'
    => 'planning_category',

'_nyp_package_validation_confirmation'
    => 'package_validation_confirmation',

    // Section 2
   
    '_nyp_kitchen_layout'
    => 'kitchen_layout',

'_nyp_ceiling_height'
    => 'ceiling_height',
 


    // Section 3
    '_nyp_manufacturer'         => 'manufacturer',
    '_nyp_finish_concept'
    => 'finish_concept',

'_nyp_handle_preference'
    => 'handle_preference',

    '_nyp_product_line'         => 'product_line',


    // Section 4
    '_nyp_reuse_appliances'     => 'reuse_appliances',
    '_nyp_appliance_brand'      => 'appliance_brand',
    '_nyp_cooktop'              => 'cooktop',
    '_nyp_oven'                 => 'oven',
    '_nyp_microwave'            => 'microwave',
    '_nyp_refrigerator'         => 'refrigerator',
    '_nyp_freezer'              => 'freezer',
    '_nyp_dishwasher'           => 'dishwasher',
    '_nyp_extractor_hood'       => 'extractor_hood',

    // Section 5 - Design Requirements

'_nyp_design_style'          => 'design_style',
'_nyp_color_scheme'          => 'color_scheme',
'_nyp_worktop_preference'    => 'worktop_preference',
'_nyp_handle_preference'     => 'handle_preference',

// Section 6 - Worktop / Niche / Ergonomics

'_nyp_worktop_material' => 'worktop_material',
'_nyp_worktop_thickness' => 'worktop_thickness',
'_nyp_work_height' => 'work_height',
'_nyp_corpus_height' => 'corpus_height',
'_nyp_plinth_height' => 'plinth_height',
'_nyp_niche_cladding' => 'niche_cladding',
'_nyp_corpus_material' => 'corpus_material',

// section 7 - Appliances / Sink / Tap
'_nyp_sink_model' => 'sink_model',
'_nyp_sink_finish' => 'sink_finish',
'_nyp_tap_model' => 'tap_model',
'_nyp_tap_finish' => 'tap_finish',



// Section 8 - Budget & Equipment Level

'_nyp_furniture_level'             => 'furniture_level',
'_nyp_manufacturer_fixed'          => 'manufacturer_fixed',
'_nyp_manufacturer_fixed_details'  => 'manufacturer_fixed_details',
'_nyp_appliance_class'             => 'appliance_class',
'_nyp_worktop_level'               => 'worktop_level',
'_nyp_budget_range'                => 'budget_range',
'_nyp_budget_scope'                => 'budget_scope',
'_nyp_planning_priority'           => 'planning_priority',


// Section 9 - Planning Software & Delivery Format

'_nyp_planning_software_used' => 'planning_software_used',
'_nyp_software_version'       => 'software_version',
'_nyp_delivery_format'        => 'delivery_format',
'_nyp_drw_required'           => 'drw_required',
'_nyp_renderings_required'    => 'renderings_required',


// Section 10 - Special Wishes / No-Gos

'_nyp_customer_priority' => 'customer_priority',

];


foreach ($fields as $metaKey => $fieldName) {

    $value = sanitize_text_field(
        wp_unslash($_POST[$fieldName] ?? '')
    );

    $order->update_meta_data(
        $metaKey,
        $value
    );
}

// Textareas

$order->update_meta_data(
    '_nyp_design_concept',
    sanitize_textarea_field(
        wp_unslash(
            $_POST['design_concept'] ?? ''
        )
    )
);

$order->update_meta_data(
    '_nyp_must_have_features',
    sanitize_textarea_field(
        wp_unslash(
            $_POST['must_have_features'] ?? ''
        )
    )
);

$order->update_meta_data(
    '_nyp_nice_to_have_features',
    sanitize_textarea_field(
        wp_unslash(
            $_POST['nice_to_have_features'] ?? ''
        )
    )
);

$order->update_meta_data(
    '_nyp_no_gos',
    sanitize_textarea_field(
        wp_unslash(
            $_POST['no_gos'] ?? ''
        )
    )
);

$order->update_meta_data(
    '_nyp_planning_notes',
    sanitize_textarea_field(
        wp_unslash(
            $_POST['planning_notes'] ?? ''
        )
    )
);

$order->update_meta_data(
    '_nyp_delivery_notes',
    sanitize_textarea_field(
        wp_unslash(
            $_POST['delivery_notes'] ?? ''
        )
    )
);

$order->update_meta_data(
    '_nyp_budget_notes',
    sanitize_textarea_field(
        wp_unslash(
            $_POST['budget_notes'] ?? ''
        )
    )
);

$order->update_meta_data(
    '_nyp_water_system_requirements',
    sanitize_textarea_field(
        wp_unslash(
            $_POST['water_system_requirements'] ?? ''
        )
    )
);

$order->update_meta_data(
    '_nyp_sink_tap_notes',
    sanitize_textarea_field(
        wp_unslash(
            $_POST['sink_tap_notes'] ?? ''
        )
    )
);

$order->update_meta_data(
    '_nyp_ergonomics_notes',
    sanitize_textarea_field(
        wp_unslash(
            $_POST['ergonomics_notes'] ?? ''
        )
    )
);

$order->update_meta_data(
    '_nyp_installation_address',
    sanitize_textarea_field(
        wp_unslash(
            $_POST['installation_address'] ?? ''
        )
    )
);

$order->update_meta_data(
    '_nyp_layout_notes',
    sanitize_textarea_field(
        wp_unslash(
            $_POST['layout_notes'] ?? ''
        )
    )
);

$order->update_meta_data(
    '_nyp_manufacturer_notes',
    sanitize_textarea_field(
        wp_unslash(
            $_POST['manufacturer_notes'] ?? ''
        )
    )
);

$order->update_meta_data(
    '_nyp_appliance_notes',
    sanitize_textarea_field(
        wp_unslash(
            $_POST['appliance_notes'] ?? ''
        )
    )
);

$order->update_meta_data(
    '_nyp_lighting_requirements',
    sanitize_textarea_field(
        wp_unslash(
            $_POST['lighting_requirements'] ?? ''
        )
    )
);

$order->update_meta_data(
    '_nyp_storage_requirements',
    sanitize_textarea_field(
        wp_unslash(
            $_POST['storage_requirements'] ?? ''
        )
    )
);

$order->update_meta_data(
    '_nyp_customer_requests',
    sanitize_textarea_field(
        wp_unslash(
            $_POST['customer_requests'] ?? ''
        )
    )
);

$order->update_meta_data(
    '_nyp_design_notes',
    sanitize_textarea_field(
        wp_unslash(
            $_POST['design_notes'] ?? ''
        )
    )
);

$order->update_meta_data(
    '_nyp_category_confirmation',
    isset($_POST['category_confirmation'])
        ? 'yes'
        : 'no'
);

$order->update_meta_data(
    '_nyp_budget_notes',
    sanitize_textarea_field(
        wp_unslash(
            $_POST['budget_notes'] ?? ''
        )
    )
);

$order->update_meta_data(
    '_nyp_delivery_notes',
    sanitize_textarea_field(
        wp_unslash(
            $_POST['delivery_notes'] ?? ''
        )
    )
);

$order->update_meta_data(
    '_nyp_must_have_features',
    sanitize_textarea_field(
        wp_unslash(
            $_POST['must_have_features'] ?? ''
        )
    )
);

$order->update_meta_data(
    '_nyp_nice_to_have_features',
    sanitize_textarea_field(
        wp_unslash(
            $_POST['nice_to_have_features'] ?? ''
        )
    )
);

$order->update_meta_data(
    '_nyp_no_gos',
    sanitize_textarea_field(
        wp_unslash(
            $_POST['no_gos'] ?? ''
        )
    )
);

$order->update_meta_data(
    '_nyp_planning_notes',
    sanitize_textarea_field(
        wp_unslash(
            $_POST['planning_notes'] ?? ''
        )
    )
);

$order->update_meta_data(
    '_nyp_confirm_measurements',
    isset($_POST['confirm_measurements'])
        ? 'yes'
        : 'no'
);

$order->update_meta_data(
    '_nyp_confirm_category_review',
    isset($_POST['confirm_category_review'])
        ? 'yes'
        : 'no'
);

$order->update_meta_data(
    '_nyp_confirm_planning_basis',
    isset($_POST['confirm_planning_basis'])
        ? 'yes'
        : 'no'
);

$order->update_meta_data(
    '_nyp_confirm_budget_guidance',
    isset($_POST['confirm_budget_guidance'])
        ? 'yes'
        : 'no'
);

$order->update_meta_data(
    '_nyp_confirm_delivery_requirements',
    isset($_POST['confirm_delivery_requirements'])
        ? 'yes'
        : 'no'
);

$order->update_meta_data(
    '_nyp_confirm_execution_responsibility',
    isset($_POST['confirm_execution_responsibility'])
        ? 'yes'
        : 'no'
);

$order->save();

$action = sanitize_text_field(
    wp_unslash(
        $_POST['nyp_action'] ?? 'save_draft'
    )
);

if ($action === 'submit_brief') {

    // Basic Validation

    // $projectName = $order->get_meta('_nyp_project_name');
    // $customerName = $order->get_meta('_nyp_customer_name');
    // $address = $order->get_meta('_nyp_installation_address');
    // $floorPlan = $order->get_meta('_nyp_floor_plan');

    // if (
    //     empty($projectName) ||
    //     empty($customerName) ||
    //     empty($address)
    // ) {

    //     wp_safe_redirect(
    //         add_query_arg(
    //             'error',
    //             'missing_required_fields',
    //             wp_get_referer()
    //         )
    //     );

    //     exit;
    // }

    // if (empty($floorPlan)) {

    //     wp_safe_redirect(
    //         add_query_arg(
    //             'error',
    //             'floor_plan_required',
    //             wp_get_referer()
    //         )
    //     );

    //     exit;
    // }

    // Mark submitted

    $requiredConfirmations = [

        'confirm_measurements',
        'confirm_scope_review',
        'confirm_scope_adjustment',
        'confirm_planning_quality',
        'confirm_budget_guidance',
        'confirm_nyp_responsibility',
    
    ];
    
    foreach ($requiredConfirmations as $field) {
    
        if (empty($_POST[$field])) {
    
            wp_die(
                'Please accept all required confirmations.'
            );
        }
    }

    $order->update_meta_data(
        '_nyp_brief_submitted',
        1
    );

    $order->update_meta_data(
        '_nyp_brief_submitted_at',
        current_time('mysql')
    );

    $order->update_meta_data(
        '_nyp_brief_submitted_by',
        get_current_user_id()
    );


    $order->save();

    // wc-pending-intake

    $order->update_status(
        'wc-intake-submitted',
        'Planning brief submitted by partner.'
    );

    wp_safe_redirect(
        add_query_arg(
            'submitted',
            '1',
            wp_get_referer()
        )
    );

    exit;
}



}



}
