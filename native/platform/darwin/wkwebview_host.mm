#include "phpwails/platform/webview_host.h"

#if defined(__APPLE__)
#import <Cocoa/Cocoa.h>
#import <WebKit/WebKit.h>
#include <cstdlib>

@interface PhpWailsMessageHandler : NSObject <WKScriptMessageHandler>
@property(nonatomic, assign) void (*callback)(const char* payload, void* context);
@property(nonatomic, assign) void* context;
@end

@implementation PhpWailsMessageHandler
- (void)userContentController:(WKUserContentController*)controller
      didReceiveScriptMessage:(WKScriptMessage*)message {
  if (!self.callback || ![message.body isKindOfClass:[NSString class]]) return;
  self.callback([(NSString*)message.body UTF8String], self.context);
}
@end

namespace phpwails {

class MacWebViewHost final : public WebViewHost {
public:
  ~MacWebViewHost() override { close(); }

  bool create(const WindowOptions& options) override {
    [NSApplication sharedApplication];
    [NSApp setActivationPolicy:NSApplicationActivationPolicyRegular];
    NSRect frame = NSMakeRect(0, 0, options.width, options.height);
    NSUInteger style = NSWindowStyleMaskTitled | NSWindowStyleMaskClosable |
                       NSWindowStyleMaskMiniaturizable | NSWindowStyleMaskResizable;
    window_ = [[NSWindow alloc] initWithContentRect:frame
                                           styleMask:style
                                             backing:NSBackingStoreBuffered
                                               defer:NO];
    if (!window_) return false;
    window_.title = [NSString stringWithUTF8String:options.title.c_str()];
    if (options.centered) [window_ center];

    WKWebViewConfiguration* configuration = [[WKWebViewConfiguration alloc] init];
    handler_ = [[PhpWailsMessageHandler alloc] init];
    handler_.callback = &MacWebViewHost::receive_message;
    handler_.context = this;
    configuration_user_content_controller_ = configuration.userContentController;
    [configuration_user_content_controller_ addScriptMessageHandler:handler_ name:@"phpwails"];
    webview_ = [[WKWebView alloc] initWithFrame:frame configuration:configuration];
    if (!webview_) return false;
    if (std::getenv("PHPWAILS_DEVTOOLS")) {
      if (@available(macOS 13.0, *)) webview_.inspectable = YES;
    }
    window_.contentView = webview_;
    [window_ makeKeyAndOrderFront:nil];
    [NSApp activateIgnoringOtherApps:YES];
    return true;
  }

  bool navigate(const std::string& url) override {
    if (!webview_ || url.empty()) return false;
    NSURL* target = [NSURL URLWithString:[NSString stringWithUTF8String:url.c_str()]];
    if (!target) return false;
    if ([target isFileURL]) {
      NSURL* accessRoot = [target URLByDeletingLastPathComponent];
      [webview_ loadFileURL:target allowingReadAccessToURL:accessRoot];
    } else {
      [webview_ loadRequest:[NSURLRequest requestWithURL:target]];
    }
    return true;
  }

  bool post_message(const std::string& json) override {
    if (!webview_ || json.empty()) return false;
    NSString* escaped = [NSString stringWithUTF8String:json.c_str()];
    NSData* data = [escaped dataUsingEncoding:NSUTF8StringEncoding];
    NSError* error = nil;
    id object = [NSJSONSerialization JSONObjectWithData:data options:0 error:&error];
    if (error || !object) return false;
    NSData* encoded = [NSJSONSerialization dataWithJSONObject:object options:0 error:&error];
    if (error || !encoded) return false;
    NSString* jsonLiteral = [[NSString alloc] initWithData:encoded encoding:NSUTF8StringEncoding];
    NSString* jsonArgument = [jsonLiteral stringByReplacingOccurrencesOfString:@"\\" withString:@"\\\\"];
    jsonArgument = [jsonArgument stringByReplacingOccurrencesOfString:@"\"" withString:@"\\\""];
    NSString* script = [NSString stringWithFormat:@"window.dispatchEvent(new MessageEvent('phpwails', {data: JSON.parse(\"%@\")}));", jsonArgument];
    WKWebView* targetWebView = webview_;
    dispatch_async(dispatch_get_main_queue(), ^{ [targetWebView evaluateJavaScript:script completionHandler:nil]; });
    return true;
  }

  void set_message_handler(WebMessageHandler handler) override { message_handler_ = std::move(handler); }

  void close() override {
    if (handler_ && configuration_user_content_controller_) {
      [configuration_user_content_controller_ removeScriptMessageHandlerForName:@"phpwails"];
    }
    if (window_) [window_ close];
    webview_ = nil;
    handler_ = nil;
    window_ = nil;
  }

private:
  static void receive_message(const char* payload, void* context) {
    auto* self = static_cast<MacWebViewHost*>(context);
    if (self && payload) {
      self->last_message_ = payload;
      if (self->message_handler_) self->message_handler_(self->last_message_);
    }
  }

  NSWindow* window_{nil};
  WKWebView* webview_{nil};
  PhpWailsMessageHandler* handler_{nil};
  WKUserContentController* configuration_user_content_controller_{nil};
  std::string last_message_;
  WebMessageHandler message_handler_;
};

std::unique_ptr<WebViewHost> create_platform_webview() {
  return std::make_unique<MacWebViewHost>();
}

} // namespace phpwails
#else
namespace phpwails {
std::unique_ptr<WebViewHost> create_platform_webview() { return {}; }
}
#endif
