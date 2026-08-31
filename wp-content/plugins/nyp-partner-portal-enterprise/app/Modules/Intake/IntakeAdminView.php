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

/**
 * Convert internal Planning Brief values into
 * human-readable admin labels.
 *
 * @param mixed $value
 *
 * @return string
 */
private function formatPlanningValue($value): string
{
    if (is_array($value)) {
        return implode(
            ', ',
            array_map(
                [$this, 'formatPlanningValue'],
                $value
            )
        );
    }

    if ($value === null || $value === '') {
        return '';
    }

    $value = (string) $value;

    $labels = [

        /*
        |--------------------------------------------------------------------------
        | Kitchen Layout
        |--------------------------------------------------------------------------
        */

        'open_plan' => 'Offene Küche',
        'l_shape' => 'L-Form',
        'u_shape' => 'U-Form',
        'galley' => 'Zweizeilige Küche',
        'island' => 'Kücheninsel',
        'single_wall' => 'Einzeilig',
        'two_wall' => 'Zweizeilig',

        /*
        |--------------------------------------------------------------------------
        | Manufacturer
        |--------------------------------------------------------------------------
        */

        'schuller' => 'Schüller',

        /*
        |--------------------------------------------------------------------------
        | Handle Preference
        |--------------------------------------------------------------------------
        */

        'handless' => 'Grifflos',
        'handles' => 'Mit Griffen',

        /*
        |--------------------------------------------------------------------------
        | Delivery Format
        |--------------------------------------------------------------------------
        */

        'pdf' => 'PDF',
        'renders' => 'Renderbilder',
        'drw' => 'DRW',
        'pdf_renders' => 'PDF + Renderbilder',
        'pdf_renders_drw' => 'PDF + Renderbilder + DRW',
    ];

    if (isset($labels[$value])) {
        return $labels[$value];
    }

    /*
    |--------------------------------------------------------------------------
    | Fallback
    |--------------------------------------------------------------------------
    |
    | If a new internal value is introduced later, don't expose
    | the raw database-style value if we can format it safely.
    |
    */

    return ucwords(
        str_replace(
            ['_', '-'],
            ' ',
            $value
        )
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
        wp_die('Ungültige Bestellung.');
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
            'ZIP-Datei konnte nicht erstellt werden.'
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
        'NYP Planungsbogen',
        [$this, 'renderMetaBox'],
        'woocommerce_page_wc-orders',
        'normal',
        'high'
    );
}

public function renderMetaBox($order): void
{
    

        if (!$order) {
            echo '<p>Bestellung nicht gefunden.</p>';
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
            'Designbrief / Planungsziele'
        );
    
        $this->row(
            'Designkonzept',
            $order->get_meta(
                '_nyp_design_concept'
            )
        );
    
        $this->row(
            'Planungspriorität',
            $order->get_meta(
                '_nyp_planning_priority'
            )
        );
    
        $this->row(
            'Unverzichtbare Ausstattungsmerkmale',
            $order->get_meta(
                '_nyp_must_have_features'
            )
        );
    
        $this->row(
            'Wünschenswerte Ausstattungsmerkmale',
            $order->get_meta(
                '_nyp_nice_to_have_features'
            )
        );
    
        $this->row(
            'No-Gos / Ausschlüsse',
            $order->get_meta(
                '_nyp_no_gos'
            )
        );
    
        $this->row(
            'Zusätzliche Planungshinweise',
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
            'Bestätigungen'
        );
    
        $this->row(
            'Maße bestätigt',
            $order->get_meta(
                '_nyp_confirm_measurements'
            )
        );
    
        $this->row(
            'Kategorieprüfung akzeptiert',
            $order->get_meta(
                '_nyp_confirm_category_review'
            )
        );
    
        $this->row(
            'Planungsgrundlage akzeptiert',
            $order->get_meta(
                '_nyp_confirm_planning_basis'
            )
        );
    
        $this->row(
            'Budgethinweis akzeptiert',
            $order->get_meta(
                '_nyp_confirm_budget_guidance'
            )
        );
    
        $this->row(
            'Lieferanforderungen akzeptiert',
            $order->get_meta(
                '_nyp_confirm_delivery_requirements'
            )
        );
    
        $this->row(
            'Ausführungsverantwortung akzeptiert',
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
            'Sonderwünsche / No-Gos'
        );
    
        $this->row(
            'Unverzichtbare Ausstattungsmerkmale',
            $order->get_meta(
                '_nyp_must_have_features'
            )
        );
    
        $this->row(
            'Wünschenswert',
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
            'Kundenpriorität',
            $order->get_meta(
                '_nyp_customer_priority'
            )
        );
    
        $this->row(
            'Planungshinweise',
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
            'Planungssoftware & Lieferung'
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
            'Lieferformat',
            $order->get_meta(
                '_nyp_delivery_format'
            )
        );
    
        $this->row(
            'DRW erforderlich',
            $order->get_meta(
                '_nyp_drw_required'
            )
        );
    
        $this->row(
            'Renderbilder erforderlich',
            $order->get_meta(
                '_nyp_renderings_required'
            )
        );
    
        $this->row(
            'Hinweise zur Lieferung',
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
            'Lieferformat'
        );
    
        $this->row(
            'Lieferformat',
            $order->get_meta(
                '_nyp_delivery_format'
            )
        );
    
        $this->row(
            'Hinweise zur Lieferung',
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
            'Budget & Ausstattungsniveau'
        );
        
        $this->row(
            'Budgetrahmen',
            $order->get_meta(
                '_nyp_budget_range'
            )
        );
        
        $this->row(
            'Planungspriorität',
            $order->get_meta(
                '_nyp_planning_priority'
            )
        );
        
        $this->row(
            'Hinweise zu Budget',
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
            'Arbeitsplatte / Nische / Ergonomie'
        );
    
        $this->row(
            'Arbeitsplattenmaterial',
            $order->get_meta('_nyp_worktop_material')
        );
        
        $this->row(
            'Arbeitsplattenstärke',
            $order->get_meta('_nyp_worktop_thickness')
        );
        
        $this->row(
            'Gewünschte Arbeitshöhe',
            $order->get_meta('_nyp_work_height')
        );
        
        $this->row(
            'Korpushöhe',
            $order->get_meta('_nyp_corpus_height')
        );
        
        $this->row(
            'Sockelhöhe',
            $order->get_meta('_nyp_plinth_height')
        );
        
        $this->row(
            'Nischenverkleidung',
            $order->get_meta('_nyp_niche_cladding')
        );
        
        $this->row(
            'Front-/Korpusmaterial',
            $order->get_meta('_nyp_corpus_material')
        );
        
        $this->row(
            'Hinweise zur Ergonomie',
            $order->get_meta('_nyp_ergonomics_notes')
        );
    
        $this->sectionEnd();
    }

    private function renderPlanningCategory(
        \WC_Order $order
    ): void {
    
        $this->sectionStart(
            'Planungskategorie'
        );
    
        $category = $order->get_meta(
            '_nyp_planning_category'
        );
    
        $categoryLabels = [
            'basic'        => 'Basisplanung',
            'professional' => 'Professionelle Küchenplanung',
            'premium'      => 'Premium-Raumkonzept',
        ];
    
        if (isset($categoryLabels[$category])) {
            $category = $categoryLabels[$category];
        }
    
        $this->row(
            'Planungskategorie',
            $category
        );
    
        $this->sectionEnd();
    }

    private function renderSubmissionInformation(
        \WC_Order $order
    ): void {
    
        $this->sectionStart(
            'Übermittlungsinformationen'
        );
    
        $this->row(
            'Bogen übermittelt',
            $order->get_meta(
                '_nyp_brief_submitted'
            )
        );
    
        $this->row(
            'Übermittelt am',
            $order->get_meta(
                '_nyp_brief_submitted_at'
            )
        );
    
        $this->row(
            'Übermittelt von',
            $order->get_meta(
                '_nyp_brief_submitted_by'
            )
        );
    
        $this->sectionEnd();
    }

    private function renderProjectInformation(\WC_Order $order): void
    {
        $this->sectionStart('Projektinformationen');

        $this->row('Projektname', $order->get_meta('_nyp_project_name'));
        $this->row('Referenznummer', $order->get_meta('_nyp_reference_number'));
        $this->row('Kundenname', $order->get_meta('_nyp_customer_name'));
        $this->row('Montageadresse', $order->get_meta('_nyp_installation_address'));
        $this->row('Montagetermin', $order->get_meta('_nyp_installation_date'));

        $this->sectionEnd();
    }

    private function renderLayoutInformation(\WC_Order $order): void
    {
        $this->sectionStart('Raum- & Küchenlayout');

        $this->row(
            'Küchenlayout',
            $order->get_meta('_nyp_kitchen_layout')
        );
        
   
        $this->row('Deckenhöhe', $order->get_meta('_nyp_ceiling_height'));
        $this->row('Layout-Hinweise', $order->get_meta('_nyp_layout_notes'));

        $this->sectionEnd();
    }

    private function renderManufacturerInformation(\WC_Order $order): void
    {
        $this->sectionStart('Herstellerinformationen');

        $this->row(
            'Hersteller',
            $order->get_meta('_nyp_manufacturer')
        );
        
        $this->row(
            'Produktlinie',
            $order->get_meta('_nyp_product_line')
        );
        
        $this->row(
            'Griffpräferenz',
            $order->get_meta('_nyp_handle_preference')
        );
        
        $this->row(
            'Farb-/Oberflächenkonzept',
            $order->get_meta('_nyp_finish_concept')
        );
        
        $this->row(
            'Herstellerhinweise',
            $order->get_meta('_nyp_manufacturer_notes')
        );

        $this->sectionEnd();
    }

    private function renderApplianceInformation(\WC_Order $order): void
    {
        $this->sectionStart('Geräteinformationen');

        $this->row('Geräte übernehmen', $order->get_meta('_nyp_reuse_appliances'));
        $this->row('Gerätemarke', $order->get_meta('_nyp_appliance_brand'));
        $this->row('Kochfeld', $order->get_meta('_nyp_cooktop'));
        $this->row('Backofen', $order->get_meta('_nyp_oven'));
        $this->row('Mikrowelle', $order->get_meta('_nyp_microwave'));
        $this->row('Kühlschrank', $order->get_meta('_nyp_refrigerator'));
        $this->row('Gefrierschrank', $order->get_meta('_nyp_freezer'));
        $this->row('Geschirrspüler', $order->get_meta('_nyp_dishwasher'));
        $this->row('Dunstabzugshaube', $order->get_meta('_nyp_extractor_hood'));
        $this->row('Hinweise', $order->get_meta('_nyp_appliance_notes'));
        $this->row(
            'Spülenmarke / -modell',
            $order->get_meta(
                '_nyp_sink_model'
            )
        );
        
        $this->row(
            'Spülenfarbe / -oberfläche',
            $order->get_meta(
                '_nyp_sink_finish'
            )
        );
        
        $this->row(
            'Armaturenmarke / -modell',
            $order->get_meta(
                '_nyp_tap_model'
            )
        );
        
        $this->row(
            'Armaturenfarbe / -oberfläche',
            $order->get_meta(
                '_nyp_tap_finish'
            )
        );
        
        $this->row(
            'Anforderungen an das Wassersystem',
            $order->get_meta(
                '_nyp_water_system_requirements'
            )
        );
        
        $this->row(
            'Hinweise zu Spüle / Armatur',
            $order->get_meta(
                '_nyp_sink_tap_notes'
            )
        );

        $this->sectionEnd();
    }

    private function renderDesignRequirements(\WC_Order $order): void
    {
        $this->sectionStart('Designanforderungen');

        $this->row('Designstil', $order->get_meta('_nyp_design_style'));
        $this->row('Farbkonzept', $order->get_meta('_nyp_color_scheme'));
        $this->row('Arbeitsplattenpräferenz', $order->get_meta('_nyp_worktop_preference'));
        $this->row('Griffpräferenz', $order->get_meta('_nyp_handle_preference'));
        $this->row('Beleuchtungsanforderungen', $order->get_meta('_nyp_lighting_requirements'));
        $this->row('Stauraumanforderungen', $order->get_meta('_nyp_storage_requirements'));
        $this->row('Kundenwünsche', $order->get_meta('_nyp_customer_requests'));
        $this->row('Designhinweise', $order->get_meta('_nyp_design_notes'));

        $this->sectionEnd();
    }

    private function renderUploadedFiles(\WC_Order $order): void
    {
        $this->sectionStart('Hochgeladene Dateien');

        $this->singleFile(
            $order->get_id(),
            '_nyp_floor_plan',
            'Grundriss',
            $order->get_meta('_nyp_floor_plan')
        );

        $this->singleFile(
            $order->get_id(),
            '_nyp_planning_export',
            'Planungsexport',
            $order->get_meta('_nyp_planning_export')
        );

        $this->multiFile(
            $order->get_id(),
            '_nyp_kitchen_photos',
            'Küchenfotos',
            $order->get_meta('_nyp_kitchen_photos')
        );

        $this->multiFile(
            $order->get_id(),
            '_nyp_inspiration_images',
            'Inspirationsbilder',
            $order->get_meta('_nyp_inspiration_images')
        );

        $this->multiFile(
            $order->get_id(),
            '_nyp_technical_documents',
            'Technische Unterlagen',
            $order->get_meta('_nyp_technical_documents')
        );

        $this->multiFile(
            $order->get_id(),
            '_nyp_additional_files',
            'Weitere Dateien',
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
        Alle Dateien herunterladen
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
        int $orderId,
        string $metaKey,
        string $label,
        $file
    ): void{
    
        if (empty($file)) {
            return;
        }
    
        $url = wp_nonce_url(
            admin_url(
                'admin-post.php?action=nyp_download_file'
                . '&order_id=' . $orderId
                . '&meta_key=' . rawurlencode($metaKey)
            ),
            'nyp_download_file'
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
                Herunterladen
              </a>';
    
        echo '</p>';
    }

    private function multiFile(
        int $orderId,
        string $metaKey,
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
    
        $files = array_filter($files);
    
        if (empty($files)) {
            return;
        }
    
        echo '<p><strong>'
            . esc_html($label)
            . '</strong></p>';
    
        echo '<ul>';
    
        foreach ($files as $index => $file) {
    
            $url = wp_nonce_url(
                admin_url(
                    'admin-post.php?action=nyp_download_file'
                    . '&order_id=' . $orderId
                    . '&meta_key=' . rawurlencode($metaKey)
                    . '&index=' . $index
                ),
                'nyp_download_file'
            );
    
            echo '<li style="margin-bottom:8px;">';
    
            echo '📄 '
                . esc_html(
                    basename($file)
                );
    
            echo ' ';
    
            echo '<a href="'
                . esc_url($url)
                . '" class="button button-small">
                    Herunterladen
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
    if (
        $value === ''
        || $value === null
        || $value === []
    ) {
        return;
    }

    $value = $this->formatPlanningValue($value);

    if ($value === '') {
        return;
    }

    echo '<p>';

    echo '<strong>'
        . esc_html($label)
        . ':</strong><br>';

    echo nl2br(
        esc_html($value)
    );

    echo '</p>';
}
}