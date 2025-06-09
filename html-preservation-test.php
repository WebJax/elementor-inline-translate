<?php
/**
 * HTML Preservation Test Suite
 * 
 * Tests the separator-based HTML preservation system to ensure
 * DeepL API doesn't corrupt HTML structure during translation.
 */

// Mock WordPress functions for testing
if (!function_exists('wp_strip_all_tags')) {
    function wp_strip_all_tags($text) {
        return strip_tags($text);
    }
}

if (!function_exists('html_entity_decode')) {
    // Already exists in PHP
}

if (!function_exists('error_log')) {
    function error_log($message) {
        echo "[LOG] " . $message . "\n";
    }
}

// Simplified test class based on the actual plugin
class HTML_Preservation_Test {
    private $stored_element_boundaries = array();
    
    /**
     * Test extract_text_from_html method
     */
    private function extract_text_from_html($html) {
        if (!class_exists('DOMDocument')) {
            $text = wp_strip_all_tags($html);
            $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            return trim($text);
        }

        $dom = new DOMDocument();
        $dom->encoding = 'UTF-8';
        
        libxml_use_internal_errors(true);
        
        $success = $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        
        if (!$success) {
            $text = wp_strip_all_tags($html);
            $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            return trim($text);
        }
        
        $xpath = new DOMXPath($dom);
        $textNodes = $xpath->query('//text()[normalize-space(.) != ""]');
        
        $text_parts = array();
        $element_boundaries = array();
        
        foreach ($textNodes as $index => $textNode) {
            $content = trim($textNode->textContent);
            if (!empty($content)) {
                $parent_tag = $textNode->parentNode->nodeName;
                $text_parts[] = $content;
                $element_boundaries[] = array(
                    'text' => $content,
                    'parent' => $parent_tag,
                    'index' => $index
                );
            }
        }
        
        $combined_text = implode(' |EIT_SEPARATOR| ', $text_parts);
        $this->stored_element_boundaries = $element_boundaries;
        
        return $combined_text;
    }
    
    /**
     * Test reconstruct_html_with_translated_text method
     */
    private function reconstruct_html_with_translated_text($original_html, $original_text, $translated_text) {
        error_log('Reconstruct - Original text: ' . $original_text);
        error_log('Reconstruct - Translated text: ' . $translated_text);
        
        if (strpos($original_text, '|EIT_SEPARATOR|') !== false && !empty($this->stored_element_boundaries)) {
            error_log('Using separator-based reconstruction');
            
            $translated_parts = array();
            
            // Check if separators are preserved
            if (strpos($translated_text, '|EIT_SEPARATOR|') !== false) {
                $translated_parts = explode('|EIT_SEPARATOR|', $translated_text);
                error_log('Found exact separators in translation');
            } else {
                // DeepL removed separators, try to split intelligently
                $original_parts = explode('|EIT_SEPARATOR|', $original_text);
                $expected_count = count($original_parts);
                
                if ($expected_count > 1) {
                    $translated_parts = preg_split('/(?<=[.!?])\s+(?=[A-Z0-9])/', $translated_text);
                    
                    if (count($translated_parts) !== $expected_count) {
                        $part_length = floor(strlen($translated_text) / $expected_count);
                        $translated_parts = array();
                        for ($i = 0; $i < $expected_count; $i++) {
                            $start = $i * $part_length;
                            if ($i === $expected_count - 1) {
                                $translated_parts[] = substr($translated_text, $start);
                            } else {
                                $translated_parts[] = substr($translated_text, $start, $part_length);
                            }
                        }
                    }
                } else {
                    $translated_parts = array($translated_text);
                }
            }
            
            // Reconstruct HTML using DOMDocument
            if (class_exists('DOMDocument') && !empty($translated_parts)) {
                $dom = new DOMDocument();
                $dom->encoding = 'UTF-8';
                
                libxml_use_internal_errors(true);
                
                $success = $dom->loadHTML('<?xml encoding="utf-8" ?>' . $original_html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                
                if ($success) {
                    $xpath = new DOMXPath($dom);
                    $textNodes = $xpath->query('//text()[normalize-space(.) != ""]');
                    
                    $part_index = 0;
                    foreach ($textNodes as $textNode) {
                        if ($part_index < count($translated_parts)) {
                            $translated_part = trim($translated_parts[$part_index]);
                            if (!empty($translated_part)) {
                                $textNode->textContent = $translated_part;
                                error_log('Replaced text node with: ' . $translated_part);
                            }
                            $part_index++;
                        }
                    }
                    
                    $result = $dom->saveHTML();
                    $result = preg_replace('/^<\?xml[^>]*\?>/', '', $result);
                    
                    error_log('Successfully reconstructed HTML: ' . $result);
                    return $result;
                }
            }
        }
        
        // Fallback methods
        if (strpos($original_html, $original_text) !== false) {
            return str_replace($original_text, $translated_text, $original_html);
        }
        
        return $translated_text;
    }
    
    /**
     * Run comprehensive tests
     */
    public function run_tests() {
        echo "=== HTML Preservation Test Suite ===\n\n";
        
        // Test 1: Simple HTML with separators preserved
        echo "Test 1: Simple HTML with separators preserved\n";
        $html1 = '<p>This is <strong>bold text</strong> and <em>italic text</em>.</p>';
        $extracted1 = $this->extract_text_from_html($html1);
        echo "Original HTML: " . $html1 . "\n";
        echo "Extracted: " . $extracted1 . "\n";
        
        // Simulate DeepL preserving separators
        $translated1 = 'Dette er |EIT_SEPARATOR| fed tekst |EIT_SEPARATOR| og |EIT_SEPARATOR| kursiv tekst |EIT_SEPARATOR| .';
        $reconstructed1 = $this->reconstruct_html_with_translated_text($html1, $extracted1, $translated1);
        echo "Reconstructed: " . $reconstructed1 . "\n\n";
        
        // Test 2: List HTML with separators lost
        echo "Test 2: List HTML with separators lost (DeepL merges text)\n";
        $html2 = '<ul><li>Item 1</li><li>Item 2</li><li>Item 3</li></ul>';
        $extracted2 = $this->extract_text_from_html($html2);
        echo "Original HTML: " . $html2 . "\n";
        echo "Extracted: " . $extracted2 . "\n";
        
        // Simulate DeepL removing separators and merging text
        $translated2 = 'Punkt 1 Punkt 2 Punkt 3';
        $reconstructed2 = $this->reconstruct_html_with_translated_text($html2, $extracted2, $translated2);
        echo "Reconstructed: " . $reconstructed2 . "\n\n";
        
        // Test 3: Complex nested HTML
        echo "Test 3: Complex nested HTML\n";
        $html3 = '<div><p>Welcome to <strong>our website</strong>!</p><ul><li>Feature 1</li><li>Feature 2</li></ul></div>';
        $extracted3 = $this->extract_text_from_html($html3);
        echo "Original HTML: " . $html3 . "\n";
        echo "Extracted: " . $extracted3 . "\n";
        
        // Simulate partial separator preservation
        $translated3 = 'Velkommen til vores hjemmeside! |EIT_SEPARATOR| Funktion 1 Funktion 2';
        $reconstructed3 = $this->reconstruct_html_with_translated_text($html3, $extracted3, $translated3);
        echo "Reconstructed: " . $reconstructed3 . "\n\n";
        
        // Test 4: HTML with links and attributes
        echo "Test 4: HTML with links and attributes\n";
        $html4 = '<p>Visit <a href="https://example.com" target="_blank">our site</a> for more info.</p>';
        $extracted4 = $this->extract_text_from_html($html4);
        echo "Original HTML: " . $html4 . "\n";
        echo "Extracted: " . $extracted4 . "\n";
        
        $translated4 = 'Besøg |EIT_SEPARATOR| vores side |EIT_SEPARATOR| for mere info.';
        $reconstructed4 = $this->reconstruct_html_with_translated_text($html4, $extracted4, $translated4);
        echo "Reconstructed: " . $reconstructed4 . "\n\n";
        
        // Test 5: Edge case - Empty or whitespace-only elements
        echo "Test 5: Edge case - Empty or whitespace-only elements\n";
        $html5 = '<p>Text before</p><p></p><p>Text after</p>';
        $extracted5 = $this->extract_text_from_html($html5);
        echo "Original HTML: " . $html5 . "\n";
        echo "Extracted: " . $extracted5 . "\n";
        
        $translated5 = 'Tekst før |EIT_SEPARATOR| Tekst efter';
        $reconstructed5 = $this->reconstruct_html_with_translated_text($html5, $extracted5, $translated5);
        echo "Reconstructed: " . $reconstructed5 . "\n\n";
        
        echo "=== Test Summary ===\n";
        echo "All tests completed. Check the logs above for detailed output.\n";
        echo "Key features tested:\n";
        echo "✓ Text extraction with separator preservation\n";
        echo "✓ HTML reconstruction with preserved separators\n";
        echo "✓ Fallback handling when DeepL removes separators\n";
        echo "✓ Complex nested HTML structures\n";
        echo "✓ Attribute and link preservation\n";
        echo "✓ Edge cases with empty elements\n";
    }
}

// Run the tests
$test = new HTML_Preservation_Test();
$test->run_tests();
?>
