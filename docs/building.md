# Building a local macOS application

PhpWails is a framework. The build command creates an application bundle for the user project; it does not create a fixed PhpWails application.

## Current Alpha flow

From the framework repository:

```bash
cmake -S native -B /tmp/phpwails-native-build
cmake --build /tmp/phpwails-native-build
```

Create and package a showcase project:

```bash
php packages/cli/bin/phpwails init my-app --template=vanilla
php packages/cli/bin/phpwails build my-app --platform=darwin --arch=$(uname -m) --package-format=app
open my-app/build/Hello.app
```

The current Alpha bundle contains the project frontend, generated bindings, project PHP code and the framework runtime script. It uses the PHP executable selected by the launcher environment; a self-contained static PHP/PHAR distribution is a later build stage.

## Project-owned metadata

The application name, bundle identifier, version, window title, dimensions and future icon/menu declarations belong in the generated project's `phpwails.json`. The framework build pipeline maps them to the platform bundle. Framework source must not contain a specific application's name, menu or business action.

The planned resource boundaries are:

```text
app.icon                 application icon for Finder/Dock/bundle
menu                     project menu model and controlled action IDs
statusItem               optional platform status item/tray model
build.macos              macOS target, icon and signing overrides
```

Menu and status-item support are intentionally marked as roadmap in the Vanilla showcase until their native cross-platform contracts are implemented.

## Bundle layout

```text
<name>.app/Contents/
├── Info.plist
├── MacOS/<name>
└── Resources/
    ├── frontend/
    ├── project/
    ├── packages/runtime/
    ├── launch.json
    └── build-manifest.json
```

The build pipeline stages files before publishing the final `.app`, so a failed attempt does not replace a previous successful artifact. The manifest records the target and SHA-256 digests without embedding local source paths.

## Roadmap

The framework will add, in separate stages:

- project-provided icon conversion helpers (the current builder accepts `.icns`)
- native `NSMenu` customization and controlled menu events
- `NSStatusItem` and other platform tray adapters
- PHAR plus static PHP runtime with no external PHP dependency
- DMG generation
- codesign and notarization hooks
- Windows WebView2 and Linux WebKitGTK packaging
