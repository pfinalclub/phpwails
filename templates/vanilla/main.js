import './bridge.js';
import { App } from './phpwails/index.js';

const result = document.querySelector('#result');
document.querySelector('#greet').addEventListener('click', async () => {
  try {
    result.textContent = await App.greet('World');
  } catch (error) {
    result.textContent = `Error: ${error?.message ?? error}`;
  }
});
