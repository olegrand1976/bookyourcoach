import type { FallbackLocale } from 'vue-i18n';
type LocaleConfig = {
    cacheable: boolean;
    fallbacks: string[];
};
export declare function createLocaleConfigs(fallbackLocale: FallbackLocale): Record<string, LocaleConfig>;
/**
 * Check if the loaders for the specified locale are all cacheable
 */
export declare function isLocaleCacheable(locale: string): any;
/**
 * Check if the loaders for the specified locale and fallback locales are all cacheable
 */
export declare function isLocaleWithFallbacksCacheable(locale: string, fallbackLocales: string[]): any;
/**
 * Returns default locale for the current domain, returns `defaultLocale` by default
 */
export declare function getDefaultLocaleForDomain(host: string): string | undefined;
export declare const isSupportedLocale: (locale?: string) => boolean;
export declare const resolveSupportedLocale: (locale: string | undefined) => string | undefined;
export {};
