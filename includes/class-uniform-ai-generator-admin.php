<?php
/**
 * Admin functionality class
 *
 * @category Class
 * @package  Uniform_AI_Generator
 * @author   Your Name <your.email@example.com>
 * @license  GPL-2.0-or-later https://www.gnu.org/licenses/gpl-2.0.html
 * @link     https://github.com/your-username/uniform-ai-generator
 */

declare(strict_types=1);

/**
 * Admin class
 */
class Uniform_AI_Generator_Admin {
    /**
     * Initialize the admin functionality
     *
     * @return void
     */
    public function init(): void {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_ajax_generate_uniform_image', array($this, 'handle_generate_uniform_image'));
        add_action('wp_ajax_save_uniform_image', array($this, 'handle_save_uniform_image'));
    }

    /**
     * Enqueue admin scripts and styles
     *
     * @param string $hook The current admin page.
     * @return void
     */
    public function enqueue_admin_assets(string $hook): void {
        if ('tools_page_uniform-ai-generator' !== $hook) {
            return;
        }

        wp_enqueue_media();
        
        wp_enqueue_style(
            'uniform-ai-generator-admin',
            UNIFORM_AI_GENERATOR_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            UNIFORM_AI_GENERATOR_VERSION
        );

        wp_enqueue_script(
            'uniform-ai-generator-admin',
            UNIFORM_AI_GENERATOR_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-util'),
            UNIFORM_AI_GENERATOR_VERSION,
            true
        );

        wp_localize_script(
            'uniform-ai-generator-admin',
            'uniformAiGenerator',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('uniform_ai_generator_nonce'),
            )
        );
    }

    /**
     * Handle AJAX request to generate uniform image
     *
     * @return void
     */
    public function handle_generate_uniform_image(): void {
        check_ajax_referer('uniform_ai_generator_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized access');
        }

        $logo_url = sanitize_text_field($_POST['logo_url'] ?? '');
        $gender = sanitize_text_field($_POST['gender'] ?? '');
        $country = sanitize_text_field($_POST['country'] ?? '');

        if (empty($logo_url) || empty($gender) || empty($country)) {
            wp_send_json_error('Missing required fields');
        }

        $plugin = new Uniform_AI_Generator();
        $result = $plugin->generate_uniform_image($logo_url, $gender, $country);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['error']);
        }
    }

    /**
     * Handle AJAX request to save uniform image
     *
     * @return void
     */
    public function handle_save_uniform_image(): void {
        check_ajax_referer('uniform_ai_generator_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized access');
        }

        $image_data = sanitize_text_field($_POST['image_data'] ?? '');

        if (empty($image_data)) {
            wp_send_json_error('Missing image data');
        }

        $plugin = new Uniform_AI_Generator();
        $result = $plugin->save_to_media_library($image_data);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['error']);
        }
    }
} 