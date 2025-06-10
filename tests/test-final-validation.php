<?php
/**
 * Final Validation Test for Elementor Inline Translate Plugin
 * Tests both element detection and actual translation functionality
 */

// WordPress Bootstrap
require_once('../../../wp-config.php');

class FinalValidationTest {
    
    public function run_comprehensive_test() {
        echo "<h1>Final Validation Test - Elementor Inline Translate</h1>\n";
        echo "<hr>\n";
        
        // Test 1: Element Detection
        $this->test_element_detection();
        
        // Test 2: Text Extraction
        $this->test_text_extraction();
        
        // Test 3: Translation API
        $this->test_translation_api();
        
        // Test 4: Full Bulk Translation
        $this->test_full_bulk_translation();
        
        echo "<h2>Test Summary</h2>\n";
        echo "<p>All tests completed. Check individual results above.</p>\n";
    }
    
    private function test_element_detection() {
        echo "<h2>Test 1: Element Detection</h2>\n";
        
        // Get a page with Elementor content
        $pages = get_posts([
            'post_type' => 'page',
            'meta_key' => '_elementor_data',
            'posts_per_page' => 1,
            'post_status' => 'publish'
        ]);
        
        if (empty($pages)) {
            echo "<p style='color: red;'>❌ No Elementor pages found</p>\n";
            return;
        }
        
        $page = $pages[0];
        echo "<p><strong>Testing page:</strong> {$page->post_title} (ID: {$page->ID})</p>\n";
        
        // Get Elementor data
        $elementor_data = get_post_meta($page->ID, '_elementor_data', true);
        if (empty($elementor_data)) {
            echo "<p style='color: red;'>❌ No Elementor data found</p>\n";
            return;
        }
        
        $elements = json_decode($elementor_data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "<p style='color: red;'>❌ Invalid JSON in Elementor data</p>\n";
            return;
        }
        
        // Use the plugin's element detection function
        if (class_exists('Elementor_Inline_Translate')) {
            $plugin = new Elementor_Inline_Translate();
            $translatable_elements = $plugin->find_translatable_elements($elements);
            
            echo "<p><strong>Total elements found:</strong> " . count($translatable_elements) . "</p>\n";
            
            if (count($translatable_elements) > 0) {
                echo "<p style='color: green;'>✅ Element detection working!</p>\n";
                
                // Show first few elements
                echo "<h3>Sample detected elements:</h3>\n";
                foreach (array_slice($translatable_elements, 0, 3) as $i => $element) {
                    echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 5px;'>\n";
                    echo "<strong>Element " . ($i + 1) . ":</strong> {$element['widgetType']}<br>\n";
                    echo "<strong>Element ID:</strong> {$element['id']}<br>\n";
                    
                    // Extract and show text
                    $text = $plugin->extract_text_from_element($element);
                    if (!empty($text)) {
                        echo "<strong>Extracted text:</strong><br>\n";
                        foreach ($text as $field => $value) {
                            echo "- <em>$field:</em> " . substr($value, 0, 100) . (strlen($value) > 100 ? '...' : '') . "<br>\n";
                        }
                    }
                    echo "</div>\n";
                }
            } else {
                echo "<p style='color: red;'>❌ No translatable elements found</p>\n";
            }
        } else {
            echo "<p style='color: red;'>❌ Plugin class not found</p>\n";
        }
    }
    
    private function test_text_extraction() {
        echo "<h2>Test 2: Text Extraction</h2>\n";
        
        // Create sample elements to test extraction
        $test_elements = [
            [
                'widgetType' => 'heading',
                'settings' => ['title' => 'Hello World']
            ],
            [
                'widgetType' => 'text-editor',
                'settings' => ['editor' => '<p>This is a test paragraph.</p>']
            ],
            [
                'widgetType' => 'button',
                'settings' => ['text' => 'Click Me']
            ],
            [
                'widgetType' => 'icon-box',
                'settings' => [
                    'title_text' => 'Icon Box Title',
                    'description_text' => 'Icon box description'
                ]
            ]
        ];
        
        if (class_exists('Elementor_Inline_Translate')) {
            $plugin = new Elementor_Inline_Translate();
            
            foreach ($test_elements as $i => $element) {
                echo "<h3>Testing {$element['widgetType']} extraction:</h3>\n";
                $extracted = $plugin->extract_text_from_element($element);
                
                if (!empty($extracted)) {
                    echo "<p style='color: green;'>✅ Extraction successful:</p>\n";
                    foreach ($extracted as $field => $text) {
                        echo "<p>- <strong>$field:</strong> $text</p>\n";
                    }
                } else {
                    echo "<p style='color: red;'>❌ No text extracted</p>\n";
                }
            }
        }
    }
    
    private function test_translation_api() {
        echo "<h2>Test 3: Translation API</h2>\n";
        
        $test_text = "Hello World";
        $target_language = "DA"; // Danish
        
        if (class_exists('Elementor_Inline_Translate')) {
            $plugin = new Elementor_Inline_Translate();
            
            echo "<p><strong>Testing translation:</strong> '$test_text' → $target_language</p>\n";
            
            $translated = $plugin->translate_text($test_text, $target_language);
            
            if ($translated && $translated !== $test_text) {
                echo "<p style='color: green;'>✅ Translation successful: '$translated'</p>\n";
            } else {
                echo "<p style='color: red;'>❌ Translation failed or returned same text</p>\n";
                echo "<p>Returned: " . var_export($translated, true) . "</p>\n";
            }
        }
    }
    
    private function test_full_bulk_translation() {
        echo "<h2>Test 4: Full Bulk Translation Simulation</h2>\n";
        
        // Get a page with Elementor content
        $pages = get_posts([
            'post_type' => 'page',
            'meta_key' => '_elementor_data',
            'posts_per_page' => 1,
            'post_status' => 'publish'
        ]);
        
        if (empty($pages)) {
            echo "<p style='color: red;'>❌ No Elementor pages found</p>\n";
            return;
        }
        
        $page = $pages[0];
        echo "<p><strong>Testing bulk translation on:</strong> {$page->post_title} (ID: {$page->ID})</p>\n";
        
        if (class_exists('Elementor_Inline_Translate')) {
            $plugin = new Elementor_Inline_Translate();
            
            // Simulate the AJAX request data
            $request_data = [
                'post_id' => $page->ID,
                'target_language' => 'DA',
                'nonce' => wp_create_nonce('eit_bulk_translate_nonce')
            ];
            
            echo "<p>Simulating bulk translation request...</p>\n";
            
            // Set up the simulation environment
            $_POST = $request_data;
            $_REQUEST = $request_data;
            
            // Capture output
            ob_start();
            
            try {
                // This would normally be called via AJAX
                $result = $plugin->handle_bulk_translate();
                $output = ob_get_clean();
                
                if ($result) {
                    echo "<p style='color: green;'>✅ Bulk translation completed successfully</p>\n";
                } else {
                    echo "<p style='color: red;'>❌ Bulk translation failed</p>\n";
                }
                
                if ($output) {
                    echo "<p><strong>Output:</strong></p>\n<pre>$output</pre>\n";
                }
                
            } catch (Exception $e) {
                ob_end_clean();
                echo "<p style='color: red;'>❌ Exception during bulk translation: " . $e->getMessage() . "</p>\n";
            }
        }
    }
}

// Run the test
$test = new FinalValidationTest();
$test->run_comprehensive_test();
?>
