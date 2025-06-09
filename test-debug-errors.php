<?php
/**
 * Debug Test with Error Reporting
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting debug test with error reporting...\n";

// WordPress Bootstrap
require_once('../../../wp-config.php');

echo "WordPress loaded successfully\n";

// Check if our plugin is available
if (!class_exists('Elementor_Inline_Translate')) {
    echo "Including plugin file...\n";
    include_once 'elementor-inline-translate.php';
}

if (class_exists('Elementor_Inline_Translate')) {
    echo "Plugin class found\n";
    
    try {
        $plugin = new Elementor_Inline_Translate();
        echo "Plugin instance created successfully\n";
        
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
            echo "JSON decode error: " . json_last_error_msg() . "\n";
            exit;
        }
        
        echo "Raw Elementor data loaded successfully\n";
        echo "Total top-level elements: " . count($elements) . "\n";
        
        // Test the method directly
        echo "Calling find_translatable_elements...\n";
        $translatable_elements = $plugin->find_translatable_elements($elements);
        
        echo "Method completed successfully\n";
        echo "Translatable elements found: " . count($translatable_elements) . "\n";
        
        if (count($translatable_elements) > 0) {
            echo "SUCCESS: Element detection is working!\n";
            
            // Show details of first few elements
            foreach (array_slice($translatable_elements, 0, 3) as $i => $element) {
                echo "Element " . ($i + 1) . ":\n";
                echo "  Type: " . $element['widgetType'] . "\n";
                echo "  ID: " . $element['id'] . "\n";
                
                // Try to extract text
                $text = $plugin->extract_text_from_element($element);
                if (!empty($text)) {
                    echo "  Text fields:\n";
                    foreach ($text as $field => $value) {
                        echo "    $field: " . substr(strip_tags($value), 0, 50) . "\n";
                    }
                } else {
                    echo "  No text extracted\n";
                }
                echo "\n";
            }
        } else {
            echo "PROBLEM: No translatable elements found\n";
        }
        
    } catch (Exception $e) {
        echo "Exception caught: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    } catch (Error $e) {
        echo "Fatal error caught: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    }
    
} else {
    echo "Plugin class not found\n";
}

echo "Test completed\n";
?>
