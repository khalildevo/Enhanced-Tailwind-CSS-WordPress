<?php
/**
 * Plugin Name: Enhanced Tailwind CSS for WordPress
 * Plugin URI: https://ksrio.com
 * Description: A plugin that adds Tailwind CSS support to WordPress with performance optimizations and WordPress integration.
 * Version: 1.0.0
 * Author: KSRIO.COM
 * Author URI: https://ksrio.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: enhanced-tailwind-wp
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Enhanced_Tailwind_WordPress {
    /**
     * Plugin instance.
     *
     * @var Enhanced_Tailwind_WordPress
     */
    private static $instance;

    /**
     * Plugin options.
     *
     * @var array
     */
    private $options;

    /**
     * Plugin directory path.
     *
     * @var string
     */
    private $plugin_dir;

    /**
     * Plugin directory URL.
     *
     * @var string
     */
    private $plugin_url;

    /**
     * Get plugin instance.
     *
     * @return Enhanced_Tailwind_WordPress
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    public function __construct() {
        $this->plugin_dir = plugin_dir_path(__FILE__);
        $this->plugin_url = plugin_dir_url(__FILE__);
        
        // Register activation and deactivation hooks
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        register_uninstall_hook(__FILE__, ['Enhanced_Tailwind_WordPress', 'uninstall']);

        // Initialize plugin
        add_action('plugins_loaded', [$this, 'init']);
        
        // Load options
        $this->options = get_option('enhanced_tailwind_wp_options', [
            'load_on_frontend' => true,
            'load_in_admin' => false,
            'use_local_file' => false,
            'safelist' => '',
            'cache_expiration' => 24, // hours
            'config' => $this->get_default_config()
        ]);
    }

    /**
     * Activate plugin.
     */
    public function activate() {
        // Set default options
        if (!get_option('enhanced_tailwind_wp_options')) {
            update_option('enhanced_tailwind_wp_options', [
                'load_on_frontend' => true,
                'load_in_admin' => false,
                'use_local_file' => false,
                'safelist' => 'bg-blue-500 text-white hover:bg-blue-700',
                'cache_expiration' => 24, // hours
                'config' => $this->get_default_config()
            ]);
        }
        
        // Create necessary directories
        $upload_dir = wp_upload_dir();
        $tailwind_dir = $upload_dir['basedir'] . '/enhanced-tailwind-wp';
        
        if (!file_exists($tailwind_dir)) {
            wp_mkdir_p($tailwind_dir);
        }
        
        // Create or update the local Tailwind browser file
        $this->maybe_update_local_tailwind_file();
    }

    /**
     * Deactivate plugin.
     */
    public function deactivate() {
        // Clear any transients we've set
        delete_transient('enhanced_tailwind_wp_css_cache');
    }

    /**
     * Uninstall plugin.
     */
    public static function uninstall() {
        // Remove options
        delete_option('enhanced_tailwind_wp_options');
        
        // Remove any stored files
        $upload_dir = wp_upload_dir();
        $tailwind_dir = $upload_dir['basedir'] . '/enhanced-tailwind-wp';
        
        if (file_exists($tailwind_dir)) {
            self::remove_directory($tailwind_dir);
        }
    }

    /**
     * Helper function to remove a directory and its contents.
     */
    private static function remove_directory($dir) {
        if (!file_exists($dir)) {
            return true;
        }
        
        if (!is_dir($dir)) {
            return unlink($dir);
        }
        
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            
            if (!self::remove_directory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }
        
        return rmdir($dir);
    }

    /**
     * Initialize plugin.
     */
    public function init() {
        // Add settings page
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        
        // Register scripts and styles
        if ($this->options['load_on_frontend']) {
            add_action('wp_enqueue_scripts', [$this, 'enqueue_tailwind_browser']);
        }
        
        if ($this->options['load_in_admin']) {
            add_action('admin_enqueue_scripts', [$this, 'enqueue_tailwind_browser']);
        }
        
        // Add Tailwind CSS class to body
        add_filter('body_class', [$this, 'add_tailwind_body_class']);
        
        // Add Tailwind to Gutenberg editor if needed
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_block_editor_assets']);
        
        // Register Gutenberg blocks
        add_action('init', [$this, 'register_gutenberg_blocks']);
        
        // Register shortcodes
        add_action('init', [$this, 'register_shortcodes']);
        
        // Add REST API endpoints
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    /**
     * Add admin menu.
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Enhanced Tailwind CSS', 'enhanced-tailwind-wp'),
            __('Tailwind CSS', 'enhanced-tailwind-wp'),
            'manage_options',
            'enhanced-tailwind-wp',
            [$this, 'render_settings_page'],
            'dashicons-admin-appearance'
        );
        
        add_submenu_page(
            'enhanced-tailwind-wp',
            __('Settings', 'enhanced-tailwind-wp'),
            __('Settings', 'enhanced-tailwind-wp'),
            'manage_options',
            'enhanced-tailwind-wp',
            [$this, 'render_settings_page']
        );
        
        add_submenu_page(
            'enhanced-tailwind-wp',
            __('Component Builder', 'enhanced-tailwind-wp'),
            __('Component Builder', 'enhanced-tailwind-wp'),
            'manage_options',
            'enhanced-tailwind-wp-components',
            [$this, 'render_component_builder_page']
        );
    }

    /**
     * Register settings.
     */
    public function register_settings() {
        register_setting(
            'enhanced_tailwind_wp_options_group',
            'enhanced_tailwind_wp_options',
            [$this, 'sanitize_options']
        );

        // General Settings
        add_settings_section(
            'enhanced_tailwind_wp_general',
            __('General Settings', 'enhanced-tailwind-wp'),
            [$this, 'render_section_info'],
            'enhanced-tailwind-wp'
        );

        add_settings_field(
            'load_on_frontend',
            __('Load on Frontend', 'enhanced-tailwind-wp'),
            [$this, 'render_load_on_frontend_field'],
            'enhanced-tailwind-wp',
            'enhanced_tailwind_wp_general'
        );

        add_settings_field(
            'load_in_admin',
            __('Load in Admin', 'enhanced-tailwind-wp'),
            [$this, 'render_load_in_admin_field'],
            'enhanced-tailwind-wp',
            'enhanced_tailwind_wp_general'
        );

        // Performance Settings
        add_settings_section(
            'enhanced_tailwind_wp_performance',
            __('Performance Settings', 'enhanced-tailwind-wp'),
            [$this, 'render_performance_section_info'],
            'enhanced-tailwind-wp'
        );

        add_settings_field(
            'use_local_file',
            __('Use Local File', 'enhanced-tailwind-wp'),
            [$this, 'render_use_local_file_field'],
            'enhanced-tailwind-wp',
            'enhanced_tailwind_wp_performance'
        );

        add_settings_field(
            'safelist',
            __('Safelist Classes', 'enhanced-tailwind-wp'),
            [$this, 'render_safelist_field'],
            'enhanced-tailwind-wp',
            'enhanced_tailwind_wp_performance'
        );

        add_settings_field(
            'cache_expiration',
            __('Cache Expiration (hours)', 'enhanced-tailwind-wp'),
            [$this, 'render_cache_expiration_field'],
            'enhanced-tailwind-wp',
            'enhanced_tailwind_wp_performance'
        );

        // Configuration Settings
        add_settings_section(
            'enhanced_tailwind_wp_config',
            __('Tailwind Configuration', 'enhanced-tailwind-wp'),
            [$this, 'render_config_section_info'],
            'enhanced-tailwind-wp'
        );

        add_settings_field(
            'config',
            __('Tailwind Config', 'enhanced-tailwind-wp'),
            [$this, 'render_config_field'],
            'enhanced-tailwind-wp',
            'enhanced_tailwind_wp_config'
        );
    }

    /**
     * Sanitize options.
     */
    public function sanitize_options($input) {
        $new_input = [];
        
        $new_input['load_on_frontend'] = isset($input['load_on_frontend']) ? true : false;
        $new_input['load_in_admin'] = isset($input['load_in_admin']) ? true : false;
        $new_input['use_local_file'] = isset($input['use_local_file']) ? true : false;
        
        if (isset($input['safelist'])) {
            $new_input['safelist'] = sanitize_text_field($input['safelist']);
        } else {
            $new_input['safelist'] = '';
        }
        
        if (isset($input['cache_expiration'])) {
            $new_input['cache_expiration'] = absint($input['cache_expiration']);
        } else {
            $new_input['cache_expiration'] = 24;
        }
        
        if (isset($input['config'])) {
            // We don't sanitize JSON strictly as it could contain valid JS that isn't valid JSON
            $new_input['config'] = $input['config'];
        } else {
            $new_input['config'] = $this->get_default_config();
        }
        
        // If use_local_file was checked, update the local file
        if ($new_input['use_local_file']) {
            $this->maybe_update_local_tailwind_file();
        }
        
        // Clear cache when settings are updated
        delete_transient('enhanced_tailwind_wp_css_cache');
        
        return $new_input;
    }

    /**
     * Render section info.
     */
    public function render_section_info() {
        echo '<p>' . esc_html__('Configure how Tailwind CSS is loaded.', 'enhanced-tailwind-wp') . '</p>';
    }

    /**
     * Render performance section info.
     */
    public function render_performance_section_info() {
        echo '<p>' . esc_html__('Configure performance settings for Tailwind CSS.', 'enhanced-tailwind-wp') . '</p>';
    }

    /**
     * Render config section info.
     */
    public function render_config_section_info() {
        echo '<p>' . esc_html__('Configure Tailwind CSS settings.', 'enhanced-tailwind-wp') . '</p>';
    }

    /**
     * Render load on frontend field.
     */
    public function render_load_on_frontend_field() {
        ?>
        <input type="checkbox" id="load_on_frontend" name="enhanced_tailwind_wp_options[load_on_frontend]" <?php checked($this->options['load_on_frontend'], true); ?> />
        <label for="load_on_frontend"><?php esc_html_e('Load Tailwind CSS on frontend', 'enhanced-tailwind-wp'); ?></label>
        <?php
    }

    /**
     * Render load in admin field.
     */
    public function render_load_in_admin_field() {
        ?>
        <input type="checkbox" id="load_in_admin" name="enhanced_tailwind_wp_options[load_in_admin]" <?php checked($this->options['load_in_admin'], true); ?> />
        <label for="load_in_admin"><?php esc_html_e('Load Tailwind CSS in admin area', 'enhanced-tailwind-wp'); ?></label>
        <?php
    }

    /**
     * Render use local file field.
     */
    public function render_use_local_file_field() {
        ?>
        <input type="checkbox" id="use_local_file" name="enhanced_tailwind_wp_options[use_local_file]" <?php checked($this->options['use_local_file'], true); ?> />
        <label for="use_local_file"><?php esc_html_e('Use local copy of Tailwind CSS browser file (recommended for performance)', 'enhanced-tailwind-wp'); ?></label>
        <?php
    }

    /**
     * Render safelist field.
     */
    public function render_safelist_field() {
        ?>
        <textarea id="safelist" name="enhanced_tailwind_wp_options[safelist]" rows="4" cols="50" class="large-text code"><?php echo esc_textarea($this->options['safelist']); ?></textarea>
        <p class="description"><?php esc_html_e('Enter Tailwind CSS classes that should always be available, separated by spaces.', 'enhanced-tailwind-wp'); ?></p>
        <?php
    }

    /**
     * Render cache expiration field.
     */
    public function render_cache_expiration_field() {
        ?>
        <input type="number" id="cache_expiration" name="enhanced_tailwind_wp_options[cache_expiration]" value="<?php echo esc_attr($this->options['cache_expiration']); ?>" min="1" step="1" />
        <p class="description"><?php esc_html_e('Number of hours before the CSS cache expires.', 'enhanced-tailwind-wp'); ?></p>
        <?php
    }

    /**
     * Render config field.
     */
    public function render_config_field() {
        ?>
        <textarea id="config" name="enhanced_tailwind_wp_options[config]" rows="10" cols="50" class="large-text code"><?php echo esc_textarea($this->options['config']); ?></textarea>
        <p class="description"><?php esc_html_e('Enter your Tailwind configuration in JavaScript format', 'enhanced-tailwind-wp'); ?></p>
        <?php
    }

    /**
     * Render settings page.
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Enhanced Tailwind CSS Settings', 'enhanced-tailwind-wp'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('enhanced_tailwind_wp_options_group');
                do_settings_sections('enhanced-tailwind-wp');
                submit_button();
                ?>
            </form>
            <div class="card">
                <h2><?php echo esc_html__('Cache Management', 'enhanced-tailwind-wp'); ?></h2>
                <p><?php echo esc_html__('Use this button to manually clear the Tailwind CSS cache:', 'enhanced-tailwind-wp'); ?></p>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="clear_tailwind_cache">
                    <?php wp_nonce_field('clear_tailwind_cache_nonce', 'clear_tailwind_cache_nonce'); ?>
                    <?php submit_button(__('Clear Cache', 'enhanced-tailwind-wp'), 'secondary'); ?>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Render component builder page.
     */
    public function render_component_builder_page() {
        // Enqueue necessary scripts and styles for the component builder
        wp_enqueue_style('enhanced-tailwind-wp-builder', $this->plugin_url . 'assets/css/component-builder.css', [], '1.0.0');
        wp_enqueue_script('enhanced-tailwind-wp-builder', $this->plugin_url . 'assets/js/component-builder.js', ['jquery', 'wp-element'], '1.0.0', true);
        
        // Add editor settings as script data
        wp_localize_script('enhanced-tailwind-wp-builder', 'enhancedTailwindWP', [
            'config' => $this->options['config'],
            'safelist' => $this->options['safelist'],
            'restUrl' => get_rest_url(null, 'enhanced-tailwind-wp/v1'),
            'nonce' => wp_create_nonce('wp_rest')
        ]);
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Tailwind CSS Component Builder', 'enhanced-tailwind-wp'); ?></h1>
            <div id="enhanced-tailwind-wp-builder">
                <div class="loading-wrapper">
                    <div class="loading-spinner"></div>
                    <p><?php echo esc_html__('Loading component builder...', 'enhanced-tailwind-wp'); ?></p>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Enqueue Tailwind Browser assets.
     */
    public function enqueue_tailwind_browser() {
        $script_url = 'https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4.1.3/dist/index.global.js';
        $script_version = '4.1.3';
        
        if ($this->options['use_local_file']) {
            $upload_dir = wp_upload_dir();
            $local_file_path = $upload_dir['basedir'] . '/enhanced-tailwind-wp/tailwind-browser.js';
            $local_file_url = $upload_dir['baseurl'] . '/enhanced-tailwind-wp/tailwind-browser.js';
            
            if (file_exists($local_file_path)) {
                $script_url = $local_file_url;
                $script_version = filemtime($local_file_path);
            }
        }
        
        // Enqueue Tailwind Browser script
        wp_enqueue_script(
            'enhanced-tailwind-browser',
            $script_url,
            [],
            $script_version,
            true
        );

        // Get cached CSS or generate it
        $cached_css = get_transient('enhanced_tailwind_wp_css_cache');
        if (false === $cached_css) {
            // In a real implementation, you would probably generate this server-side
            // For now, we'll just use the browser version
            $cached_css = '';
        }

        // Add inline CSS for safelist if available
        if (!empty($cached_css)) {
            wp_add_inline_style('enhanced-tailwind-wp-styles', $cached_css);
        }

        // Add inline script to initialize Tailwind Browser
        $config = $this->options['config'];
        $safelist = $this->options['safelist'];
        
        $inline_script = "
            document.addEventListener('DOMContentLoaded', function() {
                const tailwindConfig = {$config};
                
                // Add safelist to content
                if (!tailwindConfig.content) {
                    tailwindConfig.content = [];
                }
                
                // Add the safelist to the configuration
                if ('{$safelist}'.trim() !== '') {
                    // Create a temporary element to hold the safelist classes
                    const safelistContainer = document.createElement('div');
                    safelistContainer.id = 'tailwind-safelist-container';
                    safelistContainer.style.display = 'none';
                    safelistContainer.className = '{$safelist}';
                    document.body.appendChild(safelistContainer);
                }
                
                window.tailwind = window.tailwindcssBrowser.createTailwindBrowser({
                    config: tailwindConfig,
                    target: document.documentElement
                });
            });
        ";
        
        wp_add_inline_script('enhanced-tailwind-browser', $inline_script);
    }

    /**
     * Enqueue block editor assets.
     */
    public function enqueue_block_editor_assets() {
        if ($this->options['load_in_admin']) {
            $this->enqueue_tailwind_browser();
        }
        
        // Enqueue block editor specific assets
        wp_enqueue_script(
            'enhanced-tailwind-wp-blocks',
            $this->plugin_url . 'assets/js/blocks.js',
            ['wp-blocks', 'wp-element', 'wp-editor', 'wp-components'],
            filemtime($this->plugin_dir . 'assets/js/blocks.js'),
            true
        );
        
        wp_localize_script('enhanced-tailwind-wp-blocks', 'enhancedTailwindWPBlocks', [
            'config' => $this->options['config'],
            'safelist' => $this->options['safelist']
        ]);
    }

    /**
     * Add Tailwind CSS class to body.
     */
    public function add_tailwind_body_class($classes) {
        $classes[] = 'enhanced-tailwind-enabled';
        return $classes;
    }

    /**
     * Register Gutenberg blocks.
     */
    public function register_gutenberg_blocks() {
        // Check if Gutenberg is available
        if (!function_exists('register_block_type')) {
            return;
        }
        
        // Register the block for the editor
        register_block_type($this->plugin_dir . 'blocks/tailwind-container');
        register_block_type($this->plugin_dir . 'blocks/tailwind-button');
        register_block_type($this->plugin_dir . 'blocks/tailwind-card');
        register_block_type($this->plugin_dir . 'blocks/tailwind-grid');
    }

    /**
     * Register shortcodes.
     */
    public function register_shortcodes() {
        add_shortcode('tailwind_container', [$this, 'tailwind_container_shortcode']);
        add_shortcode('tailwind_button', [$this, 'tailwind_button_shortcode']);
        add_shortcode('tailwind_card', [$this, 'tailwind_card_shortcode']);
        add_shortcode('tailwind_grid', [$this, 'tailwind_grid_shortcode']);
    }

    /**
     * Tailwind container shortcode.
     */
    public function tailwind_container_shortcode($atts, $content = null) {
        $atts = shortcode_atts([
            'class' => 'container mx-auto px-4',
        ], $atts);
        
        return '<div class="' . esc_attr($atts['class']) . '">' . do_shortcode($content) . '</div>';
    }

    /**
     * Tailwind button shortcode.
     */
    public function tailwind_button_shortcode($atts, $content = null) {
        $atts = shortcode_atts([
            'class' => 'bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded',
            'url' => '#',
            'target' => '_self',
        ], $atts);
        
        return '<a href="' . esc_url($atts['url']) . '" target="' . esc_attr($atts['target']) . '" class="' . esc_attr($atts['class']) . '">' . esc_html($content) . '</a>';
    }

    /**
     * Tailwind card shortcode.
     */
    public function tailwind_card_shortcode($atts, $content = null) {
        $atts = shortcode_atts([
            'class' => 'bg-white rounded overflow-hidden shadow-lg',
            'title' => '',
            'image' => '',
            'alt' => '',
        ], $atts);
        
        $html = '<div class="' . esc_attr($atts['class']) . '">';
        
        if (!empty($atts['image'])) {
            $html .= '<img class="w-full" src="' . esc_url($atts['image']) . '" alt="' . esc_attr($atts['alt']) . '">';
        }
        
        $html .= '<div class="px-6 py-4">';
        
        if (!empty($atts['title'])) {
            $html .= '<div class="font-bold text-xl mb-2">' . esc_html($atts['title']) . '</div>';
        }
        
        $html .= '<div class="text-gray-700 text-base">' . do_shortcode($content) . '</div>';
        $html .= '</div></div>';
        
        return $html;
    }

    /**
     * Tailwind grid shortcode.
     */
    public function tailwind_grid_shortcode($atts, $content = null) {
        $atts = shortcode_atts([
            'class' => 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4',
        ], $atts);
        
        return '<div class="' . esc_attr($atts['class']) . '">' . do_shortcode($content) . '</div>';
    }

    /**
     * Register REST API routes.
     */
    public function register_rest_routes() {
        register_rest_route('enhanced-tailwind-wp/v1', '/save-component', [
            'methods' => 'POST',
            'callback' => [$this, 'save_component_callback'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);
        
        register_rest_route('enhanced-tailwind-wp/v1', '/get-components', [
            'methods' => 'GET',
            'callback' => [$this, 'get_components_callback'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);
    }

    /**
     * Save component REST API callback.
     */
    public function save_component_callback($request) {
        $params = $request->get_params();
        
        if (!isset($params['name']) || !isset($params['code'])) {
            return new WP_Error('missing_parameters', __('Missing required parameters', 'enhanced-tailwind-wp'), ['status' => 400]);
        }
        
        $name = sanitize_text_field($params['name']);
        $code = $params['code']; // Don't sanitize strictly as it contains HTML
        
        $components = get_option('enhanced_tailwind_wp_components', []);
        $components[$name] = $code;
        
        update_option('enhanced_tailwind_wp_components', $components);
        
        return ['success' => true, 'message' => __('Component saved successfully', 'enhanced-tailwind-wp')];
    }

    /**
     * Get components REST API callback.
     */
    public function get_components_callback() {
        $components = get_option('enhanced_tailwind_wp_components', []);
        
        return ['components' => $components];
    }

    /**
     * Update local tailwind file if needed.
     */
    private function maybe_update_local_tailwind_file() {
        $upload_dir = wp_upload_dir();
        $local_file_path = $upload_dir['basedir'] . '/enhanced-tailwind-wp/tailwind-browser.js';
        
        // Only update if the file doesn't exist or is older than 30 days
        if (!file_exists($local_file_path) || (time() - filemtime($local_file_path) > 30 * DAY_IN_SECONDS)) {
            $response = wp_remote_get('https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4.1.3/dist/index.global.js');
            
            if (!is_wp_error($response) && 200 === wp_remote_retrieve_response_code($response)) {
                $body = wp_remote_retrieve_body($response);
                file_put_contents($local_file_path, $body);
            }
        }
    }

    /**
     * Get default Tailwind config.
     */
    private function get_default_config() {
        return <<<EOT
{
  theme: {
    extend: {
      colors: {
        'wp-blue': '#0073aa',
        'wp-admin': '#f1f1f1',
        'wp-menu': '#2c3338',
        'wp-highlight': '#1d2327',
        'wp-light-gray': '#f0f0f1'
      },
    },
  },
  corePlugins: {
    preflight: false, // Disable preflight to avoid conflicts with WordPress styles
  }
}
EOT;
    }
}

// Initialize the plugin
Enhanced_Tailwind_WordPress::get_instance();

// Blocks directory structure and files if they don't exist
if (!file_exists(plugin_dir_path(__FILE__) . 'blocks')) {
    mkdir(plugin_dir_path(__FILE__) . 'blocks');
}

// Blocks and assets directories
$block_directories = [
    'blocks/tailwind-container',
    'blocks/tailwind-button',
    'blocks/tailwind-card',
    'blocks/tailwind-grid',
    'assets/css',
    'assets/js',
];

foreach ($block_directories as $dir) {
    if (!file_exists(plugin_dir_path(__FILE__) . $dir)) {
        mkdir(plugin_dir_path(__FILE__) . $dir, 0755, true);
    }
}

// block.json file for the container block
$container_block_json = 
{
    "apiVersion": 2,
    "name": "enhanced-tailwind-wp/container",
    "title": "Tailwind Container",
    "category": "design",
    "icon": "align-center",
    "description": "A container block with Tailwind CSS classes",
    "supports": {
        "html": false
    },
    "textdomain": "enhanced-tailwind-wp",
    "attributes": {
        "content": {
            "type": "string",
            "source": "html",
            "selector": "div"
        },
        "className": {
            "type": "string",
            "default": "container mx-auto px-4"
        }
    },
    "editorScript": "file:./index.js",
    "style": "file:./style.css"
}
