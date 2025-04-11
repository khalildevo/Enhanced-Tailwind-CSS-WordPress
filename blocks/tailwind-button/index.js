const { registerBlockType } = wp.blocks;
const { InspectorControls, RichText, useBlockProps } = wp.blockEditor;
const { PanelBody, TextControl, SelectControl } = wp.components;
const { __ } = wp.i18n;

registerBlockType('enhanced-tailwind-wp/button', {
    edit: function(props) {
        const { attributes, setAttributes } = props;
        const { text, url, className, target } = attributes;
        const blockProps = useBlockProps({
            className: className
        });

        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Button Settings', 'enhanced-tailwind-wp')}>
                        <TextControl
                            label={__('URL', 'enhanced-tailwind-wp')}
                            value={url}
                            onChange={(value) => setAttributes({ url: value })}
                        />
                        <SelectControl
                            label={__('Target', 'enhanced-tailwind-wp')}
                            value={target}
                            options={[
                                { label: __('Same window', 'enhanced-tailwind-wp'), value: '_self' },
                                { label: __('New window', 'enhanced-tailwind-wp'), value: '_blank' }
                            ]}
                            onChange={(value) => setAttributes({ target: value })}
                        />
                        <TextControl
                            label={__('CSS Classes', 'enhanced-tailwind-wp')}
                            value={className}
                            onChange={(value) => setAttributes({ className: value })}
                            help={__('Enter Tailwind CSS classes for this button', 'enhanced-tailwind-wp')}
                        />
                    </PanelBody>
                </InspectorControls>
                <a {...blockProps} href={url} target={target}>
                    <RichText
                        tagName="span"
                        value={text}
                        onChange={(value) => setAttributes({ text: value })}
                        placeholder={__('Button text...', 'enhanced-tailwind-wp')}
                    />
                </a>
            </>
        );
    },
    save: function(props) {
        const { attributes } = props;
        const { text, url, className, target } = attributes;
        const blockProps = useBlockProps.save({
            className: className
        });

        return (
            <a {...blockProps} href={url} target={target} rel={target === '_blank' ? 'noopener noreferrer' : undefined}>
                <RichText.Content tagName="span" value={text} />
            </a>
        );
    }
});