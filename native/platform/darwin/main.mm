#import <Cocoa/Cocoa.h>
#import <Foundation/Foundation.h>
#include "phpwails/platform/webview_host.h"
#include <atomic>
#include <cstdio>
#include <functional>
#include <iostream>
#include <mutex>
#include <string>
#include <thread>
#include <unordered_set>

static std::string json_error(const char* message) {
  return std::string(R"({"jsonrpc":"2.0","id":null,"error":{"code":-32000,"message":")") + message + R"("}})";
}

static NSDictionary* parse_json_object(const std::string& json) {
  NSString* text = [NSString stringWithUTF8String:json.c_str()];
  NSData* data = [text dataUsingEncoding:NSUTF8StringEncoding];
  if (!data) return nil;
  NSError* error = nil;
  id object = [NSJSONSerialization JSONObjectWithData:data options:0 error:&error];
  return error || ![object isKindOfClass:[NSDictionary class]] ? nil : object;
}

static std::string json_string_value(NSDictionary* object, NSString* key) {
  id value = object[key];
  return [value isKindOfClass:[NSString class]] ? std::string([value UTF8String]) : std::string();
}

/** A single long-lived PHP worker connected through stdin/stdout. */
class PersistentPhpRuntime {
public:
  using ResponseHandler = std::function<void(const std::string&)>;
  using FailureHandler = std::function<void(const std::string&)>;

  ~PersistentPhpRuntime() { stop(); }

  void set_failure_handler(FailureHandler handler) { failure_handler_ = std::move(handler); }

  bool restart() {
    const auto handler = response_handler_;
    stop();
    return start(root_, project_, handler);
  }

  bool start(const std::string& root, const std::string& project, ResponseHandler handler) {
    if (root.empty() || project.empty()) return false;
    root_ = root;
    project_ = project;
    response_handler_ = std::move(handler);
    task_ = [[NSTask alloc] init];
    input_ = [[NSPipe alloc] init];
    output_ = [[NSPipe alloc] init];
    error_ = [[NSPipe alloc] init];

    NSString* php = @"/usr/bin/php";
    NSString* runtime = [NSString stringWithUTF8String:(root + "/packages/runtime/bin/phpwails-runtime.php").c_str()];
    NSString* projectPath = [NSString stringWithUTF8String:project.c_str()];
    task_.executableURL = [NSURL fileURLWithPath:php];
    task_.arguments = @[runtime, projectPath];
    task_.standardInput = input_;
    task_.standardOutput = output_;
    task_.standardError = [NSFileHandle fileHandleWithStandardError];

    @try {
      [task_ launch];
    } @catch (NSException* exception) {
      std::cerr << "Unable to start PHP Runtime: " << exception.reason.UTF8String << "\n";
      task_ = nil;
      return false;
    }

    stopping_ = false;
    reader_ = std::thread([this] { read_responses(); });
    std::cerr << "PHP Runtime worker started (pid " << task_.processIdentifier << ")\n";
    return true;
  }

  bool send(const std::string& request) {
    if (!task_ || !task_.isRunning || request.empty() || request.size() > 1024 * 1024) return false;
    NSDictionary* object = parse_json_object(request);
    if (object && json_string_value(object, @"method") == "$/cancel") {
      NSDictionary* params = [object[@"params"] isKindOfClass:[NSDictionary class]] ? object[@"params"] : nil;
      NSArray* ids = [params[@"ids"] isKindOfClass:[NSArray class]] ? params[@"ids"] : @[];
      std::lock_guard<std::mutex> state_lock(state_mutex_);
      for (id value in ids) if ([value isKindOfClass:[NSString class]]) cancelled_ids_.insert([value UTF8String]);
      return true;
    }
    const unsigned previous = pending_count_.fetch_add(1);
    if (previous >= 64) {
      pending_count_.fetch_sub(1);
      return false;
    }
    std::lock_guard<std::mutex> lock(write_mutex_);
    @try {
      NSString* line = [NSString stringWithFormat:@"%s\n", request.c_str()];
      NSData* data = [line dataUsingEncoding:NSUTF8StringEncoding];
      [input_.fileHandleForWriting writeData:data];
      return true;
    } @catch (NSException* exception) {
      pending_count_.fetch_sub(1);
      std::cerr << "Unable to send PHP request: " << exception.reason.UTF8String << "\n";
      return false;
    }
  }

  void stop() {
    if (stopping_.exchange(true)) return;
    if (input_) {
      @try { [input_.fileHandleForWriting closeFile]; } @catch (...) {}
    }
    if (task_ && task_.isRunning) [task_ terminate];
    if (reader_.joinable()) reader_.join();
    pending_count_ = 0;
    {
      std::lock_guard<std::mutex> state_lock(state_mutex_);
      cancelled_ids_.clear();
    }
    task_ = nil;
    input_ = nil;
    output_ = nil;
    error_ = nil;
  }

private:
  void read_responses() {
    @autoreleasepool {
      NSFileHandle* handle = output_.fileHandleForReading;
      NSMutableData* pending = [NSMutableData data];
      while (!stopping_) {
        @autoreleasepool {
          NSData* chunk = nil;
          @try { chunk = [handle readDataOfLength:4096]; } @catch (...) { break; }
          if (!chunk || chunk.length == 0) break;
          [pending appendData:chunk];
          const uint8_t* bytes = static_cast<const uint8_t*>(pending.bytes);
          NSUInteger start = 0;
          for (NSUInteger i = 0; i < pending.length; ++i) {
            if (bytes[i] != '\n') continue;
            NSData* lineData = [pending subdataWithRange:NSMakeRange(start, i - start)];
            NSString* line = [[NSString alloc] initWithData:lineData encoding:NSUTF8StringEncoding];
            if (line.length > 0) {
              const std::string response = line.UTF8String;
              NSDictionary* response_object = parse_json_object(response);
              const std::string response_id = json_string_value(response_object, @"id");
              bool cancelled = false;
              if (!response_id.empty()) {
                std::lock_guard<std::mutex> state_lock(state_mutex_);
                cancelled = cancelled_ids_.erase(response_id) > 0;
              }
              if (!cancelled && response_handler_) response_handler_(response);
              pending_count_.fetch_sub(1);
            }
            start = i + 1;
          }
          if (start > 0) [pending replaceBytesInRange:NSMakeRange(0, start) withBytes:nullptr length:0];
        }
      }
      if (!stopping_ && failure_handler_) failure_handler_("PHP Runtime Worker stopped unexpectedly");
    }
  }

  NSTask* task_{nil};
  NSPipe* input_{nil};
  NSPipe* output_{nil};
  NSPipe* error_{nil};
  std::thread reader_;
  std::mutex write_mutex_;
  std::atomic<bool> stopping_{false};
  std::atomic<unsigned> pending_count_{0};
  std::mutex state_mutex_;
  std::unordered_set<std::string> cancelled_ids_;
  std::string root_;
  std::string project_;
  ResponseHandler response_handler_;
  FailureHandler failure_handler_;
};

int main(int argc, const char* argv[]) {
  @autoreleasepool {
    auto host = phpwails::create_platform_webview();
    if (!host) { std::cerr << "Unable to create platform WebView host\n"; return 3; }
    const char* root = std::getenv("PHPWAILS_ROOT");
    const std::string project = argc > 2 ? argv[2] : "";
    PersistentPhpRuntime runtime;
    int restart_attempts = 0;
    runtime.set_failure_handler([&host, &runtime, &restart_attempts](const std::string& message) {
      if (host) host->post_message(R"({"event":"runtime.stopped","error":{"code":"runtime.worker_stopped","message":"PHP Runtime Worker stopped unexpectedly"}})");
      std::cerr << message << "\n";
      PersistentPhpRuntime* runtime_ptr = &runtime;
      phpwails::WebViewHost* host_ptr = host.get();
      int* attempts_ptr = &restart_attempts;
      dispatch_async(dispatch_get_main_queue(), ^{
        if (*attempts_ptr >= 3) {
          if (host_ptr) host_ptr->post_message(R"({"event":"runtime.failed","error":{"code":"runtime.restart_limit","message":"PHP Runtime restart limit reached"}})");
          return;
        }
        ++(*attempts_ptr);
        if (runtime_ptr->restart()) {
          if (host_ptr) host_ptr->post_message(R"({"event":"runtime.restarted","message":"PHP Runtime Worker restarted"})");
        } else if (host_ptr) {
          host_ptr->post_message(R"({"event":"runtime.failed","error":{"code":"runtime.restart_failed","message":"Unable to restart PHP Runtime Worker"}})");
        }
      });
    });
    runtime.start(root ? root : "", project, [&host](const std::string& response) {
      if (host) host->post_message(response);
    });
    host->set_message_handler([&runtime, &host](const std::string& request) {
      if (!runtime.send(request) && host) host->post_message(json_error("PHP Runtime is not running or request queue is full"));
    });
    phpwails::WindowOptions options; options.title = "PhpWails";
    if (!host->create(options)) { runtime.stop(); std::cerr << "Unable to create application window\n"; return 3; }
    if (argc > 1) host->navigate("file://" + std::string(argv[1]));
    [NSApp run];
    host->close();
    runtime.stop();
  }
  return 0;
}
