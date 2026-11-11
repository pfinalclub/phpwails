#pragma once
#include <cstdint>
#include <string>
namespace phpwails {
struct WindowOptions { std::string title; int width{1024}; int height{768}; bool centered{true}; };
class Window {
public:
  explicit Window(WindowOptions options) : options_(std::move(options)) {}
  virtual ~Window() = default;
  virtual bool show() = 0;
  virtual void close() = 0;
  const WindowOptions& options() const { return options_; }
private:
  WindowOptions options_;
};
}
