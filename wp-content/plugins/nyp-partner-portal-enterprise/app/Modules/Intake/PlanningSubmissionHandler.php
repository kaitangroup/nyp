<?php

declare(strict_types=1);

namespace NYP\Modules\Intake;

use NYP\Services\PlanningSessionStorage;
use NYP\Services\PlanningWorkflowService;

defined('ABSPATH') || exit;

/*
|--------------------------------------------------------------------------
| Planning Submission Handler
|--------------------------------------------------------------------------
|
| Handles Planning Brief submissions before checkout.
|
| Responsibilities:
|
| - Validate request
| - Store planning fields
| - Store upload metadata
| - Save draft
| - Submit planning brief
|
| No WooCommerce order is required.
|
*/

class PlanningSubmissionHandler
{
    /**
     * Planning session.
     */
    protected PlanningSessionStorage $session;

    /**
     * Workflow.
     */
    protected PlanningWorkflowService $workflow;

    
    /**
     * Constructor.
     */
    public function __construct(
        PlanningSessionStorage $session,
        PlanningWorkflowService $workflow,
       
    ) {
        $this->session  = $session;
        $this->workflow = $workflow;
       
    }

    /*
    |--------------------------------------------------------------------------
    | Handle Request
    |--------------------------------------------------------------------------
    */

    /**
     * Handle planning submission.
     */
    public function handle(): void
    {

        
        if (
            empty($_POST['nyp_action'])
        ) {
            return;
        }

        switch (
            sanitize_key($_POST['nyp_action'])
        ) {

            case 'save_draft':

                $this->saveDraft();

                break;

            case 'submit_brief':

                $this->submit();

                break;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Draft
    |--------------------------------------------------------------------------
    */

    /**
     * Save planning draft.
     */
    public function saveDraft(): void
    {
        $uploadManager = new PlanningUploadManager(
            $this->session
        );
    
       
    
        $data = $this->collectPlanningData();
        $uploadData = $uploadManager->process();

        $existing = $this->session->all();

        $uploadData = array_filter(
            $uploadData,
            static fn($value) =>
                $value !== null &&
                $value !== []
        );

        $this->session->put(
            array_merge(
                $existing,
                $data,
                $uploadData
            )
        );
       
   
 
    }

    /*
    |--------------------------------------------------------------------------
    | Submit
    |--------------------------------------------------------------------------
    */

    /**
     * Submit Planning Brief.
     */
    public function submit(): void
    {
        $uploadManager = new PlanningUploadManager(
            $this->session
        );
    
       
    
        $data = $this->collectPlanningData();
        $uploadData = $uploadManager->process();

        $existing = $this->session->all();

        $uploadData = array_filter(
            $uploadData,
            static fn($value) =>
                $value !== null &&
                $value !== []
        );

        $this->session->put(
            array_merge(
                $existing,
                $data,
                $uploadData
            )
        );

        $sessionData = $this->session->all();

if (empty($sessionData['_nyp_floor_plan'])) {

    wc_add_notice(
        __(
            'Please upload floor plan before continuing.',
            'nyp'
        ),
        'error'
    );

    wp_safe_redirect(
        $this->workflow->getPlanningUrl()
    );

    exit;
}

        $this->workflow->markPlanningSubmitted();

$this->workflow->prepareCheckout();

wp_safe_redirect(
    $this->workflow->getCheckoutUrl()
);

exit;
    }

    public function removeFile(): void
{
    $relativePath = sanitize_text_field(
        wp_unslash(
            $_POST['delete_file'] ?? ''
        )
    );

    if (!$relativePath) {
        return;
    }

    $uploadManager = new PlanningUploadManager(
        $this->session
    );

    $uploadManager->remove(
        $relativePath
    );

    $this->removeUploadReference(
        $relativePath
    );

    $returnUrl = esc_url_raw(
        wp_unslash(
            $_POST['return_url'] ?? ''
        )
    );
    
    if (empty($returnUrl)) {
        $returnUrl = $this->workflow->getPlanningUrl();
    }
    
    wp_safe_redirect($returnUrl);
    
    exit;
}

protected function removeUploadReference(
    string $relativePath
): void {

    $data = $this->session->all();

    foreach ($data as $key => $value) {

        if ($value === $relativePath) {

            unset($data[$key]);

            continue;
        }

        if (is_array($value)) {

            $data[$key] = array_values(
                array_diff(
                    $value,
                    [$relativePath]
                )
            );
        }
    }

    $this->session->put($data);
}

/**
 * Collect and normalize Planning Brief data.
 *
 * Removes framework fields, sanitizes values and produces a
 * consistent dataset for session storage.
 *
 * @return array
 */
/**
 * Collect and normalize Planning Brief data.
 *
 * Produces the same meta structure used by WooCommerce orders.
 *
 * @return array
 */
protected function collectPlanningData(): array
{
    $excluded = [
        '_wpnonce',
        '_wp_http_referer',
        'nyp_action',
        'action',
        'submit',
        'order_id',
        'return_url',
    ];

    $data = [];

    foreach ($_POST as $key => $value) {

        if (in_array($key, $excluded, true)) {
            continue;
        }

        /*
        |--------------------------------------------------------------------------
        | Meta Key
        |--------------------------------------------------------------------------
        */

        $metaKey = '_nyp_' . sanitize_key($key);

        /*
        |--------------------------------------------------------------------------
        | Arrays
        |--------------------------------------------------------------------------
        */

        if (is_array($value)) {

            $data[$metaKey] = array_map(
                static function ($item) {

                    return is_scalar($item)
                        ? sanitize_text_field(
                            wp_unslash((string) $item)
                        )
                        : $item;

                },
                wp_unslash($value)
            );

            continue;
        }

        /*
        |--------------------------------------------------------------------------
        | Scalar Values
        |--------------------------------------------------------------------------
        */

        $data[$metaKey] = sanitize_text_field(
            wp_unslash((string) $value)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Normalize Confirmation Fields
    |--------------------------------------------------------------------------
    */

    $confirmationFields = [
        '_nyp_package_validation_confirmation',
        '_nyp_confirm_measurements',
        '_nyp_confirm_scope_review',
        '_nyp_confirm_scope_adjustment',
        '_nyp_confirm_planning_quality',
        '_nyp_confirm_budget_guidance',
        '_nyp_confirm_nyp_responsibility',
    ];

    foreach ($confirmationFields as $field) {

        $data[$field] = isset($data[$field])
            ? 'yes'
            : 'no';
    }

    /*
    |--------------------------------------------------------------------------
    | Preserve Workflow Data
    |--------------------------------------------------------------------------
    */

    $productId = (int) $this->session->get(
        '_nyp_product_id'
    );

    if ($productId > 0) {

        $data['_nyp_product_id'] = $productId;
    }

    /*
    |--------------------------------------------------------------------------
    | Workflow Status
    |--------------------------------------------------------------------------
    */

    $data['_nyp_brief_submitted'] = 'no';

    /*
    |--------------------------------------------------------------------------
    | Schema Version
    |--------------------------------------------------------------------------
    */

    $data['_nyp_schema_version'] = 1;
    /*
    |--------------------------------------------------------------------------
    | Premium Room Concept cannot use Express Planning
    |--------------------------------------------------------------------------
    */

    if (
        ($data['_nyp_planning_category'] ?? '') === 'premium'
    ) {
        $data['_nyp_service_speed'] = 'standard';
    }

    return $data;

    return $data;
}
}