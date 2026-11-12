export interface PhpWailsBridge {
  invoke<T>(method: string, params?: Record<string, unknown>): Promise<T>;
  cancelAll(reason?: string): void;
}

declare global { var phpwails: PhpWailsBridge | undefined; }

export function installBridge(post: (message: unknown) => void): PhpWailsBridge {
  let sequence = 0;
  const pending = new Map<string, {resolve: (v: unknown) => void; reject: (e: unknown) => void; timer: ReturnType<typeof setTimeout>}>();
  const bridge: PhpWailsBridge = {
    invoke<T>(method, params = {}) {
      const id = `invoke-${++sequence}`;
      return new Promise<T>((resolve, reject) => {
        const timer = setTimeout(() => {
          pending.delete(id);
          reject(new Error(`PHP call timed out: ${method}`));
        }, 5000);
        pending.set(id, {resolve, reject, timer});
        try { post({jsonrpc:'2.0', id, method, params}); }
        catch (error) { clearTimeout(timer); pending.delete(id); reject(error); }
      });
    },
    cancelAll(reason = 'PHP calls cancelled') {
      const ids = [...pending.keys()];
      if (ids.length) { try { post({jsonrpc: '2.0', method: '$/cancel', params: {ids}}); } catch (_) {} }
      const error = new Error(reason);
      for (const item of pending.values()) { clearTimeout(item.timer); item.reject(error); }
      pending.clear();
    }
  };
  Object.defineProperty(globalThis, 'phpwails', {value: bridge, configurable: false});
  (bridge as PhpWailsBridge & {resolve?: (message: any) => void}).resolve = (message) => {
    const item = pending.get(message.id); if (!item) return;
    pending.delete(message.id);
    clearTimeout(item.timer);
    if (message.error) item.reject(message.error); else item.resolve(message.result);
  };
  return bridge;
}
