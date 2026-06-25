<?php

namespace NYP\Modules\Intake;

if (!defined('ABSPATH')) {
    exit;
}

class IntakeOrderStorage
{
    public function register(): void
    {
        add_action(
            'template_redirect',
            [$this, 'saveProjectInfo']
        );
    }

    public function saveProjectInfo(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Remove uploaded file
        |--------------------------------------------------------------------------
        */

        if (isset($_POST['delete_file'])) {

            $this->removeFile(
                absint($_POST['order_id']),
                sanitize_text_field(
                    wp_unslash(
                        $_POST['delete_file']
                    )
                )
            );

            $returnUrl = esc_url_raw(
                wp_unslash(
                    $_POST['return_url'] ?? ''
                )
            );

            if (!$returnUrl) {

                $returnUrl = home_url();
            }

            wp_safe_redirect($returnUrl);

            exit;
        }

        /*
        |--------------------------------------------------------------------------
        | Not our form
        |--------------------------------------------------------------------------
        */

        if (!isset($_POST['nyp_nonce'])) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Verify nonce
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | Load order
        |--------------------------------------------------------------------------
        */

        $orderId = absint(
            $_POST['order_id'] ?? 0
        );

        if (!$orderId) {
            return;
        }

        $order = wc_get_order(
            $orderId
        );

        if (!$order) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Current action
        |--------------------------------------------------------------------------
        */

        $action = sanitize_text_field(
            wp_unslash(
                $_POST['nyp_action'] ?? 'save_draft'
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Validate upload limits
        |--------------------------------------------------------------------------
        */

        $this->validateUploads();

        /*
        |--------------------------------------------------------------------------
        | Required confirmation validation
        |--------------------------------------------------------------------------
        */

        $returnUrl = esc_url_raw(
            wp_unslash(
                $_POST['return_url'] ?? ''
            )
        );
        
        if (!$returnUrl) {
        
            $returnUrl = home_url();
        }

        if ($action === 'submit_brief') {

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

                    $returnUrl = remove_query_arg(
                        [
                            'submitted',
                            'draft_saved',
                            'nyp_error',
                        ],
                        $returnUrl
                    );
                    
                    $returnUrl = add_query_arg(
                        'nyp_error',
                        'confirmations_required',
                        $returnUrl
                    );

                    exit;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Upload directory
        |--------------------------------------------------------------------------
        */

        $uploadDir = wp_upload_dir();

        $targetDir =
            $uploadDir['basedir']
            . '/nyp-intake/order-'
            . $orderId;

        if (!file_exists($targetDir)) {

            wp_mkdir_p(
                $targetDir
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Process uploads FIRST
        |--------------------------------------------------------------------------
        */

        $this->handleSingleUpload(
            'floor_plan',
            $targetDir,
            $orderId,
            '_nyp_floor_plan'
        );

        $this->handleMultiUpload(
            'kitchen_photos',
            $targetDir,
            $orderId,
            '_nyp_kitchen_photos'
        );

        $this->handleMultiUpload(
            'inspiration_images',
            $targetDir,
            $orderId,
            '_nyp_inspiration_images'
        );

        $this->handleSingleUpload(
            'planning_export',
            $targetDir,
            $orderId,
            '_nyp_planning_export'
        );

        $this->handleMultiUpload(
            'technical_documents',
            $targetDir,
            $orderId,
            '_nyp_technical_documents'
        );

        $this->handleMultiUpload(
            'additional_files',
            $targetDir,
            $orderId,
            '_nyp_additional_files'
        );

        /*
        |--------------------------------------------------------------------------
        | Validate required floor plan AFTER uploads
        |--------------------------------------------------------------------------
        */

        if ($action === 'submit_brief') {

            $existingFloorPlan = $order->get_meta(
                '_nyp_floor_plan'
            );

            if (empty($existingFloorPlan)) {

                $returnUrl = remove_query_arg(
                    [
                        'submitted',
                        'draft_saved',
                        'nyp_error',
                    ],
                    $returnUrl
                );

                $returnUrl = add_query_arg(
                    'nyp_error',
                    'floor_plan_required',
                    $returnUrl
                );

                wp_safe_redirect(
                    $returnUrl
                );

                exit;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Save intake fields
        |--------------------------------------------------------------------------
        */

        $this->saveIntakeFields(
            $order
        );

        /*
        |--------------------------------------------------------------------------
        | Save confirmation checkboxes
        |--------------------------------------------------------------------------
        */

        $this->saveConfirmationFields(
            $order
        );

        /*
        |--------------------------------------------------------------------------
        | Submission status
        |--------------------------------------------------------------------------
        */

        if ($action === 'submit_brief') {

            $order->update_meta_data(
                '_nyp_brief_submitted',
                'yes'
            );

            $order->update_meta_data(
                '_nyp_submission_timestamp',
                current_time(
                    'mysql'
                )
            );

        } else {

            $order->update_meta_data(
                '_nyp_brief_submitted',
                'no'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Persist
        |--------------------------------------------------------------------------
        */

        $order->save();

        /*
        |--------------------------------------------------------------------------
        | Redirect
        |--------------------------------------------------------------------------
        */

        $returnUrl = remove_query_arg(
            [
                'submitted',
                'draft_saved',
                'nyp_error',
            ],
            $returnUrl
        );

        if ($action === 'submit_brief') {

            $returnUrl = add_query_arg(
                'submitted',
                1,
                $returnUrl
            );

        } else {

            $returnUrl = add_query_arg(
                'draft_saved',
                1,
                $returnUrl
            );
        }

        wp_safe_redirect(
            $returnUrl
        );

        exit;
    }

    /*
    --------------------------------------------------------------------------
    Helper methods continue below...
    --------------------------------------------------------------------------
    */

    /*
|--------------------------------------------------------------------------
| Save all intake fields
|--------------------------------------------------------------------------
*/

private function saveIntakeFields(
    \WC_Order $order
): void {

    $fields = [

        // Project

        '_nyp_project_name'              => 'project_name',
        '_nyp_reference_number'          => 'reference_number',
        '_nyp_studio_contact_person'     => 'studio_contact_person',

        // Planning Category

        '_nyp_planning_category'         => 'planning_category',

        // Layout

        '_nyp_kitchen_layout'            => 'kitchen_layout',
        '_nyp_ceiling_height'            => 'ceiling_height',

        // Manufacturer

        '_nyp_manufacturer'              => 'manufacturer',
        '_nyp_product_line'              => 'product_line',
        '_nyp_handle_preference'         => 'handle_preference',
        '_nyp_color_finish_concept'      => 'color_finish_concept',

        // Worktop

        '_nyp_worktop_material'          => 'worktop_material',
        '_nyp_worktop_thickness'         => 'worktop_thickness',
        '_nyp_work_height'               => 'work_height',
        '_nyp_corpus_height'             => 'corpus_height',
        '_nyp_plinth_height'             => 'plinth_height',
        '_nyp_niche_cladding'            => 'niche_cladding',

        // Appliances

        '_nyp_appliance_brand'           => 'appliance_brand',
        '_nyp_cooktop'                   => 'cooktop',
        '_nyp_oven'                      => 'oven',
        '_nyp_microwave'                 => 'microwave',
        '_nyp_refrigerator'              => 'refrigerator',
        '_nyp_freezer'                   => 'freezer',
        '_nyp_dishwasher'                => 'dishwasher',
        '_nyp_extractor_hood'            => 'extractor_hood',

        '_nyp_sink_model'                => 'sink_model',
        '_nyp_sink_finish'               => 'sink_finish',
        '_nyp_tap_model'                 => 'tap_model',
        '_nyp_tap_finish'                => 'tap_finish',

        // Budget

        '_nyp_budget_range'              => 'budget_range',
        '_nyp_planning_priority'         => 'planning_priority',

        // Delivery

        '_nyp_delivery_format'           => 'delivery_format',

    ];

    foreach ($fields as $metaKey => $fieldName) {

        $order->update_meta_data(

            $metaKey,

            sanitize_text_field(
                wp_unslash(
                    $_POST[$fieldName] ?? ''
                )
            )

        );
    }

    /*
    |--------------------------------------------------------------------------
    | Textareas
    |--------------------------------------------------------------------------
    */

    $textareas = [

        '_nyp_layout_notes'                  => 'layout_notes',

        '_nyp_manufacturer_notes'            => 'manufacturer_notes',

        '_nyp_worktop_notes'                 => 'worktop_notes',

        '_nyp_water_system_requirements'     => 'water_system_requirements',

        '_nyp_sink_tap_notes'                => 'sink_tap_notes',

        '_nyp_budget_notes'                  => 'budget_notes',

        '_nyp_delivery_notes'                => 'delivery_notes',

        '_nyp_design_concept'                => 'design_concept',

        '_nyp_must_have_features'            => 'must_have_features',

        '_nyp_nice_to_have_features'         => 'nice_to_have_features',

        '_nyp_no_gos'                        => 'no_gos',

        '_nyp_planning_notes'                => 'planning_notes',

    ];

    foreach ($textareas as $metaKey => $fieldName) {

        $order->update_meta_data(

            $metaKey,

            sanitize_textarea_field(
                wp_unslash(
                    $_POST[$fieldName] ?? ''
                )
            )

        );
    }
}

/*
|--------------------------------------------------------------------------
| Save confirmation checkboxes
|--------------------------------------------------------------------------
*/

private function saveConfirmationFields(
    \WC_Order $order
): void {

    $checkboxes = [

        '_nyp_confirm_measurements'
            => 'confirm_measurements',

        '_nyp_confirm_scope_review'
            => 'confirm_scope_review',

        '_nyp_confirm_scope_adjustment'
            => 'confirm_scope_adjustment',

        '_nyp_confirm_planning_quality'
            => 'confirm_planning_quality',

        '_nyp_confirm_budget_guidance'
            => 'confirm_budget_guidance',

        '_nyp_confirm_nyp_responsibility'
            => 'confirm_nyp_responsibility',

    ];

    foreach ($checkboxes as $metaKey => $field) {

        $order->update_meta_data(

            $metaKey,

            isset($_POST[$field])
                ? 'yes'
                : 'no'

        );
    }
}

/*
|--------------------------------------------------------------------------
| Save order meta
|--------------------------------------------------------------------------
*/

private function saveOrderMeta(
    int $orderId,
    string $metaKey,
    $value
): void {

    $order = wc_get_order(
        $orderId
    );

    if (!$order) {
        return;
    }

    $order->update_meta_data(
        $metaKey,
        $value
    );

    $order->save();
}

/*
|--------------------------------------------------------------------------
| Handle single upload
|--------------------------------------------------------------------------
*/

private function handleSingleUpload(
    string $field,
    string $targetDir,
    int $orderId,
    string $metaKey
): void {

    if (
        empty($_FILES[$field]['name']) ||
        $_FILES[$field]['error'] !== UPLOAD_ERR_OK
    ) {
        return;
    }

    $fileName = sanitize_file_name(
        $_FILES[$field]['name']
    );

    $destination =
        trailingslashit($targetDir)
        . $fileName;

    if (
        !move_uploaded_file(
            $_FILES[$field]['tmp_name'],
            $destination
        )
    ) {
        return;
    }

    $relativePath =
        'nyp-intake/order-'
        . $orderId
        . '/'
        . $fileName;

    $this->saveOrderMeta(
        $orderId,
        $metaKey,
        $relativePath
    );
}

/*
|--------------------------------------------------------------------------
| Handle multiple uploads
|--------------------------------------------------------------------------
*/

private function handleMultiUpload(
    string $field,
    string $targetDir,
    int $orderId,
    string $metaKey
): void {

    if (
        empty($_FILES[$field]['name'][0])
    ) {
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Existing files
    |--------------------------------------------------------------------------
    */

    $order = wc_get_order(
        $orderId
    );

    if (!$order) {
        return;
    }

    $files = $order->get_meta(
        $metaKey
    );

    if (!is_array($files)) {
        $files = [];
    }

    /*
    |--------------------------------------------------------------------------
    | Upload new files
    |--------------------------------------------------------------------------
    */

    foreach (
        $_FILES[$field]['name']
        as $index => $name
    ) {

        if (
            empty($name)
        ) {
            continue;
        }

        if (
            $_FILES[$field]['error'][$index]
            !== UPLOAD_ERR_OK
        ) {
            continue;
        }

        $fileName = sanitize_file_name(
            $name
        );

        $destination =
            trailingslashit($targetDir)
            . $fileName;

        if (
            !move_uploaded_file(
                $_FILES[$field]['tmp_name'][$index],
                $destination
            )
        ) {
            continue;
        }

        $files[] =
            'nyp-intake/order-'
            . $orderId
            . '/'
            . $fileName;
    }

    /*
    |--------------------------------------------------------------------------
    | Remove duplicates
    |--------------------------------------------------------------------------
    */

    $files = array_values(
        array_unique($files)
    );

    $this->saveOrderMeta(
        $orderId,
        $metaKey,
        $files
    );
}

/*
|--------------------------------------------------------------------------
| Remove uploaded file
|--------------------------------------------------------------------------
*/

private function removeFile(
    int $orderId,
    string $relativePath
): void {

    $order = wc_get_order(
        $orderId
    );

    if (!$order) {
        return;
    }

    $uploadDir = wp_upload_dir();

    $absolutePath =
        trailingslashit(
            $uploadDir['basedir']
        )
        . ltrim(
            $relativePath,
            '/'
        );

    /*
    |--------------------------------------------------------------------------
    | Delete physical file
    |--------------------------------------------------------------------------
    */

    if (
        file_exists($absolutePath)
        &&
        is_file($absolutePath)
    ) {

        @unlink(
            $absolutePath
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Remove order meta reference
    |--------------------------------------------------------------------------
    */

    $this->removeMetaReference(
        $order,
        $relativePath
    );

}

/*
|--------------------------------------------------------------------------
| Remove file reference from order meta
|--------------------------------------------------------------------------
*/

private function removeMetaReference(
    \WC_Order $order,
    string $relativePath
): void {

    $metaKeys = [

        '_nyp_floor_plan',

        '_nyp_planning_export',

        '_nyp_kitchen_photos',

        '_nyp_inspiration_images',

        '_nyp_technical_documents',

        '_nyp_additional_files',

    ];

    foreach ($metaKeys as $metaKey) {

        $value = $order->get_meta(
            $metaKey
        );

        /*
        |--------------------------------------------------------------------------
        | Single file
        |--------------------------------------------------------------------------
        */

        if (is_string($value)) {

            if ($value === $relativePath) {

                $order->delete_meta_data(
                    $metaKey
                );
            }

            continue;
        }

        /*
        |--------------------------------------------------------------------------
        | Multiple files
        |--------------------------------------------------------------------------
        */

        if (!is_array($value)) {
            continue;
        }

        $value = array_values(

            array_filter(

                $value,

                function ($file) use (
                    $relativePath
                ) {

                    return $file !== $relativePath;

                }

            )

        );

        /*
        |--------------------------------------------------------------------------
        | Delete empty arrays
        |--------------------------------------------------------------------------
        */

        if (empty($value)) {

            $order->delete_meta_data(
                $metaKey
            );

        } else {

            $order->update_meta_data(
                $metaKey,
                $value
            );

        }

    }

    $order->save();

}

/*
|--------------------------------------------------------------------------
| Validate uploaded files
|--------------------------------------------------------------------------
*/

private function validateUploads(): void
{

    $maxFileSize = 50 * 1024 * 1024;      // 50 MB

    $maxTotalSize = 250 * 1024 * 1024;    // 250 MB

    $maxFileCount = 10;

    $allowedExtensions = [

        'pdf',

        'jpg',
        'jpeg',
        'png',
        'webp',

        'dwg',

        'zip',

        'doc',
        'docx',

    ];

    $totalFiles = 0;

    $totalSize = 0;

    foreach ($_FILES as $field => $file) {

        /*
        |--------------------------------------------------------------------------
        | Single upload
        |--------------------------------------------------------------------------
        */

        if (!is_array($file['name'])) {

            if (
                empty($file['name'])
                ||
                $file['error'] === UPLOAD_ERR_NO_FILE
            ) {
                continue;
            }

            if (
                $file['error'] !== UPLOAD_ERR_OK
            ) {

                wp_die(
                    esc_html__(
                        'A file upload failed. Please try again.',
                        'nyp'
                    )
                );

            }

            $extension = strtolower(
                pathinfo(
                    $file['name'],
                    PATHINFO_EXTENSION
                )
            );

            if (
                !in_array(
                    $extension,
                    $allowedExtensions,
                    true
                )
            ) {

                wp_die(
                    esc_html__(
                        'One or more uploaded files use an unsupported file format.',
                        'nyp'
                    )
                );

            }

            if (
                $file['size']
                > $maxFileSize
            ) {

                wp_die(
                    esc_html__(
                        'Each uploaded file must not exceed 50 MB.',
                        'nyp'
                    )
                );

            }

            $totalFiles++;

            $totalSize +=
                $file['size'];

            continue;

        }

        /*
        |--------------------------------------------------------------------------
        | Multiple uploads
        |--------------------------------------------------------------------------
        */

        foreach (
            $file['name']
            as $index => $name
        ) {

            if (
                empty($name)
            ) {
                continue;
            }

            if (
                $file['error'][$index]
                !== UPLOAD_ERR_OK
            ) {

                wp_die(
                    esc_html__(
                        'A file upload failed. Please try again.',
                        'nyp'
                    )
                );

            }

            $extension = strtolower(

                pathinfo(

                    $name,

                    PATHINFO_EXTENSION

                )

            );

            if (
                !in_array(
                    $extension,
                    $allowedExtensions,
                    true
                )
            ) {

                wp_die(
                    esc_html__(
                        'One or more uploaded files use an unsupported file format.',
                        'nyp'
                    )
                );

            }

            if (
                $file['size'][$index]
                > $maxFileSize
            ) {

                wp_die(
                    esc_html__(
                        'Each uploaded file must not exceed 50 MB.',
                        'nyp'
                    )
                );

            }

            $totalFiles++;

            $totalSize +=
                $file['size'][$index];

        }

    }

    /*
    |--------------------------------------------------------------------------
    | Maximum file count
    |--------------------------------------------------------------------------
    */

    if (
        $totalFiles > $maxFileCount
    ) {

        wp_die(
            esc_html__(
                'A maximum of 10 files can be uploaded.',
                'nyp'
            )
        );

    }

    /*
    |--------------------------------------------------------------------------
    | Maximum combined upload size
    |--------------------------------------------------------------------------
    */

    if (
        $totalSize
        > $maxTotalSize
    ) {

        wp_die(
            esc_html__(
                'The total upload size exceeds the allowed limit of 250 MB.',
                'nyp'
            )
        );

    }

}

}