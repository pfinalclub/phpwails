#pragma once
#include <functional>
#include <string>
namespace phpwails {
class Application {
public:
  explicit Application(std::string title) : title_(std::move(title)) {}
  virtual ~Application() = default;
  virtual int run() = 0;
  virtual void quit() = 0;
  const std::string& title() const { return title_; }
private:
  std::string title_;
};
}
