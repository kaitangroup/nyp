<?php
namespace NYP\Modules\Intake;

class IntakeModule {
    public function register(): void {
        (new OrderStatusManager())->register();
        (new IntakeFormRenderer())->register();
        (new IntakeValidator())->register();
        (new IntakeOrderStorage())->register();
        (new IntakeUploadManager())->register();
        (new IntakeAdminView())->register();
        (new OrderWorkflowManager())->register();
        (new IntakeAccountActions())->register();
    }
}
