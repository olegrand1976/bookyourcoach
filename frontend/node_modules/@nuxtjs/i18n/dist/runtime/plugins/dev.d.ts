declare module '../context' {
    interface NuxtI18nContext {
        dev?: {
            resetI18nProperties: (locale?: string) => Promise<void>;
            deepEqual: typeof deepEqual;
        };
    }
}
declare const _default: import("#app").Plugin<Record<string, unknown>> & import("#app").ObjectPlugin<Record<string, unknown>>;
export default _default;
declare function deepEqual<T extends Record<string, unknown>, K extends keyof T>(a: T, b: T, ignoreKeys?: K[]): boolean;
