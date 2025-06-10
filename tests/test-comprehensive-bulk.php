<?php
/**
 * Comprehensive Bulk Translation Test
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== COMPREHENSIVE BULK TRANSLATION TEST ===\n\n";

// WordPress Bootstrap
require_once('../../../wp-config.php');

// Include plugin
include_once 'elementor-inline-translate.php';

if (!class_exists('Elementor_Inline_Translate')) {
    echo "Plugin class not found\n";
    exit;
}

$plugin = new Elementor_Inline_Translate();

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
echo "Testing bulk translation on: {$page->post_title} (ID: {$page->ID})\n";
echo "Target language: EN-GB (Danish to English)\n\n";

// Get Elementor data
$elementor_data = get_post_meta($page->ID, '_elementor_data', true);
if (empty($elementor_data)) {
    echo "No Elementor data found\n";
    exit;
}

$elements = json_decode($elementor_data, true);
if (!is_array($elements)) {
    echo "Invalid Elementor data format\n";
    exit;
}

echo "Total top-level elements: " . count($elements) . "\n";

// Find translatable elements
echo "Finding translatable elements...\n";
$translatable_elements = $plugin->find_translatable_elements($elements);
echo "Found " . count($translatable_elements) . " translatable elements\n\n";

if (count($translatable_elements) === 0) {
    echo "No translatable elements found. Test failed.\n";
    exit;
}

// Test bulk translation on first 10 elements to avoid hitting API limits
$test_elements = array_slice($translatable_elements, 0, 10);
echo "Testing translation on first " . count($test_elements) . " elements:\n\n";

$success_count = 0;
$error_count = 0;
$results = [];

foreach ($test_elements as $i => $element) {
    echo "=== Element " . ($i + 1) . " ===\n";
    echo "Type: " . $element['widgetType'] . "\n";
    echo "ID: " . $element['id'] . "\n";
    
    // Extract text
    $original_text = $plugin->extract_text_from_element($element['element_data']);
    echo "Original: " . substr($original_text, 0, 80) . (strlen($original_text) > 80 ? '...' : '') . "\n";
    
    if (empty($original_text)) {
        echo "Status: SKIPPED (no text)\n\n";
        continue;
    }
    
    try {
        // Translate the text
        $translated_text = $plugin->translate_text($original_text, 'EN-GB');
        
        if ($translated_text && $translated_text !== $original_text) {
            echo "Translated: " . substr($translated_text, 0, 80) . (strlen($translated_text) > 80 ? '...' : '') . "\n";
            echo "Status: SUCCESS\n";
            $success_count++;
            
            $results[] = [
                'id' => $element['id'],
                'type' => $element['widgetType'],
                'original' => substr($original_text, 0, 100),
                'translated' => substr($translated_text, 0, 100),
                'success' => true
            ];
        } else {
            echo "Status: FAILED (no translation or same text)\n";
            $error_count++;
            
            $results[] = [
                'id' => $element['id'],
                'type' => $element['widgetType'],
                'original' => substr($original_text, 0, 100),
                'error' => 'Translation failed or returned same text',
                'success' => false
            ];
        }
    } catch (Exception $e) {
        echo "Status: ERROR (" . $e->getMessage() . ")\n";
        $error_count++;
        
        $results[] = [
            'id' => $element['id'],
            'type' => $element['widgetType'],
            'original' => substr($original_text, 0, 100),
            'error' => $e->getMessage(),
            'success' => false
        ];
    }
    
    echo "\n";
    
    // Small delay to avoid hitting API rate limits
    sleep(1);
}

// Summary
echo "=== BULK TRANSLATION SUMMARY ===\n";
echo "Total elements tested: " . count($test_elements) . "\n";
echo "Successful translations: $success_count\n";
echo "Failed translations: $error_count\n";
echo "Success rate: " . round(($success_count / count($test_elements)) * 100, 1) . "%\n\n";

if ($success_count > 0) {
    echo "🎉 BULK TRANSLATION IS WORKING! 🎉\n";
    echo "The plugin successfully detected and translated Elementor elements.\n\n";
    
    echo "Sample successful translations:\n";
    foreach (array_filter($results, function($r) { return $r['success']; }) as $i => $result) {
        if ($i >= 3) break; // Show first 3 successful translations
        echo "- " . $result['type'] . ": \"" . $result['original'] . "\" → \"" . $result['translated'] . "\"\n";
    }
} else {
    echo "❌ BULK TRANSLATION FAILED\n";
    echo "No successful translations were completed.\n";
}

echo "\nTest completed.\n";
?>
