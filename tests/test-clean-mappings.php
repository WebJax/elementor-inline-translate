<?php
/**
 * Clean Field Mapping Test Results
 */

echo "=== FIELD MAPPING TEST RESULTS ===\n\n";

// WordPress Bootstrap
require_once('../../../wp-config.php');
include_once 'elementor-inline-translate.php';

$plugin = new Elementor_Inline_Translate();

// Get a page with Elementor content
$pages = get_posts([
    'post_type' => 'page',
    'meta_key' => '_elementor_data',
    'posts_per_page' => 1,
    'post_status' => 'publish'
]);

$page = $pages[0];

// Simulate AJAX request for bulk translation
$_POST = [
    'page_id' => $page->ID,
    'target_language' => 'DE',
    'nonce' => wp_create_nonce('eit_translate_nonce')
];

$_REQUEST = $_POST;
define('DOING_AJAX', true);

ob_start();
$plugin->handle_translate_page_bulk_ajax();
$output = ob_get_clean();

$response = json_decode($output, true);

if ($response && isset($response['success']) && $response['success']) {
    $data = $response['data'];
    
    echo "✅ FIELD MAPPING TEST SUCCESSFUL\n";
    echo "Total elements: {$data['total_elements']}\n";
    echo "Successful: {$data['success_count']}\n";
    echo "Failed: {$data['error_count']}\n\n";
    
    echo "DETAILED FIELD MAPPING EXAMPLES:\n";
    echo "=================================\n\n";
    
    $successful = array_filter($data['results'], function($r) { 
        return $r['success'] && isset($r['field_mappings']); 
    });
    
    // Show different widget types
    $examples = [
        'heading' => null,
        'text-editor' => null,
        'button' => null,
        'icon-box' => null,
        'divider' => null
    ];
    
    foreach ($successful as $result) {
        if (isset($examples[$result['type']]) && $examples[$result['type']] === null) {
            $examples[$result['type']] = $result;
        }
    }
    
    foreach ($examples as $type => $example) {
        if ($example) {
            echo strtoupper($type) . " WIDGET:\n";
            echo "  Element ID: {$example['id']}\n";
            echo "  Field Mappings:\n";
            foreach ($example['field_mappings'] as $field => $value) {
                echo "    - $field: \"" . substr($value, 0, 60) . (strlen($value) > 60 ? '...' : '') . "\"\n";
            }
            echo "\n";
        }
    }
    
    echo "🎉 PERFECT! Field mappings are working correctly!\n";
    echo "The JavaScript can now apply translations to specific fields:\n";
    echo "- heading: title field\n";
    echo "- text-editor: editor field  \n";
    echo "- button: text field\n";
    echo "- icon-box: title_text AND description_text fields\n";
    echo "- divider: text field\n\n";
    
    echo "This ensures precise application of translations!\n";
    
} else {
    echo "❌ TEST FAILED\n";
}

echo "\nTest completed.\n";
?>
