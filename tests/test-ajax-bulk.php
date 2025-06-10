<?php
/**
 * AJAX Bulk Translation Test
 * Tests the actual WordPress AJAX handler
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== AJAX BULK TRANSLATION TEST ===\n\n";

// WordPress Bootstrap
require_once('../../../wp-config.php');

// Include plugin
include_once 'elementor-inline-translate.php';

if (!class_exists('Elementor_Inline_Translate')) {
    echo "Plugin class not found\n";
    exit;
}

// Get a page with Elementor content
$pages = get_posts([
    'post_type' => 'page',
    'meta_key' => '_elementor_data',
    'posts_per_page' => 1,
    'post_status' => 'publish'
]);

if (empty($pages)) {
    echo "No Elementor pages found\n";
    exit;
}

$page = $pages[0];
echo "Testing AJAX bulk translation on: {$page->post_title} (ID: {$page->ID})\n";

// Simulate AJAX request
$_POST = [
    'page_id' => $page->ID,
    'target_language' => 'EN-GB',
    'nonce' => wp_create_nonce('eit_translate_nonce')
];

$_REQUEST = $_POST;

// Mock the AJAX environment
define('DOING_AJAX', true);

$plugin = new Elementor_Inline_Translate();

echo "Calling AJAX handler...\n";

// Capture output
ob_start();

try {
    $plugin->handle_translate_page_bulk_ajax();
    $output = ob_get_clean();
    
    echo "AJAX Response:\n";
    echo $output . "\n";
    
    // Try to decode JSON response
    $response = json_decode($output, true);
    if ($response) {
        echo "\nParsed Response:\n";
        if (isset($response['success']) && $response['success']) {
            $data = $response['data'];
            echo "Success: TRUE\n";
            echo "Total elements: " . $data['total_elements'] . "\n";
            echo "Success count: " . $data['success_count'] . "\n";
            echo "Error count: " . $data['error_count'] . "\n";
            echo "Target language: " . $data['target_language'] . "\n";
            
            if ($data['success_count'] > 0) {
                echo "\n🎉 AJAX BULK TRANSLATION IS WORKING! 🎉\n";
                
                echo "\nSample results:\n";
                foreach (array_slice($data['results'], 0, 3) as $i => $result) {
                    if ($result['success']) {
                        echo "- " . $result['type'] . " (" . $result['id'] . "): \"" . substr($result['original'], 0, 50) . "\" → \"" . substr($result['translated'], 0, 50) . "\"\n";
                    }
                }
            } else {
                echo "\n❌ No successful translations\n";
            }
        } else {
            echo "Success: FALSE\n";
            echo "Error: " . (isset($response['data']) ? $response['data'] : 'Unknown error') . "\n";
        }
    } else {
        echo "Could not parse JSON response\n";
    }
    
} catch (Exception $e) {
    ob_end_clean();
    echo "Exception: " . $e->getMessage() . "\n";
} catch (Error $e) {
    ob_end_clean();
    echo "Fatal error: " . $e->getMessage() . "\n";
}

echo "\nAJAX test completed.\n";
?>
