jQuery(document).ready(function($) {
    'use strict';

    // Media uploader
    var mediaUploader;
    
    $('#upload-logo-button').on('click', function(e) {
        e.preventDefault();

        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        mediaUploader = wp.media({
            title: 'Select Logo',
            button: {
                text: 'Use this logo'
            },
            multiple: false
        });

        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#logo-url').val(attachment.url);
            $('#logo-preview').html('<img src="' + attachment.url + '" style="max-width: 150px;">');
        });

        mediaUploader.open();
    });

    // Form submission
    $('#uniform-ai-generator-form').on('submit', function(e) {
        e.preventDefault();

        var $form = $(this);
        var $results = $('.uniform-ai-generator-results');
        var $loading = $('.uniform-ai-generator-loading');
        var $generatedImages = $('#generated-images');

        if (!$('#logo-url').val()) {
            alert('Please upload a logo first.');
            return;
        }

        $results.hide();
        $loading.show();
        
        var data = {
            action: 'generate_uniform_image',
            nonce: uniformAiGenerator.nonce,
            logo_url: $('#logo-url').val(),
            gender: $('#gender').val(),
            country: $('#country').val()
        };

        $.post(uniformAiGenerator.ajaxUrl, data, function(response) {
            $loading.hide();

            if (response.success && response.data.images) {
                $generatedImages.empty();
                
                response.data.images.forEach(function(imageData) {
                    var template = wp.template('generated-image');
                    $generatedImages.append(template({ url: imageData }));
                });

                $results.show();
            } else {
                alert(response.data || 'Error generating images. Please try again.');
            }
        }).fail(function() {
            $loading.hide();
            alert('Error generating images. Please try again.');
        });
    });

    // Save image to media library
    $(document).on('click', '.save-image', function() {
        var $button = $(this);
        var imageData = $button.data('image');

        $button.prop('disabled', true).text('Saving...');

        var data = {
            action: 'save_uniform_image',
            nonce: uniformAiGenerator.nonce,
            image_data: imageData
        };

        $.post(uniformAiGenerator.ajaxUrl, data, function(response) {
            $button.prop('disabled', false).text('Save to Media Library');

            if (response.success && response.data.url) {
                alert('Image saved successfully to media library!');
            } else {
                alert(response.data || 'Error saving image. Please try again.');
            }
        }).fail(function() {
            $button.prop('disabled', false).text('Save to Media Library');
            alert('Error saving image. Please try again.');
        });
    });
}); 