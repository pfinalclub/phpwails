export interface PhpWailsErrorData { name: string; category: string; retryable?: boolean; correlationId?: string; details?: unknown }
export class PhpWailsError extends Error { constructor(public readonly data: PhpWailsErrorData) { super(data.name); this.name = 'PhpWailsError'; } }
export async function invoke<T>(method: string, params: Record<string, unknown> = {}): Promise<T> {
  const bridge = (globalThis as typeof globalThis & { phpwails?: { invoke?: (m: string, p: Record<string, unknown>) => Promise<T> } }).phpwails;
  if (!bridge?.invoke) throw new PhpWailsError({name: 'bridge.unavailable', category: 'runtime', retryable: false});
  return bridge.invoke(method, params);
}
