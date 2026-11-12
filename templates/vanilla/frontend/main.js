const result = document.querySelector('#result');
const debug = document.querySelector('#debug');
const report = message => { if (debug) debug.textContent += `${new Date().toISOString()} ${message}\n`; };
report(`webkit=${typeof globalThis.webkit}, handler=${typeof globalThis.webkit?.messageHandlers?.phpwails}, App=${typeof globalThis.App}`);
window.addEventListener('phpwails-runtime-stopped', event => {
  report(`runtime stopped: ${event.detail?.message ?? 'unknown error'}`);
  result.innerHTML = '<strong>PHP Runtime 已停止</strong><br><span>正在尝试重新启动，请稍后重试。</span>';
});
const navMessages = {
  '概览': '当前为应用概览，展示运行状态与 PHP 导出方法。',
  '接口': '接口页面：当前已注册 App.greet，可通过右侧按钮调用。',
  '设置': '设置页面：开发环境配置由 phpwails.json 提供。'
};
document.querySelectorAll('.nav span').forEach(item => item.addEventListener('click', () => {
  document.querySelectorAll('.nav span').forEach(nav => nav.classList.remove('active'));
  item.classList.add('active');
  result.innerHTML = `<strong>${item.textContent}</strong><br><span>${navMessages[item.textContent] ?? '页面已切换。'}</span>`;
  report(`navigation=${item.textContent}`);
}));
document.querySelector('.nav span')?.classList.add('active');
document.querySelector('#greet').addEventListener('click', async event => {
  const button = event.currentTarget;
  button.disabled = true;
  button.textContent = '调用中…';
  result.innerHTML = '<strong>正在调用 PHP Runtime…</strong>';
  report('sending App.greet');
  try {
    const value = await globalThis.App.greet('World');
    result.innerHTML = `<strong>${value}</strong><br><span>PHP 方法调用成功</span>`;
    report(`response=${value}`);
  } catch (error) {
    result.innerHTML = `<strong>调用失败</strong><br><span>${error?.message ?? error}</span>`;
    report(`error=${error?.stack ?? error}`);
  } finally {
    button.disabled = false;
    button.textContent = '调用方法';
  }
});
