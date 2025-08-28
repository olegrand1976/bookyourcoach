export interface CacheOptions<ArgsT extends unknown[] = any[]> {
    name?: string;
    getKey: (...args: ArgsT) => string;
    shouldBypassCache: (...args: ArgsT) => boolean;
    group?: string;
    /**
     * Number of seconds to cache the response. Defaults to 1.
     */
    maxAge?: number;
}
/**
 * Create a cached function
 * Adapted from nitropack/runtime `cachedFunction`
 */
export declare function cachedFunctionI18n<T, ArgsT extends unknown[] = any[]>(fn: (...args: ArgsT) => T | Promise<T>, opts: CacheOptions<ArgsT>): (...args: ArgsT) => Promise<T | undefined>;
