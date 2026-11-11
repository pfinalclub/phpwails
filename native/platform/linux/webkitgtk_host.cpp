#include "phpwails/platform/webview_host.h"
namespace phpwails {
class UnsupportedWebViewHost final : public WebViewHost {
public:
 bool create(const WindowOptions&) override { return false; }
 bool navigate(const std::string&) override { return false; }
 bool post_message(const std::string&) override { return false; }
 void set_message_handler(WebMessageHandler) override {}
 void close() override {}
};
std::unique_ptr<WebViewHost> create_platform_webview() { return std::make_unique<UnsupportedWebViewHost>(); }
}
