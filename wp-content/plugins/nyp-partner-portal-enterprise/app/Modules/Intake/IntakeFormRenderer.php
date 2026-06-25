<?php

namespace NYP\Modules\Intake;

class IntakeFormRenderer
{
    public function register(): void
    {
        add_shortcode('nyp_intake_form', [$this, 'render']);
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


    public function render(): string
    {
        $orderId = absint(
            $_GET['order_id'] ?? 0
        );
        if (!$orderId) {
            return '<p>Invalid order.</p>';
        }



        $order = wc_get_order($orderId);

        // $order->update_meta_data(
        //     '_nyp_brief_submitted',
        //     0
        // );

        $isSubmitted =
        $order->get_meta(
            '_nyp_brief_submitted'
        ) === 'yes';
         

         echo $isSubmitted;
        $readonly = $isSubmitted
    ? 'readonly'
    : '';

        $disabled = $isSubmitted
            ? 'disabled'
            : '';

        $isLocked = 
        $order->get_meta(
            '_nyp_brief_submitted'
        ) === 'yes';

        $submittedAt = $order->get_meta(
            '_nyp_brief_submitted_at'
        );

        $submittedBy = $order->get_meta(
            '_nyp_brief_submitted_by'
        );

        ob_start();
        ?>

    <div class="nyp-intake-form">

        <h2>
            Planning Brief
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
                'Please upload a Floor Plan / Dimensioned Sketch before submitting the Planning Brief.',
                'nyp'
            );

            break;

        case 'missing_required_fields':

            echo esc_html__(
                'Please complete all required fields before submitting the Planning Brief.',
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
        Project information saved.
    </div>';

                if ($isSubmitted) {
                    echo '<div class="woocommerce-message">
    Your planning brief has been submitted and can no longer be edited.
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
    name="return_url"
    value="<?php echo esc_url( get_permalink() . '?order_id=' . $order->get_id() ); ?>"
>

<input
    type="hidden"
    name="order_id"
    value="<?php echo esc_attr($orderId); ?>"
>

<div class="nyp-form-section">

    <h3>
        Project / Commission Data
    </h3>

    <div class="nyp-form-row">

        <label>
            Project Name *
        </label>

        <input
            type="text"
            name="project_name"
            value="<?php echo esc_attr(
                $order->get_meta(
                    '_nyp_project_name'
                )
            ); ?>"
            required
        >

    </div>

    <div class="nyp-form-row">

        <label>
            Studio Reference / Project ID
        </label>

        <input
            type="text"
            name="reference_number"
            value="<?php echo esc_attr(
                $order->get_meta(
                    '_nyp_reference_number'
                )
            ); ?>"
        >

    </div>

    <div class="nyp-form-row">

        <label>
            Studio Contact Person *
        </label>

        <input
            type="text"
            name="studio_contact_person"
            value="<?php echo esc_attr(
                $order->get_meta(
                    '_nyp_studio_contact_person'
                )
            ); ?>"
            required
        >

    </div>

</div>

<div class="nyp-form-section">

    <h3>
        Planning Category
    </h3>
<?php 

$selectedCategory =
    $order->get_meta(
        '_nyp_planning_category'
    );

if (!$selectedCategory) {

    foreach (
        $order->get_items()
        as $item
    ) {

        $product = method_exists($item, 'get_product') ? $item->get_product() : null;

        if (!$product) {
            continue;
        }

        $sku = $product->get_sku();

        switch ($sku) {

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

        break;
    }
}

?>
    <p class="nyp-section-description">
        Select the planning category that best matches the project scope. NYP will review the submitted project before planning begins.
    </p>

    <div class="nyp-form-row">

        <label>
            Planning Category *
        </label>

        <select
            name="planning_category"
            required
        >

            <option value="">
                Select Category
            </option>

            <option
                value="basic"
                <?php selected(
                    $selectedCategory,
                    'basic'
                ); ?>
            >
                Basic Planning
            </option>

            <option
                value="professional"
                <?php selected(
                     $selectedCategory,
                    'professional'
                ); ?>
            >
                Professional Kitchen Design
            </option>

            <option
                value="premium"
                <?php selected(
                     $selectedCategory,
                    'premium'
                ); ?>
            >
                Premium Room Concept
            </option>

        </select>

    </div>

    <div class="nyp-form-row">

        <label>
            Package Validation Confirmation *
        </label>

        <label class="nyp-checkbox-label">

            <input
                type="checkbox"
                name="package_validation_confirmation"
                value="yes"
                <?php checked(
                    $order->get_meta(
                        '_nyp_package_validation_confirmation'
                    ),
                    'yes'
                ); ?>
                required
            >

            I understand that NYP may review the submitted project scope before planning begins. If the selected category does not match the actual requirements, NYP may request an upgrade, reduce the planning scope, or pause the project until the scope is clarified.

        </label>

    </div>

</div>

<div class="nyp-form-section">

<h3>
    Room & Kitchen Layout
</h3>

<p class="nyp-section-description">
    Room dimensions should primarily be provided through the uploaded floor plan or dimensioned sketch. Additional notes can be added below.
</p>

<div class="nyp-form-row">

    <label>
        Kitchen Layout *
    </label>

    <select
        name="kitchen_layout"
        required
    >

        <option value="">
            Select Layout
        </option>

        <option value="single_wall"
            <?php selected(
                $order->get_meta('_nyp_kitchen_layout'),
                'single_wall'
            ); ?>
        >
            Single wall kitchen
        </option>

        <option value="galley"
            <?php selected(
                $order->get_meta('_nyp_kitchen_layout'),
                'galley'
            ); ?>
        >
            Galley / two-line kitchen
        </option>

        <option value="l_shape"
            <?php selected(
                $order->get_meta('_nyp_kitchen_layout'),
                'l_shape'
            ); ?>
        >
            L-shaped kitchen
        </option>

        <option value="u_shape"
            <?php selected(
                $order->get_meta('_nyp_kitchen_layout'),
                'u_shape'
            ); ?>
        >
            U-shaped kitchen
        </option>

        <option value="island"
            <?php selected(
                $order->get_meta('_nyp_kitchen_layout'),
                'island'
            ); ?>
        >
            Island kitchen
        </option>

        <option value="peninsula"
            <?php selected(
                $order->get_meta('_nyp_kitchen_layout'),
                'peninsula'
            ); ?>
        >
            Peninsula kitchen
        </option>

        <option value="appliance_wall"
            <?php selected(
                $order->get_meta('_nyp_kitchen_layout'),
                'appliance_wall'
            ); ?>
        >
            Kitchen with appliance wall
        </option>

        <option value="open_plan"
            <?php selected(
                $order->get_meta('_nyp_kitchen_layout'),
                'open_plan'
            ); ?>
        >
            Open-plan kitchen
        </option>

        <option value="living_dining"
            <?php selected(
                $order->get_meta('_nyp_kitchen_layout'),
                'living_dining'
            ); ?>
        >
            Kitchen-living-dining concept
        </option>

        <option value="not_defined"
            <?php selected(
                $order->get_meta('_nyp_kitchen_layout'),
                'not_defined'
            ); ?>
        >
            Not defined yet
        </option>

        <option value="other"
            <?php selected(
                $order->get_meta('_nyp_kitchen_layout'),
                'other'
            ); ?>
        >
            Other / special layout
        </option>

    </select>

</div>

<div class="nyp-form-row">

    <label>
        Ceiling Height (mm)
    </label>

    <input
        type="number"
        name="ceiling_height"
        min="0"
        step="1"
        value="<?php echo esc_attr(
            $order->get_meta(
                '_nyp_ceiling_height'
            )
        ); ?>"
    >

</div>

<div class="nyp-form-row">

    <label>
        Additional Room / Layout Notes
    </label>

    <textarea
        name="layout_notes"
        rows="4"
    ><?php

        echo esc_textarea(
            $order->get_meta(
                '_nyp_layout_notes'
            )
        );

    ?></textarea>

</div>

</div>

<!-- Section 4  -->

<div class="nyp-form-section">

    <h3>
        Manufacturer / Program / Material Concept
    </h3>

    <p class="nyp-section-description">
        Please provide the preferred manufacturer, collection, material direction and design concept.
    </p>

    <div class="nyp-form-row">

        <label>
            Kitchen Manufacturer *
        </label>

        <select
            name="manufacturer"
            required
        >

            <option value="">
                Select Manufacturer
            </option>

            <option value="nobilia"
                <?php selected(
                    $order->get_meta('_nyp_manufacturer'),
                    'nobilia'
                ); ?>
            >
                Nobilia
            </option>

            <option value="schueller"
                <?php selected(
                    $order->get_meta('_nyp_manufacturer'),
                    'schueller'
                ); ?>
            >
                Schueller
            </option>

            <option value="nolte"
                <?php selected(
                    $order->get_meta('_nyp_manufacturer'),
                    'nolte'
                ); ?>
            >
                Nolte
            </option>

            <option value="other"
                <?php selected(
                    $order->get_meta('_nyp_manufacturer'),
                    'other'
                ); ?>
            >
                Other (only after prior confirmation by NYP)
            </option>

        </select>

    </div>

    <div class="nyp-form-row">

        <label>
            Product Line / Collection
        </label>

        <input
            type="text"
            name="product_line"
            value="<?php echo esc_attr(
                $order->get_meta(
                    '_nyp_product_line'
                )
            ); ?>"
            placeholder="Example: Easytouch, Structura, Nova Lack..."
        >

    </div>

    <div class="nyp-form-row">

        <label>
            Handle / Handleless Preference
        </label>

        <select
            name="handle_preference"
        >

            <option value="">
                Select
            </option>

            <option value="handleless"
                <?php selected(
                    $order->get_meta(
                        '_nyp_handle_preference'
                    ),
                    'handleless'
                ); ?>
            >
                Handleless
            </option>

            <option value="handles"
                <?php selected(
                    $order->get_meta(
                        '_nyp_handle_preference'
                    ),
                    'handles'
                ); ?>
            >
                Handles
            </option>

            <option value="mixed"
                <?php selected(
                    $order->get_meta(
                        '_nyp_handle_preference'
                    ),
                    'mixed'
                ); ?>
            >
                Mixed
            </option>

            <option value="no_preference"
                <?php selected(
                    $order->get_meta(
                        '_nyp_handle_preference'
                    ),
                    'no_preference'
                ); ?>
            >
                No Preference
            </option>

        </select>

    </div>

    <div class="nyp-form-row">

        <label>
            Color / Finish Concept
        </label>

        <textarea
            name="finish_concept"
            rows="4"
        ><?php

            echo esc_textarea(
                $order->get_meta(
                    '_nyp_finish_concept'
                )
            );

        ?></textarea>

        <small>
            Example: Island in dark green, tall units in white, worktop in stone look.
        </small>

    </div>

    <div class="nyp-form-row">

        <label>
            Special Manufacturer / Material Notes
        </label>

        <textarea
            name="manufacturer_notes"
            rows="4"
        ><?php

            echo esc_textarea(
                $order->get_meta(
                    '_nyp_manufacturer_notes'
                )
            );

        ?></textarea>

    </div>

</div>

<!-- Section 5  -->
<div class="nyp-form-section">

<h3>
    Worktop / Niche / Ergonomics
</h3>

<div class="nyp-form-row">

    <label>
        Worktop Material
    </label>

    <select name="worktop_material">

        <option value="">Select Material</option>

        <option value="laminate" <?php selected($order->get_meta('_nyp_worktop_material'),'laminate'); ?>>
            Laminate
        </option>

        <option value="compact_laminate" <?php selected($order->get_meta('_nyp_worktop_material'),'compact_laminate'); ?>>
            Compact Laminate
        </option>

        <option value="quartz" <?php selected($order->get_meta('_nyp_worktop_material'),'quartz'); ?>>
            Quartz
        </option>

        <option value="granite" <?php selected($order->get_meta('_nyp_worktop_material'),'granite'); ?>>
            Granite
        </option>

        <option value="ceramic" <?php selected($order->get_meta('_nyp_worktop_material'),'ceramic'); ?>>
            Ceramic
        </option>

        <option value="dekton" <?php selected($order->get_meta('_nyp_worktop_material'),'dekton'); ?>>
            Dekton
        </option>

        <option value="wood" <?php selected($order->get_meta('_nyp_worktop_material'),'wood'); ?>>
            Solid Wood
        </option>

        <option value="other" <?php selected($order->get_meta('_nyp_worktop_material'),'other'); ?>>
            Other
        </option>

    </select>

</div>



<div class="nyp-form-row">

    <label>
        Worktop Thickness
    </label>

    <select name="worktop_thickness">

        <option value="">Select Thickness</option>

        <option value="12mm" <?php selected($order->get_meta('_nyp_worktop_thickness'),'12mm'); ?>>12 mm</option>

        <option value="20mm" <?php selected($order->get_meta('_nyp_worktop_thickness'),'20mm'); ?>>20 mm</option>

        <option value="30mm" <?php selected($order->get_meta('_nyp_worktop_thickness'),'30mm'); ?>>30 mm</option>

        <option value="38mm" <?php selected($order->get_meta('_nyp_worktop_thickness'),'38mm'); ?>>38 mm</option>

        <option value="40mm" <?php selected($order->get_meta('_nyp_worktop_thickness'),'40mm'); ?>>40 mm</option>

        <option value="60mm" <?php selected($order->get_meta('_nyp_worktop_thickness'),'60mm'); ?>>60 mm</option>

        <option value="other" <?php selected($order->get_meta('_nyp_worktop_thickness'),'other'); ?>>Other</option>

    </select>

</div>

<div class="nyp-form-row">

    <label>
        Desired Work Height (mm)
    </label>

    <input
        type="number"
        name="work_height"
        value="<?php echo esc_attr(
            $order->get_meta(
                '_nyp_work_height'
            )
        ); ?>"
    >

</div>

<div class="nyp-form-row">

    <label>
        Corpus Height
    </label>

    <select name="corpus_height">

        <option value="">Select Height</option>

        <option value="720mm" <?php selected($order->get_meta('_nyp_corpus_height'),'720mm'); ?>>720 mm</option>

        <option value="780mm" <?php selected($order->get_meta('_nyp_corpus_height'),'780mm'); ?>>780 mm</option>

        <option value="792mm" <?php selected($order->get_meta('_nyp_corpus_height'),'792mm'); ?>>792 mm</option>

        <option value="864mm" <?php selected($order->get_meta('_nyp_corpus_height'),'864mm'); ?>>864 mm</option>

        <option value="other" <?php selected($order->get_meta('_nyp_corpus_height'),'other'); ?>>Other</option>

    </select>

</div>

<div class="nyp-form-row">

    <label>
        Plinth Height
    </label>

    <select name="plinth_height">

        <option value="">Select Height</option>

        <option value="70mm" <?php selected($order->get_meta('_nyp_plinth_height'),'70mm'); ?>>70 mm</option>

        <option value="100mm" <?php selected($order->get_meta('_nyp_plinth_height'),'100mm'); ?>>100 mm</option>

        <option value="150mm" <?php selected($order->get_meta('_nyp_plinth_height'),'150mm'); ?>>150 mm</option>

        <option value="200mm" <?php selected($order->get_meta('_nyp_plinth_height'),'200mm'); ?>>200 mm</option>

        <option value="other" <?php selected($order->get_meta('_nyp_plinth_height'),'other'); ?>>Other</option>

    </select>

</div>

<div class="nyp-form-row">

    <label>
        Niche Cladding
    </label>

    <select name="niche_cladding">

        <option value="">Select Niche Cladding</option>

        <option value="same_as_worktop" <?php selected($order->get_meta('_nyp_niche_cladding'),'same_as_worktop'); ?>>
            Same as Worktop
        </option>

        <option value="glass" <?php selected($order->get_meta('_nyp_niche_cladding'),'glass'); ?>>
            Glass
        </option>

        <option value="ceramic" <?php selected($order->get_meta('_nyp_niche_cladding'),'ceramic'); ?>>
            Ceramic
        </option>

        <option value="compact_laminate" <?php selected($order->get_meta('_nyp_niche_cladding'),'compact_laminate'); ?>>
            Compact Laminate
        </option>

        <option value="stone" <?php selected($order->get_meta('_nyp_niche_cladding'),'stone'); ?>>
            Stone
        </option>

        <option value="painted_wall" <?php selected($order->get_meta('_nyp_niche_cladding'),'painted_wall'); ?>>
            Painted Wall
        </option>

        <option value="other" <?php selected($order->get_meta('_nyp_niche_cladding'),'other'); ?>>
            Other
        </option>

    </select>

</div>

<div class="nyp-form-row">

    <label>
        Front / Corpus Material
    </label>

    <input
        type="text"
        name="corpus_material"
        value="<?php echo esc_attr(
            $order->get_meta(
                '_nyp_corpus_material'
            )
        ); ?>"
    >

</div>

<div class="nyp-form-row">

    <label>
        Worktop / Niche / Ergonomics Notes
    </label>

    <textarea
        name="ergonomics_notes"
        rows="4"
    ><?php

        echo esc_textarea(
            $order->get_meta(
                '_nyp_ergonomics_notes'
            )
        );

    ?></textarea>

</div>

</div>

<!-- Section 6 -->

<div class="nyp-form-section">

    <h3>
        Appliances / Sink / Tap
    </h3>

    <p class="nyp-section-description">
        Please specify appliance preferences together with sink, tap and water system requirements.
    </p>

    <div class="nyp-form-row">

        <label>
            Preferred Appliance Brand
        </label>

        <input
            type="text"
            name="appliance_brand"
            value="<?php echo esc_attr(
                $order->get_meta(
                    '_nyp_appliance_brand'
                )
            ); ?>"
            placeholder="e.g. Siemens, Bosch, Miele, Neff"
        >

    </div>

    <div class="nyp-form-row">

        <label>
            Cooktop
        </label>

        <input
            type="text"
            name="cooktop"
            value="<?php echo esc_attr(
                $order->get_meta(
                    '_nyp_cooktop'
                )
            ); ?>"
        >

    </div>

    <div class="nyp-form-row">

        <label>
            Oven
        </label>

        <input
            type="text"
            name="oven"
            value="<?php echo esc_attr(
                $order->get_meta(
                    '_nyp_oven'
                )
            ); ?>"
        >

    </div>

    <div class="nyp-form-row">

        <label>
            Microwave
        </label>

        <input
            type="text"
            name="microwave"
            value="<?php echo esc_attr(
                $order->get_meta(
                    '_nyp_microwave'
                )
            ); ?>"
        >

    </div>

    <div class="nyp-form-row">

        <label>
            Refrigerator
        </label>

        <input
            type="text"
            name="refrigerator"
            value="<?php echo esc_attr(
                $order->get_meta(
                    '_nyp_refrigerator'
                )
            ); ?>"
        >

    </div>

    <div class="nyp-form-row">

        <label>
            Freezer
        </label>

        <input
            type="text"
            name="freezer"
            value="<?php echo esc_attr(
                $order->get_meta(
                    '_nyp_freezer'
                )
            ); ?>"
        >

    </div>

    <div class="nyp-form-row">

        <label>
            Dishwasher
        </label>

        <select name="dishwasher">

            <option value="">
                Select
            </option>

            <option value="integrated">
                Integrated dishwasher
            </option>

            <option value="existing">
                Existing dishwasher to be planned in
            </option>

            <option value="none">
                No dishwasher required
            </option>

            <option value="raised">
                Raised dishwasher requested
            </option>

            <option value="suggest">
                Please suggest
            </option>

        </select>

    </div>

    <div class="nyp-form-row">

        <label>
            Extractor Hood
        </label>

        <input
            type="text"
            name="extractor_hood"
            value="<?php echo esc_attr(
                $order->get_meta(
                    '_nyp_extractor_hood'
                )
            ); ?>"
        >

    </div>

    <div class="nyp-form-row">

    <label>
        Sink Brand / Model
    </label>

    <input
        type="text"
        name="sink_model"
        value="<?php echo esc_attr(
            $order->get_meta(
                '_nyp_sink_model'
            )
        ); ?>"
        placeholder="e.g. Blanco Subline"
    >

</div>

<div class="nyp-form-row">

    <label>
        Sink Color / Finish
    </label>

    <input
        type="text"
        name="sink_finish"
        value="<?php echo esc_attr(
            $order->get_meta(
                '_nyp_sink_finish'
            )
        ); ?>"
    >

</div>

<div class="nyp-form-row">

    <label>
        Tap Brand / Model
    </label>

    <input
        type="text"
        name="tap_model"
        value="<?php echo esc_attr(
            $order->get_meta(
                '_nyp_tap_model'
            )
        ); ?>"
        placeholder="e.g. Quooker Flex"
    >

</div>

<div class="nyp-form-row">

    <label>
        Tap Color / Finish
    </label>

    <input
        type="text"
        name="tap_finish"
        value="<?php echo esc_attr(
            $order->get_meta(
                '_nyp_tap_finish'
            )
        ); ?>"
    >

</div>

<div class="nyp-form-row">

    <label>
        Special Tap / Water System Requirements
    </label>

    <textarea
        name="water_system_requirements"
        rows="3"
    ><?php

        echo esc_textarea(
            $order->get_meta(
                '_nyp_water_system_requirements'
            )
        );

    ?></textarea>

</div>

<div class="nyp-form-row">

    <label>
        Sink / Tap / Water System Notes
    </label>

    <textarea
        name="sink_tap_notes"
        rows="4"
    ><?php

        echo esc_textarea(
            $order->get_meta(
                '_nyp_sink_tap_notes'
            )
        );

    ?></textarea>

</div>

</div>

<!-- Section 7 -->

<div class="nyp-form-section">

    <h3>
        Budget & Equipment Level
    </h3>

    <p class="nyp-section-description">

        Budget information is used only as planning orientation.

        NYP does not provide final price calculation or sales pricing.

    </p>

    <div class="nyp-form-row">

        <label>
            Budget Range / Planning Orientation
        </label>

        <select name="budget_range">

            <option value="">
                Select Budget Range
            </option>

            <option
                value="under_10000"
                <?php selected(
                    $order->get_meta(
                        '_nyp_budget_range'
                    ),
                    'under_10000'
                ); ?>
            >
                Under €10,000
            </option>

            <option
                value="10000_20000"
                <?php selected(
                    $order->get_meta(
                        '_nyp_budget_range'
                    ),
                    '10000_20000'
                ); ?>
            >
                €10,000 - €20,000
            </option>

            <option
                value="20000_30000"
                <?php selected(
                    $order->get_meta(
                        '_nyp_budget_range'
                    ),
                    '20000_30000'
                ); ?>
            >
                €20,000 - €30,000
            </option>

            <option
                value="30000_50000"
                <?php selected(
                    $order->get_meta(
                        '_nyp_budget_range'
                    ),
                    '30000_50000'
                ); ?>
            >
                €30,000 - €50,000
            </option>

            <option
                value="50000_plus"
                <?php selected(
                    $order->get_meta(
                        '_nyp_budget_range'
                    ),
                    '50000_plus'
                ); ?>
            >
                €50,000+
            </option>

            <option
                value="unknown"
                <?php selected(
                    $order->get_meta(
                        '_nyp_budget_range'
                    ),
                    'unknown'
                ); ?>
            >
                Not Yet Defined
            </option>

        </select>

    </div>

    <?php

$planningPriority = $order->get_meta(
    '_nyp_planning_priority'
);



?>

<div class="nyp-form-row">

<label>
    Planning Priority
</label>

<select name="planning_priority">

    <option
        value=""
        <?php selected(
            $planningPriority,
            ''
        ); ?>
    >
        Select Priority
    </option>

    <option
        value="balanced"
        <?php selected(
            $planningPriority,
            'balanced'
        ); ?>
    >
        Balanced Approach
    </option>

    <option
        value="design"
        <?php selected(
            $planningPriority,
            'design'
        ); ?>
    >
        Design / Visual Impact
    </option>

    <option
        value="storage"
        <?php selected(
            $planningPriority,
            'storage'
        ); ?>
    >
        Storage
    </option>

    <option
        value="functionality"
        <?php selected(
            $planningPriority,
            'functionality'
        ); ?>
    >
        Functionality / Workflow
    </option>

    <option
        value="budget"
        <?php selected(
            $planningPriority,
            'budget'
        ); ?>
    >
        Budget-Conscious Planning
    </option>

    <option
        value="appliances"
        <?php selected(
            $planningPriority,
            'appliances'
        ); ?>
    >
        Appliances
    </option>

    <option
        value="materials"
        <?php selected(
            $planningPriority,
            'materials'
        ); ?>
    >
        Materials
    </option>

    <option
        value="presentation"
        <?php selected(
            $planningPriority,
            'presentation'
        ); ?>
    >
        Presentation / Sales Impact
    </option>

    <option
        value="everyday_use"
        <?php selected(
            $planningPriority,
            'everyday_use'
        ); ?>
    >
        Easy Everyday Use
    </option>

</select>

</div>

    <div class="nyp-form-row">

        <label>
            Budget / Equipment Notes
        </label>

        <textarea
            name="budget_notes"
            rows="4"
        ><?php

            echo esc_textarea(
                $order->get_meta(
                    '_nyp_budget_notes'
                )
            );

        ?></textarea>

    </div>

</div>

<!-- Section 8 -->

<div class="nyp-form-section">

    <h3>
        Delivery Format
    </h3>

    <p class="nyp-section-description">

        Select the preferred delivery format.

        Renderings included in the selected package will be embedded in the PDF presentation unless otherwise requested.

    </p>

    <div class="nyp-form-row">

        <label>
            Delivery Format Required
        </label>

        <select name="delivery_format">

            <option value="">
                Select Delivery Format
            </option>

            <option
                value="pdf_only"
                <?php selected(
                    $order->get_meta(
                        '_nyp_delivery_format'
                    ),
                    'pdf_only'
                ); ?>
            >
                PDF Presentation Only
            </option>

            <option
                value="pdf_renders"
                <?php selected(
                    $order->get_meta(
                        '_nyp_delivery_format'
                    ),
                    'pdf_renders'
                ); ?>
            >
                PDF Presentation + Render Images
            </option>

            <option
                value="pdf_drw"
                <?php selected(
                    $order->get_meta(
                        '_nyp_delivery_format'
                    ),
                    'pdf_drw'
                ); ?>
            >
                PDF Presentation + DRW File
            </option>

            <option
                value="pdf_renders_drw"
                <?php selected(
                    $order->get_meta(
                        '_nyp_delivery_format'
                    ),
                    'pdf_renders_drw'
                ); ?>
            >
                PDF Presentation + Render Images + DRW File
            </option>

            <option
                value="other"
                <?php selected(
                    $order->get_meta(
                        '_nyp_delivery_format'
                    ),
                    'other'
                ); ?>
            >
                Other File Export (Requires NYP Confirmation)
            </option>

            <option
                value="suggest"
                <?php selected(
                    $order->get_meta(
                        '_nyp_delivery_format'
                    ),
                    'suggest'
                ); ?>
            >
                Please Suggest
            </option>

        </select>

    </div>

    <div class="nyp-form-row">

        <label>
            Delivery Notes
        </label>

        <textarea
            name="delivery_notes"
            rows="4"
        ><?php

            echo esc_textarea(
                $order->get_meta(
                    '_nyp_delivery_notes'
                )
            );

        ?></textarea>

    </div>

</div>

<div class="nyp-form-section">

    <h3>
        Design Brief / Planning Goals
    </h3>

    <p class="nyp-section-description">
        Describe the overall design direction and planning objectives for this project.
    </p>

    <div class="nyp-form-row">

        <label>
            Desired Design Concept / Overall Direction
        </label>

        <textarea
            name="design_concept"
            rows="4"
        ><?php

            echo esc_textarea(
                $order->get_meta(
                    '_nyp_design_concept'
                )
            );

        ?></textarea>

    </div>

    <div class="nyp-form-row">

        <label>
            Planning Priority
        </label>

        <select name="planning_priority">

            <option value="">
                Select Priority
            </option>

            <option value="balanced">
                Balanced Approach
            </option>

            <option value="design">
                Design / Visual Impact
            </option>

            <option value="storage">
                Storage
            </option>

            <option value="functionality">
                Functionality / Workflow
            </option>

            <option value="budget">
                Budget-Conscious Planning
            </option>

            <option value="appliances">
                Appliances
            </option>

            <option value="materials">
                Materials
            </option>

            <option value="presentation">
                Presentation / Sales Impact
            </option>

            <option value="everyday_use">
                Easy Everyday Use
            </option>

        </select>

    </div>

    <div class="nyp-form-row">

        <label>
            Must-Have Features
        </label>

        <textarea
            name="must_have_features"
            rows="4"
        ><?php

            echo esc_textarea(
                $order->get_meta(
                    '_nyp_must_have_features'
                )
            );

        ?></textarea>

    </div>

    <div class="nyp-form-row">

        <label>
            Nice-to-Have Features
        </label>

        <textarea
            name="nice_to_have_features"
            rows="4"
        ><?php

            echo esc_textarea(
                $order->get_meta(
                    '_nyp_nice_to_have_features'
                )
            );

        ?></textarea>

    </div>

    <div class="nyp-form-row">

        <label>
            No-Gos / Exclusions
        </label>

        <textarea
            name="no_gos"
            rows="4"
        ><?php

            echo esc_textarea(
                $order->get_meta(
                    '_nyp_no_gos'
                )
            );

        ?></textarea>

    </div>

    <div class="nyp-form-row">

        <label>
            Additional Planning Notes
        </label>

        <textarea
            name="planning_notes"
            rows="5"
        ><?php

            echo esc_textarea(
                $order->get_meta(
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
        Please upload all available project documents. A floor plan is required before the planning process can begin.
    </p>

    <div class="nyp-form-row">

        <label>
        Floor Plan / Dimensioned Sketch *
        </label>

        <input
            type="file"
            class="nyp-file-upload"
            name="floor_plan"
            accept=".pdf,.jpg,.jpeg,.png"
            <?php echo $isLocked ? 'disabled' : ''; ?>
        >

        <?php

        $floor_plan = $order->get_meta(
            '_nyp_floor_plan'
        );



        $this->renderUploadedFile(
            'Current Floor Plan',
            $order->get_meta(
                '_nyp_floor_plan'
            ),
            !$isLocked
        );

        ?>

        <small>
        A dimensioned floor plan or clearly readable hand sketch is required.
        </small>

    </div>

    <div class="nyp-form-row">

        <label>
            Existing Kitchen Photos
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
            'Uploaded Kitchen Photos',
            (array) $order->get_meta(
                '_nyp_kitchen_photos'
            ),
            !$isLocked
        );

        ?>

        <small>
            Upload photos of the existing room or kitchen.
        </small>

    </div>

    <div class="nyp-form-row">

        <label>
            Inspiration Images
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
            'Uploaded Inspiration Images',
            (array) $order->get_meta(
                '_nyp_inspiration_images'
            ),
            !$isLocked
        );

        ?>

        <small>
            Reference images that reflect the desired design style.
        </small>

    </div>

    <div class="nyp-form-row">

        <label>
        Existing Planning / Sketches
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
            'Current Planning Export',
            $order->get_meta(
                '_nyp_planning_export'
            ),
            !$isLocked
        );

        ?>

        <small>
        Upload existing planning drafts, hand sketches, rough room sketches, screenshots, customer notes or similar documents.
        </small>

    </div>

    <div class="nyp-form-row">

        <label>
            Technical Documents
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
            'Uploaded Technical Documents',
            (array) $order->get_meta(
                '_nyp_technical_documents'
            ),
            !$isLocked
        );

        ?>

        <small>
            Architectural plans, measurements, utility drawings, utility plans and technical specifications.
        </small>

    </div>

    <div class="nyp-form-row">

        <label>
            Additional Attachments
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
            'Uploaded Additional Files',
            (array) $order->get_meta(
                '_nyp_additional_files'
            ),
            !$isLocked
        );

        ?>

        <small>
            Any additional files relevant to this project.
        </small>

    </div>

</div>

<div class="nyp-form-section">

    <h3>
        Confirmations
    </h3>

    <p class="nyp-section-description">
        Please review and confirm the following statements before proceeding.
    </p>

    <div class="nyp-checkbox-group">

        <label class="nyp-checkbox-label">

            <input
                type="checkbox"
                name="confirm_measurements"
                value="yes"
                <?php checked(
                    $order->get_meta(
                        '_nyp_confirm_measurements'
                    ),
                    'yes'
                ); ?>
                required
            >

            I confirm that all submitted measurements, dimensions and floor plans are accurate and complete to the best of my knowledge.

        </label>

        <label class="nyp-checkbox-label">

            <input
                type="checkbox"
                name="confirm_scope_review"
                value="yes"
                <?php checked(
                    $order->get_meta(
                        '_nyp_confirm_scope_review'
                    ),
                    'yes'
                ); ?>
                required
            >

            I understand that NYP may review the selected planning category before planning begins.

        </label>

        <label class="nyp-checkbox-label">

            <input
                type="checkbox"
                name="confirm_scope_adjustment"
                value="yes"
                <?php checked(
                    $order->get_meta(
                        '_nyp_confirm_scope_adjustment'
                    ),
                    'yes'
                ); ?>
                required
            >

            I understand that NYP may request an upgrade, reduce the planning scope, or pause/cancel the project if the selected category does not match the actual project requirements.

        </label>

        <label class="nyp-checkbox-label">

            <input
                type="checkbox"
                name="confirm_planning_quality"
                value="yes"
                <?php checked(
                    $order->get_meta(
                        '_nyp_confirm_planning_quality'
                    ),
                    'yes'
                ); ?>
                required
            >

            I understand that planning quality depends on complete and accurate project information.

        </label>

        <label class="nyp-checkbox-label">

            <input
                type="checkbox"
                name="confirm_budget_guidance"
                value="yes"
                <?php checked(
                    $order->get_meta(
                        '_nyp_confirm_budget_guidance'
                    ),
                    'yes'
                ); ?>
                required
            >

            I understand that budget and equipment information is used only as planning guidance and does not represent final pricing.

        </label>

        <label class="nyp-checkbox-label">

            <input
                type="checkbox"
                name="confirm_nyp_responsibility"
                value="yes"
                <?php checked(
                    $order->get_meta(
                        '_nyp_confirm_nyp_responsibility'
                    ),
                    'yes'
                ); ?>
                required
            >

            I understand that NYP is not responsible for installation, construction work, site execution, final measurements, manufacturer ordering, assembly, or final order verification.

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
        Save Draft
    </button>

    <button
        type="submit"
        name="nyp_action"
        value="submit_brief"
        class="button button-primary"
        onclick="return confirm('Are you sure you want to submit this planning brief? After submission, changes may require NYP review.');"
    >
    Review & Continue to Checkout
    </button>

</div>

<?php else : ?>

    <div class="nyp-brief-submitted">

<strong>
    ✓ Planning Brief Submitted
</strong>

<?php if ($submittedAt) : ?>

<div class="nyp-submission-info">

    <strong>
        Submitted:
    </strong>

    <?php

    echo esc_html(
        wp_date(
            'F j, Y g:i A',
            strtotime($submittedAt)
        )
    );

    ?>

</div>

<?php endif; ?>

<p>
    Your planning brief has been submitted successfully.
    Our planning team will now review your project and begin the design process.
</p>

</div>

<?php endif; ?>


<input
    type="hidden"
    name="return_url"
    value="<?php echo esc_url(add_query_arg(
        'order_id',
        $orderId,
        get_permalink()
    )); ?>"
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
    value="<?php echo esc_attr(
        $file
    ); ?>"
    class="button button-small"
    onclick="return confirm(
        'Remove this file?'
    );"
>
    Remove
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
    value="<?php echo esc_attr(
        $file
    ); ?>"
    class="button button-small"
    onclick="return confirm(
        'Remove this file?'
    );"
>
    Remove
</button>
    
                        <?php endif; ?>
    
                    </div>
    
                </div>
    
            <?php endforeach; ?>
    
        </div>
    
        <?php
    }
}
