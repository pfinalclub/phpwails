export interface PhpWailsBridge {
  invoke<T>(method: string, params?: Record<string, unknown>): Promise<T>;
}

declare global { var phpwails: PhpWailsBridge | undefined; }

export function installBridge(post: (message: unknown) => void): PhpWailsBridge {
  let sequence = 0;
  const pending = new Map<string, {resolve: (v: unknown) => void; reject: (e: unknown) => void}>();
  const bridge: PhpWailsBridge = {
    invoke<T>(method, params = {}) {
      const id = `invoke-${++sequence}`;
      return new Promise<T>((resolve, reject) => { pending.set(id, {resolve, reject}); post({jsonrpc:'2.0', id, method, params}); });
    }
  };
  Object.defineProperty(globalThis, 'phpwails', {value: bridge, configurable: false});
  (bridge as PhpWailsBridge & {resolve?: (message: any) => void}).resolve = (message) => {
    const item = pending.get(message.id); if (!item) return; pending.delete(message.id);
    if (message.error) item.reject(message.error); else item.resolve(message.result);
  };
  return bridge;
}
