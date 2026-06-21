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
     

       
        $isSubmitted = (bool) $order->get_meta(
            '_nyp_brief_submitted'
        );
        $readonly = $isSubmitted
    ? 'readonly'
    : '';

$disabled = $isSubmitted
    ? 'disabled'
    : '';

    $isLocked = (bool) $order->get_meta(
        '_nyp_brief_submitted'
    );

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
            if (
                isset($_GET['saved'])
            ) {
                echo '<div class="woocommerce-message">
        Project information saved.
    </div>';

 if($isSubmitted) {
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
        Reference Number
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
        Customer Name *
    </label>

    <input
        type="text"
        name="customer_name"
        value="<?php echo esc_attr(
            $order->get_meta(
                '_nyp_customer_name'
            )
        ); ?>"
        required
    >

</div>

<div class="nyp-form-row">

    <label>
        Installation Address *
    </label>

    <textarea
        name="installation_address"
        required><?php

        echo esc_textarea(
            $order->get_meta(
                '_nyp_installation_address'
            )
        );

        ?></textarea>

</div>

<div class="nyp-form-row">

    <label>
        Target Installation Date
    </label>

    <input
        type="date"
        name="installation_date"
        value="<?php echo esc_attr(
            $order->get_meta(
                '_nyp_installation_date'
            )
        ); ?>"
    >

</div>
    </div>

    <div class="nyp-form-section">

<h3>
    Planning Category
</h3>

<?php 



?>

<p class="nyp-section-description">
    Please review the selected planning category. NYP may pause processing and request an upgrade if the selected category does not match the actual project scope.
</p>

<?php

$category = '';

foreach ($order->get_items() as $item) {

    $productName = $item->get_name();

    if (
        (stripos($productName, 'Basic') !== false  ) || ( stripos($productName, 'Basis') !== false)
    ) {

        $category = 'Basic Planning';

    } elseif (
        stripos($productName, 'Professional') !== false
    ) {

        $category = 'Professional Kitchen Design';

    } elseif (
        stripos($productName, 'Premium') !== false
    ) {

        $category = 'Premium Room Concept';
    }

    break;
}

?>

<div class="nyp-form-row">

    <label>
        Selected Planning Category
    </label>

    <input
        type="text"
        value="<?php echo esc_attr($category); ?>"
        readonly
    >

</div>

<div class="nyp-form-row">

    <label>
        Category Description
    </label>

    <div class="nyp-category-info">

        <?php if ($category === 'Basic Planning') : ?>

            <p>
                Functional planning for simple kitchen layouts with clear specifications and limited design complexity.
            </p>

        <?php elseif ($category === 'Professional Kitchen Design') : ?>

            <p>
                High-quality, sellable kitchen planning with stronger design ambition, presentation value and spatial thinking.
            </p>

        <?php elseif ($category === 'Premium Room Concept') : ?>

            <p>
                Advanced room and kitchen concept with deeper design development, spatial planning and presentation quality.
            </p>

        <?php endif; ?>

    </div>

</div>

<div class="nyp-form-row">

    <label class="nyp-checkbox-label">

        <input
            type="checkbox"
            name="category_confirmation"
            value="yes"
            <?php checked(
                $order->get_meta(
                    '_nyp_category_confirmation'
                ),
                'yes'
            ); ?>
            required
        >

        I understand that NYP may review the project scope and request an upgrade if the selected planning category does not match the actual project requirements.

    </label>

</div>

</div>

    <div class="nyp-form-section">

<h3>
    Room & Kitchen Layout
</h3>

<p class="nyp-section-description">
    Please provide basic room and layout information.
</p>

<div class="nyp-form-row">

    <label>
        Room Shape
    </label>

    <select name="room_shape">

        <option value="">
            Select
        </option>

        <option value="rectangular">
            Rectangular
        </option>

        <option value="square">
            Square
        </option>

        <option value="open_plan">
            Open Plan
        </option>

        <option value="irregular">
            Irregular
        </option>

    </select>

</div>

<div class="nyp-form-row">

    <label>
        Kitchen Type
    </label>

    <select name="kitchen_type">

        <option value="">
            Select
        </option>

        <option value="single_wall">
            Single Wall
        </option>

        <option value="galley">
            Galley
        </option>

        <option value="l_shape">
            L-Shape
        </option>

        <option value="u_shape">
            U-Shape
        </option>

        <option value="island">
            Island
        </option>

        <option value="peninsula">
            Peninsula
        </option>

    </select>

</div>

<div class="nyp-form-row">

    <label>
        Room Width (mm)
    </label>

    <input
        type="number"
        name="room_width"
    >

</div>

<div class="nyp-form-row">

    <label>
        Room Length (mm)
    </label>

    <input
        type="number"
        name="room_length"
    >

</div>

<div class="nyp-form-row">

    <label>
        Ceiling Height (mm)
    </label>

    <input
        type="number"
        name="ceiling_height"
    >

</div>

<div class="nyp-form-row">

    <label>
        Additional Notes
    </label>

    <textarea
        name="layout_notes"
    ></textarea>

</div>

</div>

<div class="nyp-form-section">

    <h3>
    Manufacturer / Program / Front / Handle
    </h3>

    <p class="nyp-section-description">
        Tell us which manufacturer and planning system are being used for this project.
    </p>

    <div class="nyp-form-row">

        <label>
            Kitchen Manufacturer *
        </label>

        <input
            type="text"
            name="manufacturer"
            value="<?php echo esc_attr(
                $order->get_meta('_nyp_manufacturer')
            ); ?>"
            placeholder="e.g. Nobilia, Häcker, Schüller"
            required
        >

    </div>

    <div class="nyp-form-row">

        <label>
            Planning Software
        </label>

        <select name="planning_software">

            <option value="">
                Select
            </option>

            <option value="carat">
                Carat
            </option>

            <option value="winner">
                Winner
            </option>

            <option value="compusoft">
                Compusoft
            </option>

            <option value="kps">
                KPS
            </option>

            <option value="none">
                No Planning Software
            </option>

            <option value="other">
                Other
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
                $order->get_meta('_nyp_product_line')
            ); ?>"
            placeholder="e.g. Easytouch, Laser, Nova"
        >

    </div>

    <div class="nyp-form-row">

        <label>
            Front / Door Style
        </label>

        <input
            type="text"
            name="door_style"
            value="<?php echo esc_attr(
                $order->get_meta('_nyp_door_style')
            ); ?>"
            placeholder="Optional"
        >

    </div>

    <div class="nyp-form-row">

        <label>
            Color / Finish
        </label>

        <input
            type="text"
            name="finish"
            value="<?php echo esc_attr(
                $order->get_meta('_nyp_finish')
            ); ?>"
            placeholder="Optional"
        >

    </div>

    <div class="nyp-form-row">

        <label>
            Existing Planning Available?
        </label>

        <select name="existing_planning">

            <option value="">
                Select
            </option>

            <option value="yes">
                Yes
            </option>

            <option value="no">
                No
            </option>

        </select>

    </div>

    <div class="nyp-form-row">

        <label>
            Special Manufacturer Notes
        </label>

        <textarea
            name="manufacturer_notes"
            rows="4"
        ><?php

            echo esc_textarea(
                $order->get_meta('_nyp_manufacturer_notes')
            );

        ?></textarea>

    </div>

</div>

<div class="nyp-form-section">

    <h3>
        Worktop / Niche / Ergonomics
    </h3>

    <p class="nyp-section-description">
        Please specify worktop preferences, ergonomic requirements and niche design details. Open answers such as "Please suggest" are acceptable.
    </p>

    <div class="nyp-form-row">

        <label>
            Worktop Material
        </label>

        <select name="worktop_material">

            <option value="">Select</option>

            <option value="laminate" <?php selected($order->get_meta('_nyp_worktop_material'), 'laminate'); ?>>
                Laminate
            </option>

            <option value="compact" <?php selected($order->get_meta('_nyp_worktop_material'), 'compact'); ?>>
                Compact
            </option>

            <option value="glass" <?php selected($order->get_meta('_nyp_worktop_material'), 'glass'); ?>>
                Glass Worktop
            </option>

            <option value="ceramic" <?php selected($order->get_meta('_nyp_worktop_material'), 'ceramic'); ?>>
                Ceramic
            </option>

            <option value="quartz" <?php selected($order->get_meta('_nyp_worktop_material'), 'quartz'); ?>>
                Quartz Composite
            </option>

            <option value="natural_stone" <?php selected($order->get_meta('_nyp_worktop_material'), 'natural_stone'); ?>>
                Natural Stone
            </option>

            <option value="dekton" <?php selected($order->get_meta('_nyp_worktop_material'), 'dekton'); ?>>
                Dekton / Sintered Stone
            </option>

            <option value="wood" <?php selected($order->get_meta('_nyp_worktop_material'), 'wood'); ?>>
                Wood
            </option>

            <option value="stainless" <?php selected($order->get_meta('_nyp_worktop_material'), 'stainless'); ?>>
                Stainless Steel
            </option>

            <option value="please_suggest" <?php selected($order->get_meta('_nyp_worktop_material'), 'please_suggest'); ?>>
                Please Suggest
            </option>

        </select>

    </div>

    <div class="nyp-form-row">

        <label>
            Worktop Thickness
        </label>

        <select name="worktop_thickness">

            <option value="">Select</option>

            <option value="10mm">10 mm</option>
            <option value="12mm">12 mm</option>
            <option value="16mm">16 mm</option>
            <option value="20mm">20 mm</option>
            <option value="25mm">25 mm</option>
            <option value="30mm">30 mm</option>
            <option value="40mm">40 mm</option>
            <option value="please_suggest">Please Suggest</option>

        </select>

    </div>

    <div class="nyp-form-row">

        <label>
            Desired Work Height
        </label>

        <input
            type="text"
            name="work_height"
            value="<?php echo esc_attr(
                $order->get_meta('_nyp_work_height')
            ); ?>"
            placeholder="e.g. 92 cm or Please Suggest"
        >

    </div>

    <div class="nyp-form-row">

        <label>
            Corpus Height
        </label>

        <select name="corpus_height">

            <option value="">Select</option>

            <option value="standard">Standard</option>
            <option value="xl">XL</option>
            <option value="please_suggest">Please Suggest</option>

        </select>

    </div>

    <div class="nyp-form-row">

        <label>
            Plinth Height
        </label>

        <select name="plinth_height">

            <option value="">Select</option>

            <option value="5">5 cm</option>
            <option value="7.5">7.5 cm</option>
            <option value="10">10 cm</option>
            <option value="12.5">12.5 cm</option>
            <option value="15">15 cm</option>
            <option value="please_suggest">Please Suggest</option>

        </select>

    </div>

    <div class="nyp-form-row">

        <label>
            Niche Cladding
        </label>

        <select name="niche_cladding">

            <option value="">Select</option>

            <option value="none">No Niche Cladding</option>
            <option value="glass">Glass</option>
            <option value="mirror">Mirror</option>
            <option value="corpus_material">Corpus Material</option>
            <option value="worktop_material">Worktop Material</option>
            <option value="tiles">Tiles</option>
            <option value="stone_ceramic">Stone / Ceramic</option>
            <option value="please_suggest">Please Suggest</option>

        </select>

    </div>

</div>

<div class="nyp-form-section">

    <h3>
    Appliances / Sink / Tap
    </h3>

    <p class="nyp-section-description">
        Please provide appliance requirements and indicate whether existing appliances will be reused.
    </p>

    <div class="nyp-form-row">

        <label>
            Existing Appliances To Reuse?
        </label>

        <select name="reuse_appliances">

            <option value="">
                Select
            </option>

            <option value="yes">
                Yes
            </option>

            <option value="no">
                No
            </option>

            <option value="partial">
                Partially
            </option>

        </select>

    </div>

    <div class="nyp-form-row">

        <label>
            Preferred Appliance Manufacturer
        </label>

        <input
            type="text"
            name="appliance_brand"
            value="<?php echo esc_attr(
                $order->get_meta('_nyp_appliance_brand')
            ); ?>"
            placeholder="e.g. Bosch, Siemens, Miele, Neff"
        >

    </div>

    <div class="nyp-form-row">

        <label>
            Hob / Cooktop
        </label>

        <input
            type="text"
            name="cooktop"
            value="<?php echo esc_attr(
                $order->get_meta('_nyp_cooktop')
            ); ?>"
            placeholder="Induction, Gas, Existing Model..."
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
                $order->get_meta('_nyp_oven')
            ); ?>"
            placeholder="Model or requirements"
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
                $order->get_meta('_nyp_microwave')
            ); ?>"
            placeholder="Optional"
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
                $order->get_meta('_nyp_refrigerator')
            ); ?>"
            placeholder="Integrated / Freestanding"
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
                $order->get_meta('_nyp_freezer')
            ); ?>"
            placeholder="Optional"
        >

    </div>

    <div class="nyp-form-row">

        <label>
            Dishwasher
        </label>

        <input
            type="text"
            name="dishwasher"
            value="<?php echo esc_attr(
                $order->get_meta('_nyp_dishwasher')
            ); ?>"
            placeholder="Integrated / Existing"
        >

    </div>

    <div class="nyp-form-row">

        <label>
            Extractor Hood
        </label>

        <input
            type="text"
            name="extractor_hood"
            value="<?php echo esc_attr(
                $order->get_meta('_nyp_extractor_hood')
            ); ?>"
            placeholder="Ceiling, Wall, Downdraft..."
        >

    </div>

    <div class="nyp-form-row">

        <label>
            Additional Appliance Requirements
        </label>

        <textarea
            name="appliance_notes"
            rows="4"
        ><?php

            echo esc_textarea(
                $order->get_meta('_nyp_appliance_notes')
            );

        ?></textarea>

    </div>

</div>

<div class="nyp-form-section">

    <h3>
        Budget & Equipment Level
    </h3>

    <p class="nyp-section-description">
        The most important information is the desired quality and equipment level, not necessarily the exact customer budget.
    </p>

    <div class="nyp-form-row">

        <label>
            Furniture / Manufacturer Level
        </label>

        <select name="furniture_level">

            <option value="">Select</option>

            <option value="entry" <?php selected($order->get_meta('_nyp_furniture_level'), 'entry'); ?>>
                Entry Level
            </option>

            <option value="mid_range" <?php selected($order->get_meta('_nyp_furniture_level'), 'mid_range'); ?>>
                Solid Mid-Range
            </option>

            <option value="elevated" <?php selected($order->get_meta('_nyp_furniture_level'), 'elevated'); ?>>
                Elevated Segment
            </option>

            <option value="premium" <?php selected($order->get_meta('_nyp_furniture_level'), 'premium'); ?>>
                Premium
            </option>

            <option value="high_end" <?php selected($order->get_meta('_nyp_furniture_level'), 'high_end'); ?>>
                High-End
            </option>

            <option value="fixed" <?php selected($order->get_meta('_nyp_furniture_level'), 'fixed'); ?>>
                Manufacturer Fixed
            </option>

            <option value="please_suggest" <?php selected($order->get_meta('_nyp_furniture_level'), 'please_suggest'); ?>>
                Please Suggest
            </option>

        </select>

    </div>

    <div class="nyp-form-row">

        <label>
            Manufacturer / Program Fixed?
        </label>

        <select name="manufacturer_fixed">

            <option value="">Select</option>

            <option value="yes">
                Yes
            </option>

            <option value="no">
                No
            </option>

            <option value="still_open">
                Still Open
            </option>

            <option value="please_suggest">
                Please Suggest
            </option>

        </select>

    </div>

    <div class="nyp-form-row">

        <label>
            Manufacturer Details
        </label>

        <input
            type="text"
            name="manufacturer_fixed_details"
            value="<?php echo esc_attr(
                $order->get_meta(
                    '_nyp_manufacturer_fixed_details'
                )
            ); ?>"
            placeholder="If fixed, specify manufacturer or program"
        >

    </div>

    <div class="nyp-form-row">

        <label>
            Appliance Class
        </label>

        <select name="appliance_class">

            <option value="">Select</option>

            <option value="entry">
                Entry
            </option>

            <option value="mid_range">
                Mid-Range
            </option>

            <option value="elevated">
                Elevated
            </option>

            <option value="premium">
                Premium (e.g. Miele / Higher BSH)
            </option>

            <option value="high_end">
                High-End (Gaggenau, Bora Professional, V-Zug)
            </option>

            <option value="fixed">
                Appliances Fixed
            </option>

            <option value="please_suggest">
                Please Suggest
            </option>

        </select>

    </div>

    <div class="nyp-form-row">

        <label>
            Worktop Level
        </label>

        <select name="worktop_level">

            <option value="">Select</option>

            <option value="price_conscious">
                Price Conscious
            </option>

            <option value="elevated">
                Elevated
            </option>

            <option value="premium">
                Premium
            </option>

            <option value="high_end">
                High-End Material Solution
            </option>

            <option value="please_suggest">
                Please Suggest
            </option>

        </select>

    </div>

    <div class="nyp-form-row">

        <label>
            Budget Range (Planning Orientation)
        </label>

        <select name="budget_range">

            <option value="">Select</option>

            <option value="under_15000">
                Up to €15,000
            </option>

            <option value="15000_25000">
                €15,000 – €25,000
            </option>

            <option value="25000_40000">
                €25,000 – €40,000
            </option>

            <option value="40000_60000">
                €40,000 – €60,000
            </option>

            <option value="over_60000">
                Over €60,000
            </option>

            <option value="open">
                Open
            </option>

            <option value="not_relevant">
                Not Relevant – Plan by Equipment Level
            </option>

        </select>

    </div>

    <div class="nyp-form-row">

        <label>
            Budget Refers To
        </label>

        <select name="budget_scope">

            <option value="">Select</option>

            <option value="furniture">
                Furniture Only
            </option>

            <option value="appliances">
                Appliances Only
            </option>

            <option value="furniture_appliances">
                Furniture + Appliances
            </option>

            <option value="complete_project">
                Complete Project incl. Worktop & Accessories
            </option>

            <option value="unclear">
                Unclear
            </option>

        </select>

    </div>

    <div class="nyp-form-row">

        <label>
            Planning Priority
        </label>

        <select name="planning_priority">

            <option value="">Select</option>

            <option value="budget">
                Keep Budget
            </option>

            <option value="design">
                Design Impact
            </option>

            <option value="storage">
                Storage Capacity
            </option>

            <option value="appliances">
                Appliance Quality
            </option>

            <option value="materials">
                Material Quality
            </option>

            <option value="balanced">
                Balanced Planning
            </option>

        </select>

    </div>

    <div class="nyp-form-row">

        <label>
            Budget & Equipment Notes
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

<div class="nyp-form-section">

    <h3>
        Planning Software & Delivery Format
    </h3>

    <p class="nyp-section-description">
        Please specify the planning software currently being used and the preferred delivery format for the final planning files.
    </p>

    <div class="nyp-form-row">

        <label>
            Planning Software
        </label>

        <select name="planning_software_used">

            <option value="">Select</option>

            <option value="winner" <?php selected($order->get_meta('_nyp_planning_software_used'), 'winner'); ?>>
                Winner
            </option>

            <option value="carat" <?php selected($order->get_meta('_nyp_planning_software_used'), 'carat'); ?>>
                Carat
            </option>

            <option value="compusoft" <?php selected($order->get_meta('_nyp_planning_software_used'), 'compusoft'); ?>>
                Compusoft
            </option>

            <option value="kps" <?php selected($order->get_meta('_nyp_planning_software_used'), 'kps'); ?>>
                KPS
            </option>

            <option value="2020_design" <?php selected($order->get_meta('_nyp_planning_software_used'), '2020_design'); ?>>
                2020 Design
            </option>

            <option value="other" <?php selected($order->get_meta('_nyp_planning_software_used'), 'other'); ?>>
                Other
            </option>

            <option value="unknown" <?php selected($order->get_meta('_nyp_planning_software_used'), 'unknown'); ?>>
                Unknown
            </option>

        </select>

    </div>

    <div class="nyp-form-row">

        <label>
            Software Version
        </label>

        <input
            type="text"
            name="software_version"
            value="<?php echo esc_attr(
                $order->get_meta('_nyp_software_version')
            ); ?>"
            placeholder="e.g. Winner Flex 15"
        >

    </div>

    <div class="nyp-form-row">

        <label>
            Delivery Format Required
        </label>

        <select name="delivery_format">

            <option value="">Select</option>

            <option value="pdf" <?php selected($order->get_meta('_nyp_delivery_format'), 'pdf'); ?>>
                PDF Only
            </option>

            <option value="pdf_images" <?php selected($order->get_meta('_nyp_delivery_format'), 'pdf_images'); ?>>
                PDF + Images
            </option>

            <option value="pdf_drw" <?php selected($order->get_meta('_nyp_delivery_format'), 'pdf_drw'); ?>>
                PDF + DRW
            </option>

            <option value="pdf_images_drw" <?php selected($order->get_meta('_nyp_delivery_format'), 'pdf_images_drw'); ?>>
                PDF + Images + DRW
            </option>

            <option value="software_export" <?php selected($order->get_meta('_nyp_delivery_format'), 'software_export'); ?>>
                Native Software Export
            </option>

            <option value="please_suggest" <?php selected($order->get_meta('_nyp_delivery_format'), 'please_suggest'); ?>>
                Please Suggest
            </option>

        </select>

    </div>

    <div class="nyp-form-row">

        <label>
            DRW File Required?
        </label>

        <select name="drw_required">

            <option value="">Select</option>

            <option value="yes" <?php selected($order->get_meta('_nyp_drw_required'), 'yes'); ?>>
                Yes
            </option>

            <option value="no" <?php selected($order->get_meta('_nyp_drw_required'), 'no'); ?>>
                No
            </option>

            <option value="if_available" <?php selected($order->get_meta('_nyp_drw_required'), 'if_available'); ?>>
                If Available
            </option>

        </select>

    </div>

    <div class="nyp-form-row">

        <label>
            Renderings Required
        </label>

        <select name="renderings_required">

            <option value="">Select</option>

            <option value="yes" <?php selected($order->get_meta('_nyp_renderings_required'), 'yes'); ?>>
                Yes
            </option>

            <option value="no" <?php selected($order->get_meta('_nyp_renderings_required'), 'no'); ?>>
                No
            </option>

            <option value="please_recommend" <?php selected($order->get_meta('_nyp_renderings_required'), 'please_recommend'); ?>>
                Please Recommend
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
        Special Wishes / No-Gos
    </h3>

    <p class="nyp-section-description">
        Please describe any special customer requests, mandatory requirements, restrictions, or elements that should be avoided during planning.
    </p>

    <div class="nyp-form-row">

        <label>
            Must-Have Features
        </label>

        <textarea
            name="must_have_features"
            rows="4"
            placeholder="e.g. Kitchen island, tall storage wall, Bora cooktop, breakfast area..."
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
            placeholder="Optional features that would be appreciated if possible."
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
            placeholder="e.g. No wall cabinets, no dark colors, no visible handles..."
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
            Customer Priorities
        </label>

        <select name="customer_priority">

            <option value="">Select</option>

            <option value="design"
                <?php selected(
                    $order->get_meta('_nyp_customer_priority'),
                    'design'
                ); ?>
            >
                Design
            </option>

            <option value="storage"
                <?php selected(
                    $order->get_meta('_nyp_customer_priority'),
                    'storage'
                ); ?>
            >
                Storage
            </option>

            <option value="functionality"
                <?php selected(
                    $order->get_meta('_nyp_customer_priority'),
                    'functionality'
                ); ?>
            >
                Functionality
            </option>

            <option value="budget"
                <?php selected(
                    $order->get_meta('_nyp_customer_priority'),
                    'budget'
                ); ?>
            >
                Budget
            </option>

            <option value="appliances"
                <?php selected(
                    $order->get_meta('_nyp_customer_priority'),
                    'appliances'
                ); ?>
            >
                Appliances
            </option>

            <option value="materials"
                <?php selected(
                    $order->get_meta('_nyp_customer_priority'),
                    'materials'
                ); ?>
            >
                Materials
            </option>

            <option value="balanced"
                <?php selected(
                    $order->get_meta('_nyp_customer_priority'),
                    'balanced'
                ); ?>
            >
                Balanced Approach
            </option>

        </select>

    </div>

    <div class="nyp-form-row">

        <label>
            Additional Planning Notes
        </label>

        <textarea
            name="planning_notes"
            rows="6"
            placeholder="Any additional information that may help the planner."
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
    Split into Kitchen Form + Worktop + Special Wishes
    </h3>

    <p class="nyp-section-description">
        Please describe the desired design style, materials, storage requirements, and any special customer preferences.
    </p>

    <div class="nyp-form-row">

        <label>
            Preferred Design Style
        </label>

        <select name="design_style">

            <option value="">Select</option>

            <option value="modern">Modern</option>

            <option value="contemporary">Contemporary</option>

            <option value="minimalist">Minimalist</option>

            <option value="classic">Classic</option>

            <option value="country">Country / Cottage</option>

            <option value="industrial">Industrial</option>

            <option value="scandinavian">Scandinavian</option>

            <option value="luxury">Luxury</option>

            <option value="other">Other</option>

        </select>

    </div>

    <div class="nyp-form-row">

        <label>
            Preferred Color Scheme
        </label>

        <input
            type="text"
            name="color_scheme"
            value="<?php echo esc_attr(
                $order->get_meta('_nyp_color_scheme')
            ); ?>"
            placeholder="e.g. White & Oak, Anthracite, Cashmere"
        >

    </div>

    <div class="nyp-form-row">

        <label>
            Worktop Preference
        </label>

        <input
            type="text"
            name="worktop_preference"
            value="<?php echo esc_attr(
                $order->get_meta('_nyp_worktop_preference')
            ); ?>"
            placeholder="Quartz, Ceramic, Laminate, Granite..."
        >

    </div>

    <div class="nyp-form-row">

        <label>
            Handle Preference
        </label>

        <select name="handle_preference">

            <option value="">Select</option>

            <option value="handleless">Handleless</option>

            <option value="integrated">Integrated Handle</option>

            <option value="bar_handle">Bar Handle</option>

            <option value="knob">Knob</option>

            <option value="no_preference">No Preference</option>

        </select>

    </div>

    <div class="nyp-form-row">

        <label>
            Lighting Requirements
        </label>

        <textarea
            name="lighting_requirements"
            rows="3"
        ><?php

            echo esc_textarea(
                $order->get_meta('_nyp_lighting_requirements')
            );

        ?></textarea>

    </div>

    <div class="nyp-form-row">

        <label>
            Storage Requirements
        </label>

        <textarea
            name="storage_requirements"
            rows="3"
        ><?php

            echo esc_textarea(
                $order->get_meta('_nyp_storage_requirements')
            );

        ?></textarea>

    </div>

    <div class="nyp-form-row">

        <label>
            Special Customer Requests
        </label>

        <textarea
            name="customer_requests"
            rows="4"
        ><?php

            echo esc_textarea(
                $order->get_meta('_nyp_customer_requests')
            );

        ?></textarea>

    </div>

    <div class="nyp-form-row">

        <label>
            Additional Design Notes
        </label>

        <textarea
            name="design_notes"
            rows="5"
        ><?php

            echo esc_textarea(
                $order->get_meta('_nyp_design_notes')
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
            Floor Plan / Ground Plan *
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
            Accepted formats: PDF, JPG, JPEG, PNG
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
            Manufacturer Planning Export
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
            Optional export from Carat, Winner, Compusoft, KPS or similar software.
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
        Please review and confirm the following statements before submitting the planning brief.
    </p>

    <div class="nyp-confirmation-list">

        <label class="nyp-checkbox-label">

            <input
                type="checkbox"
                name="confirm_measurements"
                value="yes"
                <?php checked(
                    $order->get_meta('_nyp_confirm_measurements'),
                    'yes'
                ); ?>
            >

            I confirm that all submitted measurements, dimensions and floorplans are correct to the best of my knowledge.

        </label>

        <label class="nyp-checkbox-label">

            <input
                type="checkbox"
                name="confirm_category_review"
                value="yes"
                <?php checked(
                    $order->get_meta('_nyp_confirm_category_review'),
                    'yes'
                ); ?>
            >

            I understand that NYP may review the selected planning category and request an upgrade if the project scope exceeds the selected service level.

        </label>

        <label class="nyp-checkbox-label">

            <input
                type="checkbox"
                name="confirm_planning_basis"
                value="yes"
                <?php checked(
                    $order->get_meta('_nyp_confirm_planning_basis'),
                    'yes'
                ); ?>
            >

            I understand that planning quality depends on the accuracy and completeness of the information and files provided.

        </label>

        <label class="nyp-checkbox-label">

            <input
                type="checkbox"
                name="confirm_budget_guidance"
                value="yes"
                <?php checked(
                    $order->get_meta('_nyp_confirm_budget_guidance'),
                    'yes'
                ); ?>
            >

            I understand that budget and equipment information is intended as planning guidance and may require adjustment during project development.

        </label>

        <label class="nyp-checkbox-label">

            <input
                type="checkbox"
                name="confirm_delivery_requirements"
                value="yes"
                <?php checked(
                    $order->get_meta('_nyp_confirm_delivery_requirements'),
                    'yes'
                ); ?>
            >

            I confirm that the selected software, delivery format and planning requirements have been reviewed.

        </label>

        <label class="nyp-checkbox-label">

            <input
                type="checkbox"
                name="confirm_execution_responsibility"
                value="yes"
                <?php checked(
                    $order->get_meta('_nyp_confirm_execution_responsibility'),
                    'yes'
                ); ?>
            >

            I understand that NYP provides planning services only and is not responsible for installation, construction work or site execution.

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
        onclick="return confirm('Are you sure you want to submit this planning brief? After submission you will no longer be able to edit it.');"
    >
        Submit Planning Brief
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
    value="<?php echo esc_url( add_query_arg(
        'order_id',
        $orderId,
        get_permalink()
    ) ); ?>"
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
