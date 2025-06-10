<?php
/**
 * Simple Element Detection Test
 */

// WordPress Bootstrap
require_once('../../../wp-config.php');

echo "<h1>Simple Element Detection Test</h1>\n";

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

// Get Elementor data
$elementor_data = get_post_meta($page->ID, '_elementor_data', true);
if (empty($elementor_data)) {
    echo "No Elementor data found\n";
    exit;
}

$elements = json_decode($elementor_data, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "Invalid JSON in Elementor data\n";
    exit;
}

echo "Raw Elementor data loaded successfully\n";
echo "Total top-level elements: " . count($elements) . "\n";

// Check if our plugin is available
if (!class_exists('Elementor_Inline_Translate')) {
    // Include plugin file
    include_once 'elementor-inline-translate.php';
}

if (class_exists('Elementor_Inline_Translate')) {
    echo "Plugin class found\n";
    
    $plugin = new Elementor_Inline_Translate();
    
    // Test the method directly
    $translatable_elements = $plugin->find_translatable_elements($elements);
    
    echo "Translatable elements found: " . count($translatable_elements) . "\n";
    
    if (count($translatable_elements) > 0) {
        echo "SUCCESS: Element detection is working!\n";
        
        // Show first element details
        $first_element = $translatable_elements[0];
        echo "First element type: " . $first_element['widgetType'] . "\n";
        echo "First element ID: " . $first_element['id'] . "\n";
        
        // Try to extract text
        $text = $plugin->extract_text_from_element($first_element);
        if (!empty($text)) {
            echo "Text extraction successful:\n";
            foreach ($text as $field => $value) {
                echo "- $field: " . substr($value, 0, 50) . "\n";
            }
        } else {
            echo "No text extracted from first element\n";
        }
        
    } else {
        echo "PROBLEM: No translatable elements found\n";
        
        // Debug: Show first element structure
        if (!empty($elements)) {
            echo "Debug - First element structure:\n";
            print_r(array_slice($elements[0], 0, 10, true));
        }
    }
    
} else {
    echo "Plugin class not found\n";
}

echo "Test completed\n";
?>
