<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/container.php';
require_once __DIR__ . '/hooks.php';

/*
|--------------------------------------------------------------------------
| Partner Access Module
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__) . '/app/Modules/PartnerAccess/RoleManager.php';
require_once dirname(__DIR__) . '/app/Modules/PartnerAccess/RegistrationForm.php';
require_once dirname(__DIR__) . '/app/Modules/PartnerAccess/PartnerAccessModule.php';

$partnerAccessModule = new PartnerAccessModule();
$partnerAccessModule->register();
add_action('wp_enqueue_scripts', function () {

    wp_enqueue_style(
        'nyp-frontend',
        plugin_dir_url(dirname(__FILE__)) . 'assets/css/frontend.css',
        [],
        '1.0.0'
    );
    
    }, 999);