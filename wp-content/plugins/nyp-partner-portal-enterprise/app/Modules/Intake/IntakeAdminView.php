<?php

namespace NYP\Modules\Intake;

if (!defined('ABSPATH')) {
    exit;
}

class IntakeAdminView
{
    public function register(): void
{
    add_action(
        'add_meta_boxes_woocommerce_page_wc-orders',
        [$this, 'registerMetaBox']
    );

    add_action(
        'admin_post_nyp_download_order_files',
        [$this, 'downloadOrderFiles']
    );
}

public function downloadOrderFiles(): void
{
    check_admin_referer(
        'nyp_download_files'
    );

    $orderId = absint(
        $_GET['order_id'] ?? 0
    );

    $order = wc_get_order(
        $orderId
    );

    if (!$order) {
        wp_die('Invalid order.');
    }

    $zip = new \ZipArchive();

    $uploadDir = wp_upload_dir();

    $zipPath =
        $uploadDir['basedir']
        . '/nyp-order-'
        . $orderId
        . '.zip';

    if (
        $zip->open(
            $zipPath,
            \ZipArchive::CREATE |
            \ZipArchive::OVERWRITE
        ) !== true
    ) {
        wp_die(
            'Unable to create ZIP.'
        );
    }

    $metaKeys = [

        '_nyp_floor_plan',
        '_nyp_planning_export',

        '_nyp_kitchen_photos',
        '_nyp_inspiration_images',
        '_nyp_technical_documents',
        '_nyp_additional_files',

    ];

    foreach (
        $metaKeys as $metaKey
    ) {

        $value =
            $order->get_meta(
                $metaKey
            );

        if (
            is_string($value)
            &&
            !empty($value)
        ) {

            $path =
                $uploadDir['basedir']
                . '/'
                . ltrim(
                    $value,
                    '/'
                );

            if (
                file_exists($path)
            ) {

                $zip->addFile(
                    $path,
                    basename($path)
                );
            }
        }

        if (
            is_array($value)
        ) {

            foreach (
                $value as $file
            ) {

                $path =
                    $uploadDir['basedir']
                    . '/'
                    . ltrim(
                        $file,
                        '/'
                    );

                if (
                    file_exists($path)
                ) {

                    $zip->addFile(
                        $path,
                        basename($path)
                    );
                }
            }
        }
    }

    $zip->close();

    header(
        'Content-Type: application/zip'
    );

    header(
        'Content-Disposition: attachment; filename="order-'
        . $orderId
        . '-files.zip"'
    );

    readfile($zipPath);

    unlink($zipPath);

    exit;
}

public function registerMetaBox(): void
{
    add_meta_box(
        'nyp-planning-brief',
        'NYP Planning Brief',
        [$this, 'renderMetaBox'],
        'woocommerce_page_wc-orders',
        'normal',
        'high'
    );
}

public function renderMetaBox($order): void
{
    

        if (!$order) {
            echo '<p>Order not found.</p>';
            return;
        }

        echo '<div class="nyp-admin-intake">';

        $this->renderProjectInformation($order);

        $this->renderPlanningCategory($order);
        
        $this->renderLayoutInformation($order);
        
        $this->renderManufacturerInformation($order);
        
        $this->renderWorktopInformation($order);
        
        $this->renderApplianceInformation($order);
        
        $this->renderBudgetInformation($order);
        
        $this->renderDeliveryFormat($order);
        
        $this->renderDesignBrief($order);
        
        $this->renderConfirmations($order);
        
        $this->renderUploadedFiles($order);

        echo '</div>';
    }

    private function renderDesignBrief(
        \WC_Order $order
    ): void {
    
        $this->sectionStart(
            'Design Brief / Planning Goals'
        );
    
        $this->row(
            'Design Concept',
            $order->get_meta(
                '_nyp_design_concept'
            )
        );
    
        $this->row(
            'Planning Priority',
            $order->get_meta(
                '_nyp_planning_priority'
            )
        );
    
        $this->row(
            'Must-Have Features',
            $order->get_meta(
                '_nyp_must_have_features'
            )
        );
    
        $this->row(
            'Nice-to-Have Features',
            $order->get_meta(
                '_nyp_nice_to_have_features'
            )
        );
    
        $this->row(
            'No-Gos / Exclusions',
            $order->get_meta(
                '_nyp_no_gos'
            )
        );
    
        $this->row(
            'Additional Planning Notes',
            $order->get_meta(
                '_nyp_planning_notes'
            )
        );
    
        $this->sectionEnd();
    }

    private function renderConfirmations(
        \WC_Order $order
    ): void {
    
        $this->sectionStart(
            'Confirmations'
        );
    
        $this->row(
            'Measurements Confirmed',
            $order->get_meta(
                '_nyp_confirm_measurements'
            )
        );
    
        $this->row(
            'Category Review Accepted',
            $order->get_meta(
                '_nyp_confirm_category_review'
            )
        );
    
        $this->row(
            'Planning Basis Accepted',
            $order->get_meta(
                '_nyp_confirm_planning_basis'
            )
        );
    
        $this->row(
            'Budget Guidance Accepted',
            $order->get_meta(
                '_nyp_confirm_budget_guidance'
            )
        );
    
        $this->row(
            'Delivery Requirements Accepted',
            $order->get_meta(
                '_nyp_confirm_delivery_requirements'
            )
        );
    
        $this->row(
            'Execution Responsibility Accepted',
            $order->get_meta(
                '_nyp_confirm_execution_responsibility'
            )
        );
    
        $this->sectionEnd();
    }

    private function renderSpecialWishes(
        \WC_Order $order
    ): void {
    
        $this->sectionStart(
            'Special Wishes / No-Gos'
        );
    
        $this->row(
            'Must Have Features',
            $order->get_meta(
                '_nyp_must_have_features'
            )
        );
    
        $this->row(
            'Nice To Have',
            $order->get_meta(
                '_nyp_nice_to_have_features'
            )
        );
    
        $this->row(
            'No-Gos',
            $order->get_meta(
                '_nyp_no_gos'
            )
        );
    
        $this->row(
            'Customer Priority',
            $order->get_meta(
                '_nyp_customer_priority'
            )
        );
    
        $this->row(
            'Planning Notes',
            $order->get_meta(
                '_nyp_planning_notes'
            )
        );
    
        $this->sectionEnd();
    }

    private function renderSoftwareInformation(
        \WC_Order $order
    ): void {
    
        $this->sectionStart(
            'Planning Software & Delivery'
        );
    
        $this->row(
            'Software',
            $order->get_meta(
                '_nyp_planning_software_used'
            )
        );
    
        $this->row(
            'Version',
            $order->get_meta(
                '_nyp_software_version'
            )
        );
    
        $this->row(
            'Delivery Format',
            $order->get_meta(
                '_nyp_delivery_format'
            )
        );
    
        $this->row(
            'DRW Required',
            $order->get_meta(
                '_nyp_drw_required'
            )
        );
    
        $this->row(
            'Renderings Required',
            $order->get_meta(
                '_nyp_renderings_required'
            )
        );
    
        $this->row(
            'Delivery Notes',
            $order->get_meta(
                '_nyp_delivery_notes'
            )
        );
    
        $this->sectionEnd();
    }

    private function renderDeliveryFormat(
        \WC_Order $order
    ): void {
    
        $this->sectionStart(
            'Delivery Format'
        );
    
        $this->row(
            'Delivery Format',
            $order->get_meta(
                '_nyp_delivery_format'
            )
        );
    
        $this->row(
            'Delivery Notes',
            $order->get_meta(
                '_nyp_delivery_notes'
            )
        );
    
        $this->sectionEnd();
    }

    private function renderBudgetInformation(
        \WC_Order $order
    ): void {
    
        $this->sectionStart(
            'Budget & Equipment Level'
        );
        
        $this->row(
            'Budget Range',
            $order->get_meta(
                '_nyp_budget_range'
            )
        );
        
        $this->row(
            'Planning Priority',
            $order->get_meta(
                '_nyp_planning_priority'
            )
        );
        
        $this->row(
            'Budget Notes',
            $order->get_meta(
                '_nyp_budget_notes'
            )
        );
        
        $this->sectionEnd();
    }

    private function renderWorktopInformation(
        \WC_Order $order
    ): void {
    
        $this->sectionStart(
            'Worktop / Niche / Ergonomics'
        );
    
        $this->row(
            'Worktop Material',
            $order->get_meta('_nyp_worktop_material')
        );
        
        $this->row(
            'Worktop Thickness',
            $order->get_meta('_nyp_worktop_thickness')
        );
        
        $this->row(
            'Desired Work Height',
            $order->get_meta('_nyp_work_height')
        );
        
        $this->row(
            'Corpus Height',
            $order->get_meta('_nyp_corpus_height')
        );
        
        $this->row(
            'Plinth Height',
            $order->get_meta('_nyp_plinth_height')
        );
        
        $this->row(
            'Niche Cladding',
            $order->get_meta('_nyp_niche_cladding')
        );
        
        $this->row(
            'Front / Corpus Material',
            $order->get_meta('_nyp_corpus_material')
        );
        
        $this->row(
            'Ergonomics Notes',
            $order->get_meta('_nyp_ergonomics_notes')
        );
    
        $this->sectionEnd();
    }

    private function renderPlanningCategory(
        \WC_Order $order
    ): void {
    
        $this->sectionStart(
            'Planning Category'
        );
    
        $this->row(
            'Category Confirmation',
            $order->get_meta(
                '_nyp_category_confirmation'
            )
        );
    
        $this->sectionEnd();
    }

    private function renderSubmissionInformation(
        \WC_Order $order
    ): void {
    
        $this->sectionStart(
            'Submission Information'
        );
    
        $this->row(
            'Brief Submitted',
            $order->get_meta(
                '_nyp_brief_submitted'
            )
        );
    
        $this->row(
            'Submitted At',
            $order->get_meta(
                '_nyp_brief_submitted_at'
            )
        );
    
        $this->row(
            'Submitted By',
            $order->get_meta(
                '_nyp_brief_submitted_by'
            )
        );
    
        $this->sectionEnd();
    }

    private function renderProjectInformation(\WC_Order $order): void
    {
        $this->sectionStart('Project Information');

        $this->row('Project Name', $order->get_meta('_nyp_project_name'));
        $this->row('Reference Number', $order->get_meta('_nyp_reference_number'));
        $this->row('Customer Name', $order->get_meta('_nyp_customer_name'));
        $this->row('Installation Address', $order->get_meta('_nyp_installation_address'));
        $this->row('Installation Date', $order->get_meta('_nyp_installation_date'));

        $this->sectionEnd();
    }

    private function renderLayoutInformation(\WC_Order $order): void
    {
        $this->sectionStart('Room & Kitchen Layout');

        $this->row(
            'Kitchen Layout',
            $order->get_meta('_nyp_kitchen_layout')
        );
        
        $this->row(
            'Ceiling Height',
            $order->get_meta('_nyp_ceiling_height')
        );
        
        $this->row(
            'Layout Notes',
            $order->get_meta('_nyp_layout_notes')
        );
        $this->row('Ceiling Height', $order->get_meta('_nyp_ceiling_height'));
        $this->row('Layout Notes', $order->get_meta('_nyp_layout_notes'));

        $this->sectionEnd();
    }

    private function renderManufacturerInformation(\WC_Order $order): void
    {
        $this->sectionStart('Manufacturer Information');

        $this->row(
            'Manufacturer',
            $order->get_meta('_nyp_manufacturer')
        );
        
        $this->row(
            'Product Line',
            $order->get_meta('_nyp_product_line')
        );
        
        $this->row(
            'Handle Preference',
            $order->get_meta('_nyp_handle_preference')
        );
        
        $this->row(
            'Color / Finish Concept',
            $order->get_meta('_nyp_finish_concept')
        );
        
        $this->row(
            'Manufacturer Notes',
            $order->get_meta('_nyp_manufacturer_notes')
        );

        $this->sectionEnd();
    }

    private function renderApplianceInformation(\WC_Order $order): void
    {
        $this->sectionStart('Appliance Information');

        $this->row('Reuse Appliances', $order->get_meta('_nyp_reuse_appliances'));
        $this->row('Appliance Brand', $order->get_meta('_nyp_appliance_brand'));
        $this->row('Cooktop', $order->get_meta('_nyp_cooktop'));
        $this->row('Oven', $order->get_meta('_nyp_oven'));
        $this->row('Microwave', $order->get_meta('_nyp_microwave'));
        $this->row('Refrigerator', $order->get_meta('_nyp_refrigerator'));
        $this->row('Freezer', $order->get_meta('_nyp_freezer'));
        $this->row('Dishwasher', $order->get_meta('_nyp_dishwasher'));
        $this->row('Extractor Hood', $order->get_meta('_nyp_extractor_hood'));
        $this->row('Notes', $order->get_meta('_nyp_appliance_notes'));
        $this->row(
            'Sink Brand / Model',
            $order->get_meta(
                '_nyp_sink_model'
            )
        );
        
        $this->row(
            'Sink Color / Finish',
            $order->get_meta(
                '_nyp_sink_finish'
            )
        );
        
        $this->row(
            'Tap Brand / Model',
            $order->get_meta(
                '_nyp_tap_model'
            )
        );
        
        $this->row(
            'Tap Color / Finish',
            $order->get_meta(
                '_nyp_tap_finish'
            )
        );
        
        $this->row(
            'Water System Requirements',
            $order->get_meta(
                '_nyp_water_system_requirements'
            )
        );
        
        $this->row(
            'Sink / Tap Notes',
            $order->get_meta(
                '_nyp_sink_tap_notes'
            )
        );

        $this->sectionEnd();
    }

    private function renderDesignRequirements(\WC_Order $order): void
    {
        $this->sectionStart('Design Requirements');

        $this->row('Design Style', $order->get_meta('_nyp_design_style'));
        $this->row('Color Scheme', $order->get_meta('_nyp_color_scheme'));
        $this->row('Worktop Preference', $order->get_meta('_nyp_worktop_preference'));
        $this->row('Handle Preference', $order->get_meta('_nyp_handle_preference'));
        $this->row('Lighting Requirements', $order->get_meta('_nyp_lighting_requirements'));
        $this->row('Storage Requirements', $order->get_meta('_nyp_storage_requirements'));
        $this->row('Customer Requests', $order->get_meta('_nyp_customer_requests'));
        $this->row('Design Notes', $order->get_meta('_nyp_design_notes'));

        $this->sectionEnd();
    }

    private function renderUploadedFiles(\WC_Order $order): void
    {
        $this->sectionStart('Uploaded Files');

        $this->singleFile(
            'Floor Plan',
            $order->get_meta('_nyp_floor_plan')
        );

        $this->singleFile(
            'Planning Export',
            $order->get_meta('_nyp_planning_export')
        );

        $this->multiFile(
            'Kitchen Photos',
            $order->get_meta('_nyp_kitchen_photos')
        );

        $this->multiFile(
            'Inspiration Images',
            $order->get_meta('_nyp_inspiration_images')
        );

        $this->multiFile(
            'Technical Documents',
            $order->get_meta('_nyp_technical_documents')
        );

        $this->multiFile(
            'Additional Files',
            $order->get_meta('_nyp_additional_files')
        );

        $orderId = $order->get_id();

echo '<p style="margin-top:20px;">';

echo '<a
        href="'
        . esc_url(
            wp_nonce_url(
                admin_url(
                    'admin-post.php?action=nyp_download_order_files&order_id='
                    . $orderId
                ),
                'nyp_download_files'
            )
        )
        . '"
        class="button button-primary"
      >
        Download All Files
      </a>';

echo '</p>';

        $this->sectionEnd();
    }

    private function getFileUrl(
        string $relativePath
    ): string {
    
        $uploadDir = wp_upload_dir();
    
        return trailingslashit(
            $uploadDir['baseurl']
        ) . ltrim(
            $relativePath,
            '/'
        );
    }

    private function singleFile(
        string $label,
        $file
    ): void {
    
        if (empty($file)) {
            return;
        }
    
        $url = $this->getFileUrl(
            $file
        );
    
        echo '<p>';
    
        echo '<strong>'
            . esc_html($label)
            . '</strong><br>';
    
        echo '📄 '
            . esc_html(
                basename($file)
            );
    
        echo ' ';
    
        echo '<a
                href="'
                . esc_url($url)
                . '"
                target="_blank"
                class="button button-small"
              >
                Download
              </a>';
    
        echo '</p>';
    }

    private function multiFile(
        string $label,
        $files
    ): void {
    
        if (
            empty($files)
            ||
            !is_array($files)
        ) {
            return;
        }
    
        $files = array_filter(
            $files
        );
    
        if (
            empty($files)
        ) {
            return;
        }
    
        echo '<p><strong>'
            . esc_html($label)
            . '</strong></p>';
    
        echo '<ul>';
    
        foreach ($files as $file) {
    
            $url = $this->getFileUrl(
                $file
            );
    
            echo '<li style="margin-bottom:8px;">';
    
            echo '📄 '
                . esc_html(
                    basename($file)
                );
    
            echo ' ';
    
            echo '<a
                    href="'
                    . esc_url($url)
                    . '"
                    target="_blank"
                    class="button button-small"
                  >
                    Download
                  </a>';
    
            echo '</li>';
        }
    
        echo '</ul>';
    }

    private function sectionStart(string $title): void
    {
        echo '<div style="margin-bottom:25px;">';
        echo '<h3>' . esc_html($title) . '</h3>';
    }

    private function sectionEnd(): void
    {
        echo '</div>';
    }

    private function row(string $label, $value): void
    {
        if ($value === '') {
            return;
        }

        echo '<p>';
        echo '<strong>' . esc_html($label) . ':</strong><br>';
        echo nl2br(esc_html((string) $value));
        echo '</p>';
    }
}