<?php
/**
 * Plugin Name: URL Exporter
 * Plugin URI: https://www.matthewselby.com
 * Description: Export all site URLs to CSV or TXT format with clean alphabetical sorting
 * Version: 1.0.0
 * Author: Matt Selby
 * License: GPL v2 or later
 * Text Domain: url-exporter
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WP_URL_Exporter {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'handle_export']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles']);
        
        // Register WP-CLI command if available
        if (defined('WP_CLI') && WP_CLI) {
            WP_CLI::add_command('url-export', [$this, 'cli_export']);
        }
    }
    
    /**
     * Add menu item under Tools
     */
    public function add_admin_menu() {
        add_management_page(
            __('URL Exporter', 'url-exporter'),
            __('URL Exporter', 'url-exporter'),
            'manage_options',
            'url-exporter',
            [$this, 'admin_page']
        );
    }
    
    /**
     * Enqueue admin styles
     */
    public function enqueue_admin_styles($hook) {
        if ('tools_page_url-exporter' !== $hook) {
            return;
        }
        
        wp_add_inline_style('wp-admin', '
            .url-exporter-wrap {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                padding: 20px;
                margin-top: 20px;
                max-width: 800px;
            }
            .url-exporter-buttons {
                display: flex;
                gap: 15px;
                margin-top: 20px;
            }
            .url-exporter-button {
                padding: 10px 30px !important;
                font-size: 16px !important;
                height: auto !important;
                display: inline-flex;
                align-items: center;
                gap: 8px;
            }
            .url-exporter-info {
                background: #f0f8ff;
                border-left: 4px solid #0073aa;
                padding: 12px;
                margin: 20px 0;
            }
        ');
    }
    
    /**
     * Admin page HTML
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('URL Exporter', 'url-exporter'); ?></h1>
            
            <div class="url-exporter-wrap">
                <h2><?php _e('Export Site URLs', 'url-exporter'); ?></h2>
                <p><?php _e('Generate a complete list of all URLs on your site, sorted alphabetically and organized by path structure.', 'url-exporter'); ?></p>
                
                <div class="url-exporter-info">
                    <strong><?php _e('What\'s included:', 'url-exporter'); ?></strong>
                    <p><?php _e('Pages, Posts, Custom Post Types, Categories, Tags, Custom Taxonomies, Archives, Author Pages, and more.', 'url-exporter'); ?></p>
                </div>
                
                <div class="url-exporter-buttons">
                    <form method="post" style="display: inline;">
                        <?php wp_nonce_field('url_export_csv', 'url_export_nonce'); ?>
                        <input type="hidden" name="export_format" value="csv">
                        <button type="submit" name="export_urls" class="button button-primary url-exporter-button">
                            <span class="dashicons dashicons-download"></span>
                            <?php _e('Export as CSV', 'url-exporter'); ?>
                        </button>
                    </form>
                    
                    <form method="post" style="display: inline;">
                        <?php wp_nonce_field('url_export_txt', 'url_export_nonce'); ?>
                        <input type="hidden" name="export_format" value="txt">
                        <button type="submit" name="export_urls" class="button button-secondary url-exporter-button">
                            <span class="dashicons dashicons-text"></span>
                            <?php _e('Export as TXT', 'url-exporter'); ?>
                        </button>
                    </form>
                </div>
                
                <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
                    <h3><?php _e('WP-CLI Command', 'url-exporter'); ?></h3>
                    <p><?php _e('You can also export URLs using WP-CLI:', 'url-exporter'); ?></p>
                    <code style="background: #f4f4f4; padding: 8px 12px; display: inline-block; border-radius: 3px;">
                        wp url-export --format=csv<br>
                        wp url-export --format=txt
                    </code>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Handle export requests
     */
    public function handle_export() {
        if (!isset($_POST['export_urls']) || !isset($_POST['export_format'])) {
            return;
        }
        
        // Verify nonce
        $nonce_action = $_POST['export_format'] === 'csv' ? 'url_export_csv' : 'url_export_txt';
        if (!wp_verify_nonce($_POST['url_export_nonce'], $nonce_action)) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $format = sanitize_text_field($_POST['export_format']);
        $this->generate_export($format);
    }
    
    /**
     * Gather all URLs from the site
     */
    private function gather_all_urls() {
        $urls = [];
        
        // Home URL
        $urls[] = home_url('/');
        
        // Get all public post types
        $post_types = get_post_types(['public' => true], 'names');
        
        foreach ($post_types as $post_type) {
            // Skip attachment pages if not enabled
            if ($post_type === 'attachment' && get_option('wp_attachment_pages_enabled') !== '1') {
                continue;
            }
            
            // Get all posts of this type
            $posts = get_posts([
                'post_type' => $post_type,
                'posts_per_page' => -1,
                'post_status' => 'publish'
            ]);
            
            foreach ($posts as $post) {
                $urls[] = get_permalink($post->ID);
            }
            
            // Get post type archive if it has one
            if ($post_type !== 'page' && $post_type !== 'attachment') {
                $archive_link = get_post_type_archive_link($post_type);
                if ($archive_link) {
                    $urls[] = $archive_link;
                }
            }
        }
        
        // Get all taxonomies
        $taxonomies = get_taxonomies(['public' => true], 'names');
        
        foreach ($taxonomies as $taxonomy) {
            $terms = get_terms([
                'taxonomy' => $taxonomy,
                'hide_empty' => false
            ]);
            
            if (!is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $urls[] = get_term_link($term);
                }
            }
        }
        
        // Author archives
        $authors = get_users(['who' => 'authors']);
        foreach ($authors as $author) {
            $urls[] = get_author_posts_url($author->ID);
        }
        
        // Date archives (only if site has posts)
        $years = $this->get_archive_years();
        foreach ($years as $year) {
            $urls[] = get_year_link($year);
            
            // Monthly archives for each year
            $months = $this->get_archive_months($year);
            foreach ($months as $month) {
                $urls[] = get_month_link($year, $month);
            }
        }
        
        // Search page (template)
        $urls[] = home_url('/?s=');
        
        // 404 page (if custom 404 exists)
        // This is typically not indexed but included for completeness
        
        // Clean and normalize URLs
        $urls = array_map(function($url) {
            if (is_wp_error($url)) {
                return null;
            }
            // Convert to path-only for sorting
            $parsed = parse_url($url);
            return isset($parsed['path']) ? $parsed['path'] : '/';
        }, $urls);
        
        // Remove nulls and duplicates
        $urls = array_filter($urls);
        $urls = array_unique($urls);
        
        // Sort alphabetically
        sort($urls, SORT_STRING);
        
        // Convert back to full URLs
        $site_url = site_url();
        $urls = array_map(function($path) use ($site_url) {
            return $site_url . $path;
        }, $urls);
        
        return $urls;
    }
    
    /**
     * Get years that have posts
     */
    private function get_archive_years() {
        global $wpdb;
        
        $years = $wpdb->get_col("
            SELECT DISTINCT YEAR(post_date) 
            FROM $wpdb->posts 
            WHERE post_status = 'publish' 
            AND post_type = 'post' 
            ORDER BY post_date DESC
        ");
        
        return $years ?: [];
    }
    
    /**
     * Get months for a specific year that have posts
     */
    private function get_archive_months($year) {
        global $wpdb;
        
        $months = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT MONTH(post_date) 
            FROM $wpdb->posts 
            WHERE post_status = 'publish' 
            AND post_type = 'post' 
            AND YEAR(post_date) = %d
            ORDER BY post_date DESC
        ", $year));
        
        return $months ?: [];
    }
    
    /**
     * Generate and download export file
     */
    private function generate_export($format = 'csv') {
        $urls = $this->gather_all_urls();
        $filename = 'site-urls-' . date('Y-m-d-His') . '.' . $format;
        
        if ($format === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            $output = fopen('php://output', 'w');
            
            // Add BOM for Excel UTF-8 compatibility
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // CSV Header
            fputcsv($output, ['URL', 'Path']);
            
            foreach ($urls as $url) {
                $parsed = parse_url($url);
                $path = isset($parsed['path']) ? $parsed['path'] : '/';
                fputcsv($output, [$url, $path]);
            }
            
            fclose($output);
        } else {
            header('Content-Type: text/plain; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            foreach ($urls as $url) {
                echo $url . PHP_EOL;
            }
        }
        
        exit;
    }
    
    /**
     * WP-CLI command handler
     */
    public function cli_export($args, $assoc_args) {
        $format = isset($assoc_args['format']) ? $assoc_args['format'] : 'txt';
        
        if (!in_array($format, ['csv', 'txt'])) {
            WP_CLI::error('Invalid format. Use --format=csv or --format=txt');
        }
        
        $urls = $this->gather_all_urls();
        $filename = 'site-urls-' . date('Y-m-d-His') . '.' . $format;
        
        if ($format === 'csv') {
            $fp = fopen($filename, 'w');
            
            // Add BOM for Excel UTF-8 compatibility
            fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($fp, ['URL', 'Path']);
            
            foreach ($urls as $url) {
                $parsed = parse_url($url);
                $path = isset($parsed['path']) ? $parsed['path'] : '/';
                fputcsv($fp, [$url, $path]);
            }
            
            fclose($fp);
        } else {
            file_put_contents($filename, implode(PHP_EOL, $urls));
        }
        
        WP_CLI::success("Exported " . count($urls) . " URLs to " . $filename);
    }
}

// Initialize the plugin
add_action('plugins_loaded', function() {
    WP_URL_Exporter::get_instance();
});
