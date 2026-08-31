<?php

namespace NYP\Modules\Intake;

use WC_Order;
use WC_Product;

class IntakeFormRenderer
{

    protected ?\WC_Order $order = null;

    protected array $planningData = [];

    private function getReturnUrl(): string
    {
        if ($this->order) {
            return add_query_arg(
                'order_id',
                $this->order->get_id(),
                get_permalink()
            );
        }
    
        return get_permalink();
    }

  

    private function initialize(): bool
{
    $orderId = absint($_GET['order_id'] ?? 0);

    if ($orderId) {

        $this->order = wc_get_order($orderId);

        if (!$this->order) {
            return false;
        }

        return true;
    }

    $session = new \NYP\Services\PlanningSessionStorage();

    $this->planningData = $session->all();
    

    return true;
}



    private function getFilePath(
        string $relativePath
    ): string {

        $uploadDir = wp_upload_dir();

        return
            $uploadDir['basedir']
            . '/'
            . ltrim(
                $relativePath,
                '/'
            );
    }

    private function getFileUrl(
        string $relativePath
    ): string {

        $uploadDir = wp_upload_dir();

        return
            $uploadDir['baseurl']
            . '/'
            . ltrim(
                $relativePath,
                '/'
            );
    }

    private function meta(
        string $key,
        $default = ''
    ) {
    
        if ($this->order instanceof \WC_Order) {
            return $this->order->get_meta($key);
        }
    
        return $this->planningData[$key] ?? $default;
    }

    protected function isReadOnly(): bool
{
    if (!$this->order instanceof WC_Order) {
        return false;
    }

    return $this->order->is_paid();
}


    public function render(?WC_Order $order = null)
{
    $this->order = $order;
    
    /*
    |--------------------------------------------------------------------------
    | Initialize Data Source
    |--------------------------------------------------------------------------
    */

    if (!$this->order) {

        if (!$this->initialize()) {

            return '<div class="woocommerce-error">
                Ungültige Planungssitzung.
            </div>';

        }

    }

    
        $isSubmitted = $this->isReadOnly();
         

        
        $readonly = $isSubmitted
    ? 'readonly'
    : '';

        $disabled = $isSubmitted
            ? 'disabled'
            : '';

        $isLocked = $this->isReadOnly();

        $submittedAt = $this->meta(
            '_nyp_brief_submitted_at'
        );

        $submittedBy = $this->meta(
            '_nyp_brief_submitted_by'
        );

        ob_start();
        ?>

    <div class="nyp-intake-form">

        <h2>
            Planungsbogen
        </h2>
<?php



$error = sanitize_text_field(
    wp_unslash(
        $_GET['nyp_error'] ?? ''
    )
);

if ($error) :

?>

<div class="nyp-notice nyp-notice-error">

    <?php

    switch ($error) {

        case 'floor_plan_required':

            echo esc_html__(
                'Bitte laden Sie einen Grundriss / eine bemaßte Skizze hoch, bevor Sie den Planungsbogen absenden.',
                'nyp'
            );

            break;

        case 'missing_required_fields':

            echo esc_html__(
                'Bitte füllen Sie alle Pflichtfelder aus, bevor Sie den Planungsbogen absenden.',
                'nyp'
            );

            break;

    }

    ?>

</div>

<?php endif; ?> 
<?php
            if (
                isset($_GET['saved'])
            ) {
                echo '<div class="woocommerce-message">
        Projektinformationen gespeichert.
    </div>';

                if ($isSubmitted) {
                    echo '<div class="woocommerce-message">
    Ihr Planungsbogen wurde übermittelt und kann nicht mehr bearbeitet werden.
    </div>';
                }
            }

        ?>

        <form method="post"  enctype="multipart/form-data">

<?php wp_nonce_field(
    'nyp_save_project_info',
    'nyp_nonce'
); ?>



<input
    type="hidden"
    name="order_id"
    value="<?php echo esc_attr($this->order ? $this->order->get_id() : ''); ?>"
>

<div class="nyp-form-section">

    <h3>
        Projekt- / Auftragsdaten
    </h3>

    <div class="nyp-form-row">

        <label>
            Projektname *
        </label>

        <input
            type="text"
            name="project_name"
            value="<?php echo esc_attr(
                $this->meta(
                    '_nyp_project_name'
                )
            ); ?>"
            required
        >

    </div>

    <div class="nyp-form-row">

        <label>
            Studio-Referenz / Projekt-ID
        </label>

        <input
            type="text"
            name="reference_number"
            value="<?php echo esc_attr(
                $this->meta(
                    '_nyp_reference_number'
                )
            ); ?>"
        >

    </div>

    <div class="nyp-form-row">

        <label>
            Ansprechpartner Studio *
        </label>

        <input
            type="text"
            name="studio_contact_person"
            value="<?php echo esc_attr(
                $this->meta(
                    '_nyp_studio_contact_person'
                )
            ); ?>"
            required
        >

    </div>

</div>

<div class="nyp-form-section">

    <h3>
        Planungskategorie
    </h3>

<?php

/*
|--------------------------------------------------------------------------
| Resolve Planning Category
|--------------------------------------------------------------------------
|
| Priority:
|
| 1. Previously saved planning category
| 2. Planning session product (Milestone 3)
| 3. Existing WooCommerce order (Milestone 2)
|
*/

$selectedCategory = $this->meta(
    '_nyp_planning_category'
);

if (empty($selectedCategory)) {

    /*
    |--------------------------------------------------------------------------
    | Planning Session Product
    |--------------------------------------------------------------------------
    */

    $productId = (int) $this->meta(
        '_nyp_product_id'
    );

    if ($productId > 0) {

        $product = wc_get_product($productId);

        if ($product instanceof WC_Product) {

            switch ($product->get_sku()) {

                case 'NYP-BASIC':
                    $selectedCategory = 'basic';
                    break;

                case 'NYP-PROFESSIONAL':
                    $selectedCategory = 'professional';
                    break;

                case 'NYP-PREMIUM':
                    $selectedCategory = 'premium';
                    break;
            }
        }

    /*
    |--------------------------------------------------------------------------
    | Existing Order (Backward Compatibility)
    |--------------------------------------------------------------------------
    */

    } elseif ($this->order instanceof WC_Order) {

        foreach ($this->order->get_items() as $item) {

            $product = method_exists($item, 'get_product') ? $item->get_product() : null;

            if (!$product instanceof WC_Product) {
                continue;
            }

            switch ($product->get_sku()) {

                case 'NYP-BASIC':
                    $selectedCategory = 'basic';
                    break 2;

                case 'NYP-PROFESSIONAL':
                    $selectedCategory = 'professional';
                    break 2;

                case 'NYP-PREMIUM':
                    $selectedCategory = 'premium';
                    break 2;
            }
        }
    }
}

?>

    <p class="nyp-section-description">
        Wählen Sie die Planungskategorie, die am besten zum Projektumfang passt. NYP prüft das eingereichte Projekt, bevor die Planung beginnt.
    </p>

    <div class="nyp-form-row">

        <label>
            Planungskategorie *
        </label>

        <select
            name="planning_category"
            required
        >

            <option value="">
                Kategorie auswählen
            </option>

            <option
                value="basic"
                <?php selected(
                    $selectedCategory,
                    'basic'
                ); ?>
            >
                Basisplanung
            </option>

            <option
                value="professional"
                <?php selected(
                    $selectedCategory,
                    'professional'
                ); ?>
            >
                Professionelle Küchenplanung
            </option>

            <option
                value="premium"
                <?php selected(
                    $selectedCategory,
                    'premium'
                ); ?>
            >
                Premium-Raumkonzept
            </option>

        </select>

    </div>

    <div class="nyp-form-row">

        <label>
            Bestätigung der Paketprüfung *
        </label>

        <label class="nyp-checkbox-label">

            <input
                type="checkbox"
                name="package_validation_confirmation"
                value="yes"
                <?php checked(
                    $this->meta(
                        '_nyp_package_validation_confirmation'
                    ),
                    'yes'
                ); ?>
                required
            >

            Mir ist bewusst, dass NYP den eingereichten Projektumfang vor Beginn der Planung prüfen kann. Falls die gewählte Kategorie nicht den tatsächlichen Anforderungen entspricht, kann NYP ein Upgrade verlangen, den Planungsumfang reduzieren oder das Projekt bis zur Klärung des Umfangs pausieren.

        </label>

    </div>

</div>

<div class="nyp-form-section">

<h3>
    Raum- & Küchenlayout
</h3>

<p class="nyp-section-description">
    Raummaße sollten in erster Linie über den hochgeladenen Grundriss oder die bemaßte Skizze angegeben werden. Zusätzliche Hinweise können unten ergänzt werden.
</p>

<div class="nyp-form-row">

    <label>
        Küchenlayout *
    </label>

    <select
        name="kitchen_layout"
        required
    >

        <option value="">
            Layout auswählen
        </option>

        <option value="single_wall"
            <?php selected(
                $this->meta('_nyp_kitchen_layout'),
                'single_wall'
            ); ?>
        >
            Einzeilige Küche
        </option>

        <option value="galley"
            <?php selected(
                $this->meta('_nyp_kitchen_layout'),
                'galley'
            ); ?>
        >
            Zweizeilige Küche
        </option>

        <option value="l_shape"
            <?php selected(
                $this->meta('_nyp_kitchen_layout'),
                'l_shape'
            ); ?>
        >
            L-förmige Küche
        </option>

        <option value="u_shape"
            <?php selected(
                $this->meta('_nyp_kitchen_layout'),
                'u_shape'
            ); ?>
        >
            U-förmige Küche
        </option>

        <option value="island"
            <?php selected(
                $this->meta('_nyp_kitchen_layout'),
                'island'
            ); ?>
        >
            Kücheninsel
        </option>

        <option value="peninsula"
            <?php selected(
                $this->meta('_nyp_kitchen_layout'),
                'peninsula'
            ); ?>
        >
            Halbinselküche
        </option>

        <option value="appliance_wall"
            <?php selected(
                $this->meta('_nyp_kitchen_layout'),
                'appliance_wall'
            ); ?>
        >
            Küche mit Geräteschrankwand
        </option>

        <option value="open_plan"
            <?php selected(
                $this->meta('_nyp_kitchen_layout'),
                'open_plan'
            ); ?>
        >
            Offene Küche
        </option>

        <option value="living_dining"
            <?php selected(
                $this->meta('_nyp_kitchen_layout'),
                'living_dining'
            ); ?>
        >
            Wohnküchen-Konzept
        </option>

        <option value="not_defined"
            <?php selected(
                $this->meta('_nyp_kitchen_layout'),
                'not_defined'
            ); ?>
        >
            Noch nicht festgelegt
        </option>

        <option value="other"
            <?php selected(
                $this->meta('_nyp_kitchen_layout'),
                'other'
            ); ?>
        >
            Sonstiges / spezielles Layout
        </option>

    </select>

</div>

<div class="nyp-form-row">

    <label>
        Deckenhöhe (mm)
    </label>

    <input
        type="number"
        name="ceiling_height"
        min="0"
        step="1"
        value="<?php echo esc_attr(
            $this->meta(
                '_nyp_ceiling_height'
            )
        ); ?>"
    >

</div>

<div class="nyp-form-row">

    <label>
        Zusätzliche Raum-/Layout-Hinweise
    </label>

    <textarea
        name="layout_notes"
        rows="4"
    ><?php

        echo esc_textarea(
            $this->meta(
                '_nyp_layout_notes'
            )
        );

    ?></textarea>

</div>

</div>

<!-- Section 4  -->

<div class="nyp-form-section">

    <h3>
        Hersteller / Programm / Materialkonzept
    </h3>

    <p class="nyp-section-description">
        Bitte geben Sie den bevorzugten Hersteller, das Programm, die Materialrichtung und das Designkonzept an.
    </p>

    <div class="nyp-form-row">

        <label>
            Küchenhersteller *
        </label>

        <select
            name="manufacturer"
            required
        >

            <option value="">
                Hersteller auswählen
            </option>

            <option value="nobilia"
                <?php selected(
                    $this->meta('_nyp_manufacturer'),
                    'nobilia'
                ); ?>
            >
                Nobilia
            </option>

            <option value="schueller"
                <?php selected(
                    $this->meta('_nyp_manufacturer'),
                    'schueller'
                ); ?>
            >
                Schüller
            </option>

            <option value="nolte"
                <?php selected(
                    $this->meta('_nyp_manufacturer'),
                    'nolte'
                ); ?>
            >
                Nolte
            </option>

            <option value="other"
                <?php selected(
                    $this->meta('_nyp_manufacturer'),
                    'other'
                ); ?>
            >
                Sonstiges (nur nach vorheriger Bestätigung durch NYP)
            </option>

        </select>

    </div>

    <div class="nyp-form-row">

        <label>
            Produktlinie / Kollektion
        </label>

        <input
            type="text"
            name="product_line"
            value="<?php echo esc_attr(
                $this->meta(
                    '_nyp_product_line'
                )
            ); ?>"
            placeholder="Beispiel: Easytouch, Structura, Nova Lack..."
        >

    </div>

    <div class="nyp-form-row">

        <label>
            Griff- / Grifflos-Präferenz
        </label>

        <select
            name="handle_preference"
        >

            <option value="">
                Auswählen
            </option>

            <option value="handleless"
                <?php selected(
                    $this->meta(
                        '_nyp_handle_preference'
                    ),
                    'handleless'
                ); ?>
            >
                Grifflos
            </option>

            <option value="handles"
                <?php selected(
                    $this->meta(
                        '_nyp_handle_preference'
                    ),
                    'handles'
                ); ?>
            >
                Mit Griffen
            </option>

            <option value="mixed"
                <?php selected(
                    $this->meta(
                        '_nyp_handle_preference'
                    ),
                    'mixed'
                ); ?>
            >
                Gemischt
            </option>

            <option value="no_preference"
                <?php selected(
                    $this->meta(
                        '_nyp_handle_preference'
                    ),
                    'no_preference'
                ); ?>
            >
                Keine Präferenz
            </option>

        </select>

    </div>

    <div class="nyp-form-row">

        <label>
            Farb- / Oberflächenkonzept
        </label>

        <textarea
            name="finish_concept"
            rows="4"
        ><?php

            echo esc_textarea(
                $this->meta(
                    '_nyp_finish_concept'
                )
            );

        ?></textarea>

        <small>
            Beispiel: Insel in Dunkelgrün, Hochschränke in Weiß, Arbeitsplatte in Steinoptik.
        </small>

    </div>

    <div class="nyp-form-row">

        <label>
            Besondere Hinweise zu Hersteller / Material
        </label>

        <textarea
            name="manufacturer_notes"
            rows="4"
        ><?php

            echo esc_textarea(
                $this->meta(
                    '_nyp_manufacturer_notes'
                )
            );

        ?></textarea>

    </div>

</div>

<!-- Section 5  -->
<div class="nyp-form-section">

<h3>
    Arbeitsplatte / Nische / Ergonomie
</h3>

<div class="nyp-form-row">

    <label>
        Arbeitsplattenmaterial
    </label>

    <select name="worktop_material">

        <option value="">Material auswählen</option>

        <option value="laminate" <?php selected($this->meta('_nyp_worktop_material'),'laminate'); ?>>
            Laminat
        </option>

        <option value="compact_laminate" <?php selected($this->meta('_nyp_worktop_material'),'compact_laminate'); ?>>
            Kompaktlaminat
        </option>

        <option value="quartz" <?php selected($this->meta('_nyp_worktop_material'),'quartz'); ?>>
            Quarz
        </option>

        <option value="granite" <?php selected($this->meta('_nyp_worktop_material'),'granite'); ?>>
            Granit
        </option>

        <option value="ceramic" <?php selected($this->meta('_nyp_worktop_material'),'ceramic'); ?>>
            Keramik
        </option>

        <option value="dekton" <?php selected($this->meta('_nyp_worktop_material'),'dekton'); ?>>
            Dekton
        </option>

        <option value="wood" <?php selected($this->meta('_nyp_worktop_material'),'wood'); ?>>
            Massivholz
        </option>

        <option value="other" <?php selected($this->meta('_nyp_worktop_material'),'other'); ?>>
            Sonstiges
        </option>

    </select>

</div>



<div class="nyp-form-row">

    <label>
        Arbeitsplattenstärke
    </label>

    <select name="worktop_thickness">

        <option value="">Stärke auswählen</option>

        <option value="12mm" <?php selected($this->meta('_nyp_worktop_thickness'),'12mm'); ?>>12 mm</option>

        <option value="20mm" <?php selected($this->meta('_nyp_worktop_thickness'),'20mm'); ?>>20 mm</option>

        <option value="30mm" <?php selected($this->meta('_nyp_worktop_thickness'),'30mm'); ?>>30 mm</option>

        <option value="38mm" <?php selected($this->meta('_nyp_worktop_thickness'),'38mm'); ?>>38 mm</option>

        <option value="40mm" <?php selected($this->meta('_nyp_worktop_thickness'),'40mm'); ?>>40 mm</option>

        <option value="60mm" <?php selected($this->meta('_nyp_worktop_thickness'),'60mm'); ?>>60 mm</option>

        <option value="other" <?php selected($this->meta('_nyp_worktop_thickness'),'other'); ?>>Sonstiges</option>

    </select>

</div>

<div class="nyp-form-row">

    <label>
        Gewünschte Arbeitshöhe (mm)
    </label>

    <input
        type="number"
        name="work_height"
        value="<?php echo esc_attr(
            $this->meta(
                '_nyp_work_height'
            )
        ); ?>"
    >

</div>

<div class="nyp-form-row">

    <label>
        Korpushöhe
    </label>

    <select name="corpus_height">

        <option value="">Höhe auswählen</option>

        <option value="720mm" <?php selected($this->meta('_nyp_corpus_height'),'720mm'); ?>>720 mm</option>

        <option value="780mm" <?php selected($this->meta('_nyp_corpus_height'),'780mm'); ?>>780 mm</option>

        <option value="792mm" <?php selected($this->meta('_nyp_corpus_height'),'792mm'); ?>>792 mm</option>

        <option value="864mm" <?php selected($this->meta('_nyp_corpus_height'),'864mm'); ?>>864 mm</option>

        <option value="other" <?php selected($this->meta('_nyp_corpus_height'),'other'); ?>>Sonstiges</option>

    </select>

</div>

<div class="nyp-form-row">

    <label>
        Sockelhöhe
    </label>

    <select name="plinth_height">

        <option value="">Höhe auswählen</option>

        <option value="70mm" <?php selected($this->meta('_nyp_plinth_height'),'70mm'); ?>>70 mm</option>

        <option value="100mm" <?php selected($this->meta('_nyp_plinth_height'),'100mm'); ?>>100 mm</option>

        <option value="150mm" <?php selected($this->meta('_nyp_plinth_height'),'150mm'); ?>>150 mm</option>

        <option value="200mm" <?php selected($this->meta('_nyp_plinth_height'),'200mm'); ?>>200 mm</option>

        <option value="other" <?php selected($this->meta('_nyp_plinth_height'),'other'); ?>>Sonstiges</option>

    </select>

</div>

<div class="nyp-form-row">

    <label>
        Nischenverkleidung
    </label>

    <select name="niche_cladding">

        <option value="">Nischenverkleidung auswählen</option>

        <option value="same_as_worktop" <?php selected($this->meta('_nyp_niche_cladding'),'same_as_worktop'); ?>>
            Wie Arbeitsplatte
        </option>

        <option value="glass" <?php selected($this->meta('_nyp_niche_cladding'),'glass'); ?>>
            Glas
        </option>

        <option value="ceramic" <?php selected($this->meta('_nyp_niche_cladding'),'ceramic'); ?>>
            Keramik
        </option>

        <option value="compact_laminate" <?php selected($this->meta('_nyp_niche_cladding'),'compact_laminate'); ?>>
            Kompaktlaminat
        </option>

        <option value="stone" <?php selected($this->meta('_nyp_niche_cladding'),'stone'); ?>>
            Stein
        </option>

        <option value="painted_wall" <?php selected($this->meta('_nyp_niche_cladding'),'painted_wall'); ?>>
            Gestrichene Wand
        </option>

        <option value="other" <?php selected($this->meta('_nyp_niche_cladding'),'other'); ?>>
            Sonstiges
        </option>

    </select>

</div>

<div class="nyp-form-row">

    <label>
        Front-/Korpusmaterial
    </label>

    <input
        type="text"
        name="corpus_material"
        value="<?php echo esc_attr(
            $this->meta(
                '_nyp_corpus_material'
            )
        ); ?>"
    >

</div>

<div class="nyp-form-row">

    <label>
        Hinweise zu Arbeitsplatte / Nische / Ergonomie
    </label>

    <textarea
        name="ergonomics_notes"
        rows="4"
    ><?php

        echo esc_textarea(
            $this->meta(
                '_nyp_ergonomics_notes'
            )
        );

    ?></textarea>

</div>

</div>

<!-- Section 6 -->

<div class="nyp-form-section">

    <h3>
        Geräte / Spüle / Armatur
    </h3>

    <p class="nyp-section-description">
        Bitte geben Sie Ihre Gerätewünsche sowie Anforderungen an Spüle, Armatur und Wassersystem an.
    </p>

    <div class="nyp-form-row">

        <label>
            Bevorzugte Gerätemarke
        </label>

        <input
            type="text"
            name="appliance_brand"
            value="<?php echo esc_attr(
                $this->meta(
                    '_nyp_appliance_brand'
                )
            ); ?>"
            placeholder="z. B. Siemens, Bosch, Miele, Neff"
        >

    </div>

    <div class="nyp-form-row">

        <label>
            Kochfeld
        </label>

        <input
            type="text"
            name="cooktop"
            value="<?php echo esc_attr(
                $this->meta(
                    '_nyp_cooktop'
                )
            ); ?>"
        >

    </div>

    <div class="nyp-form-row">

        <label>
            Backofen
        </label>

        <input
            type="text"
            name="oven"
            value="<?php echo esc_attr(
                $this->meta(
                    '_nyp_oven'
                )
            ); ?>"
        >

    </div>

    <div class="nyp-form-row">

        <label>
            Mikrowelle
        </label>

        <input
            type="text"
            name="microwave"
            value="<?php echo esc_attr(
                $this->meta(
                    '_nyp_microwave'
                )
            ); ?>"
        >

    </div>

    <div class="nyp-form-row">

        <label>
            Kühlschrank
        </label>

        <input
            type="text"
            name="refrigerator"
            value="<?php echo esc_attr(
                $this->meta(
                    '_nyp_refrigerator'
                )
            ); ?>"
        >

    </div>

    <div class="nyp-form-row">

        <label>
            Gefrierschrank
        </label>

        <input
            type="text"
            name="freezer"
            value="<?php echo esc_attr(
                $this->meta(
                    '_nyp_freezer'
                )
            ); ?>"
        >

    </div>

    <div class="nyp-form-row">

        <label>
            Geschirrspüler
        </label>

        <select name="dishwasher">

            <option value="">
                Auswählen
            </option>

            <option value="integrated">
                Integrierter Geschirrspüler
            </option>

            <option value="existing">
                Vorhandener Geschirrspüler soll eingeplant werden
            </option>

            <option value="none">
                Kein Geschirrspüler erforderlich
            </option>

            <option value="raised">
                Erhöhter Geschirrspüler gewünscht
            </option>

            <option value="suggest">
                Bitte vorschlagen
            </option>

        </select>

    </div>

    <div class="nyp-form-row">

        <label>
            Dunstabzugshaube
        </label>

        <input
            type="text"
            name="extractor_hood"
            value="<?php echo esc_attr(
                $this->meta(
                    '_nyp_extractor_hood'
                )
            ); ?>"
        >

    </div>

    <div class="nyp-form-row">

    <label>
        Spülenmarke / -modell
    </label>

    <input
        type="text"
        name="sink_model"
        value="<?php echo esc_attr(
            $this->meta(
                '_nyp_sink_model'
            )
        ); ?>"
        placeholder="z. B. Blanco Subline"
    >

</div>

<div class="nyp-form-row">

    <label>
        Spülenfarbe / -oberfläche
    </label>

    <input
        type="text"
        name="sink_finish"
        value="<?php echo esc_attr(
            $this->meta(
                '_nyp_sink_finish'
            )
        ); ?>"
    >

</div>

<div class="nyp-form-row">

    <label>
        Armaturenmarke / -modell
    </label>

    <input
        type="text"
        name="tap_model"
        value="<?php echo esc_attr(
            $this->meta(
                '_nyp_tap_model'
            )
        ); ?>"
        placeholder="z. B. Quooker Flex"
    >

</div>

<div class="nyp-form-row">

    <label>
        Armaturenfarbe / -oberfläche
    </label>

    <input
        type="text"
        name="tap_finish"
        value="<?php echo esc_attr(
            $this->meta(
                '_nyp_tap_finish'
            )
        ); ?>"
    >

</div>

<div class="nyp-form-row">

    <label>
        Besondere Anforderungen an Armatur / Wassersystem
    </label>

    <textarea
        name="water_system_requirements"
        rows="3"
    ><?php

        echo esc_textarea(
            $this->meta(
                '_nyp_water_system_requirements'
            )
        );

    ?></textarea>

</div>

<div class="nyp-form-row">

    <label>
        Hinweise zu Spüle / Armatur / Wassersystem
    </label>

    <textarea
        name="sink_tap_notes"
        rows="4"
    ><?php

        echo esc_textarea(
            $this->meta(
                '_nyp_sink_tap_notes'
            )
        );

    ?></textarea>

</div>

</div>

<!-- Section 7 -->

<div class="nyp-form-section">

    <h3>
        Budget & Ausstattungsniveau
    </h3>

    <p class="nyp-section-description">

        Die Budgetangabe dient ausschließlich zur Planungsorientierung.

        NYP erstellt keine verbindliche Preiskalkulation oder Verkaufspreise.

    </p>

    <div class="nyp-form-row">

        <label>
            Budgetrahmen / Planungsorientierung
        </label>

        <select name="budget_range">

            <option value="">
                Budgetrahmen auswählen
            </option>

            <option
                value="under_10000"
                <?php selected(
                    $this->meta(
                        '_nyp_budget_range'
                    ),
                    'under_10000'
                ); ?>
            >
                Unter 10.000 €
            </option>

            <option
                value="10000_20000"
                <?php selected(
                    $this->meta(
                        '_nyp_budget_range'
                    ),
                    '10000_20000'
                ); ?>
            >
                10.000 € – 20.000 €
            </option>

            <option
                value="20000_30000"
                <?php selected(
                    $this->meta(
                        '_nyp_budget_range'
                    ),
                    '20000_30000'
                ); ?>
            >
                20.000 € – 30.000 €
            </option>

            <option
                value="30000_50000"
                <?php selected(
                    $this->meta(
                        '_nyp_budget_range'
                    ),
                    '30000_50000'
                ); ?>
            >
                30.000 € – 50.000 €
            </option>

            <option
                value="50000_plus"
                <?php selected(
                    $this->meta(
                        '_nyp_budget_range'
                    ),
                    '50000_plus'
                ); ?>
            >
                50.000 €+
            </option>

            <option
                value="unknown"
                <?php selected(
                    $this->meta(
                        '_nyp_budget_range'
                    ),
                    'unknown'
                ); ?>
            >
                Noch nicht festgelegt
            </option>

        </select>

    </div>

    <?php

$planningPriority = $this->meta(
    '_nyp_planning_priority'
);



?>

<div class="nyp-form-row">

<label>
    Planungspriorität
</label>

<select name="planning_priority">

    <option
        value=""
        <?php selected(
            $planningPriority,
            ''
        ); ?>
    >
        Priorität auswählen
    </option>

    <option
        value="balanced"
        <?php selected(
            $planningPriority,
            'balanced'
        ); ?>
    >
        Ausgewogener Ansatz
    </option>

    <option
        value="design"
        <?php selected(
            $planningPriority,
            'design'
        ); ?>
    >
        Design / Optische Wirkung
    </option>

    <option
        value="storage"
        <?php selected(
            $planningPriority,
            'storage'
        ); ?>
    >
        Stauraum
    </option>

    <option
        value="functionality"
        <?php selected(
            $planningPriority,
            'functionality'
        ); ?>
    >
        Funktionalität / Arbeitsablauf
    </option>

    <option
        value="budget"
        <?php selected(
            $planningPriority,
            'budget'
        ); ?>
    >
        Budgetbewusste Planung
    </option>

    <option
        value="appliances"
        <?php selected(
            $planningPriority,
            'appliances'
        ); ?>
    >
        Geräte
    </option>

    <option
        value="materials"
        <?php selected(
            $planningPriority,
            'materials'
        ); ?>
    >
        Materialien
    </option>

    <option
        value="presentation"
        <?php selected(
            $planningPriority,
            'presentation'
        ); ?>
    >
        Präsentation / Verkaufswirkung
    </option>

    <option
        value="everyday_use"
        <?php selected(
            $planningPriority,
            'everyday_use'
        ); ?>
    >
        Einfache Alltagstauglichkeit
    </option>

</select>

</div>

    <div class="nyp-form-row">

        <label>
            Hinweise zu Budget / Ausstattung
        </label>

        <textarea
            name="budget_notes"
            rows="4"
        ><?php

            echo esc_textarea(
                $this->meta(
                    '_nyp_budget_notes'
                )
            );

        ?></textarea>

    </div>

</div>

<!-- Section 8 -->

<div class="nyp-form-section">

    <h3>
        Liefer-/Ausgabeformat
    </h3>

    <p class="nyp-section-description">

        Wählen Sie das gewünschte Lieferformat.

        Im gewählten Paket enthaltene Renderings werden in die PDF-Präsentation eingebettet, sofern nichts anderes gewünscht wird.

    </p>

    <div class="nyp-form-row">

        <label>
            Gewünschtes Lieferformat
        </label>

        <select name="delivery_format">

            <option value="">
                Lieferformat auswählen
            </option>

            <option
                value="pdf_only"
                <?php selected(
                    $this->meta(
                        '_nyp_delivery_format'
                    ),
                    'pdf_only'
                ); ?>
            >
                Nur PDF-Präsentation
            </option>

            <option
                value="pdf_renders"
                <?php selected(
                    $this->meta(
                        '_nyp_delivery_format'
                    ),
                    'pdf_renders'
                ); ?>
            >
                PDF-Präsentation + Renderbilder
            </option>

            <option
                value="pdf_drw"
                <?php selected(
                    $this->meta(
                        '_nyp_delivery_format'
                    ),
                    'pdf_drw'
                ); ?>
            >
                PDF-Präsentation + DRW-Datei
            </option>

            <option
                value="pdf_renders_drw"
                <?php selected(
                    $this->meta(
                        '_nyp_delivery_format'
                    ),
                    'pdf_renders_drw'
                ); ?>
            >
                PDF-Präsentation + Renderbilder + DRW-Datei
            </option>

            <option
                value="other"
                <?php selected(
                    $this->meta(
                        '_nyp_delivery_format'
                    ),
                    'other'
                ); ?>
            >
                Anderes Dateiformat (Bestätigung durch NYP erforderlich)
            </option>

            <option
                value="suggest"
                <?php selected(
                    $this->meta(
                        '_nyp_delivery_format'
                    ),
                    'suggest'
                ); ?>
            >
                Bitte vorschlagen
            </option>

        </select>

    </div>

    <div class="nyp-form-row">

        <label>
            Hinweise zur Lieferung
        </label>

        <textarea
            name="delivery_notes"
            rows="4"
        ><?php

            echo esc_textarea(
                $this->meta(
                    '_nyp_delivery_notes'
                )
            );

        ?></textarea>

    </div>

</div>

<div class="nyp-form-section">

    <h3>
        Designbrief / Planungsziele
    </h3>

    <p class="nyp-section-description">
        Beschreiben Sie die grundsätzliche Gestaltungsrichtung und die Planungsziele für dieses Projekt.
    </p>

    <div class="nyp-form-row">

        <label>
            Gewünschtes Designkonzept / Grundrichtung
        </label>

        <textarea
            name="design_concept"
            rows="4"
        ><?php

            echo esc_textarea(
                $this->meta(
                    '_nyp_design_concept'
                )
            );

        ?></textarea>

    </div>

    <div class="nyp-form-row">

        <label>
            Planungspriorität
        </label>

        <select name="planning_priority">

            <option value="">
                Priorität auswählen
            </option>

            <option value="balanced">
                Ausgewogener Ansatz
            </option>

            <option value="design">
                Design / Optische Wirkung
            </option>

            <option value="storage">
                Stauraum
            </option>

            <option value="functionality">
                Funktionalität / Arbeitsablauf
            </option>

            <option value="budget">
                Budgetbewusste Planung
            </option>

            <option value="appliances">
                Geräte
            </option>

            <option value="materials">
                Materialien
            </option>

            <option value="presentation">
                Präsentation / Verkaufswirkung
            </option>

            <option value="everyday_use">
                Einfache Alltagstauglichkeit
            </option>

        </select>

    </div>

    <div class="nyp-form-row">

        <label>
            Unverzichtbare Ausstattungsmerkmale
        </label>

        <textarea
            name="must_have_features"
            rows="4"
        ><?php

            echo esc_textarea(
                $this->meta(
                    '_nyp_must_have_features'
                )
            );

        ?></textarea>

    </div>

    <div class="nyp-form-row">

        <label>
            Wünschenswerte Ausstattungsmerkmale
        </label>

        <textarea
            name="nice_to_have_features"
            rows="4"
        ><?php

            echo esc_textarea(
                $this->meta(
                    '_nyp_nice_to_have_features'
                )
            );

        ?></textarea>

    </div>

    <div class="nyp-form-row">

        <label>
            No-Gos / Ausschlüsse
        </label>

        <textarea
            name="no_gos"
            rows="4"
        ><?php

            echo esc_textarea(
                $this->meta(
                    '_nyp_no_gos'
                )
            );

        ?></textarea>

    </div>

    <div class="nyp-form-row">

        <label>
            Zusätzliche Planungshinweise
        </label>

        <textarea
            name="planning_notes"
            rows="5"
        ><?php

            echo esc_textarea(
                $this->meta(
                    '_nyp_planning_notes'
                )
            );

        ?></textarea>

    </div>

</div>



<div class="nyp-form-section">

    <h3>
        Uploads
    </h3>

    <p class="nyp-section-description">
        Bitte laden Sie alle verfügbaren Projektunterlagen hoch. Ein Grundriss ist erforderlich, bevor mit der Planung begonnen werden kann.
    </p>

    <div class="nyp-form-row">

        <label>
        Grundriss / bemaßte Skizze *
        </label>

        <input
            type="file"
            class="nyp-file-upload"
            name="floor_plan"
            accept=".pdf,.jpg,.jpeg,.png"
            <?php echo $isLocked ? 'disabled' : ''; ?>
        >

        <?php

        $floor_plan = $this->meta(
            '_nyp_floor_plan'
        );



        $this->renderUploadedFile(
            'Aktueller Grundriss',
            $this->meta(
                '_nyp_floor_plan'
            ),
            !$isLocked
        );

        ?>

        <small>
        Ein bemaßter Grundriss oder eine gut lesbare handgezeichnete Skizze ist erforderlich.
        </small>

    </div>

    <div class="nyp-form-row">

        <label>
            Fotos der vorhandenen Küche
        </label>

        <input
            type="file"
            class="nyp-file-upload"
            name="kitchen_photos[]"
            multiple
            accept=".jpg,.jpeg,.png,.webp"
            <?php echo $isLocked ? 'disabled' : ''; ?>
        >

        <?php

        $this->renderUploadedFiles(
            'Hochgeladene Küchenfotos',
            (array) $this->meta(
                '_nyp_kitchen_photos'
            ),
            !$isLocked
        );

        ?>

        <small>
            Laden Sie Fotos des vorhandenen Raums bzw. der vorhandenen Küche hoch.
        </small>

    </div>

    <div class="nyp-form-row">

        <label>
            Inspirationsbilder
        </label>

        <input
            type="file"
            class="nyp-file-upload"
            name="inspiration_images[]"
            multiple
            accept=".jpg,.jpeg,.png,.webp"
            <?php echo $isLocked ? 'disabled' : ''; ?>
        >

        <?php

        $this->renderUploadedFiles(
            'Hochgeladene Inspirationsbilder',
            (array) $this->meta(
                '_nyp_inspiration_images'
            ),
            !$isLocked
        );

        ?>

        <small>
            Referenzbilder, die den gewünschten Gestaltungsstil widerspiegeln.
        </small>

    </div>

    <div class="nyp-form-row">

        <label>
        Vorhandene Planung / Skizzen
        </label>

        <input
            type="file"
            class="nyp-file-upload"
            name="planning_export"
            accept=".pdf,.zip,.dwg"
            <?php echo $isLocked ? 'disabled' : ''; ?>
        >

        <?php

        $this->renderUploadedFile(
            'Aktueller Planungsexport',
            $this->meta(
                '_nyp_planning_export'
            ),
            !$isLocked
        );

        ?>

        <small>
        Laden Sie vorhandene Planungsentwürfe, handgezeichnete Skizzen, grobe Raumskizzen, Screenshots, Kundenhinweise oder ähnliche Unterlagen hoch.
        </small>

    </div>

    <div class="nyp-form-row">

        <label>
            Technische Unterlagen
        </label>

        <input
            type="file"
            class="nyp-file-upload"
            name="technical_documents[]"
            multiple
            accept=".pdf,.dwg,.zip,.doc,.docx"
            <?php echo $isLocked ? 'disabled' : ''; ?>
        >

        <?php

        $this->renderUploadedFiles(
            'Hochgeladene technische Unterlagen',
            (array) $this->meta(
                '_nyp_technical_documents'
            ),
            !$isLocked
        );

        ?>

        <small>
            Baupläne, Maße, Anschlusszeichnungen, Ver- und Entsorgungspläne sowie technische Spezifikationen.
        </small>

    </div>

    <div class="nyp-form-row">

        <label>
            Weitere Anhänge
        </label>

        <input
            type="file"
            class="nyp-file-upload"
            name="additional_files[]"
            multiple
            <?php echo $isLocked ? 'disabled' : ''; ?>
        >

        <?php

        $this->renderUploadedFiles(
            'Hochgeladene weitere Dateien',
            (array) $this->meta(
                '_nyp_additional_files'
            ),
            !$isLocked
        );

        ?>

        <small>
            Weitere für dieses Projekt relevante Dateien.
        </small>

    </div>

</div>
<div class="nyp-form-section">

    <h3>
        Serviceoptionen
    </h3>

    <p class="nyp-section-description">
        Wählen Sie den Planungsservice, der am besten zu Ihrem Zeitplan passt. Die Standardplanung ist in Ihrem gewählten Planungspaket enthalten. Die Express-Planung bietet eine bevorzugte Bearbeitung und beschleunigte Lieferung.
    </p>

    <?php

    $serviceSpeed = $this->meta(
        '_nyp_service_speed'
    );

    if (empty($serviceSpeed)) {
        $serviceSpeed = 'standard';
    }

    ?>

    <div class="nyp-service-options">

        <label class="nyp-service-option">

            <input
                type="radio"
                name="service_speed"
                value="standard"
                <?php checked(
                    $serviceSpeed,
                    'standard'
                ); ?>
            >

            <span class="nyp-service-content">

                <strong>
                    Standardplanung
                </strong>

                <span class="nyp-service-price">
                    Inbegriffen
                </span>

                <small>
                    Regulärer Planungsablauf. Ihr Projekt wird nach Zahlungseingang in die normale NYP-Planungswarteschlange aufgenommen.
                </small>

            </span>

        </label>
        <div id="nyp-express-option">
        <label class="nyp-service-option">

            <input
                type="radio"
                name="service_speed"
                value="express"
                <?php checked(
                    $serviceSpeed,
                    'express'
                ); ?>
            >

            <span class="nyp-service-content">

                <strong>
                    Express-Planung
                </strong>

                <span class="nyp-service-price">
                    + 400 €
                </span>

                <small>
                Bevorzugte Über-Nacht-Planung mit beschleunigter Bearbeitung. Dieses Upgrade wird Ihrer Bestellung im Checkout hinzugefügt.
                </small>

            </span>

        </label>
            </div>

    </div>

</div>

<div class="nyp-form-section">

    <h3>
        Bestätigungen
    </h3>

    <p class="nyp-section-description">
        Bitte prüfen und bestätigen Sie die folgenden Aussagen, bevor Sie fortfahren.
    </p>

    <div class="nyp-checkbox-group">

        <label class="nyp-checkbox-label">

            <input
                type="checkbox"
                name="confirm_measurements"
                value="yes"
                <?php checked(
                    $this->meta(
                        '_nyp_confirm_measurements'
                    ),
                    'yes'
                ); ?>
                required
            >

            Ich bestätige, dass alle übermittelten Maße, Abmessungen und Grundrisse nach bestem Wissen korrekt und vollständig sind.

        </label>

        <label class="nyp-checkbox-label">

            <input
                type="checkbox"
                name="confirm_scope_review"
                value="yes"
                <?php checked(
                    $this->meta(
                        '_nyp_confirm_scope_review'
                    ),
                    'yes'
                ); ?>
                required
            >

            Mir ist bewusst, dass NYP die gewählte Planungskategorie vor Beginn der Planung prüfen kann.

        </label>

        <label class="nyp-checkbox-label">

            <input
                type="checkbox"
                name="confirm_scope_adjustment"
                value="yes"
                <?php checked(
                    $this->meta(
                        '_nyp_confirm_scope_adjustment'
                    ),
                    'yes'
                ); ?>
                required
            >

            Mir ist bewusst, dass NYP ein Upgrade verlangen, den Planungsumfang reduzieren oder das Projekt pausieren/stornieren kann, falls die gewählte Kategorie nicht den tatsächlichen Projektanforderungen entspricht.

        </label>

        <label class="nyp-checkbox-label">

            <input
                type="checkbox"
                name="confirm_planning_quality"
                value="yes"
                <?php checked(
                    $this->meta(
                        '_nyp_confirm_planning_quality'
                    ),
                    'yes'
                ); ?>
                required
            >

            Mir ist bewusst, dass die Planungsqualität von vollständigen und korrekten Projektinformationen abhängt.

        </label>

        <label class="nyp-checkbox-label">

            <input
                type="checkbox"
                name="confirm_budget_guidance"
                value="yes"
                <?php checked(
                    $this->meta(
                        '_nyp_confirm_budget_guidance'
                    ),
                    'yes'
                ); ?>
                required
            >

            Mir ist bewusst, dass Budget- und Ausstattungsangaben nur als Planungsorientierung dienen und keine endgültige Preisgestaltung darstellen.

        </label>

        <label class="nyp-checkbox-label">

            <input
                type="checkbox"
                name="confirm_nyp_responsibility"
                value="yes"
                <?php checked(
                    $this->meta(
                        '_nyp_confirm_nyp_responsibility'
                    ),
                    'yes'
                ); ?>
                required
            >

            Mir ist bewusst, dass NYP nicht verantwortlich ist für Montage, Bauarbeiten, Ausführung vor Ort, finale Aufmaße, Herstellerbestellung, Zusammenbau oder die finale Bestellprüfung.

        </label>

    </div>

</div>

<?php if (!$isSubmitted) : ?>

<div class="nyp-form-actions">

    <button
        type="submit"
        name="nyp_action"
        value="save_draft"
        class="button"
    >
        Entwurf speichern
    </button>

    <button
        type="submit"
        name="nyp_action"
        value="submit_brief"
        class="button button-primary"
        onclick="return confirm('Möchten Sie wirklich zur Kasse fortfahren? Sie können den Planungsbogen vor der Zahlung weiterhin bearbeiten.');"
    >
    Prüfen & weiter zur Kasse
    </button>

</div>

<?php else : ?>

    <div class="nyp-brief-submitted">

<strong>
    ✓ Planungsbogen übermittelt
</strong>

<?php if ($submittedAt) : ?>

<div class="nyp-submission-info">

    <strong>
        Übermittelt am:
    </strong>

    <?php

    echo esc_html(
        wp_date(
            'd.m.Y H:i',
            strtotime($submittedAt)
        )
    );

    ?>

</div>

<?php endif; ?>

<p>
    Ihr Planungsbogen wurde erfolgreich übermittelt.
    NYP prüft nun Ihre übermittelten Projektdaten und die gewählte Planungskategorie.
</p>

</div>

<?php endif; ?>


<input
    type="hidden"
    name="return_url"
    value="<?php echo esc_url($this->getReturnUrl()); ?>"
>
</form>
    </div>

    <?php

    return ob_get_clean();
    }


    private function renderUploadedFile(
        string $label,
        ?string $file,
        bool $allowDelete = true
    ): void {




        if (empty($file)) {
            return;
        }


        ?>
    
        <div class="nyp-file-card">
    
            <div class="nyp-file-name">
    
                📄 <?php echo esc_html(
                    basename($file)
                ); ?>
    
            </div>
    
            <div class="nyp-file-actions">
    
              
    
                <?php if ($allowDelete) : ?>
    
                    <button
    type="submit"
    name="delete_file"
    value="<?php echo esc_attr($file); ?>"
    class="button button-small"
    onclick="return confirm('Diese Datei entfernen?');"
>
    Entfernen
</button>
    
                <?php endif; ?>
    
            </div>
    
        </div>
    
        <?php
    }
    private function renderUploadedFiles(
        string $label,
        array $files = [],
        bool $allowDelete = true
    ): void {

        $files = array_values(
            array_filter(
                $files,
                function ($file) {
                    return !empty($file);
                }
            )
        );

        if (empty($files)) {
            return;
        }

        ?>
    
        <div class="nyp-uploaded-files">
    
            <strong class="nyp-upload-group-title">
                <?php echo esc_html($label); ?>
            </strong>
    
            <?php foreach ($files as $file) : ?>
    
                <div class="nyp-file-card">
    
                    <div class="nyp-file-name">
    
                        <?php

                        $extension = strtolower(
                            pathinfo(
                                $file,
                                PATHINFO_EXTENSION
                            )
                        );

                $icon = '📄';

                if (
                    in_array(
                        $extension,
                        ['jpg', 'jpeg', 'png', 'webp']
                    )
                ) {
                    $icon = '🖼️';
                }

                echo $icon . ' ' .
                    esc_html(
                        basename($file)
                    );

                ?>
    
                    </div>
    
                    <div class="nyp-file-actions">
    
                       
    
                        <?php if ($allowDelete) : ?>

                            <button
    type="submit"
    name="delete_file"
    value="<?php echo esc_attr($file); ?>"
    class="button button-small"
    onclick="return confirm('Diese Datei entfernen?');"
>
    Entfernen
</button>
    
    
    
                        <?php endif; ?>
    
                    </div>
    
                </div>
    
            <?php endforeach; ?>
    
        </div>
    
        <?php
    }
}