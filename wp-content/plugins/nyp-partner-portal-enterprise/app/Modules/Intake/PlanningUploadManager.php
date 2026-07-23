<?php

declare(strict_types=1);

namespace NYP\Modules\Intake;

defined('ABSPATH') || exit;

use NYP\Services\PlanningSessionStorage;
use NYP\Services\PlanningWorkflowService;

/*
|--------------------------------------------------------------------------
| Planning Upload Manager
|--------------------------------------------------------------------------
|
| Handles Planning Brief uploads before checkout.
|
| Responsibilities:
|
| - Validate uploads
| - Store uploaded files
| - Return relative paths
| - Delete uploaded files
|
| This class is independent from WooCommerce orders.
|
*/

class PlanningUploadManager
{
    /**
     * Upload directory.
     */
    protected string $targetDirectory;

    /**
     * Relative directory.
     */
    protected string $relativeDirectory;

    protected PlanningWorkflowService $workflow;
    /**
     * Constructor.
     *
     * @param string $sessionId
     */
    public function __construct(
        PlanningSessionStorage $session
    ) {

        $uploadDir = wp_upload_dir();
        $sessionId = $session->getSessionId();
        $this->targetDirectory =
            trailingslashit(
                $uploadDir['basedir']
            )
            . 'nyp-intake/session/'
            . $sessionId;

        $this->relativeDirectory =
            'nyp-intake/session/'
            . $sessionId;

        if (!file_exists($this->targetDirectory)) {

            wp_mkdir_p(
                $this->targetDirectory
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Process Uploads
    |--------------------------------------------------------------------------
    */

    /**
     * Process all Planning Brief uploads.
     *
     * @return array
     */
    public function process(): array
    {
        $this->validate();

        return [

            '_nyp_floor_plan' => $this->processSingle(
                'floor_plan'
            ),

            '_nyp_kitchen_photos' => $this->processMultiple(
                'kitchen_photos'
            ),

            '_nyp_inspiration_images' => $this->processMultiple(
                'inspiration_images'
            ),

            '_nyp_planning_export' => $this->processSingle(
                'planning_export'
            ),

            '_nyp_technical_documents' => $this->processMultiple(
                'technical_documents'
            ),

            '_nyp_additional_files' => $this->processMultiple(
                'additional_files'
            ),

        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Single Upload
    |--------------------------------------------------------------------------
    */

    protected function processSingle(
        string $field
    ): ?string {

        if (
            empty($_FILES[$field]['name'])
            ||
            $_FILES[$field]['error'] !== UPLOAD_ERR_OK
        ) {
            return null;
        }

        $fileName = sanitize_file_name(
            $_FILES[$field]['name']
        );

        $destination =
            trailingslashit(
                $this->targetDirectory
            )
            . $fileName;

        if (
            !move_uploaded_file(
                $_FILES[$field]['tmp_name'],
                $destination
            )
        ) {
            return null;
        }

        return
            $this->relativeDirectory
            . '/'
            . $fileName;
    }

    /*
    |--------------------------------------------------------------------------
    | Multiple Upload
    |--------------------------------------------------------------------------
    */

    protected function processMultiple(
        string $field
    ): array {

        $files = [];

        if (
            empty($_FILES[$field]['name'][0])
        ) {
            return [];
        }

        foreach (
            $_FILES[$field]['name']
            as $index => $name
        ) {

            if (
                empty($name)
            ) {
                continue;
            }

            if (
                $_FILES[$field]['error'][$index]
                !== UPLOAD_ERR_OK
            ) {
                continue;
            }

            $fileName = sanitize_file_name(
                $name
            );

            $destination =
                trailingslashit(
                    $this->targetDirectory
                )
                . $fileName;

            if (
                !move_uploaded_file(
                    $_FILES[$field]['tmp_name'][$index],
                    $destination
                )
            ) {
                continue;
            }

            $files[] =
                $this->relativeDirectory
                . '/'
                . $fileName;
        }

        return array_values(
            array_unique($files)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Remove Uploaded File
    |--------------------------------------------------------------------------
    */

    public function remove(
        string $relativePath
    ): void {

        $uploadDir = wp_upload_dir();

        $absolutePath =
            trailingslashit(
                $uploadDir['basedir']
            )
            . ltrim(
                $relativePath,
                '/'
            );

        if (
            file_exists($absolutePath)
            &&
            is_file($absolutePath)
        ) {

            @unlink(
                $absolutePath
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Uploads
    |--------------------------------------------------------------------------
    */

    protected function validationError(string $message): void
{

     error_log('Planning Upload Validation Error: ' . $message);
    wc_add_notice(
        esc_html__($message, 'nyp'),
        'error'
    );

    wp_safe_redirect(
        $this->workflow->getPlanningUrl()
    );
    exit;
}

protected function validate(): void
{

    $maxFileSize  = 50 * 1024 * 1024;   // 50 MB
    $maxTotalSize = 250 * 1024 * 1024;  // 250 MB
    $maxFileCount = 10;
    

    $allowedExtensions = [
        'pdf',
        'jpg',
        'jpeg',
        'png',
        'webp',
        'dwg',
        'zip',
        'doc',
        'docx',
    ];

    $totalFiles = 0;
    $totalSize  = 0;

    foreach ($_FILES as $file) {

        /*
        |--------------------------------------------------------------------------
        | Single Upload
        |--------------------------------------------------------------------------
        */
        if (!is_array($file['name'])) {

            if ($file['error'] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            switch ($file['error']) {

                case UPLOAD_ERR_OK:
                    break;

                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:

                    $this->validationError(
                        'The uploaded file exceeds the maximum allowed size of 50 MB.'
                    );

                default:

                    $this->validationError(
                        'The file could not be uploaded. Please try again.'
                    );
            }

            $extension = strtolower(
                pathinfo($file['name'], PATHINFO_EXTENSION)
            );

            if (!in_array($extension, $allowedExtensions, true)) {

                $this->validationError(
                    'One or more uploaded files use an unsupported file format.'
                );
            }

            if ($file['size'] > $maxFileSize) {

                $this->validationError(
                    'Each uploaded file must not exceed 50 MB.'
                );
            }

            $totalFiles++;
            $totalSize += $file['size'];

            continue;
        }

        /*
        |--------------------------------------------------------------------------
        | Multiple Uploads
        |--------------------------------------------------------------------------
        */

        foreach ($file['name'] as $index => $name) {

            if ($file['error'][$index] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            switch ($file['error'][$index]) {

                case UPLOAD_ERR_OK:
                    break;

                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:

                    $this->validationError(
                        'The uploaded file exceeds the maximum allowed size of 50 MB.'
                    );

                default:

                    $this->validationError(
                        'The file could not be uploaded. Please try again.'
                    );
            }

            $extension = strtolower(
                pathinfo($name, PATHINFO_EXTENSION)
            );

            if (!in_array($extension, $allowedExtensions, true)) {

                $this->validationError(
                    'One or more uploaded files use an unsupported file format.'
                );
            }

            if ($file['size'][$index] > $maxFileSize) {

                $this->validationError(
                    'Each uploaded file must not exceed 50 MB.'
                );
            }

            $totalFiles++;
            $totalSize += $file['size'][$index];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Maximum File Count
    |--------------------------------------------------------------------------
    */

    if ($totalFiles > $maxFileCount) {

        $this->validationError(
            'A maximum of 10 files can be uploaded.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Maximum Combined Upload Size
    |--------------------------------------------------------------------------
    */

    if ($totalSize > $maxTotalSize) {

        $this->validationError(
            'The total upload size exceeds the allowed limit of 250 MB.'
        );
    }
}
}