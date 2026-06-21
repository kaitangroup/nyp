document.addEventListener(
    'DOMContentLoaded',
    function () {

        document
            .querySelectorAll(
                '.nyp-file-upload'
            )
            .forEach(function(input){

                FilePond.create(input, {
                    allowMultiple:
                        input.hasAttribute(
                            'multiple'
                        ),
                maxFiles: 10,

                maxFileSize: '50MB'

                });

            });

    }
);