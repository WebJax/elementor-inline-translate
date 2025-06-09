<?php
/**
 * Direct test for bulk translation debugging
 */

// Load WordPress
require_once('../../../wp-load.php');

// Check if user is logged in and has admin privileges
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    die('Access denied. Please log in as an administrator.');
}

echo "<h1>Bulk Translation Debug Test</h1>\n";

// Test page ID (front page)
$page_id = get_option('page_on_front', 401);
echo "<p>Testing page ID: $page_id</p>\n";

// Check if page has Elementor data
$elementor_data = get_post_meta($page_id, '_elementor_data', true);
if (empty($elementor_data)) {
    echo "<p style='color: red;'>❌ No Elementor data found for page $page_id</p>\n";
    
    // Try to find a page that does have Elementor data
    global $wpdb;
    $elementor_pages = $wpdb->get_results(
        "SELECT post_id, meta_value FROM {$wpdb->postmeta} 
         WHERE meta_key = '_elementor_data' 
         AND meta_value != '' 
         AND meta_value != '[]' 
         LIMIT 5"
    );
    
    if ($elementor_pages) {
        echo "<h3>Pages with Elementor data:</h3>\n";
        foreach ($elementor_pages as $page) {
            echo "<p>Page ID: {$page->post_id} (data length: " . strlen($page->meta_value) . " chars)</p>\n";
        }
        
        // Use the first one for testing
        $page_id = $elementor_pages[0]->post_id;
        echo "<p style='color: blue;'>🔄 Using page ID $page_id for testing instead</p>\n";
    } else {
        echo "<p style='color: red;'>❌ No pages found with Elementor data</p>\n";
        exit;
    }
}

echo "<h3>Triggering bulk translation...</h3>\n";

// Simulate the AJAX request
$_POST['nonce'] = wp_create_nonce('eit_translate_nonce');
$_POST['page_id'] = $page_id;
$_POST['target_language'] = 'DE';

// Get the plugin instance
$plugin = Elementor_Inline_Translate::instance();

// Call the bulk translation handler directly
ob_start();
try {
    $plugin->handle_translate_page_bulk_ajax();
} catch (Exception $e) {
    echo "<p style='color: red;'>Exception: " . $e->getMessage() . "</p>\n";
}
$output = ob_get_clean();

echo "<h3>AJAX Response:</h3>\n";
echo "<pre>" . htmlspecialchars($output) . "</pre>\n";

echo "<h3>Check debug.log for detailed debugging info</h3>\n";
echo "<p><a href='javascript:history.back()'>← Back</a></p>\n";
