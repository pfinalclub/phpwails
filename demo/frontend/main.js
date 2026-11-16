const result = document.querySelector('#result');
const debug = document.querySelector('#debug');
const status = document.querySelector('#runtime-status');
const report = message => { if (debug) debug.textContent += `${new Date().toISOString()} ${message}\n`; };

report(`webkit=${typeof globalThis.webkit}, bridge=${typeof globalThis.phpwails}, App=${typeof globalThis.App}`);
const show = value => { result.innerHTML = `<strong>${typeof value === 'string' ? value : JSON.stringify(value)}</strong>`; };
const run = async (button, task) => {
  button.disabled = true;
  try { const value = await task(); show(value); report(`response=${JSON.stringify(value)}`); }
  catch (error) { result.innerHTML = `<strong>调用失败</strong><br>${error?.message ?? error}`; report(`error=${error?.stack ?? error}`); }
  finally { button.disabled = false; }
};

document.querySelector('#greet')?.addEventListener('click', event => run(event.currentTarget, () => globalThis.App.greet('World')));
document.querySelector('#info')?.addEventListener('click', event => run(event.currentTarget, () => globalThis.App.frameworkInfo()));
document.querySelector('#validate')?.addEventListener('click', event => run(event.currentTarget, () => globalThis.App.validateName('PhpWails contributor')));
window.addEventListener('phpwails-runtime-stopped', event => {
  status.textContent = 'Runtime stopped';
  status.previousElementSibling.style.background = '#d04444';
  report(`runtime stopped: ${event.detail?.message ?? 'unknown error'}`);
});
