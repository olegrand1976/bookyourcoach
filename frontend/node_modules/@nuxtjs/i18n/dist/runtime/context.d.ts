import type { NuxtApp } from '#app';
import type { Locale, I18n } from 'vue-i18n';
import type { ComposableContext } from './utils.js';
import type { I18nPublicRuntimeConfig, LocaleObject } from '#internal-i18n-types';
export declare const useLocaleConfigs: () => import("vue").Ref<Record<string, {
    cacheable: boolean;
    fallbacks: string[];
}> | undefined, Record<string, {
    cacheable: boolean;
    fallbacks: string[];
}> | undefined>;
export declare const useResolvedLocale: () => import("vue").Ref<string, string>;
/**
 * @internal
 */
export interface NuxtI18nContext {
    vueI18n: I18n;
    config: I18nPublicRuntimeConfig;
    /** Initial request/visit */
    initial: boolean;
    /** Locale messages attached during SSR and loaded during hydration */
    preloaded: boolean;
    /** SSG with dynamic locale resources */
    dynamicResourcesSSG: boolean;
    rootRedirect: {
        path: string;
        code: number;
    } | undefined;
    redirectStatusCode: number;
    /** Get default locale */
    getDefaultLocale: () => string;
    /** Get current locale */
    getLocale: () => string;
    /** Set locale directly  */
    setLocale: (locale: string) => Promise<void>;
    /** Set locale - suspend if `skipSettingLocaleOnNavigate` is enabled  */
    setLocaleSuspend: (locale: string) => Promise<void>;
    /** Get normalized runtime locales */
    getLocales: () => LocaleObject[];
    /** Set locale to locale cookie */
    setCookieLocale: (locale: string) => void;
    /** Get current base URL */
    getBaseUrl: (locale?: string) => string;
    /** Load locale messages */
    loadMessages: (locale: Locale) => Promise<void>;
    composableCtx: ComposableContext;
}
export declare function createNuxtI18nContext(nuxt: NuxtApp, vueI18n: I18n, defaultLocale: string): NuxtI18nContext;
export declare function useNuxtI18nContext(nuxt: NuxtApp): NuxtI18nContext;
