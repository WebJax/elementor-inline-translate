(function($) {
    'use strict';

    // Translation state management
    let isTranslating = false;
    let eventsBound = false;

    $(window).on('elementor:init', function() {
        console.log('EIT Debug: Elementor init event detected');
        
        // Simplified single event binding approach
        function bindTranslateEvents() {
            if (eventsBound) {
                console.log('EIT Debug: Events already bound, skipping');
                return;
            }
            
            console.log('EIT Debug: Binding translate events');
            
            // Single DOM event binding for all translate buttons
            $(document).on('click', '.eit-translate-button', function(e) {
                console.log('EIT Debug: Translate button clicked!', e);
                e.preventDefault();
                e.stopPropagation();
                
                // Prevent multiple concurrent translations
                if (isTranslating) {
                    console.log('EIT Debug: Translation already in progress, ignoring click');
                    return;
                }
                
                var controlName = $(this).data('control-name');
                console.log('EIT Debug: Button has control name:', controlName);
                handleTranslateEvent(e.target);
            });
            
            eventsBound = true;
            console.log('EIT Debug: Event binding completed');
        }
        
        // Wait for Elementor to be fully loaded
        elementor.on('preview:loaded', function() {
            console.log('EIT Debug: Elementor preview loaded');
            bindTranslateEvents();
        });
        
        // Also bind immediately in case preview is already loaded
        setTimeout(function() {
            console.log('EIT Debug: Delayed binding attempt');
            bindTranslateEvents();
        }, 1000);
        
        function handleTranslateEvent(buttonElement) {
            console.log('EIT Debug: Starting translation process');
            
            if (isTranslating) {
                console.log('EIT Debug: Already translating, aborting');
                return;
            }
            
            isTranslating = true;
            
            let widgetModel = null;
            
            // Try to get the currently edited element from panel
            try {
                if (elementor && elementor.getPanelView) {
                    const panelView = elementor.getPanelView();
                    if (panelView && panelView.getCurrentPageView) {
                        const pageView = panelView.getCurrentPageView();
                        if (pageView && pageView.getOption && pageView.getOption('editedElementView')) {
                            const editedElementView = pageView.getOption('editedElementView');
                            if (editedElementView && editedElementView.model) {
                                widgetModel = editedElementView.model;
                                console.log('EIT Debug: Got widget model from edited element view');
                            }
                        }
                    }
                }
            } catch (error) {
                console.error('EIT Debug: Error getting panel view:', error);
            }
            
            if (!widgetModel) {
                console.error('EIT Error: Could not find widget model');
                elementor.notifications.showToast({
                    message: 'EIT: Kunne ikke finde det aktuelle element. Sørg for at elementet er valgt.',
                    type: 'error'
                });
                isTranslating = false;
                return;
            }
            
            console.log('EIT Debug: Successfully found widget model:', widgetModel);
            handleTranslationForElement(widgetModel);
        }
        
        function handleTranslationForElement(widgetModel) {
            console.log('EIT Debug: Starting translation for element:', widgetModel);
            
            if (!widgetModel) {
                console.error('EIT Error: No widget model provided');
                elementor.notifications.showToast({
                    message: 'EIT: Intern fejl (model mangler).',
                    type: 'error'
                });
                isTranslating = false; // Reset flag on error
                return;
            }
            
            // Get the text content that needs translation
            let widgetType, settings, textToTranslate, textFieldKey;
            let elementId = '';
            
            // Handle different model types safely
            try {
                if (widgetModel.get && typeof widgetModel.get === 'function') {
                    // This is a Backbone model
                    widgetType = widgetModel.get('widgetType');
                    settings = widgetModel.get('settings');
                    elementId = widgetModel.get('id') || '';
                    console.log('EIT Debug: Using Backbone model interface');
                } else if (widgetModel.attributes) {
                    // This is a Backbone model accessed via attributes
                    widgetType = widgetModel.attributes.widgetType;
                    settings = widgetModel.attributes.settings;
                    elementId = widgetModel.attributes.id || '';
                    console.log('EIT Debug: Using Backbone model attributes');
                } else {
                    // This might be a plain object
                    widgetType = widgetModel.widgetType;
                    settings = widgetModel.settings;
                    elementId = widgetModel.id || '';
                    console.log('EIT Debug: Using plain object interface');
                }
            } catch (error) {
                console.error('EIT Error: Error extracting widget data:', error);
                elementor.notifications.showToast({
                    message: 'EIT: Fejl ved læsning af widget data.',
                    type: 'error'
                });
                isTranslating = false;
                return;
            }
            
            console.log('EIT Debug: Widget type:', widgetType);
            console.log('EIT Debug: Settings:', settings);
            console.log('EIT Debug: Element ID:', elementId);
            
            if (!widgetType || !settings) {
                console.error('EIT Error: Could not extract widget type or settings', {widgetType, settings});
                elementor.notifications.showToast({
                    message: 'EIT: Kunne ikke læse widget data.',
                    type: 'error'
                });
                isTranslating = false;
                return;
            }
            
            // Extract text content based on widget type
            // Handle both Backbone models and plain objects for settings
            function getSetting(key) {
                try {
                    if (settings && settings.get && typeof settings.get === 'function') {
                        return settings.get(key);
                    } else if (settings && settings.attributes) {
                        return settings.attributes[key];
                    } else if (settings) {
                        return settings[key];
                    }
                } catch (error) {
                    console.error('EIT Debug: Error getting setting:', key, error);
                }
                return null;
            }
            
            // Identify text content based on widget type
            switch (widgetType) {
                case 'heading':
                    textToTranslate = getSetting('title');
                    textFieldKey = 'title';
                    break;
                case 'text-editor':
                    textToTranslate = getSetting('editor');
                    textFieldKey = 'editor';
                    break;
                case 'button':
                    textToTranslate = getSetting('text');
                    textFieldKey = 'text';
                    break;
                default:
                    console.error('EIT Error: Unsupported widget type:', widgetType);
                    elementor.notifications.showToast({
                        message: 'EIT: Widget type "' + widgetType + '" understøttes ikke endnu.',
                        type: 'warning'
                    });
                    isTranslating = false;
                    return;
            }
            
            console.log('EIT Debug: Text to translate:', textToTranslate);
            console.log('EIT Debug: Text field key:', textFieldKey);
            
            if (!textToTranslate || textToTranslate.trim() === '') {
                elementor.notifications.showToast({
                    message: 'EIT: Ingen tekst at oversætte.',
                    type: 'warning'
                });
                isTranslating = false;
                return;
            }
            
            // Show loading notification
            elementor.notifications.showToast({
                message: 'EIT: Oversætter tekst...',
                type: 'info'
            });
            
            // Use eit_vars instead of eitEditor for consistency
            const ajaxUrl = (typeof eit_vars !== 'undefined' ? eit_vars.ajax_url : 
                            (typeof eitEditor !== 'undefined' ? eitEditor.ajax_url : ajaxurl));
            const nonce = (typeof eit_vars !== 'undefined' ? eit_vars.nonce : 
                          (typeof eitEditor !== 'undefined' ? eitEditor.nonce : ''));
            
            console.log('EIT Debug: AJAX URL:', ajaxUrl);
            console.log('EIT Debug: Nonce:', nonce);
            console.log('EIT Debug: Text to translate:', textToTranslate);
            console.log('EIT Debug: Element ID:', elementId);
            console.log('EIT Debug: Control name:', textFieldKey);
            
            // Send AJAX request to translate the text
            jQuery.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'eit_translate_text',
                    nonce: nonce,
                    text: textToTranslate,
                    target_lang: 'en', // Default to English, could be made configurable
                    element_id: elementId,
                    control_name: textFieldKey
                },
                success: function(response) {
                    console.log('EIT Debug: Translation response received:', response);
                    console.log('EIT Debug: Response success:', response.success);
                    console.log('EIT Debug: Response data:', response.data);
                    
                    if (response.success && response.data && response.data.translated_text) {
                        // Update the widget's text content safely
                        function setSetting(key, value) {
                            try {
                                if (settings && settings.set && typeof settings.set === 'function') {
                                    settings.set(key, value);
                                    console.log('EIT Debug: Used Backbone model.set()');
                                } else if (settings && settings.attributes) {
                                    settings.attributes[key] = value;
                                    console.log('EIT Debug: Updated model attributes directly');
                                    // Trigger change event on the settings model if possible
                                    if (settings.trigger && typeof settings.trigger === 'function') {
                                        setTimeout(function() {
                                            try {
                                                settings.trigger('change:' + key);
                                                settings.trigger('change');
                                            } catch (triggerError) {
                                                console.log('EIT Debug: Error triggering settings change:', triggerError);
                                            }
                                        }, 10);
                                    }
                                } else if (settings) {
                                    settings[key] = value;
                                    console.log('EIT Debug: Updated plain object property');
                                }
                            } catch (error) {
                                console.error('EIT Debug: Error setting value:', error);
                            }
                        }
                        
                        console.log('EIT Debug: Setting new translated text:', response.data.translated_text);
                        setSetting(textFieldKey, response.data.translated_text);
                        
                        // Mark the document as modified so changes get saved
                        try {
                            if (elementor.documents && elementor.documents.getCurrent) {
                                const currentDocument = elementor.documents.getCurrent();
                                if (currentDocument && currentDocument.setDirty) {
                                    currentDocument.setDirty(true);
                                    console.log('EIT Debug: Marked document as dirty for saving');
                                }
                            }
                        } catch (error) {
                            console.log('EIT Debug: Could not mark document as dirty:', error);
                        }
                        
                        // FINAL APPROACH: Use Elementor's official control update mechanism
                        console.log('EIT Debug: Using official Elementor control update...');
                        
                        setTimeout(function() {
                            try {
                                // Method 1: Update via the panel control directly
                                if (elementor.getPanelView && elementor.getPanelView().getCurrentPageView) {
                                    const currentPageView = elementor.getPanelView().getCurrentPageView();
                                    if (currentPageView && currentPageView.children) {
                                        // Find the specific control for our text field
                                        const controls = currentPageView.children._views || currentPageView.children;
                                        
                                        Object.keys(controls).forEach(function(controlId) {
                                            const control = controls[controlId];
                                            if (control && control.options && control.options.model) {
                                                const controlModel = control.options.model;
                                                if (controlModel.get && controlModel.get('name') === textFieldKey) {
                                                    console.log('EIT Debug: Found control for field:', textFieldKey);
                                                    
                                                    // Update the control's input value
                                                    if (control.$el && control.$el.find) {
                                                        const input = control.$el.find('input, textarea, .elementor-control-input-wrapper');
                                                        if (input.length > 0) {
                                                            // For TinyMCE editors
                                                            if (textFieldKey === 'editor' && window.tinymce) {
                                                                const editorId = input.attr('id');
                                                                if (editorId && window.tinymce.get(editorId)) {
                                                                    // Set HTML content properly in TinyMCE
                                                                    window.tinymce.get(editorId).setContent(response.data.translated_text);
                                                                    // Also trigger change event to ensure model updates
                                                                    window.tinymce.get(editorId).fire('change');
                                                                    console.log('EIT Debug: Updated TinyMCE editor content with HTML');
                                                                } else {
                                                                    // Fallback: update textarea directly if TinyMCE not available
                                                                    input.val(response.data.translated_text).trigger('input').trigger('change');
                                                                    console.log('EIT Debug: Updated textarea directly as TinyMCE fallback');
                                                                }
                                                            } else {
                                                                // For regular inputs
                                                                input.val(response.data.translated_text).trigger('input').trigger('change');
                                                                console.log('EIT Debug: Updated input value and triggered events');
                                                            }
                                                        }
                                                    }
                                                    
                                                    // Trigger control-specific events
                                                    if (control.onModelChange && typeof control.onModelChange === 'function') {
                                                        control.onModelChange();
                                                        console.log('EIT Debug: Called control onModelChange');
                                                    }
                                                    
                                                    if (control.render && typeof control.render === 'function') {
                                                        control.render();
                                                        console.log('EIT Debug: Re-rendered control');
                                                    }
                                                }
                                            }
                                        });
                                    }
                                }
                                
                                // Method 2: Force the entire panel to refresh
                                setTimeout(function() {
                                    try {
                                        if (elementor.getPanelView && elementor.getPanelView().getCurrentPageView) {
                                            const currentPageView = elementor.getPanelView().getCurrentPageView();
                                            if (currentPageView && currentPageView.renderContent) {
                                                currentPageView.renderContent();
                                                console.log('EIT Debug: Re-rendered panel content');
                                            }
                                        }
                                    } catch (panelError) {
                                        console.log('EIT Debug: Panel refresh failed:', panelError);
                                    }
                                }, 100);
                                
                            } catch (controlError) {
                                console.log('EIT Debug: Control update failed:', controlError);
                            }
                        }, 50);
                        
                        // Method 3: Use Elementor's internal settings update system
                        setTimeout(function() {
                            try {
                                // Force settings to be marked as changed
                                if (widgetModel && widgetModel.get && widgetModel.get('settings')) {
                                    const settingsModel = widgetModel.get('settings');
                                    
                                    // Mark the specific setting as changed
                                    if (settingsModel._previousAttributes) {
                                        delete settingsModel._previousAttributes[textFieldKey];
                                    }
                                    
                                    // Set the value again to ensure it triggers change events
                                    settingsModel.set(textFieldKey, response.data.translated_text, {silent: false});
                                    
                                    // Force change detection
                                    settingsModel.changed[textFieldKey] = response.data.translated_text;
                                    settingsModel._changing = true;
                                    
                                    // Trigger the change events
                                    settingsModel.trigger('change:' + textFieldKey, settingsModel, response.data.translated_text);
                                    settingsModel.trigger('change', settingsModel);
                                    
                                    settingsModel._changing = false;
                                    
                                    console.log('EIT Debug: Forced settings model change detection');
                                }
                                
                                // Also trigger widget model events
                                if (widgetModel && widgetModel.trigger) {
                                    widgetModel.trigger('change:settings:' + textFieldKey, widgetModel, response.data.translated_text);
                                    widgetModel.trigger('change:settings', widgetModel);
                                    widgetModel.trigger('change', widgetModel);
                                    console.log('EIT Debug: Triggered widget model events');
                                }
                                
                            } catch (settingsError) {
                                console.log('EIT Debug: Settings update failed:', settingsError);
                            }
                        }, 150);
                        
                        // Method 4: Force preview iframe to completely reload the specific element
                        setTimeout(function() {
                            try {
                                const previewFrame = document.getElementById('elementor-preview-iframe');
                                if (previewFrame && previewFrame.contentWindow) {
                                    const previewDoc = previewFrame.contentDocument || previewFrame.contentWindow.document;
                                    
                                    // Find the element in preview and update it
                                    if (previewDoc) {
                                        const previewElement = previewDoc.querySelector('[data-id="' + elementId + '"]');
                                        if (previewElement) {
                                            // For text editor, find the content area and update it
                                            if (textFieldKey === 'editor') {
                                                const contentDiv = previewElement.querySelector('.elementor-text-editor');
                                                if (contentDiv) {
                                                    // Use innerHTML to preserve HTML formatting
                                                    contentDiv.innerHTML = response.data.translated_text;
                                                    console.log('EIT Debug: Directly updated preview content with HTML');
                                                }
                                            } else if (textFieldKey === 'title') {
                                                // For headings
                                                const headingElement = previewElement.querySelector('.elementor-heading-title');
                                                if (headingElement) {
                                                    headingElement.textContent = response.data.translated_text;
                                                    console.log('EIT Debug: Directly updated heading content');
                                                }
                                            } else if (textFieldKey === 'text') {
                                                // For buttons
                                                const buttonText = previewElement.querySelector('.elementor-button-text');
                                                if (buttonText) {
                                                    buttonText.textContent = response.data.translated_text;
                                                    console.log('EIT Debug: Directly updated button text');
                                                }
                                            }
                                        }
                                    }
                                }
                            } catch (previewError) {
                                console.log('EIT Debug: Preview direct update failed:', previewError);
                            }
                        }, 250);
                        
                        elementor.notifications.showToast({
                            message: 'EIT: Tekst er blevet oversat med bevarelse af HTML formatering! Ændringerne vises i preview\'et.',
                            type: 'success'
                        });
                        
                        console.log('EIT Debug: Translation completed successfully');
                        
                        // Reset the translation flag
                        isTranslating = false;
                    } else {
                        console.error('EIT Error: Translation failed:', response);
                        elementor.notifications.showToast({
                            message: 'EIT: Oversættelse fejlede: ' + (response.data || 'Ukendt fejl'),
                            type: 'error'
                        });
                    }
                    
                    // Always reset the translation flag
                    isTranslating = false;
                },
                error: function(xhr, status, error) {
                    console.error('EIT Error: AJAX request failed:', xhr, status, error);
                    elementor.notifications.showToast({
                        message: 'EIT: Netværksfejl under oversættelse.',
                        type: 'error'
                    });
                    
                    // Reset the translation flag on error
                    isTranslating = false;
                },
                complete: function() {
                    // Ensure the flag is always reset, even if success/error handlers fail
                    isTranslating = false;
                }
            });
        }
        
        console.log('Elementor Inline Translate editor script loaded and initialized.');
    });
})(jQuery);