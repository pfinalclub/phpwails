#pragma once
#include <functional>
#include <string>
namespace phpwails {
struct BridgeMessage { std::string json; };
using MessageHandler = std::function<std::string(const std::string&)>;
class Bridge {
public:
  virtual ~Bridge() = default;
  virtual void receive(const BridgeMessage& message) = 0;
  virtual void send(std::string json) = 0;
};
class RpcBridge final : public Bridge {
public:
  explicit RpcBridge(MessageHandler handler) : handler_(std::move(handler)) {}
  void receive(const BridgeMessage& message) override;
  void send(std::string json) override;
  std::string last_response() const { return last_response_; }
private:
  MessageHandler handler_;
  std::string last_response_;
};
}
