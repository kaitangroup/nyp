<?php

namespace NYP\Modules\PartnerAccess;

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