#include "phpwails/json_rpc_router.h"
#include <regex>
namespace phpwails {
std::string JsonRpcRouter::route(const std::string& request) const {
  static const std::regex method_re("\\\"method\\\"\\s*:\\s*\\\"([^\\\"]+)\\\"");
  static const std::regex id_re("\\\"id\\\"\\s*:\\s*\\\"([^\\\"]+)\\\"");
  std::smatch method_match, id_match;
  if (!std::regex_search(request, method_match, method_re) || !std::regex_search(request, id_match, id_re))
    return R"({"jsonrpc":"2.0","id":null,"error":{"code":-32600,"message":"Invalid Request"}})";
  const std::string params = request;
  const std::string result = invoke_ ? invoke_(method_match[1].str(), params) : "null";
  return "{\"jsonrpc\":\"2.0\",\"id\":\"" + id_match[1].str() + "\",\"result\":" + result + "}";
}
}
