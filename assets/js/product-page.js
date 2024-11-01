jQuery(document).ready(function ($) {

    $(document).on('change', '.product input[name=quantity], .product select[name=quantity]', function () {
        var product = $(this).closest('.product');
        var productId = product.find('[name=add-to-cart]').val();
        var quantity = $(this).val();
        product.find('.sopp-shipping-costs').block({
            message: null,
            overlayCSS: {
                background: '#fff',
                opacity: 0.6
            }
        });
        $.post(SOPP_product_page_settings.ajaxurl,
                {action: 'sopp_get_shipping_info', sopp_product_id: productId, sopp_quantity: quantity},
        function (response) {
            product.find('.sopp-shipping-costs').unblock();
            product.find('.sopp-shipping-costs').html(response);
        });
    });
    $('.product input[name=quantity], .product select[name=quantity]').change();

});