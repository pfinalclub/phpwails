const result = document.querySelector('#result');
const debug = document.querySelector('#debug');
const report = message => { debug.textContent += `${new Date().toISOString()} ${message}\n`; };
result.textContent = 'Bridge ready';
report(`webkit=${typeof globalThis.webkit}, handler=${typeof globalThis.webkit?.messageHandlers?.phpwails}, App=${typeof globalThis.App}`);
document.querySelector('#greet').addEventListener('click', async () => {
  result.textContent = 'Calling PHP...'; report('sending App.greet');
  try { result.textContent = await globalThis.App.greet('World'); report(`response=${result.textContent}`); }
  catch (error) { result.textContent = `Error: ${error?.message ?? error}`; report(`error=${error?.stack ?? error}`); }
});
