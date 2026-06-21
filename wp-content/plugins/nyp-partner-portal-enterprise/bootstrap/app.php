<?php

if (!defined('ABSPATH')) {
    exit;
}
require_once __DIR__ . '/autoload.php';
require_once __DIR__ . '/container.php';
require_once __DIR__ . '/hooks.php';

/*
|--------------------------------------------------------------------------
| Partner Access Module
|--------------------------------------------------------------------------
*/



use NYP\Modules\PartnerAccess\PartnerAccessModule;
use NYP\Modules\Intake\IntakeModule;
use NYP\Services\PlanningIdGenerator;


 (new IntakeModule())->register();

$partnerAccessModule = new PartnerAccessModule();
$partnerAccessModule->register();
add_action('wp_enqueue_scripts', function () {

    wp_enqueue_style(
        'nyp-frontend',
        plugin_dir_url(dirname(__FILE__)) . 'assets/css/frontend.css',
        [],
        '1.0.0'
    );


    
            wp_enqueue_style(
                'filepond-css',
                'https://unpkg.com/filepond/dist/filepond.min.css',
                [],
                null
            );
    
            wp_enqueue_script(
                'filepond-js',
                'https://unpkg.com/filepond/dist/filepond.min.js',
                [],
                null,
                true
            );
    
            wp_enqueue_script(
                'nyp-intake',
                plugin_dir_url(
                    dirname(__FILE__)
                ) . 'assets/js/intake.js',
                ['filepond-js'],
                '1.0',
                true
            );
    
      
    
    }, 999);