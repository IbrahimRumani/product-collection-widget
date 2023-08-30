jQuery(document).ready(function($) {
    // Triggered when the product attribute changes
    $(document).on('change', 'select[data-setting="product_attributes"]', function() {
        var attribute_name = $(this).val();

        $.ajax({
            url: customWooElementor.ajaxurl,
            method: 'POST',
            data: {
                'action': 'get_attribute_terms',
                'attribute_name': attribute_name
            },
            success: function(response) {
                var terms = JSON.parse(response);
                var termsControl = $('select[data-setting="attribute_terms"]');

                // Clear previous options and append new ones
                termsControl.empty();
                $.each(terms, function(slug, name) {
                    termsControl.append('<option value="' + slug + '">' + name + '</option>');
                });

                // Refresh the control to show new values
                termsControl.trigger('change');
            }
        });
    });
});
