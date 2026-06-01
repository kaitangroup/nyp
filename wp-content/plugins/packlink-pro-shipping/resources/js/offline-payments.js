jQuery(function($){
    $('form.checkout').on('change', 'input[name^="shipping_method"]', function(){
        $('body').trigger('update_checkout', [{ packlink_custom_flag: 1 }]);
    });
});
