(function($) {
    'use strict';

    // Initialize the component builder when the DOM is ready
    $(document).ready(function() {
        // Replaces the loading wrapper with the actual component builder
        $('#enhanced-tailwind-wp-builder .loading-wrapper').replaceWith(
            `<div class="component-builder-wrapper">
                <div class="component-builder-header">
                    <h2>${wp.i18n.__('Create and Save Tailwind Components', 'enhanced-tailwind-wp')}</h2>
                </div>
                <div class="component-builder-content">
                    <div class="component-builder-sidebar">
                        <div class="component-name-input">
                            <label for="component-name">${wp.i18n.__('Component Name', 'enhanced-tailwind-wp')}</label>
                            <input type="text" id="component-name" placeholder="${wp.i18n.__('Enter component name...', 'enhanced-tailwind-wp')}" />
                        </div>
                        <div class="saved-components">
                            <h3>${wp.i18n.__('Saved Components', 'enhanced-tailwind-wp')}</h3>
                            <div class="components-list"></div>
                        </div>
                    </div>
                    <div class="component-builder-main">
                        <div class="code-editor-section">
                            <h3>${wp.i18n.__('HTML Code', 'enhanced-tailwind-wp')}</h3>
                            <textarea id="component-code" placeholder="${wp.i18n.__('Enter your HTML with Tailwind classes here...', 'enhanced-tailwind-wp')}"></textarea>
                        </div>
                        <div class="preview-section">
                            <h3>${wp.i18n.__('Live Preview', 'enhanced-tailwind-wp')}</h3>
                            <div class="preview-container">
                                <div id="component-preview"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="component-builder-footer">
                    <button class="button button-primary" id="save-component">${wp.i18n.__('Save Component', 'enhanced-tailwind-wp')}</button>
                    <button class="button" id="preview-component">${wp.i18n.__('Update Preview', 'enhanced-tailwind-wp')}</button>
                    <div class="shortcode-display">
                        <label>${wp.i18n.__('Shortcode:', 'enhanced-tailwind-wp')}</label>
                        <input type="text" id="component-shortcode" readonly />
                    </div>
                </div>
            </div>`
        );

        // Initialize Tailwind browser for the preview
        if (window.tailwindcssBrowser) {
            const config = enhancedTailwindWP.config || {};
            
            window.tailwindEditor = window.tailwindcssBrowser.createTailwindBrowser({
                config: JSON.parse(config),
                target: document.getElementById('component-preview')
            });
        }

        // Load saved components
        loadSavedComponents();

        // Event handlers
        $('#preview-component').on('click', updatePreview);
        $('#save-component').on('click', saveComponent);
        $('#component-code').on('keyup', debounce(updatePreview, 500));
    });

    // Load saved components from the backend
    function loadSavedComponents() {
        $.ajax({
            url: enhancedTailwindWP.restUrl + '/get-components',
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', enhancedTailwindWP.nonce);
            },
            success: function(response) {
                if (response.components) {
                    const componentsList = $('.components-list');
                    componentsList.empty();
                    
                    Object.keys(response.components).forEach(function(name) {
                        const component = $(`<div class="saved-component">
                            <span class="component-name">${name}</span>
                            <div class="component-actions">
                                <button class="load-component" data-name="${name}">Load</button>
                            </div>
                        </div>`);
                        
                        componentsList.append(component);
                    });
                    
                    // Add event listeners for load buttons
                    $('.load-component').on('click', function() {
                        const name = $(this).data('name');
                        loadComponent(name, response.components[name]);
                    });
                }
            },
            error: function(error) {
                console.error('Error loading components:', error);
            }
        });
    }

    // Load a component into the editor
    function loadComponent(name, code) {
        $('#component-name').val(name);
        $('#component-code').val(code);
        $('#component-shortcode').val(`[tailwind_component name="${name}"]`);
        updatePreview();
    }

    // Update the preview with the current code
    function updatePreview() {
        const code = $('#component-code').val();
        $('#component-preview').html(code);
        
        // Re-process Tailwind classes if needed
        if (window.tailwindEditor) {
            window.tailwindEditor.refresh();
        }
    }

    // Save the current component
    function saveComponent() {
        const name = $('#component-name').val();
        const code = $('#component-code').val();
        
        if (!name) {
            alert(wp.i18n.__('Please enter a component name', 'enhanced-tailwind-wp'));
            return;
        }
        
        if (!code) {
            alert(wp.i18n.__('Please enter component code', 'enhanced-tailwind-wp'));
            return;
        }
        
        $.ajax({
            url: enhancedTailwindWP.restUrl + '/save-component',
            method: 'POST',
            data: {
                name: name,
                code: code
            },
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', enhancedTailwindWP.nonce);
            },
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    $('#component-shortcode').val(`[tailwind_component name="${name}"]`);
                    loadSavedComponents();
                } else {
                    alert(wp.i18n.__('Error saving component', 'enhanced-tailwind-wp'));
                }
            },
            error: function(error) {
                console.error('Error saving component:', error);
                alert(wp.i18n.__('Error saving component', 'enhanced-tailwind-wp'));
            }
        });
    }

    // Debounce function to limit how often a function runs
    function debounce(func, wait) {
        let timeout;
        return function() {
            const context = this, args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                func.apply(context, args);
            }, wait);
        };
    }

})(jQuery);