<?php

/**
 * The plugin bootstrap file
 *
 * @link              https://robertdevore.com
 * @since             1.0.0
 * @package           Module_Usage_Tracker_For_WP_Bakery
 *
 * @wordpress-plugin
 *
 * Plugin Name: Module Usage Tracker for WP Bakery
 * Description: Tracks and displays usage of WP Bakery modules on the website.
 * Plugin URI:  https://github.com/robertdevore/markdown-exporter-for-wordpress/
 * Version:     1.0.0
 * Author:      Robert DeVore
 * Author URI:  https://robertdevore.com/
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: module-usage-tracker-wp-bakery
 * Domain Path: /languages
 * Update URI:  https://github.com/robertdevore/module-usage-tracker-for-wp-bakery/
 */

defined( 'ABSPATH' ) || exit;

require 'vendor/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
	'https://github.com/robertdevore/module-usage-tracker-for-wp-bakery/',
	__FILE__,
	'module-usage-tracker-for-wp-bakery'
);

// Set the branch that contains the stable release.
$myUpdateChecker->setBranch( 'main' );

define( 'MUT_WP_BAKERY_VERSION', '1.1.0' );
define( 'MUT_WP_BAKERY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// Include the table class.
require_once MUT_WP_BAKERY_PLUGIN_DIR . 'classes/Module_Usage_Tracker_Table.php';

/**
 * Class ModuleUsageTrackerForWPBakery
 *
 * Handles the creation and management of the WP Bakery Module Usage Tracker.
 */
class ModuleUsageTrackerForWPBakery {
    /**
     * ModuleUsageTrackerForWPBakery constructor.
     *
     * Initializes the plugin by setting up hooks.
     */
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'create_settings_page' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        add_action( 'wp_ajax_get_module_details', [ $this, 'get_module_details' ] );
    }

    /**
     * Create the settings page in the admin menu.
     *
     * Adds a new top-level menu page for the Module Tracker.
     *
     * @return void
     */
    public function create_settings_page() {
        add_menu_page(
            esc_html__( 'WP Bakery Module Tracker', 'module-usage-tracker-wp-bakery' ), // Page title
            esc_html__( 'Module Tracker', 'module-usage-tracker-wp-bakery' ), // Menu title
            'manage_options', // Capability
            'module-usage-tracker-wp-bakery', // Menu slug
            [ $this, 'render_settings_page' ], // Callback function
            'dashicons-visibility', // Icon
            80 // Position
        );
    }

    /**
     * Render the settings page content.
     *
     * Displays the table of modules, filter dropdown, and search box.
     *
     * @return void
     */
    public function render_settings_page() {
        $modules = $this->get_registered_vc_modules();

        // Handle filter parameters.
        $usage_filter = isset( $_GET['usage_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['usage_filter'] ) ) : '';

        // Filter modules based on the selected filter.
        if ( ! empty( $usage_filter ) ) {
            $modules = array_filter( $modules, function( $module ) use ( $usage_filter ) {
                switch ( $usage_filter ) {
                    case 'high':
                        return $module['count'] >= 10; // Define your threshold
                    case 'medium':
                        return $module['count'] >= 5 && $module['count'] < 10;
                    case 'low':
                        return $module['count'] < 5;
                    default:
                        return true;
                }
            });

            // Reset array keys after filtering.
            $modules = array_values( $modules );
        }

        echo '<div class="wrap"><h1>' . esc_html__( 'WP Bakery Module Tracker', 'module-usage-tracker-wp-bakery' );

        echo '<a id="mut-wpb-support-btn" href="https://robertdevore.com/contact/" target="_blank" class="button button-alt" style="margin-left: 10px;">';
        echo '<span class="dashicons dashicons-format-chat" style="vertical-align: middle;"></span> ';
        esc_html_e( 'Support', 'module-usage-tracker-wp-bakery' );
        echo '</a>';

        echo '<a id="mut-wpb-docs-btn" href="https://robertdevore.com/articles/content-restriction-for-wordpress/" target="_blank" class="button button-alt" style="margin-left: 5px;">';
        echo '<span class="dashicons dashicons-media-document" style="vertical-align: middle;"></span> ';
        esc_html_e( 'Documentation', 'module-usage-tracker-wp-bakery' );
        echo '</a>';

        echo '</h1>';
        echo '<hr />';

        // Add Filter Dropdown and Search Box.
        ?>
        <form method="get">
            <input type="hidden" name="page" value="module-usage-tracker-wp-bakery" />
            
            <!-- Preserve 'orderby' and 'order' parameters if they exist -->
            <?php
            if ( isset( $_GET['orderby'] ) ) {
                echo '<input type="hidden" name="orderby" value="' . esc_attr( wp_unslash( $_GET['orderby'] ) ) . '" />';
            }
            if ( isset( $_GET['order'] ) ) {
                echo '<input type="hidden" name="order" value="' . esc_attr( wp_unslash( $_GET['order'] ) ) . '" />';
            }
            ?>

            <label for="usage_filter" style="margin-right: 10px;"><?php esc_html_e( 'Filter by Usage:', 'module-usage-tracker-wp-bakery' ); ?></label>
            <select name="usage_filter" id="usage_filter" onchange="this.form.submit()">
                <option value=""><?php esc_html_e( 'All', 'module-usage-tracker-wp-bakery' ); ?></option>
                <option value="high" <?php selected( $usage_filter, 'high' ); ?>><?php esc_html_e( 'Highly Used (≥10)', 'module-usage-tracker-wp-bakery' ); ?></option>
                <option value="medium" <?php selected( $usage_filter, 'medium' ); ?>><?php esc_html_e( 'Moderately Used (5-9)', 'module-usage-tracker-wp-bakery' ); ?></option>
                <option value="low" <?php selected( $usage_filter, 'low' ); ?>><?php esc_html_e( 'Low Usage (<5)', 'module-usage-tracker-wp-bakery' ); ?></option>
            </select>

            <?php
            // Instantiate the list table with filtered modules
            $list_table = new ModuleUsageListTable( $modules );
            $list_table->prepare_items();
            ?>
            <div style="float: right;">
                <?php $list_table->search_box( esc_html__( 'Search Modules', 'module-usage-tracker-wp-bakery' ), 'module_search' ); ?>
            </div>
            <div style="clear: both;"></div>
            <?php
            $list_table->display();
        ?>

        <!-- Modal HTML -->
        <div id="module-details-modal" aria-hidden="true">
            <div id="modal-content">
                <span id="modal-close" aria-label="Close Modal">&times;</span>
                <!-- AJAX content will be injected here -->
            </div>
        </div>

        <?php
        echo '</div>';
    }

    /**
     * Retrieve all registered WP Bakery modules and count their usage.
     *
     * @return array List of modules with usage counts and associated pages.
     */
    private function get_registered_vc_modules() {
        global $wpdb;
        $modules = [];

        if ( ! class_exists( 'WPBMap' ) ) {
            error_log( 'WPBMap class not found. Ensure WP Bakery is active.' );
            return $modules;
        }

        $registered_modules = WPBMap::getShortCodes();
        foreach ( $registered_modules as $name => $data ) {
            $posts_with_module = $wpdb->get_results( $wpdb->prepare(
                "SELECT ID, post_content FROM $wpdb->posts WHERE post_content LIKE %s AND post_type IN ('page', 'post') AND post_status = 'publish'",
                '%' . $wpdb->esc_like( '[' . $name ) . '%'
            ) );

            $instance_count  = 0;
            $unique_page_ids = [];

            foreach ( $posts_with_module as $post ) {
                $count_in_post = substr_count( $post->post_content, '[' . $name );
                $instance_count += $count_in_post;

                // Only add unique post IDs.
                if ( ! in_array( $post->ID, $unique_page_ids, true ) ) {
                    $unique_page_ids[] = $post->ID;
                }
            }

            $modules[ $name ] = [
                'name'  => $name,
                'count' => $instance_count,
                'pages' => $unique_page_ids
            ];
        }

        return $modules;
    }

    /**
     * Enqueue necessary CSS and JavaScript files.
     *
     * @param string $hook The current admin page.
     *
     * @return void
     */
    public function enqueue_scripts( $hook ) {
        if ( 'toplevel_page_module-usage-tracker-wp-bakery' !== $hook ) {
            return;
        }

        // Enqueue CSS.
        wp_enqueue_style( 'module-usage-tracker-css', plugin_dir_url( __FILE__ ) . 'assets/css/styles.css', [], MUT_WP_BAKERY_VERSION );

        // Enqueue JavaScript.
        wp_enqueue_script( 'module-usage-tracker-js', plugin_dir_url( __FILE__ ) . 'assets/js/scripts.js', [ 'jquery' ], MUT_WP_BAKERY_VERSION, true );

        // Localize script with AJAX URL and nonce.
        wp_localize_script( 'module-usage-tracker-js', 'moduleTracker', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'module_usage_tracker_nonce' )
        ] );
    }

    /**
     * AJAX handler to get module details and pages where the module is found.
     *
     * @since 1.0.0
     *
     * @return void Outputs JSON response and exits.
     */
    public function get_module_details() {
        check_ajax_referer( 'module_usage_tracker_nonce', 'security' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Unauthorized access', 'module-usage-tracker-wp-bakery' ) );
        }

        if ( ! isset( $_POST['module'] ) ) {
            wp_send_json_error( __( 'No module specified', 'module-usage-tracker-wp-bakery' ) );
        }

        $module_name = sanitize_text_field( wp_unslash( $_POST['module'] ) );
        $module_data = $this->get_registered_vc_modules();

        if ( ! isset( $module_data[ $module_name ] ) ) {
            wp_send_json_error( __( 'Module not found', 'module-usage-tracker-wp-bakery' ) );
        }

        $pages = [];
        foreach ( $module_data[ $module_name ]['pages'] as $page_id ) {
            $pages[] = [
                'id'    => $page_id,
                'title' => esc_html( get_the_title( $page_id ) ),
                'link'  => esc_url( get_edit_post_link( $page_id ) ),
            ];
        }

        $total_usage_count = $module_data[ $module_name ]['count'];
        $unique_post_count = count( $module_data[ $module_name ]['pages'] ); // Accurate unique post count

        $title_html = sprintf(
            '<h3>%s</h3><p><strong>%s</strong></p>',
            esc_html__( 'Widget Usage Details', 'module-usage-tracker-wp-bakery' ),
            sprintf(
                esc_html__( 'The "%s" widget is used %d times across %d unique posts:', 'module-usage-tracker-wp-bakery' ),
                esc_html( $module_name ),
                esc_html( $total_usage_count ),
                esc_html( $unique_post_count )
            )
        );

        wp_send_json_success( [
            'title' => $title_html,
            'pages' => $pages,
        ] );
    }
}

new ModuleUsageTrackerForWPBakery();
