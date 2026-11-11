#include <string>
#include <unordered_map>
namespace phpwails {
struct InvocationResult { bool ok; std::string payload; };
class InvokeDispatcher {
public:
  using Handler = InvocationResult (*)(const std::string&);
  void expose(std::string method, Handler handler) { handlers_[std::move(method)] = handler; }
  InvocationResult invoke(const std::string& method, const std::string& params) const {
    auto it = handlers_.find(method);
    if (it == handlers_.end()) return {false, "method_not_found"};
    return it->second(params);
  }
private:
  std::unordered_map<std::string, Handler> handlers_;
};
}
