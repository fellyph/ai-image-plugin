<?php
/**
 * Main plugin class for Uniform AI Generator
 *
 * @category WordPress
 * @package  Uniform_AI_Generator
 * @author   WordPress Developer <dev@example.com>
 * @license  GPL-2.0+ https://www.gnu.org/licenses/gpl-2.0.txt
 * @link     https://example.com/uniform-ai-generator
 */

declare(strict_types=1);
use Felix_Arntz\AI_Services\Services\API\Enums\AI_Capability;
use Felix_Arntz\AI_Services\Services\API\Enums\Content_Role;
use Felix_Arntz\AI_Services\Services\API\Types\Content;
use Felix_Arntz\AI_Services\Services\API\Types\Parts;
use Felix_Arntz\AI_Services\Services\API\Types\Parts\Inline_Data_Part;
use Felix_Arntz\AI_Services\Services\API\Types\Text_Generation_Config;

/**
 * Main plugin class
 */
class Uniform_AI_Generator
{
    /**
     * Admin instance
     *
     * @var Uniform_AI_Generator_Admin
     */
    private $_admin;

    /**
     * Initialize the plugin
     *
     * @return void
     */
    public function init(): void
    {
        $this->_loadDependencies();
        $this->_setupHooks();
    }

    /**
     * Load required dependencies
     *
     * @return void
     */
    private function _loadDependencies(): void
    {
        include_once plugin_dir_path(dirname(__FILE__)) . 
            'admin/class-uniform-ai-generator-admin.php';
        $this->_admin = new Uniform_AI_Generator_Admin();
    }

    /**
     * Setup plugin hooks
     *
     * @return void
     */
    private function _setupHooks(): void
    {
        add_action('admin_menu', array($this, 'addAdminMenu'));
    }

    /**
     * Add admin menu
     *
     * @return void
     */
    public function addAdminMenu(): void
    {
        add_management_page(
            __('Uniform AI Generator', 'uniform-ai-generator'),
            __('Uniform AI Generator', 'uniform-ai-generator'),
            'manage_options',
            'uniform-ai-generator',
            array($this, 'renderAdminPage')
        );
    }

    /**
     * Generate uniform image using AI
     *
     * @param string $logo_url URL of the uploaded logo
     * @param string $gender   Gender of the model
     * @param string $outfit   Style of the uniform
     *
     * @return array Array containing the generated image URLs or error message
     */
    public function generateUniformImage(
        string $logo_url, 
        string $gender, 
        string $outfit
    ): array {
        try {
            if (!function_exists('ai_services')) {
                throw new Exception('AI Services plugin is not available');
            }

            // Verify the logo file exists and is accessible
            $logo_headers = get_headers($logo_url, true);
            if (!$logo_headers || strpos($logo_headers[0], '200') === false) {
                throw new Exception('Unable to access logo file: ' . $logo_url);
            }

            // Wait for WordPress to be fully loaded
            if (!did_action('admin_init')) {
                throw new Exception('WordPress is not fully initialized yet');
            }

            try {
                $service = ai_services()->get_available_service(
                    array('capabilities' => array(AI_Capability::IMAGE_GENERATION))
                );
            } catch (Exception $e) {
                throw new Exception(
                    'Failed to initialize AI service: ' . $e->getMessage()
                );
            }

            if (!$service) {
                throw new Exception(
                    'No available AI service with image generation capability'
                );
            }
            
            $prompt = sprintf(
                'Create a photorealistic image of a %s model wearing a %s ' .
                'with the following logo placed on the left chest area. ' .
                'The image should be professional and suitable for business use.',
                $gender,
                $outfit
            );

            try {
                $parts = new Parts();
                $parts->add_text_part($prompt);
                $parts->add_file_data_part('image/png,image/jpg,image/jpeg', $logo_url);
                $content = new Content(Content_Role::USER, $parts);
            } catch (Exception $e) {
                throw new Exception(
                    'Failed to prepare content: ' . $e->getMessage()
                );
            }

            try {
                $candidates = $service
                    ->get_model(
                        array(
                            'feature'      => 'uniform-ai-generator',
                            'capabilities' => array(
                                AI_Capability::MULTIMODAL_INPUT,
                                AI_Capability::TEXT_GENERATION,
                            )
                        )
                    )
                    ->generate_text($content);
            } catch (Exception $e) {
                throw new Exception('Failed to generate image: ' . $e->getMessage());
            }

            if (!$candidates || !is_iterable($candidates)) {
                throw new Exception('No image candidates were generated');
            }

            $images = array();
            foreach ($candidates as $candidate) {
                foreach ($candidate->get_content()->get_parts() as $part) {
                    if ($part instanceof Inline_Data_Part) {
                        $images[] = $part->get_base64_data();
                    }
                }
            }

            if (empty($images)) {
                throw new Exception('No valid images were generated');
            }

            return array(
                'success' => true,
                'images' => $images,
            );

        } catch (Exception $e) {
            error_log('Error in generateUniformImage: ' . $e->getMessage());
            error_log('Error trace: ' . $e->getTraceAsString());
            
            return array(
                'success' => false,
                'error' => $e->getMessage(),
                'error_details' => array(
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                )
            );
        }
    }

    /**
     * Save generated image to media library
     *
     * @param string $image_data Base64 encoded image data
     *
     * @return array Array containing the saved image URL or error message
     */
    public function saveToMediaLibrary(string $image_data): array
    {
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
            include_once ABSPATH . 'wp-admin/includes/image.php';
            
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
    public function renderAdminPage(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        include_once UNIFORM_AI_GENERATOR_PLUGIN_DIR . 
        'admin/templates/admin-page.php';
    }
}