<?php
/**
 * Test Updated Bulk Translation with Field Mappings
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== TESTING UPDATED BULK TRANSLATION ===\n\n";

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
echo "Testing page: {$page->post_title} (ID: {$page->ID})\n";

// Simulate AJAX request for bulk translation
$_POST = [
    'page_id' => $page->ID,
    'target_language' => 'DE',
    'nonce' => wp_create_nonce('eit_translate_nonce')
];

$_REQUEST = $_POST;
define('DOING_AJAX', true);

$plugin = new Elementor_Inline_Translate();

echo "Calling updated bulk translation handler...\n";

// Capture output
ob_start();
$plugin->handle_translate_page_bulk_ajax();
$output = ob_get_clean();

echo "Raw response:\n";
echo substr($output, 0, 500) . (strlen($output) > 500 ? '...' : '') . "\n\n";

// Parse response
$response = json_decode($output, true);

if ($response && isset($response['success']) && $response['success']) {
    $data = $response['data'];
    
    echo "✅ BULK TRANSLATION SUCCESSFUL\n";
    echo "Total elements: {$data['total_elements']}\n";
    echo "Successful: {$data['success_count']}\n";
    echo "Failed: {$data['error_count']}\n\n";
    
    echo "SAMPLE RESULTS WITH FIELD MAPPINGS:\n";
    echo "===================================\n";
    
    $successful = array_filter($data['results'], function($r) { return $r['success']; });
    
    foreach (array_slice($successful, 0, 3) as $i => $result) {
        echo ($i + 1) . ". Element ID: {$result['id']} (Type: {$result['type']})\n";
        echo "   Original: " . substr($result['original'], 0, 60) . "...\n";
        echo "   Translated: " . substr($result['translated'], 0, 60) . "...\n";
        
        if (isset($result['field_mappings'])) {
            echo "   Field Mappings:\n";
            foreach ($result['field_mappings'] as $field => $value) {
                echo "     - $field: " . substr($value, 0, 40) . (strlen($value) > 40 ? '...' : '') . "\n";
            }
        } else {
            echo "   No field mappings (simple translation)\n";
        }
        echo "\n";
    }
    
    echo "🎉 Field mapping functionality added!\n";
    echo "Now JavaScript can apply translations to specific fields.\n";
    
} else {
    echo "❌ BULK TRANSLATION FAILED\n";
    if (isset($response['data'])) {
        echo "Error: " . $response['data'] . "\n";
    }
}

echo "\nTest completed.\n";
?>
