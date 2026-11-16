#pragma once
#include <string>

namespace phpwails {

class StatusItemHost {
public:
  virtual ~StatusItemHost() = default;
  virtual bool show(const std::string& icon_path, const std::string& tooltip) = 0;
  virtual void hide() = 0;
  virtual void close() = 0;
};

} // namespace phpwails
