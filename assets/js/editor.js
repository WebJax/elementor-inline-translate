(function($) {
    'use strict';

    // Translation state management
    let isTranslating = false;
    let eventsBound = false;

    // Helper function for TinyMCE content setting with proper HTML rendering
    function setTinyMCEContent(editor, content, fallbackElement) {
        return new Promise((resolve, reject) => {
            try {
                if (!editor) {
                    // No editor available, use fallback
                    if (fallbackElement && fallbackElement.length) {
                        fallbackElement.val(content).trigger('input').trigger('change');
                        console.log('EIT Debug: Used textarea fallback');
                    }
                    resolve();
                    return;
                }

                // Function to actually set the content
                const setContent = () => {
                    try {
                        // Set content with explicit HTML format
                        editor.setContent(content, {format: 'html'});
                        
                        // Ensure editor is in visual mode for proper HTML rendering
                        if (editor.isHidden()) {
                            editor.show();
                        }
                        
                        // Switch to visual mode if in text mode
                        if (editor.settings && editor.settings.plugins && editor.settings.plugins.indexOf('quickbar') !== -1) {
                            // For newer TinyMCE versions
                            if (editor.mode && editor.mode.get() === 'text') {
                                editor.mode.set('design');
                            }
                        }
                        
                        // Fire events to ensure proper model updates
                        editor.fire('change');
                        editor.fire('input');
                        editor.fire('keyup');
                        editor.fire('ExecCommand', {command: 'mceInsertContent', value: ''});
                        
                        // Save to underlying textarea
                        editor.save();
                        
                        // Force a re-render by triggering model change
                        setTimeout(() => {
                            editor.fire('change');
                        }, 100);
                        
                        console.log('EIT Debug: Successfully updated TinyMCE content with HTML rendering');
                        resolve();
                    } catch (error) {
                        console.error('EIT Error: Failed to set TinyMCE content:', error);
                        reject(error);
                    }
                };

                // Check if editor is ready
                if (editor.initialized && !editor.removed) {
                    setContent();
                } else if (!editor.removed) {
                    // Wait for editor to initialize
                    const initHandler = () => {
                        editor.off('init', initHandler);
                        setContent();
                    };
                    editor.on('init', initHandler);
                    
                    // Timeout fallback
                    setTimeout(() => {
                        if (!editor.initialized) {
                            console.warn('EIT Warning: TinyMCE init timeout, using fallback');
                            if (fallbackElement && fallbackElement.length) {
                                fallbackElement.val(content).trigger('input').trigger('change');
                            }
                            resolve();
                        }
                    }, 3000);
                } else {
                    // Editor is removed, use fallback
                    if (fallbackElement && fallbackElement.length) {
                        fallbackElement.val(content).trigger('input').trigger('change');
                        console.log('EIT Debug: Editor removed, used textarea fallback');
                    }
                    resolve();
                }
            } catch (error) {
                console.error('EIT Error: TinyMCE content setting failed:', error);
                reject(error);
            }
        });
    }

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
            
            // Event binding for copy from reference buttons
            $(document).on('click', '.eit-copy-reference-button', function(e) {
                console.log('EIT Debug: Copy reference button clicked!', e);
                e.preventDefault();
                e.stopPropagation();
                
                var controlName = $(this).data('control-name');
                console.log('EIT Debug: Copy button has control name:', controlName);
                handleCopyFromReference(e.target, controlName);
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
            
            // Get target language from widget settings
            const elementSettings = elementor.getContainer(elementId).model.get('settings');
            const targetLanguage = elementSettings.get('eit_target_language') || 'EN-GB';
            
            console.log('EIT Debug: Selected target language:', targetLanguage);
            
            // Send AJAX request to translate the text
            jQuery.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'eit_translate_text',
                    nonce: nonce,
                    text: textToTranslate,
                    target_lang: targetLanguage,
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
                                                                const editor = editorId ? window.tinymce.get(editorId) : null;
                                                                
                                                                setTinyMCEContent(editor, response.data.translated_text, input)
                                                                    .then(() => {
                                                                        console.log('EIT Debug: TinyMCE content updated successfully');
                                                                    })
                                                                    .catch((error) => {
                                                                        console.error('EIT Error: TinyMCE update failed, using fallback:', error);
                                                                        input.val(response.data.translated_text).trigger('input').trigger('change');
                                                                    });
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

        /**
         * Handle copy from reference button click
         */
        function handleCopyFromReference(buttonElement, controlName) {
            console.log('EIT Debug: Starting copy from reference for control:', controlName);
            
            if (!eit_vars.is_polylang_active) {
                console.error('EIT Error: PolyLang is not active');
                elementor.notifications.showToast({
                    message: 'EIT: PolyLang plugin er ikke aktiv.',
                    type: 'error'
                });
                return;
            }
            
            if (!eit_vars.is_translation) {
                console.error('EIT Error: Current page is not a translation');
                elementor.notifications.showToast({
                    message: 'EIT: Denne side er ikke en oversættelse.',
                    type: 'warning'
                });
                return;
            }
            
            // Get current widget model
            let widgetModel = getCurrentWidgetModel();
            if (!widgetModel) {
                console.error('EIT Error: Could not find widget model for copy operation');
                elementor.notifications.showToast({
                    message: 'EIT: Kunne ikke finde det aktuelle element.',
                    type: 'error'
                });
                return;
            }
            
            let elementId = '';
            try {
                if (widgetModel.get && typeof widgetModel.get === 'function') {
                    elementId = widgetModel.get('id') || '';
                } else if (widgetModel.attributes) {
                    elementId = widgetModel.attributes.id || '';
                } else {
                    elementId = widgetModel.id || '';
                }
            } catch (error) {
                console.error('EIT Error: Error extracting element ID:', error);
                return;
            }
            
            console.log('EIT Debug: Getting reference text for element:', elementId, 'control:', controlName);
            
            // Set button to loading state
            const button = jQuery(buttonElement);
            button.addClass('loading').prop('disabled', true);
            
            // Show loading notification
            elementor.notifications.showToast({
                message: 'EIT: Henter reference tekst...',
                type: 'info'
            });
            
            // Get reference text from main language
            jQuery.ajax({
                url: eit_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'eit_get_reference_text',
                    nonce: eit_vars.nonce,
                    element_id: elementId,
                    control_name: controlName,
                    post_id: eit_vars.current_post_id
                },
                success: function(response) {
                    console.log('EIT Debug: Reference text response:', response);
                    
                    if (response.success && response.data && response.data.reference_text) {
                        // Copy reference text to current control
                        copyTextToControl(widgetModel, controlName, response.data.reference_text);
                        
                        elementor.notifications.showToast({
                            message: 'EIT: Reference tekst kopieret!',
                            type: 'success'
                        });
                    } else {
                        console.error('EIT Error: Failed to get reference text:', response);
                        elementor.notifications.showToast({
                            message: 'EIT: Kunne ikke hente reference tekst: ' + (response.data || 'Ukendt fejl'),
                            type: 'error'
                        });
                    }
                    
                    // Remove loading state
                    button.removeClass('loading').prop('disabled', false);
                },
                error: function(xhr, status, error) {
                    console.error('EIT Error: AJAX request failed for reference text:', xhr, status, error);
                    elementor.notifications.showToast({
                        message: 'EIT: Netværksfejl ved hentning af reference tekst.',
                        type: 'error'
                    });
                    
                    // Remove loading state
                    button.removeClass('loading').prop('disabled', false);
                }
            });
        }

        /**
         * Get current widget model helper function
         */
        function getCurrentWidgetModel() {
            let widgetModel = null;
            
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
                console.error('EIT Debug: Error getting current widget model:', error);
            }
            
            return widgetModel;
        }

        /**
         * Copy text to control and update UI
         */
        function copyTextToControl(widgetModel, controlName, text) {
            console.log('EIT Debug: Copying text to control:', controlName, 'Text:', text);
            
            if (!widgetModel || !controlName || !text) {
                console.error('EIT Error: Missing parameters for copyTextToControl');
                return;
            }
            
            // Get settings from model
            let settings = null;
            try {
                if (widgetModel.get && typeof widgetModel.get === 'function') {
                    settings = widgetModel.get('settings');
                } else if (widgetModel.attributes) {
                    settings = widgetModel.attributes.settings;
                } else {
                    settings = widgetModel.settings;
                }
            } catch (error) {
                console.error('EIT Error: Error getting settings from model:', error);
                return;
            }
            
            if (!settings) {
                console.error('EIT Error: Could not get settings from widget model');
                return;
            }
            
            // Update setting value
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
            
            setSetting(controlName, text);
            
            // Update the UI controls - same approach as translation
            setTimeout(function() {
                updateControlUI(controlName, text);
            }, 50);
            
            // Mark document as dirty
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
        }

        /**
         * Update control UI with new text
         */
        function updateControlUI(controlName, text) {
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
                                if (controlModel.get && controlModel.get('name') === controlName) {
                                    console.log('EIT Debug: Found control for field:', controlName);
                                    
                                    // Update the control's input value
                                    if (control.$el && control.$el.find) {
                                        const input = control.$el.find('input, textarea, .elementor-control-input-wrapper');
                                        if (input.length > 0) {
                                            // For TinyMCE editors
                                            if (controlName === 'editor' && window.tinymce) {
                                                const editorId = input.attr('id');
                                                const editor = editorId ? window.tinymce.get(editorId) : null;
                                                
                                                setTinyMCEContent(editor, text, input)
                                                    .then(() => {
                                                        console.log('EIT Debug: TinyMCE reference content updated successfully');
                                                    })
                                                    .catch((error) => {
                                                        console.error('EIT Error: TinyMCE reference copy failed, using fallback:', error);
                                                        input.val(text).trigger('input').trigger('change');
                                                    });
                                            } else {
                                                // For regular inputs
                                                input.val(text).trigger('input').trigger('change');
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
            } catch (error) {
                console.error('EIT Debug: Error updating control UI:', error);
            }
        }

        /**
         * Load reference text automatically when widget is selected (if on translation page)
         */
        function loadReferenceTextForWidget(widgetModel) {
            if (!eit_vars.is_polylang_active || !eit_vars.is_translation) {
                return; // Not a translation page or PolyLang not active
            }
            
            console.log('EIT Debug: Loading reference text for widget');
            
            let widgetType, elementId;
            
            try {
                if (widgetModel.get && typeof widgetModel.get === 'function') {
                    widgetType = widgetModel.get('widgetType');
                    elementId = widgetModel.get('id') || '';
                } else if (widgetModel.attributes) {
                    widgetType = widgetModel.attributes.widgetType;
                    elementId = widgetModel.attributes.id || '';
                } else {
                    widgetType = widgetModel.widgetType;
                    elementId = widgetModel.id || '';
                }
            } catch (error) {
                console.error('EIT Error: Error extracting widget data for reference:', error);
                return;
            }
            
            if (!widgetType || !elementId) {
                console.log('EIT Debug: Missing widget type or element ID for reference loading');
                return;
            }
            
            // Only load reference for supported widget types
            let supportedControls = [];
            switch (widgetType) {
                case 'heading':
                    supportedControls = ['title'];
                    break;
                case 'text-editor':
                    supportedControls = ['editor'];
                    break;
                case 'button':
                    supportedControls = ['text'];
                    break;
                default:
                    console.log('EIT Debug: Widget type not supported for reference loading:', widgetType);
                    return;
            }
            
            // Load reference text for each supported control
            supportedControls.forEach(function(controlName) {
                jQuery.ajax({
                    url: eit_vars.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'eit_get_reference_text',
                        nonce: eit_vars.nonce,
                        element_id: elementId,
                        control_name: controlName,
                        post_id: eit_vars.current_post_id
                    },
                    success: function(response) {
                        console.log('EIT Debug: Reference text loaded for control:', controlName, response);
                        
                        if (response.success && response.data && response.data.reference_text) {
                            // Store reference text in a global object for UI display
                            window.eitReferenceTexts = window.eitReferenceTexts || {};
                            window.eitReferenceTexts[elementId + '_' + controlName] = response.data.reference_text;
                            
                            // Update reference text fields in UI if they exist
                            updateReferenceTextDisplay(elementId, controlName, response.data.reference_text);
                            
                            // Also try to update it using a delayed approach for dynamic UI
                            setTimeout(function() {
                                updateReferenceTextDisplay(elementId, controlName, response.data.reference_text);
                            }, 500);
                        } else {
                            console.log('EIT Debug: No reference text found for control:', controlName);
                            // Update UI to show that no reference text was found
                            updateReferenceTextDisplay(elementId, controlName, 'Ingen reference tekst fundet');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('EIT Debug: Failed to load reference text for control:', controlName, error);
                    }
                });
            });
        }

        /**
         * Update reference text display in UI
         */
        function updateReferenceTextDisplay(elementId, controlName, referenceText) {
            try {
                // Find reference text display elements with improved selectors
                const possibleSelectors = [
                    '.eit-reference-text[data-control-name="' + controlName + '"]',
                    '[data-setting="eit_reference_text_' + controlName + '"] textarea',
                    '.elementor-control-eit_reference_text_' + controlName + ' textarea',
                    '.eit-reference-field textarea'
                ];
                
                let referenceDisplay = null;
                
                // Try each selector until we find the element
                for (let selector of possibleSelectors) {
                    referenceDisplay = jQuery(selector);
                    if (referenceDisplay.length > 0) {
                        console.log('EIT Debug: Found reference display with selector:', selector);
                        break;
                    }
                }
                
                if (referenceDisplay && referenceDisplay.length > 0) {
                    referenceDisplay.val(referenceText);
                    referenceDisplay.attr('placeholder', referenceText);
                    // Also update any readonly attribute to show the text
                    referenceDisplay.prop('readonly', true);
                    console.log('EIT Debug: Updated reference text display for:', controlName);
                } else {
                    console.log('EIT Debug: Could not find reference text display element for:', controlName);
                }
            } catch (error) {
                console.error('EIT Debug: Error updating reference text display:', error);
            }
        }

        // Listen for element selection to load reference text
        elementor.hooks.addAction('panel/open_editor/widget', function(panel, model, view) {
            console.log('EIT Debug: Widget editor opened, loading reference text');
            loadReferenceTextForWidget(model);
        });

        /**
         * BULK TRANSLATION FUNCTIONALITY
         * Add bulk translation feature for translating entire pages
         */

        // Add bulk translation button to Elementor navigator
        function addBulkTranslationButton() {
            console.log('EIT Debug: Attempting to add bulk translation button...');
            
            try {
                // Multiple attempts with different timing
                const attempts = [500, 1000, 2000, 3000];
                
                attempts.forEach((delay, index) => {
                    setTimeout(function() {
                        console.log('EIT Debug: Attempt', index + 1, 'to add bulk button after', delay, 'ms');
                        
                        // Try multiple selectors for different Elementor versions
                        const possibleSelectors = [
                            '#elementor-navigator',
                            '.elementor-navigator',
                            '#elementor-navigator__elements',
                            '.elementor-navigator__elements'
                        ];
                        
                        let navigatorEl = null;
                        for (const selector of possibleSelectors) {
                            navigatorEl = $(selector);
                            if (navigatorEl.length > 0) {
                                console.log('EIT Debug: Found navigator with selector:', selector);
                                break;
                            }
                        }
                        
                        // Fallback: try accessing through elementor object
                        if (!navigatorEl || navigatorEl.length === 0) {
                            if (elementor && elementor.navigator) {
                                try {
                                    const navigatorLayout = elementor.navigator.getLayout();
                                    if (navigatorLayout && navigatorLayout.$el) {
                                        navigatorEl = navigatorLayout.$el;
                                        console.log('EIT Debug: Found navigator through elementor object');
                                    }
                                } catch (e) {
                                    console.log('EIT Debug: Could not access navigator through elementor object:', e.message);
                                }
                            }
                        }
                        
                        if (navigatorEl && navigatorEl.length > 0) {
                            // Check if button already exists
                            if (navigatorEl.find('.eit-bulk-translate-btn').length > 0) {
                                console.log('EIT Debug: Bulk button already exists, skipping...');
                                return;
                            }

                            // Create bulk translation button
                            const bulkButton = $(`
                                <div class="eit-bulk-translate-container" style="margin: 10px; padding: 15px; background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 6px;">
                                    <h4 style="margin: 0 0 12px 0; font-size: 13px; color: #495057; font-weight: 600;">🌐 Bulk Oversættelse</h4>
                                    <div class="eit-bulk-controls">
                                        <select class="eit-bulk-target-lang" style="width: 100%; margin-bottom: 10px; padding: 6px; border: 1px solid #ced4da; border-radius: 4px;">
                                            <option value="DA">🇩🇰 Dansk</option>
                                            <option value="DE">🇩🇪 Tysk</option>
                                            <option value="EN-GB">🇬🇧 Engelsk</option>
                                            <option value="FR">🇫🇷 Fransk</option>
                                            <option value="ES">🇪🇸 Spansk</option>
                                        </select>
                                        <button class="eit-bulk-translate-btn" style="width: 100%; padding: 8px 12px; background: linear-gradient(45deg, #007cba, #00a0d2); color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; font-weight: 500; margin-bottom: 8px;">
                                            <i class="eicon-globe" style="margin-right: 5px;"></i> Oversæt Hele Siden
                                        </button>
                                        <div class="eit-bulk-progress" style="display: none;">
                                            <div class="eit-progress-bar" style="width: 100%; height: 20px; background: #e9ecef; border-radius: 10px; overflow: hidden; margin-bottom: 5px;">
                                                <div class="eit-progress-fill" style="height: 100%; background: linear-gradient(90deg, #007cba, #00a0d2); width: 0%; transition: width 0.3s;"></div>
                                            </div>
                                            <div class="eit-progress-text" style="font-size: 11px; text-align: center; color: #6c757d;">0/0 elementer</div>
                                        </div>
                                        <div class="eit-bulk-status" style="font-size: 11px; margin-top: 5px; display: none; padding: 5px; border-radius: 3px;"></div>
                                    </div>
                                </div>
                            `);

                            // Insert button at the top of navigator
                            navigatorEl.prepend(bulkButton);

                            // Bind click event
                            bulkButton.find('.eit-bulk-translate-btn').on('click', function() {
                                const targetLang = bulkButton.find('.eit-bulk-target-lang').val();
                                console.log('EIT Debug: Starting bulk translation to:', targetLang);
                                startBulkTranslation(targetLang);
                            });

                            console.log('EIT Debug: Bulk translation button successfully added to navigator!');
                            return; // Exit early on success
                        } else {
                            console.log('EIT Debug: Navigator element not found on attempt', index + 1);
                        }
                    }, delay);
                });
                
            } catch (error) {
                console.error('EIT Error: Failed to add bulk translation button:', error);
            }
        }

        /**
         * Start bulk translation process
         */
        function startBulkTranslation(targetLanguage) {
            console.log('EIT Debug: Starting bulk translation to:', targetLanguage);

            if (isTranslating) {
                elementor.notifications.showToast({
                    message: 'EIT: En oversættelse er allerede i gang.',
                    type: 'warning'
                });
                return;
            }

            // Get current post ID
            const postId = eit_vars.current_post_id || elementor.config.post_id;
            if (!postId) {
                elementor.notifications.showToast({
                    message: 'EIT: Kunne ikke finde post ID.',
                    type: 'error'
                });
                return;
            }

            // Show confirmation dialog
            if (!confirm('Er du sikker på at du vil oversætte hele siden? Dette kan tage noget tid og kan ikke fortrydes.')) {
                return;
            }

            isTranslating = true;

            // Update UI to show progress
            const container = $('.eit-bulk-translate-container');
            const button = container.find('.eit-bulk-translate-btn');
            const progress = container.find('.eit-bulk-progress');
            const status = container.find('.eit-bulk-status');

            button.prop('disabled', true).html('<i class="eicon-loading eicon-animation-spin"></i> Oversætter...');
            progress.show();
            status.show().text('Starter bulk oversættelse...');

            // Show initial notification
            elementor.notifications.showToast({
                message: 'EIT: Starter bulk oversættelse af hele siden...',
                type: 'info'
            });

            // Send AJAX request for bulk translation
            jQuery.ajax({
                url: eit_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'eit_translate_page_bulk',
                    nonce: eit_vars.nonce,
                    target_language: targetLanguage,
                    page_id: postId
                },
                success: function(response) {
                    console.log('EIT Debug: Bulk translation response:', response);

                    if (response.success && response.data) {
                        const data = response.data;
                        const results = data.results || [];
                        const successful = data.success_count || 0;
                        const failed = data.error_count || 0;
                        const total = data.total_elements || 0;

                        // Update progress bar
                        const progressPercent = total > 0 ? 100 : 0;
                        progress.find('.eit-progress-fill').css('width', progressPercent + '%');
                        progress.find('.eit-progress-text').text(`${total}/${total} elementer`);

                        // Apply translations to the page
                        applyBulkTranslations(results);

                        // Update status
                        status.html(`
                            <div style="color: #00a32a;">✓ ${successful} oversatte</div>
                            ${failed > 0 ? `<div style="color: #d63638;">✗ ${failed} fejlede</div>` : ''}
                        `);

                        // Show success notification
                        elementor.notifications.showToast({
                            message: `EIT: Bulk oversættelse fuldført! ${successful} elementer oversat${failed > 0 ? `, ${failed} fejlede` : ''}.`,
                            type: successful > 0 ? 'success' : 'warning'
                        });

                        // Mark document as dirty for saving
                        try {
                            if (elementor.documents && elementor.documents.getCurrent) {
                                const currentDocument = elementor.documents.getCurrent();
                                if (currentDocument && currentDocument.setDirty) {
                                    currentDocument.setDirty(true);
                                    console.log('EIT Debug: Marked document as dirty after bulk translation');
                                }
                            }
                        } catch (error) {
                            console.log('EIT Debug: Could not mark document as dirty:', error);
                        }

                        // Auto-hide status after 10 seconds
                        setTimeout(function() {
                            status.fadeOut();
                            progress.fadeOut();
                        }, 10000);

                    } else {
                        console.error('EIT Error: Bulk translation failed:', response);
                        status.html('<div style="color: #d63638;">Bulk oversættelse fejlede</div>');
                        
                        elementor.notifications.showToast({
                            message: 'EIT: Bulk oversættelse fejlede: ' + (response.data || 'Ukendt fejl'),
                            type: 'error'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('EIT Error: Bulk translation AJAX failed:', xhr, status, error);
                    
                    container.find('.eit-bulk-status').html('<div style="color: #d63638;">Netværksfejl</div>');
                    
                    elementor.notifications.showToast({
                        message: 'EIT: Netværksfejl under bulk oversættelse.',
                        type: 'error'
                    });
                },
                complete: function() {
                    // Reset UI state
                    button.prop('disabled', false).html('<i class="eicon-globe"></i> Oversæt Hele Siden');
                    isTranslating = false;
                }
            });
        }

        /**
         * Apply bulk translations to page elements
         */
        function applyBulkTranslations(results) {
            console.log('EIT Debug: Applying bulk translations:', results);

            if (!Array.isArray(results)) {
                console.error('EIT Error: Invalid results data');
                return;
            }

            let appliedCount = 0;

            results.forEach(function(result) {
                if (result.success && result.translated) {
                    try {
                        // Find the element in Elementor
                        const elementId = result.id;
                        const widgetType = result.type;
                        
                        console.log('EIT Debug: Applying translation to element:', elementId, 'type:', widgetType);
                        
                        // Get the element container
                        const container = elementor.getContainer(elementId);
                        
                        if (container) {
                            const element = container.model;
                            const settings = element.get('settings');
                            
                            let fieldsUpdated = 0;
                            
                            // If we have field mappings, use them for precise application
                            if (result.field_mappings && typeof result.field_mappings === 'object') {
                                console.log('EIT Debug: Using field mappings for element:', elementId, result.field_mappings);
                                
                                Object.keys(result.field_mappings).forEach(function(fieldKey) {
                                    const translatedValue = result.field_mappings[fieldKey];
                                    if (settings) {
                                        settings.set(fieldKey, translatedValue);
                                        fieldsUpdated++;
                                        console.log('EIT Debug: Updated field', fieldKey, 'with:', translatedValue.substring(0, 50));
                                    }
                                });
                            } else {
                                // Fallback to simple translation application
                                const translatedText = result.translated;
                                let settingKey = '';
                                
                                switch (widgetType) {
                                    case 'heading':
                                        settingKey = 'title';
                                        break;
                                    case 'text-editor':
                                        settingKey = 'editor';
                                        break;
                                    case 'button':
                                        settingKey = 'text';
                                        break;
                                    case 'divider':
                                        settingKey = 'text';
                                        break;
                                    default:
                                        console.log('EIT Debug: Unsupported widget type for simple application:', widgetType);
                                        return;
                                }
                                
                                if (settingKey && settings) {
                                    settings.set(settingKey, translatedText);
                                    fieldsUpdated++;
                                    console.log('EIT Debug: Applied simple translation to', settingKey, 'of element:', elementId);
                                }
                            }
                            
                            if (fieldsUpdated > 0) {
                                appliedCount++;
                                console.log('EIT Debug: Successfully updated', fieldsUpdated, 'fields for element:', elementId);
                            }
                        } else {
                            console.log('EIT Debug: Could not find container for element:', elementId);
                        }
                    } catch (error) {
                        console.error('EIT Error: Failed to apply translation to element:', result.id, error);
                    }
                }
            });

            console.log(`EIT Debug: Applied ${appliedCount} out of ${results.length} translations`);

            // Force preview refresh to show all changes
            setTimeout(function() {
                try {
                    if (elementor.getPreviewView && elementor.getPreviewView().forceRefresh) {
                        elementor.getPreviewView().forceRefresh();
                        console.log('EIT Debug: Forced preview refresh after bulk translation');
                    }
                } catch (error) {
                    console.log('EIT Debug: Could not force preview refresh:', error);
                }
            }, 500);
        }

        // Initialize bulk translation when navigator is ready
        elementor.on('navigator:loaded', function() {
            console.log('EIT Debug: Navigator loaded, adding bulk translation button');
            addBulkTranslationButton();
        });

        // Initialize bulk translation when panel is ready
        elementor.on('panel:loaded', function() {
            console.log('EIT Debug: Panel loaded, trying bulk translation button');
            addBulkTranslationButton();
        });

        // Alternative: Add bulk translation button to top bar
        function addBulkTranslationToTopBar() {
            console.log('EIT Debug: Attempting to add bulk translation to top bar...');
            
            setTimeout(function() {
                const topBar = $('#elementor-panel-header-menu-button').parent();
                if (topBar.length > 0 && topBar.find('.eit-bulk-translate-topbar').length === 0) {
                    const bulkButton = $(`
                        <div class="eit-bulk-translate-topbar" style="display: inline-block; margin-left: 10px;">
                            <button class="eit-topbar-bulk-btn" style="background: linear-gradient(45deg, #007cba, #00a0d2); color: white; border: none; border-radius: 3px; padding: 6px 12px; cursor: pointer; font-size: 11px;" title="Bulk oversættelse">
                                🌐 Bulk
                            </button>
                        </div>
                    `);
                    
                    topBar.append(bulkButton);
                    
                    bulkButton.find('.eit-topbar-bulk-btn').on('click', function() {
                        // Show a simple modal for language selection
                        const language = prompt('Vælg målsprog:\\n\\nDA = Dansk\\nDE = Tysk\\nEN-GB = Engelsk\\nFR = Fransk\\nES = Spansk', 'DA');
                        if (language) {
                            startBulkTranslation(language);
                        }
                    });
                    
                    console.log('EIT Debug: Bulk translation button added to top bar');
                }
            }, 1000);
        }

        // Also try to add it after a delay in case navigator is already loaded
        setTimeout(function() {
            addBulkTranslationButton();
            addBulkTranslationToTopBar();
        }, 3000);

        // END BULK TRANSLATION FUNCTIONALITY
        
        console.log('Elementor Inline Translate editor script loaded and initialized.');
    });
})(jQuery);