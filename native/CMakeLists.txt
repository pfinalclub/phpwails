cmake_minimum_required(VERSION 3.20)
project(phpwails_native LANGUAGES C CXX OBJCXX)
set(CMAKE_CXX_STANDARD 17)
set(CMAKE_CXX_STANDARD_REQUIRED ON)
set(PHPWAILS_PLATFORM_SOURCE)
if(APPLE)
  set(PHPWAILS_PLATFORM_SOURCE platform/darwin/wkwebview_host.mm)
elseif(WIN32)
  set(PHPWAILS_PLATFORM_SOURCE platform/windows/webview2_host.cpp)
elseif(UNIX)
  set(PHPWAILS_PLATFORM_SOURCE platform/linux/webkitgtk_host.cpp)
endif()
add_library(phpwails_core STATIC
  core/src/bridge.cpp
  core/src/ui_dispatcher.cpp
  core/src/application.cpp
  core/src/window.cpp
  extension/src/invoke_dispatch.cpp
  core/src/json_rpc_router.cpp
  ${PHPWAILS_PLATFORM_SOURCE}
)
target_include_directories(phpwails_core PUBLIC core/include)
if(APPLE)
  target_link_libraries(phpwails_core PRIVATE "-framework Cocoa" "-framework WebKit")
  add_executable(phpwails-native platform/darwin/main.mm)
  set_target_properties(phpwails-native PROPERTIES RUNTIME_OUTPUT_DIRECTORY "${CMAKE_SOURCE_DIR}/../build/native")
  target_link_libraries(phpwails-native PRIVATE phpwails_core "-framework Cocoa" "-framework WebKit")
  target_include_directories(phpwails-native PRIVATE core/include)
endif()
if(WIN32 AND EXISTS "${CMAKE_CURRENT_SOURCE_DIR}/platform/windows/CMakeLists.txt")
  add_subdirectory(platform/windows)
elseif(APPLE AND EXISTS "${CMAKE_CURRENT_SOURCE_DIR}/platform/darwin/CMakeLists.txt")
  add_subdirectory(platform/darwin)
elseif(UNIX AND EXISTS "${CMAKE_CURRENT_SOURCE_DIR}/platform/linux/CMakeLists.txt")
  add_subdirectory(platform/linux)
endif()
