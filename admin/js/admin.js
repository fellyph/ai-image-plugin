jQuery(document).ready(function($) {
    'use strict';

    // Media uploader for logo
    $('#upload-logo-button').on('click', function(e) {
        e.preventDefault();

        var mediaUploader = wp.media({
            title: 'Select Logo',
            button: {
                text: 'Use this logo'
            },
            multiple: false
        });

        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#logo-url').val(attachment.url);
            $('#logo-preview').html('<img src="' + attachment.url + '" alt="Logo preview">');
        });

        mediaUploader.open();
    });

    // Form submission
    $('#uniform-ai-generator-form').on('submit', function(e) {
        e.preventDefault();

        var $results = $('.uniform-ai-generator-results');
        var $loading = $('.uniform-ai-generator-loading');
        var $generatedImages = $('#generated-images');

        // Show loading
        $loading.show();
        $results.hide();

        // Generate images
        $.ajax({
            url: uniformAiGenerator.ajaxUrl,
            type: 'POST',
            data: {
                action: 'generateUniformImage',
                nonce: uniformAiGenerator.nonce,
                logo_url: $('#logo-url').val(),
                gender: $('#gender').val(),
                outfit: $('#outfit').val()
            },
            beforeSend: function() {
                console.log('Sending request with data:', this.data);
            },
            success: function(response) {
                console.log('Response received:', response);
                if (response.success && response.data && response.data.images && Array.isArray(response.data.images)) {
                    $generatedImages.empty();
                    
                    response.data.images.forEach(function(imageData) {
                        const imageUrl = imageData.startsWith('data:image/') ? 
                            imageData : 
                            'data:image/png;base64,' + imageData;
                        
                        var template = wp.template('generated-image');
                        $generatedImages.append(template({
                            url: imageUrl
                        }));
                    });

                    $results.fadeIn();
                } else {
                    const errorMessage = response.data?.error || uniformAiGenerator.i18n.error;
                    console.error('Error in response:', response);
                    alert(errorMessage);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX Error Details:', {
                    status: jqXHR.status,
                    statusText: jqXHR.statusText,
                    responseText: jqXHR.responseText,
                    textStatus: textStatus,
                    errorThrown: errorThrown
                });
                
                let errorMessage = uniformAiGenerator.i18n.error;
                try {
                    const response = JSON.parse(jqXHR.responseText);
                    if (response.data && response.data.error_details) {
                        console.error('Detailed error:', response.data.error_details);
                        errorMessage = `Error: ${response.data.error_details.message}\nFile: ${response.data.error_details.file}\nLine: ${response.data.error_details.line}`;
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                }
                
                alert(errorMessage);
            },
            complete: function() {
                $loading.hide();
            }
        });
    });

    // Save image to media library
    $(document).on('click', '.save-image', function(e) {
        e.preventDefault();

        var $button = $(this);
        var imageData = $button.data('image');

        $button.prop('disabled', true).text(uniformAiGenerator.i18n.saving);

        $.ajax({
            url: uniformAiGenerator.ajaxUrl,
            type: 'POST',
            data: {
                action: 'save_uniform_image',
                nonce: uniformAiGenerator.nonce,
                image_data: imageData
            },
            success: function(response) {
                if (response.success && response.data.url) {
                    $button.text('Saved!');
                    $button.closest('.image-actions')
                           .find('.download-image')
                           .attr('href', response.data.url);
                } else {
                    alert(response.data || uniformAiGenerator.i18n.error);
                    $button.prop('disabled', false)
                           .text('Save to Media Library');
                }
            },
            error: function() {
                alert(uniformAiGenerator.i18n.error);
                $button.prop('disabled', false)
                       .text('Save to Media Library');
            }
        });
    });
}); 