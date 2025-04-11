# Enhanced Tailwind CSS for WordPress

A powerful WordPress plugin that seamlessly integrates Tailwind CSS into your WordPress site with performance optimizations and advanced features.

## Features

- **Easy Integration**: Add Tailwind CSS to your WordPress site with minimal configuration
- **Performance Optimized**: Local file caching, expiration controls, and safelist management
- **Frontend & Admin Support**: Load Tailwind CSS on the frontend, admin area, or both
- **Custom Configuration**: Full control over your Tailwind CSS configuration
- **Gutenberg Integration**: Custom blocks built with Tailwind CSS classes
- **Component Builder**: Create and save custom Tailwind CSS components
- **Shortcodes**: Use built-in shortcodes for common UI elements

## Installation

1. Download the plugin zip file
2. Go to WordPress Admin > Plugins > Add New > Upload Plugin
3. Upload the zip file and activate the plugin
4. Go to "Tailwind CSS" in the WordPress admin menu to configure the plugin

Alternatively, you can install the plugin via FTP:

1. Unzip the plugin file
2. Upload the `enhanced-tailwind-wp` folder to your `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress

## Configuration

### General Settings

- **Load on Frontend**: Enable to load Tailwind CSS on your website frontend
- **Load in Admin**: Enable to load Tailwind CSS in the WordPress admin area

### Performance Settings

- **Use Local File**: Store a local copy of the Tailwind CSS browser file for better performance
- **Safelist Classes**: Specify Tailwind classes that should always be available
- **Cache Expiration**: Set how long the CSS cache should be valid (in hours)

### Tailwind Configuration

- **Tailwind Config**: Customize your Tailwind configuration in JavaScript format

## Component Builder

The plugin includes a Component Builder tool that lets you:

1. Create custom Tailwind CSS components
2. Save components for reuse across your site
3. Export and import components

To access the Component Builder, go to Tailwind CSS > Component Builder in your WordPress admin menu.

## Available Shortcodes

### [tailwind_container]

Creates a container with Tailwind CSS classes.

```
[tailwind_container class="container mx-auto px-4"]
  Your content here
[/tailwind_container]
```

### [tailwind_button]

Creates a styled button.

```
[tailwind_button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded" url="https://example.com" target="_blank"]
  Click Me
[/tailwind_button]
```

### [tailwind_card]

Creates a card component.

```
[tailwind_card class="bg-white rounded overflow-hidden shadow-lg" title="Card Title" image="https://example.com/image.jpg" alt="Image description"]
  Card content here
[/tailwind_card]
```

### [tailwind_grid]

Creates a responsive grid layout.

```
[tailwind_grid class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"]
  [tailwind_card]Item 1[/tailwind_card]
  [tailwind_card]Item 2[/tailwind_card]
  [tailwind_card]Item 3[/tailwind_card]
[/tailwind_grid]
```

## Gutenberg Blocks

The plugin includes custom Gutenberg blocks with Tailwind CSS:

- **Tailwind Container**: A container block with configurable Tailwind classes
- **Tailwind Button**: A styled button block
- **Tailwind Card**: A card component block
- **Tailwind Grid**: A responsive grid layout block

## REST API Endpoints

For developers, the plugin provides REST API endpoints:

- `POST /wp-json/enhanced-tailwind-wp/v1/save-component`: Save a custom component
- `GET /wp-json/enhanced-tailwind-wp/v1/get-components`: Retrieve all saved components

## Hooks and Filters

Documentation for available hooks and filters coming soon.

## Frequently Asked Questions

### Will this plugin conflict with my theme's styles?

The plugin is configured with `preflight: false` by default to prevent conflicts with WordPress core styles. You can adjust this in the Tailwind configuration if needed.

### Can I use my existing Tailwind configuration?

Yes! You can paste your existing Tailwind configuration into the plugin settings.

### Does this plugin support Tailwind JIT mode?

Yes, the plugin uses Tailwind CSS Browser which includes Just-In-Time compilation.

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher

## Support and Contribution

For support or to contribute to this project, please visit the [GitHub repository](https://github.com/your-username/enhanced-tailwind-wp).

## License

GPL-2.0+ - See [LICENSE](LICENSE) for details.
