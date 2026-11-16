PhpWails 桌面应用开发框架 — 完整需求规格说明书

1. 项目愿景与核心定位

• 产品定位：基于现代 PHP 8（8.2+）与操作系统原生 WebView 的轻量级跨平台桌面应用开发框架。
• 核心价值：让 PHP 开发者无需学习 Go、Rust 或 C++，即可使用熟悉的 PHP 语法编写桌面端后端逻辑，结合任意现代前端技术栈（Vue / React / Svelte / Tailwind / 原生 HTML），构建出安装包体积小（15~25MB）、启动快、零外部运行环境依赖的原生桌面软件（.exe / .app / .dmg / .AppImage）。
• 差异化优势：区别于打包完整 Chromium 的 Electron/NativePHP，PhpWails 采用系统原生渲染内核 + 无端口内存级 IPC 通信 + 静态单文件融合架构。

2. 目标用户与使用场景

• 核心用户：广大 PHP 开发者、全栈独立开发者、企业内部系统维护人员。
• 典型场景：
• 轻量级跨平台效率工具（数据库客户端、接口调试工具、代码生成器、日志分析器）。
• 本地离线业务软件（结合 SQLite 的收银系统、库存管理、本地数据看板）。
• 现有 PHP 业务算法/SDK 的桌面化封装。

3. 系统架构与核心功能需求

3.1 底层渲染与窗口管理模块（Window & WebView）

• 原生内核调度：通过 PHP FFI 或轻量级 C 扩展调用各平台原生 WebView（Windows WebView2、macOS WKWebView、Linux WebKitGTK）。
• 窗口生命周期与属性控制：
• 支持配置窗口标题、初始宽高、最小/最大尺寸限制、窗口居中、全屏、置顶（Always on Top）。
• 支持无边框窗口（Frameless）与自定义拖拽区域（如 data-wails-drag），满足现代化自定义标题栏 UI 需求。
• 支持窗口关闭拦截事件（如提示“是否保存未提交的更改”或最小化到托盘）。
• 运行时依赖自检（Windows）：在 Windows 启动入口内置引导检测，若目标机器缺失 WebView2 Runtime，自动弹出原生提示并引导静默安装。

3.2 前后端双向通信与自动绑定（IPC & Bindings）

• 无端口内存级通信：严禁在本地监听 127.0.0.1 HTTP 端口。所有前端到后端的调用必须通过 WebView 原生消息通道直达 PHP 进程内存。
• 注解驱动的方法暴露：提供 #[Export]（或 #[Bind]）PHP 8 Attribute，开发者只需在业务类方法上添加注解即可暴露给前端。
• TypeScript / JS 胶水代码自动生成（Codegen）：
• 框架内置 PHP 反射与 AST 扫描器，解析暴露方法的参数类型、默认参数、返回类型（含 DTO 对象结构）。
• 自动在前端目录生成对应的 JS 调用存根与完整的 .d.ts 类型声明文件（例如 window.phpwails.App.GetUser(id: number): Promise<User>），确保前端在 VS Code / WebStorm 中享有 100% 自动补全与类型检查。
• 全局事件总线（Event Bus）：
• 支持后端向前端主动推送实时事件（Events::emit('download:progress', ['percent' => 80])）。
• 支持前端向后端触发事件及取消监听（EventsOn / EventsOff / EventsOnce）。

3.3 异步非阻塞任务调度（Async & Concurrency）

• 防界面卡死机制：由于 GUI 渲染循环必须独占主线程，框架必须解决 PHP 同步阻塞导致窗口“未响应”的问题。
• 多模式异步支持：
• 轻量级异步：集成 Fiber（纤程）与非阻塞事件循环，处理网络请求或定时任务。
• 重计算隔离：提供 #[Async] 注解或后台 Worker 进程池机制，将大文件读写、图像处理、海量数据导出自动派发至后台子进程执行，完成后通过 IPC 回调唤醒前端 Promise。

3.4 虚拟资源路由（Asset Handler）

• 自定义协议拦截：注册 phpwails://（或平台等效的自定义 URL Scheme）拦截所有前端静态资源请求。
• 双模式无缝切换：
• 开发模式：直接代理或转发至前端开发服务器（如 Vite http://localhost:5173），支持秒级热更新（HMR）。
• 生产模式：直接从内存中的 phar:// 归档包读取 HTML、JS、CSS、字体及图片字节流，自动识别 MIME Type 并返回，零磁盘临时文件解压。

3.5 原生操作系统能力封装（System Runtime API）

提供统一的 PHP 命名空间（PhpWails\Runtime\*），屏蔽三大操作系统 API 差异：
• 原生对话框（Dialog）：打开单选/多选文件框、选择文件夹框、保存文件框（支持文件后缀过滤）、原生系统警告/确认弹窗。
• 系统托盘（System Tray）：支持设置托盘图标、悬浮提示文本、托盘右键原生菜单及点击事件回调。
• 原生应用菜单（Menu）：支持 macOS 顶部全局菜单栏与 Windows 窗口菜单栏配置，绑定快捷键（如 Cmd+S / Ctrl+S）。
• 剪贴板与系统交互（Clipboard & Shell）：读写系统剪贴板文本、调用系统默认浏览器打开外部 URL、在访达/资源管理器中定位文件。

4. 开发者工具链需求（PhpWails CLI）

提供一个独立分发的命令行工具 phpwails，覆盖项目全生命周期：

命令 核心功能描述
phpwails doctor 环境诊断工具，自动检查当前系统的 PHP 版本、FFI 权限、C/C++ 编译器、WebView 依赖及 Node 环境是否齐全。
phpwails init 交互式项目脚手架，支持选择前端模板（Vue / React / Svelte / Vanilla）及 PHP 依赖结构，一键生成标准工程。
phpwails dev 启动全栈热重载开发模式：拉起 Vite 服务 -> 监听 PHP 文件变动自动重启桌面窗口 -> 实时重新生成 TS .d.ts 类型绑定。
phpwails build 一键生产打包流水线（详见第 5 节），支持 --platform（windows/darwin/linux）和 --arch（amd64/arm64）参数。

5. 单文件打包与分发流水线需求

构建系统必须实现零外部环境依赖的最终产物输出：

1. 资源与代码归档：将业务 PHP 代码、vendor 依赖以及前端 npm run build 产物打包为加密/压缩的 .phar 归档。
2. 动态库内置与管理：将各平台底层胶水库（webview.dll / libwebview.dylib / libwebview.so）安全内嵌，启动时校验哈希并按需加载。
3. 静态运行时融合：
   • 依托 static-php-cli 构建的各平台纯净 micro.sfx 运行时（内置 FFI、Phar、SQLite、Curl、Mbstring 等常用扩展）。
   • CLI 自动从官方缓存仓库下载对应目标平台的 micro.sfx，与 app.phar 执行二进制融合。
4. 平台原生封装与元数据注入：
   • Windows：剥离控制台黑框（设为 WINDOWS GUI 子系统），注入应用图标（.ico）与文件版本属性，输出单文件 MyApp.exe（可选支持 NSIS 安装包生成）。
   • macOS：自动生成标准 MyApp.app 目录结构与 Info.plist，转换并注入 .icns 图标，打包为美观的 MyApp.dmg 镜像，并预留 codesign 签名与公证钩子。
   • Linux：打包生成单文件 .AppImage 或 .deb 安装包。

5. 非功能性需求与性能指标

• 安装包体积：基础 Hello World 应用打包后体积严格控制在 25MB 以内。
• 冷启动速度：双击图标到首屏界面渲染完成，耗时 < 500ms。
• 空载内存占用：基础应用运行时总内存占用（含 WebView 渲染进程）控制在 80MB 以内。
• IPC 通信延迟：前端调用简单 PHP 方法并收到返回值的往返延迟 < 2ms。

7. 开源项目演进里程碑（Roadmap）

• V0.1 (Alpha - 核心闭环)：完成 PHP 8 FFI 窗口封装、#[Export] 方法绑定、基础 CLI 初始化与本地运行。
• V0.2 (Beta - 工程化体验)：上线 TS .d.ts 自动生成器、Vite 热重载 dev 模式、phpwails:// 自定义协议静态资源加载器。
• V0.3 (RC - 打包与原生能力)：打通 micro.sfx + PHAR 单文件跨平台打包，接入原生文件对话框与系统托盘 API。
• V1.0 (GA - 正式版)：完善后台异步 Worker 池、Windows WebView2 引导器、macOS 签名脚本及完整的官方文档站。
