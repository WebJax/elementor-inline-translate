(function($) {
    'use strict';

    $(window).on('elementor:init', function() {
        console.log('EIT Debug: Elementor init event detected');
        
        // Multiple binding approaches to ensure we catch the translate event
        function bindTranslateEvents() {
            console.log('EIT Debug: Binding translate events');
            
            // Method 1: Standard editor channel
            if (elementor && elementor.channels && elementor.channels.editor) {
                elementor.channels.editor.on('eit:translate', handleTranslateEvent);
                console.log('EIT Debug: Bound to elementor.channels.editor');
            }
            
            // Method 2: Try the main elementor object
            if (elementor && elementor.on) {
                elementor.on('eit:translate', handleTranslateEvent);
                console.log('EIT Debug: Bound to main elementor object');
            }
            
            // Method 3: Try the panel object
            if (elementor && elementor.getPanelView) {
                const panel = elementor.getPanelView();
                if (panel && panel.on) {
                    panel.on('eit:translate', handleTranslateEvent);
                    console.log('EIT Debug: Bound to panel view');
                }
            }
            
            // Method 4: Direct DOM event binding for button clicks
            $(document).on('click', '[data-event="eit:translate"]', function(e) {
                console.log('EIT Debug: Direct button click detected!', e);
                e.preventDefault();
                handleTranslateEvent(null, e.target);
            });
            
            // Method 5: Look for our custom translate buttons
            $(document).on('click', '.eit-translate-button', function(e) {
                console.log('EIT Debug: Custom translate button clicked!', e);
                e.preventDefault();
                var controlName = $(this).data('control-name');
                console.log('EIT Debug: Button has control name:', controlName);
                handleTranslateEvent(null, e.target);
            });
            
            // Method 6: Look for buttons with specific text or class
            $(document).on('click', '.elementor-control-eit_translate_button button', function(e) {
                console.log('EIT Debug: Button class click detected!', e);
                e.preventDefault();
                handleTranslateEvent(null, e.target);
            });
            
            console.log('EIT Debug: All event bindings completed');
        }
        
        // Wait for Elementor to be fully loaded
        elementor.on('preview:loaded', function() {
            console.log('EIT Debug: Elementor preview loaded');
            console.log('EIT Debug: eitEditor object:', typeof eitEditor !== 'undefined' ? eitEditor : 'undefined');
            bindTranslateEvents();
        });
        
        // Also bind immediately in case preview is already loaded
        setTimeout(function() {
            console.log('EIT Debug: Delayed binding attempt');
            bindTranslateEvents();
        }, 1000);
        
        function handleTranslateEvent(controlView, buttonElement) {
            console.log('EIT Debug: eit:translate event triggered!', controlView, buttonElement);
            
            let widgetModel = null;
            
            // Method 1: Try to get from controlView if available
            if (controlView && controlView.container && controlView.container.settings) {
                console.log('EIT Debug: Using controlView.container.settings');
                widgetModel = controlView.container.settings;
            }
            
            // Method 2: Try to get the currently edited element from panel
            if (!widgetModel) {
                console.log('EIT Debug: Trying to get currently edited element from panel');
                try {
                    if (elementor && elementor.getPanelView && elementor.getPanelView()) {
                        const panelView = elementor.getPanelView();
                        console.log('EIT Debug: Panel view:', panelView);
                        
                        if (panelView.getCurrentPageView && panelView.getCurrentPageView()) {
                            const pageView = panelView.getCurrentPageView();
                            console.log('EIT Debug: Page view:', pageView);
                            
                            if (pageView.getOption && pageView.getOption('editedElementView')) {
                                const editedElementView = pageView.getOption('editedElementView');
                                console.log('EIT Debug: Edited element view:', editedElementView);
                                
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
            }
            
            // Method 3: Try to get from elementor.documents if available
            if (!widgetModel) {
                console.log('EIT Debug: Trying to get from elementor.documents');
                try {
                    if (elementor && elementor.documents && elementor.documents.getCurrent) {
                        const currentDocument = elementor.documents.getCurrent();
                        console.log('EIT Debug: Current document:', currentDocument);
                        
                        if (currentDocument && currentDocument.history && currentDocument.history.currentItem) {
                            const historyItem = currentDocument.history.currentItem;
                            console.log('EIT Debug: History item:', historyItem);
                            
                            if (historyItem.model) {
                                widgetModel = historyItem.model;
                                console.log('EIT Debug: Got widget model from history');
                            }
                        }
                    }
                } catch (error) {
                    console.error('EIT Debug: Error getting from documents:', error);
                }
            }
            
            // Method 4: Try to find the selected element by looking at the DOM
            if (!widgetModel && buttonElement) {
                console.log('EIT Debug: Trying to find element from button context');
                try {
                    // Look for the control name in the button data
                    const $button = $(buttonElement);
                    const controlName = $button.data('control-name') || $button.closest('[data-control-name]').data('control-name');
                    console.log('EIT Debug: Found control name:', controlName);
                    
                    if (controlName) {
                        // Try to get the elementor panel data
                        if (elementor && elementor.getPanelView) {
                            const panel = elementor.getPanelView();
                            if (panel && panel.content && panel.content.currentView && panel.content.currentView.model) {
                                widgetModel = panel.content.currentView.model;
                                console.log('EIT Debug: Got widget model from panel content');
                            }
                        }
                    }
                } catch (error) {
                    console.error('EIT Debug: Error finding element from button context:', error);
                }
            }
            
            if (!widgetModel) {
                console.error('EIT Error: Could not find widget model using any method');
                elementor.notifications.showToast({
                    message: 'EIT: Kunne ikke finde det aktuelle element. Sørg for at elementet er valgt.',
                    type: 'error'
                });
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
                return;
            }
            
            // Get the text content that needs translation
            let widgetType, settings, textToTranslate, textFieldKey;
            let elementId = '';
            
            // Handle different model types (Backbone models vs plain objects)
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
            
            console.log('EIT Debug: Widget type:', widgetType);
            console.log('EIT Debug: Settings:', settings);
            console.log('EIT Debug: Element ID:', elementId);
            
            if (!widgetType || !settings) {
                console.error('EIT Error: Could not extract widget type or settings', {widgetType, settings});
                elementor.notifications.showToast({
                    message: 'EIT: Kunne ikke læse widget data.',
                    type: 'error'
                });
                return;
            }
            
            // Extract text content based on widget type
            // Handle both Backbone models and plain objects for settings
            function getSetting(key) {
                if (settings && settings.get && typeof settings.get === 'function') {
                    return settings.get(key);
                } else if (settings && settings.attributes) {
                    return settings.attributes[key];
                } else if (settings) {
                    return settings[key];
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
                    return;
            }
            
            console.log('EIT Debug: Text to translate:', textToTranslate);
            console.log('EIT Debug: Text field key:', textFieldKey);
            
            if (!textToTranslate || textToTranslate.trim() === '') {
                elementor.notifications.showToast({
                    message: 'EIT: Ingen tekst at oversætte.',
                    type: 'warning'
                });
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
                    console.log('EIT Debug: Translation response:', response);
                    
                    if (response.success && response.data.translated_text) {
                        // Update the widget's text content
                        function setSetting(key, value) {
                            if (settings && settings.set && typeof settings.set === 'function') {
                                settings.set(key, value);
                            } else if (settings && settings.attributes) {
                                settings.attributes[key] = value;
                            } else if (settings) {
                                settings[key] = value;
                            }
                        }
                        
                        console.log('EIT Debug: Setting new translated text:', response.data.translated_text);
                        setSetting(textFieldKey, response.data.translated_text);
                        
                        // Mark the document as modified so changes get saved
                        if (elementor.documents && elementor.documents.getCurrent) {
                            const currentDocument = elementor.documents.getCurrent();
                            if (currentDocument && currentDocument.setDirty) {
                                currentDocument.setDirty(true);
                                console.log('EIT Debug: Marked document as dirty for saving');
                            }
                        }
                        
                        // Trigger re-render of the widget using the correct Elementor API
                        console.log('EIT Debug: Triggering widget re-render...');
                        
                        // Method 1: Update via settings model
                        if (settings && settings.trigger) {
                            settings.trigger('change:' + textFieldKey);
                            settings.trigger('change');
                        }
                        
                        // Method 2: Update via widget model
                        if (widgetModel.trigger) {
                            widgetModel.trigger('change:settings');
                            widgetModel.trigger('change');
                        }
                        
                        // Method 3: Force immediate preview update via Elementor's data channels
                        setTimeout(function() {
                            console.log('EIT Debug: Forcing preview refresh...');
                            
                            try {
                                // Trigger the element render event
                                if (elementor.channels && elementor.channels.data) {
                                    elementor.channels.data.trigger('element:after:edit', widgetModel);
                                    elementor.channels.data.trigger('element:after:change', widgetModel);
                                }
                                
                                // Try to get the element view and render it directly
                                const elementView = elementor.getPreviewView().getChildView(widgetModel.get('id'));
                                if (elementView && elementView.renderHTML) {
                                    console.log('EIT Debug: Re-rendering element view directly');
                                    elementView.renderHTML();
                                }
                                
                                // Force the controls panel to update as well
                                if (elementor.getPanelView && elementor.getPanelView().getCurrentPageView) {
                                    const currentPageView = elementor.getPanelView().getCurrentPageView();
                                    if (currentPageView && currentPageView.refreshControls) {
                                        currentPageView.refreshControls();
                                    }
                                }
                                
                            } catch (error) {
                                console.log('EIT Debug: Error in forced refresh, but translation should still work:', error);
                            }
                        }, 100);
                        
                        elementor.notifications.showToast({
                            message: 'EIT: Tekst er blevet oversat! Ændringerne vises i preview\'et.',
                            type: 'success'
                        });
                        
                        console.log('EIT Debug: Translation completed successfully');
                        
                        // Additional step: Force the controls to refresh by simulating a re-selection
                        setTimeout(function() {
                            console.log('EIT Debug: Refreshing controls panel...');
                            try {
                                // Force the panel to refresh its controls
                                if (elementor.getPanelView && elementor.getPanelView().content && 
                                    elementor.getPanelView().content.currentView && 
                                    elementor.getPanelView().content.currentView.refreshSettingsModel) {
                                    elementor.getPanelView().content.currentView.refreshSettingsModel();
                                }
                                
                                // Alternative: trigger a fake element selection to refresh the panel
                                if (elementor.channels && elementor.channels.editor) {
                                    elementor.channels.editor.trigger('element:select', widgetModel);
                                }
                            } catch (error) {
                                console.log('EIT Debug: Could not refresh controls panel, but translation was successful:', error);
                            }
                        }, 500);
                    } else {
                        console.error('EIT Error: Translation failed:', response);
                        elementor.notifications.showToast({
                            message: 'EIT: Oversættelse fejlede: ' + (response.data || 'Ukendt fejl'),
                            type: 'error'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('EIT Error: AJAX request failed:', xhr, status, error);
                    elementor.notifications.showToast({
                        message: 'EIT: Netværksfejl under oversættelse.',
                        type: 'error'
                    });
                }
            });
        }
        
        console.log('Elementor Inline Translate editor script loaded and initialized.');
    });
})(jQuery);