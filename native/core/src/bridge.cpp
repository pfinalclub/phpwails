#include "phpwails/bridge.h"
namespace phpwails {
void RpcBridge::receive(const BridgeMessage& message) {
  if (handler_) send(handler_(message.json));
}
void RpcBridge::send(std::string json) { last_response_ = std::move(json); }
}
