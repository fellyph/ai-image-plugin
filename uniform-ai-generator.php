<?php
/**
 * Plugin Name: Uniform AI Generator
 * Plugin URI: https://github.com/your-username/uniform-ai-generator
 * Description: A WordPress plugin to generate uniform images using AI. 
 *             Requires AI Services plugin.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://your-website.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: uniform-ai-generator
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Requires at least: 5.8
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('UNIFORM_AI_GENERATOR_VERSION', '1.0.0');
define('UNIFORM_AI_GENERATOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('UNIFORM_AI_GENERATOR_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Check if AI Services plugin is active
 * 
 * @return bool True if dependencies are met, false otherwise
 */
function Uniform_Ai_Generator_Check_dependencies() {
    if (!function_exists('ai_services')) {
        add_action('admin_notices', 'Uniform_Ai_Generator_Dependency_notice');
        return false;
    }
    return true;
}

/**
 * Display dependency notice
 * 
 * @return void
 */
function Uniform_Ai_Generator_Dependency_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php _e('Uniform AI Generator requires the AI Services plugin to be installed and activated.', 'uniform-ai-generator'); ?></p>
    </div>
    <?php
}

// Initialize plugin only if dependencies are met
if (Uniform_Ai_Generator_Check_dependencies()) {
    include_once UNIFORM_AI_GENERATOR_PLUGIN_DIR . 
        'includes/class-uniform-ai-generator.php';
    include_once UNIFORM_AI_GENERATOR_PLUGIN_DIR . 
        'admin/class-uniform-ai-generator-admin.php';
    
    /**
     * Initialize the plugin
     *
     * @return void
     */
    function Uniform_Ai_Generator_init() {
        $plugin = new Uniform_AI_Generator();
        $plugin->init();
    }
    add_action('plugins_loaded', 'Uniform_Ai_Generator_init');
} 