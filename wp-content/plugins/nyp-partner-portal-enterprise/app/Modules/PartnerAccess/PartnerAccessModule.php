<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once dirname(__FILE__) . '/RegistrationProcessor.php';
require_once dirname(__FILE__) . '/ProductVisibilityManager.php';

require_once dirname(__FILE__) . '/ApprovalManager.php';

class PartnerAccessModule
{
    public function register()
    {
        $roleManager = new RoleManager();

        add_action(
            'init',
            [$roleManager, 'register']
        );

        $registrationForm = new RegistrationForm();
        $registrationForm->register();
    
        $registrationProcessor = new RegistrationProcessor();
        $registrationProcessor->register();
        $approvalManager = new ApprovalManager();
        $approvalManager->register();
       
$productVisibilityManager = new ProductVisibilityManager();
$productVisibilityManager->register();
    }
}