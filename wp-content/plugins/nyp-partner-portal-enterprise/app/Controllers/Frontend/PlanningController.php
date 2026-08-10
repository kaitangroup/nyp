<?php

declare(strict_types=1);

namespace NYP\Controllers\Frontend;

use NYP\Services\PlanningSessionStorage;
use NYP\Services\PlanningWorkflowService;
use NYP\Modules\Intake\IntakeFormRenderer;
use NYP\Modules\Intake\PlanningSubmissionHandler;

defined('ABSPATH') || exit;

/*
|--------------------------------------------------------------------------
| Planning Controller
|--------------------------------------------------------------------------
|
| Handles the customer planning workflow.
| Coordinates between the frontend, services and intake module.
|
*/

class PlanningController
{
    /**
     * Planning session storage.
     *
     * @var PlanningSessionStorage
     */
    protected PlanningSessionStorage $session;

    /**
     * Workflow service.
     *
     * @var PlanningWorkflowService
     */
    protected PlanningWorkflowService $workflow;

    /**
     * Intake renderer.
     *
     * @var IntakeFormRenderer
     */
    protected IntakeFormRenderer $renderer;

    protected PlanningSubmissionHandler $submissionHandler;

    /**
     * Constructor.
     */
    public function __construct(
        PlanningSessionStorage $session,
        PlanningWorkflowService $workflow,
        IntakeFormRenderer $renderer,
        PlanningSubmissionHandler $submissionHandler
    ) {
        $this->session  = $session;
        $this->workflow = $workflow;
        $this->renderer = $renderer;
        $this->submissionHandler = $submissionHandler;
    }

    /**
 * Start a new planning workflow.
 *
 * URL:
 * /?nyp_action=start_planning&product_id=123
 *
 * @return void
 */
public function start(): void
{
    if (!$this->canAccessPlanning()) {

        wc_add_notice(
            __(
                'Your partner account is currently awaiting approval by NYP. You will be able to submit planning requests after approval.',
                'nyp'
            ),
            'notice'
        );

        wp_safe_redirect(
            wc_get_page_permalink('myaccount')
        );

        exit;
    }

    $productId = absint(
        $_GET['product_id'] ?? 0
    );

    if (!$productId) {

        wp_safe_redirect(
            home_url()
        );

        exit;
    }

    // Fresh planning session.
    $this->session->clear();

    $this->session->set(
        '_nyp_product_id',
        $productId
    );

    $this->session->set(
        '_nyp_brief_submitted',
        'no'
    );

    wp_safe_redirect(
        $this->workflow->getPlanningUrl()
    );

    exit;
}
    /*
    |--------------------------------------------------------------------------
    | Display Planning Brief
    |--------------------------------------------------------------------------
    */

    /**
     * Render the Planning Brief.
     *
     * @return void
     */
    public function show(): string
    {

        if (!$this->canAccessPlanning()) {

            return sprintf(
                '<div class="woocommerce-info nyp-planning-access-denied">%s</div>',
                esc_html__(
                    'Your partner account is currently awaiting approval by NYP. You will be able to submit planning requests after approval.',
                    'nyp'
                )
            );
        }
        return $this->renderer->render();
    }

    /*
    |--------------------------------------------------------------------------
    | Draft
    |--------------------------------------------------------------------------
    */

    /**
     * Save planning draft.
     *
     * @param array $data
     *
     * @return void
     */
    /**
     * Save planning draft.
     */
    public function saveDraft(): void
    {
        $this->submissionHandler->handle();
    }

    /*
    |--------------------------------------------------------------------------
    | Submit
    |--------------------------------------------------------------------------
    */

    /**
     * Submit the Planning Brief.
     */
    public function submit(): void
    {
        $this->submissionHandler->handle();

        wp_safe_redirect(
            $this->workflow->getCheckoutUrl()
        );

        exit;
    }
    /*
    |--------------------------------------------------------------------------
    | Reset
    |--------------------------------------------------------------------------
    */

    /**
     * Reset planning session.
     *
     * @return void
     */
    public function reset(): void
    {
        $this->workflow->reset();

        wp_safe_redirect(
            $this->workflow->getPlanningUrl()
        );

        exit;
    }

    public function handleGetActions(): void
    {
        $action = sanitize_key($_GET['nyp_action'] ?? '');
    
        switch ($action) {
    
            case 'start_planning':
                $this->start();
                break;
    
            case 'reset_planning':
                $this->reset();
                break;
        }
    }
    
    public function handlePostActions(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

     /*
    |--------------------------------------------------------------------------
    | Remove File
    |--------------------------------------------------------------------------
    */

    if (!empty($_POST['delete_file'])) {

        $this->submissionHandler->removeFile();

        return;
    }

    $action = sanitize_key(
        $_POST['nyp_action'] ?? ''
    );

    switch ($action) {

        
        case 'save_draft':

            $this->submissionHandler->saveDraft();

            break;

        case 'submit_brief':

            $this->submissionHandler->submit();

            wp_safe_redirect(
                $this->workflow->getCheckoutUrl()
            );

            exit;
    }
}

/**
 * Determine whether the current user can access the Planning Brief.
 *
 * Only approved partners may access the planning workflow.
 *
 * Administrators are allowed for administrative/testing purposes.
 *
 * @return bool
 */
private function canAccessPlanning(): bool
{
    if (!is_user_logged_in()) {
        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | Administrators
    |--------------------------------------------------------------------------
    */

    if (current_user_can('manage_options')) {
        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Partner Role
    |--------------------------------------------------------------------------
    */

    $user = wp_get_current_user();

    if (
        !in_array(
            'nyp_partner',
            (array) $user->roles,
            true
        )
    ) {
        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | Partner Approval Status
    |--------------------------------------------------------------------------
    */

    return get_user_meta(
        $user->ID,
        'nyp_partner_status',
        true
    ) === 'approved';
}



}