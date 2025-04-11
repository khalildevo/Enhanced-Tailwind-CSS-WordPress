(function() {
    'use strict';
    
    // We don't need to do anything here since we're registering blocks in their individual files
    // This file is mainly for block-specific configurations and shared utilities
    
    // Add a Tailwind CSS class category to the block editor
    wp.blocks.registerBlockCollection('enhanced-tailwind-wp', {
        title: 'Tailwind CSS',
        icon: 'admin-appearance'
    });
    
    // Add the Tailwind configuration to the window object for use in blocks
    window.enhancedTailwindWPConfig = enhancedTailwindWPBlocks.config;
    window.enhancedTailwindWPSafelist = enhancedTailwindWPBlocks.safelist;
    
    // Add a utility function to help with Tailwind class management
    window.enhancedTailwindWP = {
        /**
         * Get common Tailwind class suggestions for a specific category
         * @param {string} category - The category of classes (e.g., 'spacing', 'colors', etc.)
         * @return {Array} An array of class suggestions
         */
        getClassSuggestions: function(category) {
            const suggestions = {
                spacing: [
                    'p-2', 'p-4', 'p-6', 'p-8',
                    'px-2', 'px-4', 'px-6', 'px-8',
                    'py-2', 'py-4', 'py-6', 'py-8',
                    'm-2', 'm-4', 'm-6', 'm-8',
                    'mx-auto', 'my-2', 'my-4', 'my-6'
                ],
                colors: [
                    'text-blue-500', 'text-green-500', 'text-red-500', 'text-gray-700',
                    'bg-blue-500', 'bg-green-500', 'bg-red-500', 'bg-gray-200',
                    'hover:bg-blue-700', 'hover:bg-green-700', 'hover:bg-red-700'
                ],
                layout: [
                    'flex', 'items-center', 'justify-center', 'justify-between',
                    'grid', 'grid-cols-2', 'grid-cols-3', 'grid-cols-4',
                    'gap-2', 'gap-4', 'gap-6'
                ],
                typography: [
                    'font-bold', 'font-semibold', 'font-normal',
                    'text-xs', 'text-sm', 'text-base', 'text-lg', 'text-xl',
                    'text-center', 'text-right'
                ],
                effects: [
                    'shadow-sm', 'shadow', 'shadow-md', 'shadow-lg',
                    'opacity-75', 'opacity-50', 'opacity-25'
                ]
            };
            
            return suggestions[category] || [];
        }
    };
})();