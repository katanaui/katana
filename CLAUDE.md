# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Workspace Overview

**KatanaUI** is a Laravel Blade component library providing a curated collection of pre-built, reusable UI components. It's designed as a Composer package that integrates seamlessly with Laravel applications, offering both basic form components and advanced interactive components built with TailwindCSS and Alpine.js.

## Primary Tech Stack

- **Framework**: Laravel 10-11 (via Illuminate Support)
- **Frontend**: TailwindCSS + Alpine.js
- **Component Format**: Laravel Blade anonymous components (.blade.php) with YAML metadata (.yml)
- **PHP Version**: 8.0+ (tested on 8.0, 8.1, 8.2, 8.3, 8.4)
- **Key Dependencies**:
  - `illuminate/support: ^11.0|^12.0` - Laravel framework
  - `gehrisandro/tailwind-merge-laravel: ^1.0` - TailwindCSS class utilities
  - `symfony/yaml: ^7.0` - Component metadata parsing
  - `orchestra/testbench: ^6.0` - Testing framework
  - `phpunit/phpunit: ^9.0` - Unit testing

## Common Development Commands

### Building Component JavaScript

The library includes a build system for compiling component-specific JavaScript files using esbuild.

```bash
# Install build dependencies (one-time setup)
npm install

# Build all component JS files (production - minified)
npm run build

# Watch for changes during development (non-minified with sourcemaps)
npm run dev
```

Build output goes to `public/katana/` (git-ignored). When the package is installed in a Laravel app, these assets can be published:

```bash
# In consuming Laravel app
php artisan vendor:publish --tag=katana-assets
```

This copies compiled files from `public/katana/` to the Laravel app's `public/katana/` directory.

### Testing
```bash
# Run all tests
composer test

# Generate coverage report (creates coverage/ directory with HTML report)
composer test-coverage
```

### Code Structure
- **Source Code**: `src/` directory contains the main PHP classes
  - `Katana.php` - Main class that parses component metadata from YAML
  - `KatanaServiceProvider.php` - Laravel service provider for auto-registration
  - `KatanaFacade.php` - Facade for easy programmatic access
  - `Filament/` - Filament-specific integrations
- **Components**: `resources/views/components/katana/` contains 40+ components
- **Configuration**: `config/config.php` - Package configuration file

### Component Files
Each component consists of:
- `.blade.php` file - The Blade template with component markup and logic
- `.yml` file - Component metadata (documentation, API info)
- Component subdirectories may contain additional resources (CSS, JavaScript, etc.)

## Build System Architecture

### Component JavaScript Files

Each component can have an associated JavaScript file in `resources/js/katana/[component-name].js`:

```
resources/js/katana/
├── tiptap.js          # TipTap editor initialization
├── code-editor.js     # (future) Code editor component
└── [component].js     # (future) Other component JS files
```

Each JS file is automatically compiled by esbuild into:
```
public/katana/
├── tiptap.js          # Compiled and minified
├── code-editor.js     # Compiled and minified
└── [component].js     # Compiled and minified
```

### How Component JS Works

1. **Source files** in `resources/js/katana/` use modern JavaScript (ES6 modules)
2. **esbuild** bundles and minifies them to `public/katana/`
3. **Blade components** reference the compiled files via `<script src="{{ asset('katana/tiptap.js') }}" defer></script>`
4. **Window globals** export functions to `window.KatanaJS` namespace for Alpine.js to access
5. **TipTap dependencies** must be installed in the consuming Laravel app and made available globally:
   ```javascript
   // In consuming app's app.js
   import { Editor } from '@tiptap/core'
   import StarterKit from '@tiptap/starter-kit'
   import Link from '@tiptap/extension-link'
   window.Editor = Editor;
   window.StarterKit = StarterKit;
   window.TipTapLink = Link;
   ```

### Adding JS to a New Component

1. Create a new file: `resources/js/katana/my-component.js`
2. Export initialization functions and attach to `window.KatanaJS`
3. In the component Blade file, add: `<script src="{{ asset('katana/my-component.js') }}" defer></script>`
4. Build with `npm run build`
5. Assets publish to consuming app via `php artisan vendor:publish --tag=katana-assets`

**Example component JS file:**
```javascript
// resources/js/katana/my-component.js
export function initMyComponent(element, config = {}) {
  // Component initialization logic
  return {
    init() { /* ... */ },
    destroy() { /* ... */ }
  };
}

// Export globally
window.KatanaJS = window.KatanaJS || {};
window.KatanaJS.myComponent = { init: initMyComponent };
```

**In the Blade template:**
```blade
@once
  <script src="{{ asset('katana/my-component.js') }}" defer></script>
@endonce

<div x-data="setupComponent('{{ $id }}', {{ json_encode($config) }})">
  <!-- component markup -->
</div>

<script>
function setupComponent(id, config) {
  return {
    init() {
      window.KatanaJS.myComponent.init(document.getElementById(id), config);
    }
  };
}
</script>
```

## Architecture and Design Patterns

### Service Provider Registration

The `KatanaServiceProvider` automatically registers components using Laravel's anonymous component feature. Components are registered with a configurable namespace (default: `katana`):

- **With namespace** (default): `<x-katana.button>`, `<x-katana.input>`, etc.
- **Without namespace**: Configure `KATANA_COMPONENTS_NAMESPACE=""` to use `<x-button>`, `<x-input>`
- **Custom namespace**: Set to `ui` to use `<x-ui.button>`, `<x-ui.input>`, etc.

Configuration is managed via `config/katana.php`:
```php
'components' => [
    'namespace' => 'katana', // Set to '' for no namespace
]
```

### Component Categories

Components are organized into these main groups:

1. **Form Components** - Building blocks for forms
   - Basic: `input`, `checkbox`, `label`, `select`, `textarea`, `radio`
   - Advanced: `image-upload`, `audio-upload`, `address-autocomplete`

2. **UI Components** - General purpose interface elements
   - `button`, `badge`, `alert`, `modal`, `drawer`, `tooltip`, `popover`

3. **Advanced Interactive** - Complex components with special functionality
   - `tiptap` - Rich text editor with full WYSIWYG capabilities
   - `code-editor` - Code syntax highlighting editor
   - `command` - Command palette/search interface
   - `dialog` - Modal dialog system

4. **Layout & Content** - Structural and content components
   - `accordion` - Collapsible accordion panels
   - `separator` - Visual dividers
   - `infinite-canvas` - Canvas-based infinite drawing surface
   - `backgrounds` - Background animation patterns

5. **Animations & Effects**
   - `text-animations-decrypt` - Animated text decryption effect
   - Various background animations

6. **Filament Integration**
   - `address-autocomplete` - Filament admin panel field

### YAML Component Metadata

Each component has an accompanying `.yml` file that contains:
- Component name and description
- Configuration options and prop documentation
- Usage examples
- Related components

Example structure:
```yaml
name: Button
description: A versatile button component with multiple variants
props:
  size: ['sm', 'md', 'lg']
  variant: ['primary', 'secondary', 'danger']
  disabled: boolean
```

### TailwindCSS Integration

Components use TailwindCSS utility classes extensively. The library includes `tailwind-merge-laravel` to properly merge conflicting Tailwind classes:

```blade
<!-- Using twMerge for proper class merging -->
<button @class(['px-4 py-2 rounded', $customClass])>
  {{ $slot }}
</button>
```

### Component Composition

Components follow Laravel's anonymous component pattern:
- Blade templates use `$slot` for default content
- Named slots for complex layouts
- Component attributes passed via `@bindAttributes()`
- Clean separation of concerns between markup and logic

## Testing Strategy

The project uses **PHPUnit 9.0+** with **Orchestra Testbench** for testing Laravel package functionality.

- Tests verify component registration
- Tests validate service provider bootstrap
- Tests check configuration loading and YAML parsing
- Use `composer test` to run all tests
- Use `composer test-coverage` to generate HTML coverage reports in the `coverage/` directory

## Configuration

### Key Configuration Options

The package is configured via `config/katana.php` (published to Laravel's `config/katana.php` on installation):

```php
'components' => [
    'namespace' => 'katana', // Component namespace prefix
]

'api_keys' => [
    'address_autocomplete' => env('GOOGLE_PLACES_API_KEY'), // For address autocomplete
]
```

### Environment Variables

- `GOOGLE_PLACES_API_KEY` - Required for address autocomplete component functionality

## Package Distribution

- **Type**: PHP Composer library
- **Distribution**: GitHub (`https://github.com/katanaui/katana`)
- **License**: MIT
- **Author**: Tony Lea (DevDojo)
- **Auto-discovery**: Enabled via `extra.laravel` in `composer.json`

The package auto-registers via Laravel's package discovery feature - no manual service provider registration needed.

## Key Files to Know

- `src/Katana.php` - Entry point for programmatic component access
- `src/KatanaServiceProvider.php` - Component registration and configuration
- `src/KatanaFacade.php` - Facade for `Katana` class
- `config/config.php` - Default configuration
- `resources/views/components/katana/` - All component templates
- `composer.json` - Package definition and scripts

## Important Notes

- This is a **pure Blade component library** - no Node.js build step required
- Components leverage **Alpine.js** for interactivity (Alpine scripts embedded in Blade templates)
- The library uses **YAML metadata** for documentation and component discovery
- **TailwindCSS** is expected to be installed in consuming Laravel applications
- Components are registered as **anonymous components** for clean syntax and easy customization
- The package supports **customizable namespacing** for flexibility in different applications
