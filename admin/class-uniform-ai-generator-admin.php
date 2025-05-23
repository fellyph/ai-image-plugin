<?php
/**
 * Admin class for Uniform AI Generator
 *
 * @category WordPress
 * @package  Uniform_AI_Generator
 * @author   Fellyph Cintra <fellyph.cintra@gmail.com>
 * @license  GPL-2.0+ https://www.gnu.org/licenses/gpl-2.0.txt
 * @link     https://example.com/uniform-ai-generator
 */

declare(strict_types=1);

/**
 * Admin class
 *
 * @category WordPress
 * @package  Uniform_AI_Generator
 * @author   Fellyph Cintra <fellyph.cintra@gmail.com>
 * @license  GPL-2.0+ https://www.gnu.org/licenses/gpl-2.0.txt
 * @link     https://example.com/uniform-ai-generator
 */
class Uniform_AI_Generator_Admin
{
    /**
     * Initialize the admin
     *
     * @return void
     */
    public function __construct()
    {
        add_action('admin_enqueue_scripts', array($this, 'enqueueAdminScripts'));
        add_action(
            'wp_ajax_generateUniformImage', 
            array($this, 'handleGenerateImage')
        );
        add_action('wp_ajax_save_uniform_image', array($this, 'handleSaveImage'));
    }

    /**
     * Enqueue admin scripts and styles
     *
     * @param string $hook The current admin page.
     *
     * @return void
     */
    public function enqueueAdminScripts(string $hook): void
    {
        if ('tools_page_uniform-ai-generator' !== $hook) {
            return;
        }

        wp_enqueue_media();
        
        wp_enqueue_style(
            'uniform-ai-generator-admin',
            UNIFORM_AI_GENERATOR_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            UNIFORM_AI_GENERATOR_VERSION
        );

        wp_enqueue_script(
            'uniform-ai-generator-admin',
            UNIFORM_AI_GENERATOR_PLUGIN_URL . 'admin/js/admin.js',
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
                'i18n' => array(
                    'generating' => __(
                        'Generating images...', 
                        'uniform-ai-generator'
                    ),
                    'saving' => __('Saving image...', 'uniform-ai-generator'),
                    'error' => __('An error occurred', 'uniform-ai-generator'),
                ),
            )
        );
    }

    /**
     * Handle AJAX request for generating images
     *
     * @return void
     */
    public function handleGenerateImage(): void
    {
        try {
            // Enable error reporting
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
            
            // Debug logging
            error_log('Starting image generation request');
            error_log('POST data: ' . print_r($_POST, true));
            error_log('Current user can manage options: ' . (current_user_can('manage_options') ? 'yes' : 'no'));
            error_log('AI Services function exists: ' . (function_exists('ai_services') ? 'yes' : 'no'));

            // Verify nonce first
            if (!isset($_POST['nonce'])) {
                throw new Exception('Nonce is missing');
            }
            if (!wp_verify_nonce($_POST['nonce'], 'uniform_ai_generator_nonce')) {
                throw new Exception('Security check failed');
            }

            // Check permissions
            if (!current_user_can('manage_options')) {
                throw new Exception('Insufficient permissions');
            }

            // Validate required fields
            $logo_url = sanitize_text_field($_POST['logo_url'] ?? '');
            $gender = sanitize_text_field($_POST['gender'] ?? '');
            $outfit = sanitize_text_field($_POST['outfit'] ?? '');

            error_log('Validated fields:');
            error_log('Logo URL: ' . $logo_url);
            error_log('Gender: ' . $gender);
            error_log('Outfit: ' . $outfit);

            if (empty($logo_url)) {
                throw new Exception('Logo URL is required');
            }
            if (empty($gender)) {
                throw new Exception('Gender is required');
            }
            if (empty($outfit)) {
                throw new Exception('Outfit is required');
            }

            // Check if AI Services is available
            if (!function_exists('ai_services')) {
                throw new Exception('AI Services plugin is not available');
            }

            error_log('Initializing AI Services');
            
            // Initialize generator
            try {
                $generator = new Uniform_AI_Generator();
                error_log('Generator instance created');
                
                $result = $generator->generateUniformImage($logo_url, $gender, $outfit);
                error_log('Generation result: ' . print_r($result, true));

                if (!$result['success']) {
                    throw new Exception($result['error'] ?? 'Unknown error occurred');
                }

                wp_send_json_success($result);
            } catch (Exception $e) {
                error_log('Error in generator: ' . $e->getMessage());
                error_log('Error trace: ' . $e->getTraceAsString());
                throw $e;
            }

        } catch (Exception $e) {
            error_log('Uniform AI Generator Error: ' . $e->getMessage());
            error_log('Error trace: ' . $e->getTraceAsString());
            
            // Get the WordPress debug log path
            $debug_log = WP_CONTENT_DIR . '/debug.log';
            if (file_exists($debug_log)) {
                error_log('Last 10 lines of debug.log:');
                $log_content = array_slice(file($debug_log), -10);
                foreach ($log_content as $line) {
                    error_log(trim($line));
                }
            }
            
            wp_send_json_error(
                array(
                    'error' => $e->getMessage(),
                    'error_details' => array(
                        'message' => $e->getMessage(),
                        'code' => $e->getCode(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString()
                    )
                )
            );
        }
    }

    /**
     * Handle AJAX request for saving images
     *
     * @return void
     */
    public function handleSaveImage(): void
    {
        check_ajax_referer('uniform_ai_generator_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(
                __(
                    'Insufficient permissions',
                    'uniform-ai-generator'
                )
            );
        }

        $image_data = sanitize_text_field($_POST['image_data'] ?? '');

        if (empty($image_data)) {
            wp_send_json_error(__('No image data provided', 'uniform-ai-generator'));
        }

        $generator = new Uniform_AI_Generator();
        $result = $generator->save_to_media_library($image_data);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['error']);
        }
    }
} 