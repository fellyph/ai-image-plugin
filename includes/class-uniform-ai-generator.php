<?php
/**
 * Main plugin class
 *
 * @package Uniform_AI_Generator
 * @category Class
 * @author Your Name
 * @license GPL-2.0-or-later
 * @link https://github.com/your-username/uniform-ai-generator
 */

declare(strict_types=1);

/**
 * Main plugin class
 */
class Uniform_AI_Generator {
    /**
     * Initialize the plugin
     *
     * @return void
     */
    public function init(): void {
        $this->load_dependencies();
        $this->setup_hooks();
    }

    /**
     * Load required dependencies
     *
     * @return void
     */
    private function load_dependencies(): void {
        // Load any additional dependencies here
    }

    /**
     * Setup WordPress hooks
     *
     * @return void
     */
    private function setup_hooks(): void {
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }

    /**
     * Add admin menu item
     *
     * @return void
     */
    public function add_admin_menu(): void {
        add_management_page(
            __('Uniform AI Generator', 'uniform-ai-generator'),
            __('Uniform AI Generator', 'uniform-ai-generator'),
            'manage_options',
            'uniform-ai-generator',
            array($this, 'render_admin_page')
        );
    }

    /**
     * Generate uniform image using AI
     *
     * @param string $logo_url URL of the uploaded logo
     * @param string $gender Gender of the model
     * @param string $country Country of the model
     * @return array Array containing the generated image URLs or error message
     */
    public function generate_uniform_image(string $logo_url, string $gender, string $country): array {
        try {
            $service = ai_services()->get_available_service(array('capabilities' => array(\Felix_Arntz\AI_Services\Services\API\Enums\AI_Capability::IMAGE_GENERATION)));
            
            $prompt = sprintf(
                'Create a photorealistic image of a %s model from %s wearing a white corporate uniform shirt with the following logo placed on the left chest area. The image should be professional and suitable for business use.',
                $gender,
                $country
            );

            $candidates = $service
                ->get_model(array(
                    'feature' => 'uniform-ai-generator',
                    'capabilities' => array(\Felix_Arntz\AI_Services\Services\API\Enums\AI_Capability::IMAGE_GENERATION),
                    'generationConfig' => \Felix_Arntz\AI_Services\Services\API\Types\Image_Generation_Config::from_array(array(
                        'candidateCount' => 3,
                    )),
                ))
                ->generate_image($prompt);

            $images = array();
            foreach ($candidates as $candidate) {
                foreach ($candidate->get_content()->get_parts() as $part) {
                    if ($part instanceof \Felix_Arntz\AI_Services\Services\API\Types\Parts\Inline_Data_Part) {
                        $images[] = $part->get_base64_data();
                    }
                }
            }

            return array(
                'success' => true,
                'images' => $images,
            );

        } catch (Exception $e) {
            return array(
                'success' => false,
                'error' => $e->getMessage(),
            );
        }
    }

    /**
     * Save generated image to media library
     *
     * @param string $image_data Base64 encoded image data
     * @return array Array containing the saved image URL or error message
     */
    public function save_to_media_library(string $image_data): array {
        try {
            $upload_dir = wp_upload_dir();
            $filename = 'uniform-' . time() . '.png';
            
            // Convert base64 to image and save
            $image_data = str_replace('data:image/png;base64,', '', $image_data);
            $image_data = str_replace(' ', '+', $image_data);
            $decoded_data = base64_decode($image_data);
            
            $file_path = $upload_dir['path'] . '/' . $filename;
            file_put_contents($file_path, $decoded_data);

            // Prepare attachment data
            $attachment = array(
                'post_mime_type' => 'image/png',
                'post_title' => sanitize_file_name($filename),
                'post_content' => '',
                'post_status' => 'inherit'
            );

            // Insert attachment
            $attach_id = wp_insert_attachment($attachment, $file_path);
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            
            // Generate metadata and update attachment
            $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
            wp_update_attachment_metadata($attach_id, $attach_data);

            return array(
                'success' => true,
                'url' => wp_get_attachment_url($attach_id),
            );

        } catch (Exception $e) {
            return array(
                'success' => false,
                'error' => $e->getMessage(),
            );
        }
    }

    /**
     * Render admin page
     *
     * @return void
     */
    public function render_admin_page(): void {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        require_once UNIFORM_AI_GENERATOR_PLUGIN_DIR . 'admin/templates/admin-page.php';
    }
} 