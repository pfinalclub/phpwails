# PhpWails

> 用 PHP 构建跨平台桌面应用的开源框架。

PhpWails 的目标是让 PHP 开发者使用熟悉的 PHP 语法编写桌面应用业务逻辑，并结合 Vue、React、Svelte 或原生 HTML，构建 Windows、macOS 和 Linux 应用。

项目目前处于 **Alpha / 原型阶段**。当前已打通 macOS 上的基础开发链路，Windows、Linux 和生产打包仍在开发中。

## 核心特性

- **PHP 业务逻辑**：使用 PHP 8.2+ 编写应用后端逻辑。
- **原生 WebView**：使用操作系统提供的 WebView，而不是打包完整 Chromium。
  - Windows：WebView2
  - macOS：WKWebView
  - Linux：WebKitGTK
- **无业务 HTTP 端口**：前端与 PHP 通过 WebView 原生消息通道通信。
- **Attribute 导出**：使用 `#[Export]` 将 PHP 方法暴露给前端。
- **自动绑定**：生成 JavaScript 调用对象、TypeScript 声明和方法清单。
- **轻量分发目标**：计划使用静态 PHP Runtime + PHAR 生成不依赖系统 PHP 的应用。
- **跨平台 CLI**：统一管理环境检查、项目创建、开发和构建流程。

## 当前状态

已完成的原型能力：

- `phpwails doctor`
- `phpwails init`
- `phpwails bindings`
- `phpwails dev`
- PHP `#[Export]` 方法发现
- JSON-RPC 请求/响应模型
- PHP Export Dispatcher
- macOS `NSWindow` + `WKWebView` Launcher
- Native WebView 消息 Handler
- macOS Web Inspector 调试支持
- Vanilla 前端模板

尚未完成：

- 完整 Zend PHP 扩展
- Windows WebView2 生产适配
- Linux WebKitGTK 生产适配
- 非阻塞 PHP Worker 管理
- 系统对话框、托盘、菜单和剪贴板 API
- PHAR + static-php-cli 生产打包
- 代码签名、公证和安装包发布

## 环境要求

当前 macOS 原型开发需要：

- macOS 13+
- PHP 8.2+
- CMake 3.20+
- Apple Clang / C++17
- Xcode Command Line Tools
- Node.js 和 npm（前端开发需要）
- Cocoa 和 WebKit Framework

检查环境：

```bash
php packages/cli/bin/phpwails doctor
```

## 快速开始

### 1. 创建项目

```bash
php packages/cli/bin/phpwails init my-app --template=vanilla
```

可用模板：

```text
vanilla
vue
react
svelte
```

当前 Vanilla 模板是完整度最高的示例。

### 2. 进入项目并生成绑定

```bash
cd my-app
php ../packages/cli/bin/phpwails bindings .
```

生成文件：

```text
frontend/phpwails/
├── manifest.json
├── index.js
└── index.d.ts
```

### 3. 启动开发模式

从仓库根目录执行：

```bash
php packages/cli/bin/phpwails dev my-app
```

或者从项目目录执行：

```bash
cd my-app
php /Users/pfinal/www/phpwails/packages/cli/bin/phpwails dev
```

`dev` 会自动：

1. 验证 `phpwails.json`
2. 加载 `App.php`
3. 扫描 `#[Export]` 方法
4. 生成前端绑定
5. 启动 Native WebView Launcher
6. 启动 PHP Runtime Worker

### 4. 示例 PHP 方法

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

前端调用：

```js
const message = await globalThis.App.greet('World');
```

## CLI 命令

### `doctor`

检查 PHP、JSON、Phar、Node.js 和 npm：

```bash
php packages/cli/bin/phpwails doctor
php packages/cli/bin/phpwails doctor --format=json
```

### `init`

创建项目：

```bash
php packages/cli/bin/phpwails init my-app --template=vanilla
```

### `bindings`

生成前端绑定：

```bash
php packages/cli/bin/phpwails bindings my-app
```

检查绑定是否最新：

```bash
php packages/cli/bin/phpwails bindings my-app --check
```

### `dev`

启动开发模式：

```bash
php packages/cli/bin/phpwails dev my-app
```

只验证配置和绑定，不启动窗口：

```bash
php packages/cli/bin/phpwails dev my-app --no-window
```

## 项目结构

```text
phpwails/
├── packages/
│   ├── runtime/       PHP Runtime、Attribute、Dispatcher、协议
│   ├── codegen/       JS、TypeScript 和 Manifest 生成器
│   ├── cli/           doctor、init、bindings、dev、build
│   └── build/         构建和分发流水线
├── native/
│   ├── core/          Bridge、Router、Window、生命周期
│   ├── extension/     PHP Native Extension 边界
│   └── platform/      Windows、macOS、Linux 适配器
├── frontend/
│   └── runtime/       前端 Bridge 和调用运行时
├── templates/         官方项目模板
├── examples/          示例项目
├── tests/              契约、集成、E2E 和性能测试
├── docs/               开发文档
└── specs/              产品规格和实现规划
```

## 架构概览

```text
Frontend WebView
      │ Native WebView Message
      ▼
Native Bridge / JSON-RPC
      │ Process Pipe
      ▼
PHP Runtime Worker
      │ Reflection
      ▼
#[Export] PHP Method
```

生产环境的目标架构不会通过本地 HTTP 端口连接前端和 PHP。

## 调试

开发模式会启用 macOS WKWebView 可检查能力。

在 Safari 中打开：

```text
Safari → Settings → Advanced
→ Show features for web developers
→ Develop → PhpWails → Web Inspector
```

页面内也会显示基本诊断信息，例如：

```text
Bridge ready
Calling PHP...
Hello, World!
```

## 设计目标

- 基础应用安装包目标：不超过 25MB（不包含系统自带 WebView）。
- 冷启动目标：基准设备上低于 500ms。
- 空载内存目标：不超过 80MB。
- 简单 IPC 往返目标：低于 2ms。

这些指标目前是产品目标，必须在真实平台和干净设备上持续测量，不能视为当前原型已经达到的性能保证。

## 路线图

### V0.1 Alpha

- PHP 方法导出
- 基础 Native WebView 窗口
- CLI 初始化和开发模式
- 基础前后端消息通信

### V0.2 Beta

- 完整 TypeScript 类型生成
- 前端热更新
- PHP 文件变更自动重载
- 生产资源加载器

### V0.3 RC

- static-php-cli + PHAR 打包
- 原生文件对话框
- 系统托盘
- Windows、macOS、Linux 分发产物

### V1.0

- 异步 Worker 池
- Windows WebView2 运行时引导
- macOS 签名和公证
- 菜单、快捷键、剪贴板和系统外壳 API
- 完整官方文档

## 贡献

项目仍在早期设计和实现阶段。提交代码前建议先阅读：

- `READM.md`：完整产品需求文档
- `specs/002-phpwails-framework-spec/spec.md`：功能规格
- `specs/002-phpwails-framework-spec/plan.md`：技术计划
- `specs/002-phpwails-framework-spec/tasks.md`：实现任务

## 许可证

许可证尚未最终确定。正式发布前会补充 `LICENSE`、贡献指南、行为准则和安全策略。
