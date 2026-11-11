# PhpWails

> An open-source desktop application framework for PHP developers.

PhpWails aims to let PHP developers build cross-platform desktop applications with familiar PHP business logic and any modern frontend stack, including Vue, React, Svelte, or plain HTML.

The project is currently in the **Alpha / prototype stage**. The basic macOS development path is working; Windows, Linux, and production packaging are still under development.

## Core Features

- **PHP business logic**: Write application logic with PHP 8.2+.
- **System WebViews**: Use the WebView provided by the operating system instead of bundling a full Chromium runtime.
  - Windows: WebView2
  - macOS: WKWebView
  - Linux: WebKitGTK
- **No business HTTP port**: Frontend-to-PHP communication uses the native WebView message channel.
- **Attribute-based exports**: Expose PHP methods with `#[Export]`.
- **Generated bindings**: Generate JavaScript methods, TypeScript declarations, and a binding manifest.
- **Small distributables**: The planned production runtime combines a static PHP runtime with a PHAR archive.
- **Unified CLI**: Manage diagnostics, project creation, development, and builds from one command-line tool.

## Current Status

Working prototype capabilities:

- `phpwails doctor`
- `phpwails init`
- `phpwails bindings`
- `phpwails dev`
- PHP `#[Export]` method discovery
- JSON-RPC request/response model
- PHP export dispatcher
- macOS `NSWindow` + `WKWebView` launcher
- Native WebView message handler
- macOS Web Inspector support
- Vanilla frontend template

Not implemented yet:

- Complete Zend PHP extension
- Production Windows WebView2 adapter
- Production Linux WebKitGTK adapter
- Non-blocking PHP worker management
- Native dialogs, tray, menus, and clipboard APIs
- Production PHAR + static-php-cli packaging
- Code signing, notarization, and installer distribution

## Requirements

The current macOS prototype requires:

- macOS 13+
- PHP 8.2+
- CMake 3.20+
- Apple Clang / C++17
- Xcode Command Line Tools
- Node.js and npm for frontend development
- Cocoa and WebKit frameworks

Run the environment check with:

```bash
php packages/cli/bin/phpwails doctor
```

## Quick Start

### 1. Create a project

```bash
php packages/cli/bin/phpwails init my-app --template=vanilla
```

Available templates:

```text
vanilla
vue
react
svelte
```

The Vanilla template currently has the most complete implementation.

### 2. Generate bindings

```bash
cd my-app
php ../packages/cli/bin/phpwails bindings .
```

Generated files:

```text
frontend/phpwails/
├── manifest.json
├── index.js
└── index.d.ts
```

### 3. Start development mode

From the repository root:

```bash
php packages/cli/bin/phpwails dev my-app
```

Or from inside the project:

```bash
cd my-app
php /Users/pfinal/www/phpwails/packages/cli/bin/phpwails dev
```

`dev` currently:

1. Validates `phpwails.json`.
2. Loads `App.php`.
3. Discovers `#[Export]` methods.
4. Generates frontend bindings.
5. Starts the native WebView launcher.
6. Starts the PHP runtime worker.

### 4. Export a PHP method

```php
<?php

use PhpWails\Runtime\Attribute\Export;

final class App
{
    #[Export]
    public function greet(string $name = 'World'): string
    {
        return "Hello, {$name}!";
    }
}
```

Call it from the frontend:

```js
const message = await globalThis.App.greet('World');
```

## CLI Commands

### `doctor`

Check PHP, JSON, Phar, Node.js, and npm:

```bash
php packages/cli/bin/phpwails doctor
php packages/cli/bin/phpwails doctor --format=json
```

### `init`

Create a project:

```bash
php packages/cli/bin/phpwails init my-app --template=vanilla
```

### `bindings`

Generate frontend bindings:

```bash
php packages/cli/bin/phpwails bindings my-app
```

Check whether generated bindings are current:

```bash
php packages/cli/bin/phpwails bindings my-app --check
```

### `dev`

Start development mode:

```bash
php packages/cli/bin/phpwails dev my-app
```

Validate configuration and generate bindings without opening a window:

```bash
php packages/cli/bin/phpwails dev my-app --no-window
```

## Project Structure

```text
phpwails/
├── packages/
│   ├── runtime/       PHP runtime, attributes, dispatcher, protocol
│   ├── codegen/       JavaScript, TypeScript, and manifest generator
│   ├── cli/           doctor, init, bindings, dev, build
│   └── build/         build and distribution pipeline
├── native/
│   ├── core/          bridge, router, window, lifecycle
│   ├── extension/     PHP native extension boundary
│   └── platform/      Windows, macOS, and Linux adapters
├── frontend/
│   └── runtime/       frontend bridge and invocation runtime
├── templates/         official project templates
├── examples/          example projects
├── tests/             contract, integration, E2E, and performance tests
├── docs/              development documentation
└── specs/             product specifications and implementation plans
```

## Architecture

```text
Frontend WebView
      │ Native WebView message
      ▼
Native bridge / JSON-RPC
      │ Process pipe
      ▼
PHP runtime worker
      │ Reflection
      ▼
#[Export] PHP method
```

The production architecture will not use a local HTTP port for frontend-to-PHP business communication.

## Debugging

Development mode enables inspectable macOS WKWebViews.

In Safari, enable the developer menu under:

```text
Safari → Settings → Advanced
→ Show features for web developers
→ Develop → PhpWails → Web Inspector
```

The example page also displays basic diagnostics:

```text
Bridge ready
Calling PHP...
Hello, World!
```

## Performance Goals

- Basic application download artifact: at most 25 MB, excluding the system WebView.
- Cold start: below 500 ms on the published reference device.
- Idle memory: at most 80 MB.
- Simple IPC round trip: below 2 ms.

These are product goals. They must be measured on real platforms and clean devices and are not guarantees of the current prototype.

## Roadmap

### V0.1 Alpha

- PHP method exports
- Basic native WebView window
- CLI initialization and development mode
- Basic frontend/backend messaging

### V0.2 Beta

- Complete TypeScript generation
- Frontend hot reload
- Automatic PHP file reload
- Production asset loader

### V0.3 RC

- static-php-cli + PHAR packaging
- Native file dialogs
- System tray
- Windows, macOS, and Linux distributables

### V1.0

- Async worker pool
- Windows WebView2 runtime bootstrap
- macOS signing and notarization
- Menus, shortcuts, clipboard, and shell APIs
- Complete official documentation

## Contributing

The project is still in an early design and implementation stage. Before contributing, read:

- `READM.md`: complete product requirements
- `specs/002-phpwails-framework-spec/spec.md`: feature specification
- `specs/002-phpwails-framework-spec/plan.md`: technical plan
- `specs/002-phpwails-framework-spec/tasks.md`: implementation tasks

## License

The license has not been finalized yet. `LICENSE`, contribution guidelines, code of conduct, and security policy will be added before the first formal release.
