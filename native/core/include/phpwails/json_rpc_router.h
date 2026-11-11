#pragma once
#include <functional>
#include <string>
namespace phpwails {
class JsonRpcRouter final {
public:
  using Invoke = std::function<std::string(const std::string&, const std::string&)>;
  explicit JsonRpcRouter(Invoke invoke) : invoke_(std::move(invoke)) {}
  std::string route(const std::string& request) const;
private:
  Invoke invoke_;
};
}
