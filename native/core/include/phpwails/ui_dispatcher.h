#pragma once
#include <functional>
namespace phpwails { class UiDispatcher { public: virtual ~UiDispatcher() = default; virtual void post(std::function<void()> task) = 0; }; }
