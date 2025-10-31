# Build System Documentation

This document describes how to build and maintain component JavaScript files in KatanaUI.

## Overview

KatanaUI includes a build system for managing component-specific JavaScript files. Each component can have its own JavaScript file that is compiled and minified for use in consuming Laravel applications.

## Setup

### Initial Installation

```bash
npm install
```

This installs all dependencies defined in `package.json`, including:
- **esbuild** - The build tool for bundling and minifying component JavaScript
- **@tiptap/** packages - TipTap editor libraries (add more as needed for other components)

### One-time Setup

No additional setup required. The build system is ready to use after `npm install`.

## Building

### Production Build (Minified)

```bash
npm run build
```

This creates minified JavaScript files in `public/katana/`:
- Removes whitespace and comments
- Minifies variable names
- Bundles dependencies into each component file
- Suitable for publishing to consuming apps

### Development Build (With Sourcemaps)

```bash
npm watch
```

This watches for changes and rebuilds when files change:
- Includes sourcemaps for easier debugging
- Non-minified output for readability during development
- Automatically rebuilds when `resources/js/katana/*.js` files change

### Clean Builds

```bash
rm -rf public/katana/
npm run build
```

This removes old build artifacts and creates a fresh build.

## File Structure

```
katana/
├── resources/js/katana/
│   ├── tiptap.js              # Source file for TipTap component
│   └── [component].js          # Source files for other components
│
├── public/katana/
│   ├── tiptap.js              # Compiled output (git-ignored)
│   └── [component].js          # Compiled output (git-ignored)
│
├── resources/views/components/katana/
│   ├── tiptap/
│   │   └── tiptap.blade.php    # References the compiled JS file
│   └── [component]/
│       └── [component].blade.php
│
├── esbuild.config.js           # Build configuration
└── package.json                # Dependencies and build scripts
```

## Creating Component JavaScript Files

### Step 1: Create the Source File

Create a new file in `resources/js/katana/[component-name].js`:

```javascript
// resources/js/katana/my-component.js

/**
 * Initialize the component
 * @param {HTMLElement} element - The DOM element to initialize
 * @param {Object} config - Component configuration options
 * @returns {Object} Component instance with public methods
 */
export function initMyComponent(element, config = {}) {
  // Your component logic here
  return {
    // Public methods
    init() {
      console.log('Component initialized');
    },
    destroy() {
      console.log('Component destroyed');
    }
  };
}

// Export globally for use in Blade templates
window.KatanaJS = window.KatanaJS || {};
window.KatanaJS.myComponent = {
  init: initMyComponent,
};
```

### Step 2: Update the Blade Component

In your component's Blade file (`resources/views/components/katana/my-component/my-component.blade.php`):

```blade
@props([
    'id' => 'component-' . uniqid(),
    'config' => [],
])

@once
    <script src="{{ asset('katana/my-component.js') }}" defer></script>
@endonce

<div x-data="setupComponent('{{ $id }}', {{ json_encode($config) }})" x-init="init()">
    <!-- Component markup -->
</div>

@once
    <script>
    function setupComponent(id, config) {
        return {
            init() {
                if (window.KatanaJS?.myComponent?.init) {
                    const element = document.getElementById(id);
                    window.KatanaJS.myComponent.init(element, config);
                }
            }
        };
    }
    </script>
@endonce
```

### Step 3: Build

```bash
npm run build
```

This compiles `resources/js/katana/my-component.js` to `public/katana/my-component.js`.

### Step 4: Test Locally

The compiled files are immediately available at `public/katana/` when running the package locally.

### Step 5: Publish to Consuming App

When a Laravel app installs KatanaUI, assets are published with:

```bash
php artisan vendor:publish --tag=katana-assets
```

This copies all files from `public/katana/` to the consuming app's `public/katana/` directory.

## Adding Dependencies

When creating new components with external dependencies:

### 1. Add to package.json

```bash
npm install --save [package-name]
```

This updates `package.json` with the new dependency.

### 2. Use in Your Component

```javascript
// resources/js/katana/my-editor.js
import { someLibrary } from 'some-library';

export function initMyEditor(element, config = {}) {
  // Use the library
  const instance = someLibrary(element, config);
  return { /* ... */ };
}
```

### 3. Build

```bash
npm run build
```

esbuild automatically bundles all dependencies into the output file.

## Configuration

### esbuild.config.js

The build configuration is defined in `esbuild.config.js`:

- **Entry points**: Auto-detected from `resources/js/katana/*.js`
- **Output directory**: `public/katana/`
- **Format**: IIFE (Immediately Invoked Function Expression) for browser compatibility
- **Bundling**: All dependencies are bundled into each component file
- **Sourcemaps**: Generated in development mode only

### package.json Scripts

```json
{
  "scripts": {
    "build": "NODE_ENV=production node esbuild.config.js",
    "watch": "node esbuild.config.js --watch"
  }
}
```

- `npm run build` - Production build (minified, no sourcemaps)
- `npm watch` - Development watch mode (sourcemaps, non-minified)

## Troubleshooting

### Build Fails

Check that all required dependencies are installed:

```bash
npm install
```

### Source Maps Not Working

Ensure you're using `npm watch` instead of `npm run build`. Production builds intentionally exclude sourcemaps.

### Changes Not Reflecting

- Stop the watch mode (Ctrl+C)
- Run `npm run build` again
- Verify the file was created in `public/katana/`
- Clear browser cache or do a hard refresh (Cmd+Shift+R or Ctrl+Shift+R)

### Dependency Import Errors

Verify the dependency is:
1. Installed: Check `package.json` and `node_modules/`
2. Correct name: Check the package documentation
3. Exported correctly: Some packages require specific import paths

Example:
```javascript
// May not work
import { Editor } from '@tiptap/core';

// Check package.json exports or try
import Editor from '@tiptap/core';
```

## Publishing

### Before Releasing

1. Ensure all components are built:
   ```bash
   npm run build
   ```

2. Verify output files exist and are minified:
   ```bash
   ls -lh public/katana/
   file public/katana/*.js  # Should show minified
   ```

3. Test asset publishing works:
   ```bash
   # In a test Laravel app
   composer require katanaui/katana
   php artisan vendor:publish --tag=katana-assets
   ```

### Git

Build output in `public/katana/` is git-ignored per `.gitignore`. Consuming apps must:
1. Install the package: `composer require katanaui/katana`
2. Publish assets: `php artisan vendor:publish --tag=katana-assets`

The consuming app's `public/katana/` directory is not git-ignored, allowing team members to keep built files in sync.

## Performance Tips

1. **Lazy Loading**: Currently, script tags load all JS eagerly. For future optimization, consider lazy-loading via fetch when components are actually needed.

2. **Code Splitting**: If components become large, esbuild can be configured to split code, though this requires corresponding changes in how components reference the files.

3. **Dependency Sharing**: If multiple components use the same large dependency, consider extracting to a shared bundle.

## FAQ

**Q: Why are dependencies bundled into each component file?**
A: This makes each component self-contained and published independently. Consuming apps don't need to manage component dependencies separately.

**Q: What if two components use the same dependency?**
A: Currently, the dependency is bundled in both files, causing duplication. This is a trade-off for simplicity. If this becomes a problem, we can implement shared bundles.

**Q: Can I use TypeScript?**
A: Yes, with additional configuration. TypeScript files would need to be transpiled before esbuild processes them.

**Q: How do I debug minified code?**
A: Use sourcemaps with `npm watch`. Sourcemaps map minified code back to original source for debugging.
