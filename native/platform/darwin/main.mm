#import <Cocoa/Cocoa.h>
#include "phpwails/platform/webview_host.h"
#include "phpwails/json_rpc_router.h"
#include <cstdio>
#include <iostream>
#include <sstream>

static std::string shell_quote(const std::string& value) {
  std::string result = "'";
  for (char c : value) { if (c == '\'') result += "'\\''"; else result += c; }
  return result + "'";
}

static std::string invoke_php_runtime(const std::string& request, const std::string& project) {
  const char* root = std::getenv("PHPWAILS_ROOT");
  if (!root || project.empty()) return "{\"jsonrpc\":\"2.0\",\"id\":null,\"error\":{\"code\":-32000,\"message\":\"PHP runtime is not configured\"}}";
  const std::string runtime = std::string(root) + "/packages/runtime/bin/phpwails-runtime.php";
  const std::string command = "printf '%s\\n' " + shell_quote(request) + " | php " + shell_quote(runtime) + " " + shell_quote(project);
  FILE* pipe = popen(command.c_str(), "r");
  if (!pipe) return "{\"jsonrpc\":\"2.0\",\"id\":null,\"error\":{\"code\":-32000,\"message\":\"Unable to start PHP runtime\"}}";
  char buffer[4096]; std::string response;
  while (fgets(buffer, sizeof(buffer), pipe)) response += buffer;
  pclose(pipe);
  while (!response.empty() && (response.back() == '\n' || response.back() == '\r')) response.pop_back();
  return response.empty() ? "null" : response;
}

int main(int argc, const char* argv[]) {
  @autoreleasepool {
    auto host = phpwails::create_platform_webview();
    if (!host) { std::cerr << "Unable to create platform WebView host\n"; return 3; }
    const std::string project = argc > 2 ? argv[2] : "";
    host->set_message_handler([&project](const std::string& request) { return invoke_php_runtime(request, project); });
    phpwails::WindowOptions options; options.title = "PhpWails";
    if (!host->create(options)) { std::cerr << "Unable to create application window\n"; return 3; }
    if (argc > 1) host->navigate("file://" + std::string(argv[1]));
    [NSApp run];
    host->close();
  }
  return 0;
}
