const pending = new Map();
let sequence = 0;
function send(message) {
  const handler = globalThis.webkit?.messageHandlers?.phpwails;
  if (!handler) throw new Error('PhpWails native bridge unavailable');
  handler.postMessage(JSON.stringify(message));
}
globalThis.phpwails = {
  invoke(method, params = {}) {
    const id = `invoke-${++sequence}`;
    return new Promise((resolve, reject) => {
      const timer = setTimeout(() => { pending.delete(id); reject(new Error(`PHP call timed out: ${method}`)); }, 5000);
      pending.set(id, {resolve: value => { clearTimeout(timer); resolve(value); }, reject: error => { clearTimeout(timer); reject(error); }});
      try { send({jsonrpc:'2.0', id, method, params}); }
      catch (error) { clearTimeout(timer); pending.delete(id); reject(error); }
    });
  },
  cancelAll(reason = 'PHP calls cancelled') {
    const ids = [...pending.keys()];
    if (ids.length) { try { send({jsonrpc:'2.0', method:'$/cancel', params:{ids}}); } catch (_) {} }
    const error = new Error(reason);
    for (const item of pending.values()) item.reject(error);
    pending.clear();
  }
};
globalThis.addEventListener('phpwails', event => {
  try {
    const message = typeof event.data === 'string' ? JSON.parse(event.data) : event.data;
    if (message?.event === 'runtime.stopped') {
      const error = new Error(message.error?.message ?? 'PHP Runtime Worker stopped');
      for (const item of pending.values()) item.reject(error);
      pending.clear();
      globalThis.dispatchEvent(new CustomEvent('phpwails-runtime-stopped', {detail: message.error}));
      return;
    }
    const item = pending.get(message?.id); if (!item) return;
    pending.delete(message.id);
    if (message.error) item.reject(message.error); else item.resolve(message.result);
  } catch (error) { console.error('PhpWails response error', error); }
});
