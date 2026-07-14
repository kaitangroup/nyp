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
    $productId = absint($_GET['product_id'] ?? 0);

    if (!$productId) {
        wp_safe_redirect(home_url());

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



}