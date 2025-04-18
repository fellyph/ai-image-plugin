<?php
/**
 * Admin page template
 *
 * @package Uniform_AI_Generator
 */

defined('ABSPATH') || exit;
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="uniform-ai-generator-container">
        <div class="uniform-ai-generator-form">
            <h2><?php esc_html_e('Generate Uniform Images', 'uniform-ai-generator'); ?></h2>
            
            <form id="uniform-ai-generator-form">
                <div class="form-field">
                    <label for="logo"><?php esc_html_e('Upload Logo', 'uniform-ai-generator'); ?></label>
                    <div class="logo-upload-container">
                        <button type="button" class="button" id="upload-logo-button">
                            <?php esc_html_e('Choose Logo', 'uniform-ai-generator'); ?>
                        </button>
                        <input type="hidden" id="logo-url" name="logo_url" value="">
                        <div id="logo-preview"></div>
                    </div>
                </div>

                <div class="form-field">
                    <label for="gender"><?php esc_html_e('Model Gender', 'uniform-ai-generator'); ?></label>
                    <select id="gender" name="gender" required>
                        <option value=""><?php esc_html_e('Select Gender', 'uniform-ai-generator'); ?></option>
                        <option value="male"><?php esc_html_e('Male', 'uniform-ai-generator'); ?></option>
                        <option value="female"><?php esc_html_e('Female', 'uniform-ai-generator'); ?></option>
                    </select>
                </div>

                <div class="form-field">
                    <label for="country"><?php esc_html_e('Model Country', 'uniform-ai-generator'); ?></label>
                    <select id="country" name="country" required>
                        <option value=""><?php esc_html_e('Select Country', 'uniform-ai-generator'); ?></option>
                        <option value="United States"><?php esc_html_e('United States', 'uniform-ai-generator'); ?></option>
                        <option value="United Kingdom"><?php esc_html_e('United Kingdom', 'uniform-ai-generator'); ?></option>
                        <option value="Canada"><?php esc_html_e('Canada', 'uniform-ai-generator'); ?></option>
                        <option value="Australia"><?php esc_html_e('Australia', 'uniform-ai-generator'); ?></option>
                        <option value="Brazil"><?php esc_html_e('Brazil', 'uniform-ai-generator'); ?></option>
                        <!-- Add more countries as needed -->
                    </select>
                </div>

                <div class="form-field">
                    <button type="submit" class="button button-primary" id="generate-button">
                        <?php esc_html_e('Generate Images', 'uniform-ai-generator'); ?>
                    </button>
                </div>
            </form>
        </div>

        <div class="uniform-ai-generator-results" style="display: none;">
            <h2><?php esc_html_e('Generated Images', 'uniform-ai-generator'); ?></h2>
            <div id="generated-images" class="images-grid"></div>
        </div>

        <div class="uniform-ai-generator-loading" style="display: none;">
            <span class="spinner is-active"></span>
            <p><?php esc_html_e('Generating images...', 'uniform-ai-generator'); ?></p>
        </div>
    </div>
</div>

<script type="text/template" id="tmpl-generated-image">
    <div class="generated-image">
        <img src="{{ data.url }}" alt="<?php esc_attr_e('Generated Uniform', 'uniform-ai-generator'); ?>">
        <div class="image-actions">
            <button class="button save-image" data-image="{{ data.url }}">
                <?php esc_html_e('Save to Media Library', 'uniform-ai-generator'); ?>
            </button>
            <a href="{{ data.url }}" download class="button download-image">
                <?php esc_html_e('Download', 'uniform-ai-generator'); ?>
            </a>
        </div>
    </div>
</script> 