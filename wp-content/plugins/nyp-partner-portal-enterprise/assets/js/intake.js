document.addEventListener('DOMContentLoaded', function () {

    /*
    |--------------------------------------------------------------------------
    | Register FilePond Plugins
    |--------------------------------------------------------------------------
    */

    if (
        typeof FilePondPluginFileValidateSize !== 'undefined' &&
        typeof FilePondPluginFileValidateType !== 'undefined'
    ) {
        FilePond.registerPlugin(
            FilePondPluginFileValidateSize,
            FilePondPluginFileValidateType
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Initialize FilePond
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll('.nyp-file-upload')
        .forEach(function (input) {

            FilePond.create(input, {

                storeAsFile: true,

                allowMultiple: input.hasAttribute('multiple'),

                maxFiles: 10,

                maxFileSize: '50MB',
                labelMaxFileSizeExceeded: 'File is too large.',

                labelMaxFileSize: 'Maximum file size is {filesize}.'

            });

        });

        /*
    |--------------------------------------------------------------------------
    | Premium Room Concept
    | Hide Express Planning
    |--------------------------------------------------------------------------
    */

    const planningCategory = document.querySelector(
        'select[name="planning_category"]'
    );

    const expressOption = document.getElementById(
        'nyp-express-option'
    );

    const standardOption = document.querySelector(
        'input[name="service_speed"][value="standard"]'
    );

    const expressRadio = document.querySelector(
        'input[name="service_speed"][value="express"]'
    );

    function toggleExpressPlanning() {

        if (
            !planningCategory ||
            !expressOption ||
            !standardOption ||
            !expressRadio
        ) {
            return;
        }

      

        if (planningCategory.value === 'premium') {

            expressOption.style.display = 'none';

            if (expressRadio.checked) {
                standardOption.checked = true;
            }

        } else {

            expressOption.style.display = '';

        }

    }

    toggleExpressPlanning();

    planningCategory.addEventListener(
        'change',
        toggleExpressPlanning
    );

});