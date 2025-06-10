<?php
/**
 * Text Extraction Debug Test
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing text extraction...\n";

// WordPress Bootstrap
require_once('../../../wp-config.php');

// Include plugin
include_once 'elementor-inline-translate.php';

if (class_exists('Elementor_Inline_Translate')) {
    $plugin = new Elementor_Inline_Translate();
    
    // Get a page with Elementor content
    $pages = get_posts([
        'post_type' => 'page',
        'meta_key' => '_elementor_data',
        'posts_per_page' => 1,
        'post_status' => 'publish'
    ]);
    
    if (!empty($pages)) {
        $page = $pages[0];
        echo "Testing page: {$page->post_title} (ID: {$page->ID})\n";
        
        // Get Elementor data
        $elementor_data = get_post_meta($page->ID, '_elementor_data', true);
        $elements = json_decode($elementor_data, true);
        
        // Find translatable elements
        $translatable_elements = $plugin->find_translatable_elements($elements);
        echo "Found " . count($translatable_elements) . " translatable elements\n\n";
        
        // Test text extraction on first few elements
        foreach (array_slice($translatable_elements, 0, 5) as $i => $element) {
            echo "=== Element " . ($i + 1) . " ===\n";
            echo "Type: " . $element['widgetType'] . "\n";
            echo "ID: " . $element['id'] . "\n";
            
            // Show element settings structure
            if (isset($element['element_data']['settings'])) {
                $settings = $element['element_data']['settings'];
                echo "Available settings keys: " . implode(', ', array_keys($settings)) . "\n";
                
                // Show a few setting values
                foreach (array_slice($settings, 0, 3, true) as $key => $value) {
                    if (is_string($value)) {
                        echo "  $key: " . substr($value, 0, 100) . "\n";
                    } elseif (is_array($value)) {
                        echo "  $key: [array with " . count($value) . " items]\n";
                    } else {
                        echo "  $key: " . gettype($value) . "\n";
                    }
                }
            } else {
                echo "No settings found\n";
            }
            
            // Try to extract text
            $text = $plugin->extract_text_from_element($element['element_data']);
            if (!empty($text)) {
                echo "Extracted text: " . substr($text, 0, 100) . "\n";
            } else {
                echo "No text extracted\n";
            }
            
            echo "\n";
        }
        
        // Test translation on first element with text
        foreach ($translatable_elements as $element) {
            $text = $plugin->extract_text_from_element($element['element_data']);
            if (!empty($text)) {
                echo "=== Translation Test ===\n";
                echo "Original text: " . substr($text, 0, 50) . "\n";
                
                $translated = $plugin->translate_text($text, 'EN-GB');
                if ($translated) {
                    echo "Translated: " . substr($translated, 0, 50) . "\n";
                } else {
                    echo "Translation failed\n";
                }
                break; // Test only first element with text
            }
        }
    }
} else {
    echo "Plugin class not found\n";
}

echo "\nTest completed\n";
?>
