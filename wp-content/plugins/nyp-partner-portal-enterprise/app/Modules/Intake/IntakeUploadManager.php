<?php

namespace NYP\Modules\Intake;

if (!defined('ABSPATH')) {
    exit;
}

class IntakeUploadManager
{
    public function register(): void
    {
        add_action(
            'template_redirect',
            [$this, 'handleUploads']
        );
    }

    public function handleUploads(): void
    {

        if (
            isset($_POST['delete_file'])
        ) {
        
            $this->removeFile(
                absint($_POST['order_id']),
                sanitize_text_field(
                    wp_unslash(
                        $_POST['delete_file']
                    )
                )
            );
        
            $returnUrl = esc_url_raw(
                wp_unslash(
                    $_POST['return_url'] ?? ''
                )
            );
            
            if (!$returnUrl) {
            
                $returnUrl = home_url();
            }
            
            wp_safe_redirect(
                $returnUrl
            );
            
            exit;
        }
       
      
        if (
            !isset($_POST['nyp_nonce'])
        ) {
            return;
        }

        

        if (
            !wp_verify_nonce(
                sanitize_text_field(
                    wp_unslash($_POST['nyp_nonce'])
                ),
                'nyp_save_project_info'
            )
        ) {
            return;
        }

     

        $orderId = absint(
            $_POST['order_id'] ?? 0
        );

        if (!$orderId) {
            return;
        }

        $this->validateUploads();

        $uploadDir = wp_upload_dir();

        $targetDir =
            $uploadDir['basedir'] .
            '/nyp-intake/order-' .
            $orderId;

            error_log('BEFORE CREATE DIRECTORY');

        if (!file_exists($targetDir)) {

            wp_mkdir_p($targetDir);
        }

        error_log('BEFORE FLOOR PLAN');

        $this->handleSingleUpload(
            'floor_plan',
            $targetDir,
            $orderId,
            '_nyp_floor_plan'
        );

        error_log('After FLOOR PLAN');

        $this->handleMultiUpload(
            'kitchen_photos',
            $targetDir,
            $orderId,
            '_nyp_kitchen_photos'
        );

        $this->handleMultiUpload(
            'inspiration_images',
            $targetDir,
            $orderId,
            '_nyp_inspiration_images'
        );

        $this->handleSingleUpload(
            'planning_export',
            $targetDir,
            $orderId,
            '_nyp_planning_export'
        );

        $this->handleMultiUpload(
            'technical_documents',
            $targetDir,
            $orderId,
            '_nyp_technical_documents'
        );

        $this->handleMultiUpload(
            'additional_files',
            $targetDir,
            $orderId,
            '_nyp_additional_files'
        );
    }

    private function handleSingleUpload(
        string $field,
        string $targetDir,
        int $orderId,
        string $metaKey
    ): void {
    
        if (
            empty($_FILES[$field]['name'])
        ) {
            return;
        }
    
        $fileName = sanitize_file_name(
            $_FILES[$field]['name']
        );
    
        $destination =
            $targetDir .
            '/' .
            $fileName;
    
        $result = move_uploaded_file(
            $_FILES[$field]['tmp_name'],
            $destination
        );
    
        if (!$result) {
            return;
        }
    
        $relativePath =
        'nyp-intake/order-' .
        $orderId .
        '/' .
        $fileName;
    
    $this->saveOrderMeta(
        $orderId,
        $metaKey,
        $relativePath
    );
    }

    private function handleMultiUpload(
        string $field,
        string $targetDir,
        int $orderId,
        string $metaKey
    ): void {
    
        if (
            empty($_FILES[$field]['name'][0])
        ) {
            return;
        }
    
        $files = [];
        $files = array_values(
            array_filter($files)
        );
    
        foreach (
            $_FILES[$field]['name']
            as $index => $name
        ) {
    
            if (empty($name)) {
                continue;
            }
    
            $fileName = sanitize_file_name(
                $name
            );
    
            $destination =
                $targetDir .
                '/' .
                $fileName;
    
            $result = move_uploaded_file(
                $_FILES[$field]['tmp_name'][$index],
                $destination
            );

            $relativePath =
            'nyp-intake/order-' .
            $orderId .
            '/' .
            $fileName;
    
            if ($result) {
                $files[] = $relativePath;
            }
        }

        if (empty($files)) {

            $order = wc_get_order(
                $orderId
            );
        
            $order->delete_meta_data(
                $metaKey
            );
        
            $order->save();
        
            return;
        }
    
        $this->saveOrderMeta(
            $orderId,
            $metaKey,
            $files
        );
    }

    private function saveOrderMeta(
        int $orderId,
        string $metaKey,
        $value
    ): void {
    
        $order = wc_get_order(
            $orderId
        );
    
        if (!$order) {
            return;
        }
    
        $order->update_meta_data(
            $metaKey,
            $value
        );
    
        $order->save();
    }
    private function removeFile(
        int $orderId,
        string $relativePath
    ): void {
    
        $order = wc_get_order(
            $orderId
        );
    
        if (!$order) {
            return;
        }
    
        $uploadDir = wp_upload_dir();
    
        $fullPath =
            $uploadDir['basedir']
            . '/'
            . ltrim(
                $relativePath,
                '/'
            );
    
        if (
            file_exists($fullPath)
        ) {
            unlink($fullPath);
        }
    
        $this->removeMetaReference(
            $order,
            $relativePath
        );
    }

    private function removeMetaReference(
        \WC_Order $order,
        string $relativePath
    ): void {
    
        $metaKeys = [
    
            '_nyp_floor_plan',
    
            '_nyp_planning_export',
    
            '_nyp_kitchen_photos',
    
            '_nyp_inspiration_images',
    
            '_nyp_technical_documents',
    
            '_nyp_additional_files',
    
        ];
    
        foreach (
            $metaKeys as $metaKey
        ) {
    
            $value =
                $order->get_meta(
                    $metaKey
                );
    
            /*
            Single file
            */
    
            if (
                is_string($value)
                &&
                $value === $relativePath
            ) {
    
                $order->delete_meta_data(
                    $metaKey
                );
    
                continue;
            }
    
            /*
            Multiple files
            */
    
            if (
                is_array($value)
            ) {
    
                $value = array_values(
                    array_filter(
                        $value,
                        function ($file) use (
                            $relativePath
                        ) {
                            return
                                $file !==
                                $relativePath;
                        }
                    )
                );
    
                $order->update_meta_data(
                    $metaKey,
                    $value
                );
            }
        }
    
        $order->save();
    }

    private function validateUploads(): void
{
    $maxFileSize = 50 * 1024 * 1024; // 50 MB

    $maxTotalSize = 250 * 1024 * 1024; // 250 MB

    $maxFileCount = 10;

    $totalSize = 0;

    $totalFiles = 0;

    foreach ($_FILES as $file) {

        /*
        Single file
        */

        if (!is_array($file['name'])) {

            if (
                !empty($file['name'])
                &&
                $file['error'] === 0
            ) {

                $totalFiles++;

                $totalSize += $file['size'];

                if (
                    $file['size']
                    > $maxFileSize
                ) {

                    wp_die(
                        'A file exceeds the maximum allowed size of 50 MB.'
                    );
                }
            }

            continue;
        }

        /*
        Multiple files
        */

        foreach (
            $file['name']
            as $index => $name
        ) {

            if (
                empty($name)
            ) {
                continue;
            }

            if (
                $file['error'][$index]
                !== 0
            ) {
                continue;
            }

            $totalFiles++;

            $totalSize +=
                $file['size'][$index];

            if (
                $file['size'][$index]
                > $maxFileSize
            ) {

                wp_die(
                    'A file exceeds the maximum allowed size of 50 MB.'
                );
            }
        }
    }

    if (
        $totalFiles > $maxFileCount
    ) {

        wp_die(
            'Maximum 10 files are allowed per planning brief.'
        );
    }

    if (
        $totalSize > $maxTotalSize
    ) {

        wp_die(
            'Total upload size exceeds 250 MB.'
        );
    }
}
}