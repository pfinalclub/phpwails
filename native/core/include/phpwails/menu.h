#pragma once
#include <functional>
#include <string>
#include <vector>

namespace phpwails {

struct MenuItem {
  std::string id;
  std::string label;
  std::string role;
  std::string action;
  std::string shortcut;
  bool enabled{true};
  bool checked{false};
  bool separator{false};
  std::vector<MenuItem> children;
};

using MenuActionHandler = std::function<void(const std::string& id, const std::string& action)>;

class MenuHost {
public:
  virtual ~MenuHost() = default;
  virtual bool install(const std::string& application_name, const std::vector<MenuItem>& items) = 0;
  virtual void set_action_handler(MenuActionHandler handler) = 0;
  virtual void close() = 0;
};

} // namespace phpwails
