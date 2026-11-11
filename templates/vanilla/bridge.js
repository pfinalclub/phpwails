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
      pending.set(id, {resolve, reject});
      send({jsonrpc: '2.0', id, method, params});
    });
  }
};

globalThis.addEventListener('phpwails', (event) => {
  const message = event.data;
  if (typeof message === 'string') {
    try { handle(JSON.parse(message)); } catch (error) { console.error(error); }
  } else handle(message);
});

function handle(message) {
  const item = pending.get(message?.id);
  if (!item) return;
  pending.delete(message.id);
  if (message.error) item.reject(message.error); else item.resolve(message.result);
}
