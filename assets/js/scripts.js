jQuery(function($) {
    // Handle view details click.
    $('.view-details').on('click', function(e) {
        e.preventDefault();
        var moduleName = $(this).data('module');

        $.post(moduleTracker.ajax_url, {
            action: 'get_module_details',
            security: moduleTracker.nonce,
            module: moduleName
        }, function(response) {
            if (response.success) {
                var content = response.data.title;
                if (response.data.pages.length > 0) {
                    content += '<ul>';
                    response.data.pages.forEach(function(page) {
                        content += '<li><a href="' + page.link + '" target="_blank">' + page.title + '</a></li>';
                    });
                    content += '</ul>';
                } else {
                    content += '<p><?php echo esc_js( __( "No pages found.", "module-usage-tracker-wp-bakery" ) ); ?></p>';
                }
                $('#modal-content').html(content);
                $('#module-details-modal').addClass('is-visible');
            } else {
                // Display error within the modal.
                var errorContent = '<p>' + response.data + '</p>';
                $('#modal-content').html(errorContent);
                $('#module-details-modal').addClass('is-visible');
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            // Handle AJAX errors.
            var errorContent = '<p><?php echo esc_js( __( "An unexpected error occurred. Please try again later.", "module-usage-tracker-wp-bakery" ) ); ?></p>';
            $('#modal-content').html(errorContent);
            $('#module-details-modal').addClass('is-visible');
        });
    });

    // Close modal on overlay or close button click.
    $('#module-details-modal').on('click', function(event) {
        if ($(event.target).is('#module-details-modal') || $(event.target).is('#modal-close')) {
            $(this).removeClass('is-visible');
        }
    });
});
