#pragma once
#include <memory>
#include <string>
#include <functional>
#include "phpwails/window.h"
namespace phpwails {
using WebMessageHandler = std::function<std::string(const std::string&)>;
class WebViewHost {
public:
  virtual ~WebViewHost() = default;
  virtual bool create(const WindowOptions& options) = 0;
  virtual bool navigate(const std::string& url) = 0;
  virtual bool post_message(const std::string& json) = 0;
  virtual void set_message_handler(WebMessageHandler handler) = 0;
  virtual void close() = 0;
};
std::unique_ptr<WebViewHost> create_platform_webview();
}
