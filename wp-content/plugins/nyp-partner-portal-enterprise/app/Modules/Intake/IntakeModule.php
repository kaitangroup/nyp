<?php
namespace NYP\Modules\Intake;

use NYP\Controllers\Frontend\PlanningController;
use NYP\Services\PlanningSessionStorage;
use NYP\Services\PlanningWorkflowService;
use NYP\Helpers\ProductHelper;
use NYP\Services\PlanningOrderImporter;
use WC_Product;
use WC_Order;
use NYP\Modules\Checkout\PaymentGatewayManager;
use NYP\Modules\Intake\Emails\EmailManager;

class IntakeModule {
    public function register(): void {

        $session = new PlanningSessionStorage();

        $workflow = new PlanningWorkflowService(
            $session
        );

    
        $renderer = new IntakeFormRenderer();
        $submission = new PlanningSubmissionHandler(
            $session,
            $workflow
        );
        
    
        $planningController = new PlanningController(
            $session,
            $workflow,
            $renderer,
            $submission
        );

        $paymentGatewayManager = new PaymentGatewayManager(
            $session
        );
        
        $paymentGatewayManager->register();

        

        add_filter(
            'woocommerce_loop_add_to_cart_link',
            [$this, 'planningLoopButton'],
            10,
            3
        );

        add_action(
            'woocommerce_init',
            [$this, 'replaceSingleProductButton']
        );
        

    
        add_action(
            'template_redirect',
            [$planningController, 'handleGetActions']
        );

        add_action(
            'template_redirect',
            [$planningController, 'handlePostActions'],
            2
        );
    
        add_shortcode(
            'nyp_planning_brief',
            [$planningController, 'show']
        );

        add_action(
            'woocommerce_new_order',
            [$this, 'importPlanningToOrder'],
            20,
            1
        );

        /**
 * Hide the Overnight Upgrade product from the shop archive.
 */
add_action(
    'pre_get_posts',
    function (\WP_Query $query) {

        if (
            is_admin() ||
            !$query->is_main_query()
        ) {
            return;
        }

        if (
            !is_shop() &&
            !is_product_category() &&
            !is_product_tag()
        ) {
            return;
        }

        $query->set(
            'post__not_in',
            array_merge(
                (array) $query->get('post__not_in'),
                [30]
            )
        );
    }
);

       

        (new OrderStatusManager())->register();
     //   (new IntakeFormRenderer())->register();
     
        (new IntakeOrderStorage())->register();
     //  (new IntakeUploadManager())->register();
        (new IntakeAdminView())->register();
        (new OrderWorkflowManager())->register();
        (new IntakeAccountActions())->register();
        (new EmailManager())->register();
    }

    public function replaceSingleProductButton(): void
{
    remove_action(
        'woocommerce_single_product_summary',
        'woocommerce_template_single_add_to_cart',
        30
    );

    add_action(
        'woocommerce_single_product_summary',
        [$this, 'planningSingleButton'],
        30
    );
}

    public function planningSingleButton(): void
{
    global $product;

    if (!$product instanceof WC_Product) {
        return;
    }

    echo $this->planningLoopButton(
        '',
        $product,
        []
    );
}


    public function importPlanningToOrder(
        int $orderId
    ): void {
    
        $importer = new PlanningOrderImporter();
    
        if (!$importer->hasPlanning()) {
            return;
        }
    
        $order = wc_get_order($orderId);
    
        if (!$order instanceof \WC_Order) {
            return;
        }
    
        $importer->import($order);
    
        error_log(
            'Planning imported into Order #' . $orderId
        );
    }

    /**
 * Determine whether the current user can start planning.
 *
 * @return bool
 */
private function canStartPlanning(): bool
{
    if (!is_user_logged_in()) {
        return false;
    }

    if (current_user_can('manage_options')) {
        return true;
    }

    $user = wp_get_current_user();

    return in_array(
        'nyp_partner',
        (array) $user->roles,
        true
    );
}

public function planningLoopButton(
    string $html,
    WC_Product $product,
    array $args
): string {

    /*
    |--------------------------------------------------------------------------
    | Planning Products Only
    |--------------------------------------------------------------------------
    */

    if (!ProductHelper::isPlanningProduct($product)) {
        return $html;
    }

    /*
    |--------------------------------------------------------------------------
    | Partner Access
    |--------------------------------------------------------------------------
    */

    if (!$this->canStartPlanning()) {
        return $html;
    }

    /*
    |--------------------------------------------------------------------------
    | Start Planning Button
    |--------------------------------------------------------------------------
    */

    $url = add_query_arg(
        [
            'nyp_action' => 'start_planning',
            'product_id' => $product->get_id(),
        ],
        (new PlanningWorkflowService())->getPlanningUrl()
    );

    return sprintf(
        '<a href="%1$s" class="button">%2$s</a>',
        esc_url($url),
        esc_html__(
            'Start Planning',
            'nyp-partner-portal-enterprise'
        )
    );
}

    

    
}
