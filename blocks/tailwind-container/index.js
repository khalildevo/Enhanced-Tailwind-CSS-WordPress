// blocks/tailwind-container/index.js
const { registerBlockType } = wp.blocks;
const { InnerBlocks, InspectorControls, useBlockProps } = wp.blockEditor;
const { PanelBody, TextControl } = wp.components;
const { __ } = wp.i18n;

registerBlockType('enhanced-tailwind-wp/container', {
    edit: function(props) {
        const { attributes, setAttributes } = props;
        const { className } = attributes;
        const blockProps = useBlockProps({
            className: className
        });

        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Tailwind Classes', 'enhanced-tailwind-wp')}>
                        <TextControl
                            label={__('CSS Classes', 'enhanced-tailwind-wp')}
                            value={className}
                            onChange={(value) => setAttributes({ className: value })}
                            help={__('Enter Tailwind CSS classes for this container', 'enhanced-tailwind-wp')}
                        />
                    </PanelBody>
                </InspectorControls>
                <div {...blockProps}>
                    <InnerBlocks />
                </div>
            </>
        );
    },
    save: function(props) {
        const { attributes } = props;
        const { className } = attributes;
        const blockProps = useBlockProps.save({
            className: className
        });

        return (
            <div {...blockProps}>
                <InnerBlocks.Content />
            </div>
        );
    }
});