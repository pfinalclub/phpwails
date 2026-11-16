# PhpWails Demo

这是 PhpWails 的官方 Vanilla 展示项目。它不是一个固定的业务应用，而是一个可复制的框架项目模板，用来展示：

- PHP `#[Export]` 方法发现
- 自动生成 JavaScript / TypeScript 绑定
- JSON-RPC 2.0 调用
- Native WebView message channel
- 长驻 PHP Runtime Worker
- macOS 原生窗口
- 项目级 macOS `.app` 打包

## 开发运行

从 PhpWails 仓库根目录执行：

```bash
php packages/cli/bin/phpwails doctor
php packages/cli/bin/phpwails init my-app --template=vanilla
cd my-app
php ../packages/cli/bin/phpwails dev --no-window
```

生成绑定并启动 macOS 窗口：

```bash
php ../packages/cli/bin/phpwails bindings .
php ../packages/cli/bin/phpwails dev .
```

## 打包 macOS App

先在框架仓库根目录编译 Native launcher：

```bash
cmake -S native -B /tmp/phpwails-build
cmake --build /tmp/phpwails-build
```

然后在生成的项目目录执行：

```bash
php ../packages/cli/bin/phpwails build --platform=darwin --arch=$(uname -m) --package-format=app
```

产物位于：

```text
build/Hello.app/
└── Contents/
    ├── Info.plist
    ├── MacOS/Hello
    └── Resources/
        ├── frontend/
        ├── project/
        ├── packages/runtime/
        ├── launch.json
        └── build-manifest.json
```

当前 Alpha 的 `.app` 包含 Demo 自带的 `assets/AppIcon.icns`，并使用开发机 PHP Runtime。静态 PHP、PHAR、原生菜单、状态栏、DMG、签名和公证属于后续框架能力，Demo 页面会明确标记这些状态，不会伪造已完成能力。
