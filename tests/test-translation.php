<?php
/**
 * Test file for Elementor Inline Translate functionality
 * Access this file at: https://llp.test/wp-content/plugins/elementor-inline-translate/test-translation.php
 */

// Ensure WordPress is loaded
if (!defined('ABSPATH')) {
    // Load WordPress
    require_once('../../../wp-load.php');
}

// Check if user is logged in and has admin privileges
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    wp_die('Access denied. Please log in as an administrator.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Elementor Inline Translate - Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .test-section { background: #f9f9f9; padding: 20px; margin: 20px 0; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        pre { background: #eee; padding: 10px; border-radius: 3px; overflow-x: auto; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>🧪 Elementor Inline Translate - Test</h1>
    
    <div class="test-section">
        <h2>Plugin Status</h2>
        <?php
        echo '<p class="info">Plugin Version: ' . EIT_PLUGIN_VERSION . '</p>';
        
        if (class_exists('Elementor_Inline_Translate')) {
            echo '<p class="success">✅ Plugin class loaded</p>';
            $instance = Elementor_Inline_Translate::instance();
            
            $methods = ['handle_translate_text_ajax', 'handle_translate_page_bulk_ajax', 'core_translate_text'];
            foreach ($methods as $method) {
                if (method_exists($instance, $method)) {
                    echo '<p class="success">✅ Method ' . $method . ' exists</p>';
                } else {
                    echo '<p class="error">❌ Method ' . $method . ' missing</p>';
                }
            }
        } else {
            echo '<p class="error">❌ Plugin class not found</p>';
        }
        
        // Check dependencies
        if (defined('ELEMENTOR_VERSION')) {
            echo '<p class="success">✅ Elementor active (v' . ELEMENTOR_VERSION . ')</p>';
        } else {
            echo '<p class="error">❌ Elementor not found</p>';
        }
        
        if (function_exists('pll_default_language')) {
            echo '<p class="success">✅ PolyLang active</p>';
        } else {
            echo '<p class="info">⚠️ PolyLang not active (optional)</p>';
        }
        ?>
    </div>

    <div class="test-section">
        <h2>Translation Test</h2>
        <div id="translation-test">
            <p>Target Language: 
                <select id="target-lang">
                    <option value="DA">🇩🇰 Dansk</option>
                    <option value="DE">🇩🇪 Tysk</option>
                    <option value="EN-GB">🇬🇧 Engelsk</option>
                    <option value="FR">🇫🇷 Fransk</option>
                </select>
            </p>
            <p>Text to translate: <input type="text" id="test-text" value="Hello, how are you today?" style="width: 300px;"></p>
            <button onclick="testTranslation()">Test Single Translation</button>
            <button onclick="testBulkTranslation()">Test Bulk Translation</button>
            <div id="test-results" style="margin-top: 20px;"></div>
        </div>
    </div>

    <div class="test-section">
        <h2>AJAX Endpoints</h2>
        <?php
        global $wp_filter;
        $endpoints = [
            'wp_ajax_eit_translate_text' => 'Single translation',
            'wp_ajax_eit_translate_page_bulk' => 'Bulk translation'
        ];
        
        foreach ($endpoints as $hook => $description) {
            if (isset($wp_filter[$hook])) {
                echo '<p class="success">✅ ' . $description . ' endpoint registered</p>';
            } else {
                echo '<p class="error">❌ ' . $description . ' endpoint not registered</p>';
            }
        }
        ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
        const nonce = '<?php echo wp_create_nonce('eit_translate_nonce'); ?>';
        
        function testTranslation() {
            const text = document.getElementById('test-text').value;
            const targetLang = document.getElementById('target-lang').value;
            const resultsDiv = document.getElementById('test-results');
            
            resultsDiv.innerHTML = '<p class="info">🔄 Testing single translation...</p>';
            
            jQuery.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'eit_translate_text',
                    nonce: nonce,
                    text: text,
                    target_lang: targetLang,
                    element_id: 'test-element',
                    control_name: 'title'
                },
                success: function(response) {
                    console.log('Translation response:', response);
                    if (response.success) {
                        resultsDiv.innerHTML = `
                            <p class="success">✅ Translation successful!</p>
                            <p><strong>Original:</strong> ${text}</p>
                            <p><strong>Translated (${targetLang}):</strong> ${response.data.translated_text}</p>
                        `;
                    } else {
                        resultsDiv.innerHTML = `
                            <p class="error">❌ Translation failed</p>
                            <pre>${JSON.stringify(response, null, 2)}</pre>
                        `;
                    }
                },
                error: function(xhr, status, error) {
                    resultsDiv.innerHTML = `
                        <p class="error">❌ AJAX Error: ${error}</p>
                        <pre>Status: ${status}\nResponse: ${xhr.responseText}</pre>
                    `;
                }
            });
        }
        
        function testBulkTranslation() {
            const targetLang = document.getElementById('target-lang').value;
            const resultsDiv = document.getElementById('test-results');
            
            resultsDiv.innerHTML = '<p class="info">🔄 Testing bulk translation...</p>';
            
            jQuery.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'eit_translate_page_bulk',
                    nonce: nonce,
                    target_language: targetLang,
                    page_id: <?php echo get_option('page_on_front', 1); ?> // Use front page for testing
                },
                success: function(response) {
                    console.log('Bulk translation response:', response);
                    if (response.success) {
                        const data = response.data;
                        resultsDiv.innerHTML = `
                            <p class="success">✅ Bulk translation successful!</p>
                            <p><strong>Total elements:</strong> ${data.total_elements}</p>
                            <p><strong>Successful:</strong> ${data.success_count}</p>
                            <p><strong>Errors:</strong> ${data.error_count}</p>
                            <p><strong>Target language:</strong> ${data.target_language}</p>
                            <details>
                                <summary>View results</summary>
                                <pre>${JSON.stringify(data.results, null, 2)}</pre>
                            </details>
                        `;
                    } else {
                        resultsDiv.innerHTML = `
                            <p class="error">❌ Bulk translation failed</p>
                            <pre>${JSON.stringify(response, null, 2)}</pre>
                        `;
                    }
                },
                error: function(xhr, status, error) {
                    resultsDiv.innerHTML = `
                        <p class="error">❌ AJAX Error: ${error}</p>
                        <pre>Status: ${status}\nResponse: ${xhr.responseText}</pre>
                    `;
                }
            });
        }
    </script>
</body>
</html>
