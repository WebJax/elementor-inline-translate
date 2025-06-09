<!DOCTYPE html>
<html>
<head>
    <title>Bulk Translation AJAX Test</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <h1>Bulk Translation AJAX Test</h1>
    
    <div>
        <h3>Test Configuration</h3>
        <p>Page ID: <input type="number" id="page-id" value="401" /></p>
        <p>Target Language: 
            <select id="target-lang">
                <option value="EN-GB">English</option>
                <option value="DE">German</option>
                <option value="DA">Danish</option>
            </select>
        </p>
        <button onclick="testBulkTranslation()">Test Bulk Translation</button>
    </div>
    
    <div id="results" style="margin-top: 20px;"></div>
    
    <script>
        function testBulkTranslation() {
            const pageId = document.getElementById('page-id').value;
            const targetLang = document.getElementById('target-lang').value;
            const resultsDiv = document.getElementById('results');
            
            resultsDiv.innerHTML = '<p>🔄 Testing bulk translation...</p>';
            
            jQuery.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'eit_translate_page_bulk',
                    nonce: '<?php echo wp_create_nonce('eit_translate_nonce'); ?>',
                    page_id: pageId,
                    target_language: targetLang
                },
                success: function(response) {
                    console.log('Response:', response);
                    
                    if (response.success) {
                        const data = response.data;
                        resultsDiv.innerHTML = `
                            <div style="background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;">
                                <h3 style="color: #155724;">✅ Bulk Translation Successful!</h3>
                                <p><strong>Total Elements:</strong> ${data.total_elements}</p>
                                <p><strong>Successful:</strong> ${data.success_count}</p>
                                <p><strong>Errors:</strong> ${data.error_count}</p>
                                <p><strong>Target Language:</strong> ${data.target_language}</p>
                                
                                <details style="margin-top: 15px;">
                                    <summary>View Detailed Results</summary>
                                    <pre style="background: #f8f9fa; padding: 10px; margin-top: 10px; overflow: auto; max-height: 400px;">${JSON.stringify(data, null, 2)}</pre>
                                </details>
                            </div>
                        `;
                    } else {
                        resultsDiv.innerHTML = `
                            <div style="background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;">
                                <h3 style="color: #721c24;">❌ Bulk Translation Failed</h3>
                                <p><strong>Error:</strong> ${response.data || 'Unknown error'}</p>
                                <details style="margin-top: 15px;">
                                    <summary>View Full Response</summary>
                                    <pre style="background: #f8f9fa; padding: 10px; margin-top: 10px;">${JSON.stringify(response, null, 2)}</pre>
                                </details>
                            </div>
                        `;
                    }
                },
                error: function(xhr, status, error) {
                    resultsDiv.innerHTML = `
                        <div style="background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;">
                            <h3 style="color: #721c24;">❌ AJAX Error</h3>
                            <p><strong>Status:</strong> ${status}</p>
                            <p><strong>Error:</strong> ${error}</p>
                            <p><strong>Response:</strong> ${xhr.responseText}</p>
                        </div>
                    `;
                }
            });
        }
    </script>
</body>
</html>

<?php
// Load WordPress to access functions
require_once('../../../wp-load.php');
?>
