import type { Ref } from 'vue';
import type { Locale } from 'vue-i18n';
import type { I18nHeadMetaInfo, I18nHeadOptions, SeoAttributesOptions } from '#internal-i18n-types';
import type { RouteLocationAsRelativeI18n, RouteLocationResolvedI18n, RouteMap, RouteMapI18n } from 'vue-router';
import type { RouteLocationGenericPath, I18nRouteMeta } from '../types.js';
import type { NuxtApp } from '#app';
export * from 'vue-i18n';
export * from './shared.js';
declare module '#app' {
    interface NuxtApp {
        $localePath: LocalePathFunction;
        $localeRoute: LocaleRouteFunction;
        $routeBaseName: RouteBaseNameFunction;
        $switchLocalePath: SwitchLocalePathFunction;
        /**
         * @deprecated use {@link useLocaleHead} instead
         */
        $localeHead: LocaleHeadFunction;
        /**
         * @deprecated use {@link $routeBaseName} instead
         */
        $getRouteBaseName: RouteBaseNameFunction;
    }
}
declare module 'vue' {
    interface ComponentCustomProperties {
        $localePath: LocalePathFunction;
        $localeRoute: LocaleRouteFunction;
        $routeBaseName: RouteBaseNameFunction;
        $switchLocalePath: SwitchLocalePathFunction;
        /**
         * @deprecated use {@link useLocaleHead} instead
         */
        $localeHead: LocaleHeadFunction;
        /**
         * @deprecated use {@link $routeBaseName} instead
         */
        $getRouteBaseName: RouteBaseNameFunction;
    }
}
/**
 * Used to set i18n params for the current route.
 *
 * @params params - an object with {@link Locale} keys with localized parameters
 */
export type SetI18nParamsFunction = (params: I18nRouteMeta) => void;
/**
 * Returns a {@link SetI18nParamsFunction} used to set i18n params for the current route.
 *
 * @param options - An {@link SeoAttributesOptions} object.
 */
export declare function useSetI18nParams(seo?: SeoAttributesOptions, nuxtApp?: NuxtApp): SetI18nParamsFunction;
/**
 * Returns localized head properties for locale-related aspects.
 *
 * @param options - An {@link I18nHeadOptions} object.
 */
export type LocaleHeadFunction = (options: I18nHeadOptions) => I18nHeadMetaInfo;
/**
 * Returns localized head properties for locale-related aspects.
 *
 * @param options - An {@link I18nHeadOptions} object
 * @returns A ref with localized {@link I18nHeadMetaInfo | head properties}.
 */
export declare function useLocaleHead({ dir, lang, seo }?: I18nHeadOptions, nuxtApp?: NuxtApp): Ref<I18nHeadMetaInfo>;
/**
 * NOTE: regarding composables accepting narrowed route arguments
 * route path string autocompletion is disabled as this can break depending on `strategy`
 * if route resolve is improved to work regardless of strategy this can be enabled again
 *
 * the following would be the complete narrowed type
 * route: Name | RouteLocationAsRelativeI18n | RouteLocationAsStringI18n | RouteLocationAsPathI18n
 */
type RouteLocationI18nGenericPath = Omit<RouteLocationAsRelativeI18n, 'path'> & {
    path?: string;
};
/**
 * Resolves the route base name for the given route.
 *
 * @param route - a route name or route object.
 *
 * @returns Route base name without localization suffix or `undefined` if no name was found.
 */
export type RouteBaseNameFunction = <Name extends keyof RouteMap = keyof RouteMap>(route: Name | RouteLocationGenericPath) => keyof RouteMapI18n | undefined;
/**
 * Returns a {@link RouteBaseNameFunction} used get the base name of a route.
 * @example
 * ```ts
 * const routeBaseName = useRouteBaseName()
 * routeBaseName(route.value) // about-us
 * routeBaseName('about-us__nl') // about-us
 * ```
 */
export declare function useRouteBaseName(nuxtApp?: NuxtApp): RouteBaseNameFunction;
/**
 * Resolves a localized path for the given route.
 *
 * @param route - a route name or route object.
 * @param locale - (default: current locale).
 *
 * @returns Returns the localized path for the given route.
 */
export type LocalePathFunction = <Name extends keyof RouteMapI18n = keyof RouteMapI18n>(route: Name | RouteLocationI18nGenericPath, locale?: Locale) => string;
/**
 * Returns a {@link LocalePathFunction} used to resolve a localized path.
 * @example
 * ```ts
 * const localePath = useLocalePath()
 * localePath('about-us', 'nl') // /nl/over-ons
 * localePath({ name: 'about-us' }, 'nl') // /nl/over-ons
 * ```
 */
export declare function useLocalePath(nuxtApp?: NuxtApp): LocalePathFunction;
/**
 * Resolves a localized route object for the given route.
 *
 * @param route - a route name or route object.
 * @param locale - (default: current locale).
 *
 * @returns A route or `undefined` if no route was resolved.
 */
export type LocaleRouteFunction = <Name extends keyof RouteMapI18n = keyof RouteMapI18n>(route: Name | RouteLocationI18nGenericPath, locale?: Locale) => RouteLocationResolvedI18n<Name> | undefined;
/**
 * Returns a {@link LocaleRouteFunction} used to resolve localized route objects.
 */
export declare function useLocaleRoute(nuxtApp?: NuxtApp): LocaleRouteFunction;
/**
 * Resolves a localized variant of the current path.
 *
 * @param locale - (default: current locale).
 */
export type SwitchLocalePathFunction = (locale: Locale) => string;
/**
 * Returns a {@link SwitchLocalePathFunction} used to resolve a localized variant of the current path.
 * @example
 * ```ts
 * const switchLocalePath = useSwitchLocalePath()
 * switchLocalePath('en') // /about
 * switchLocalePath('nl') // /nl/over-ons
 * ```
 */
export declare function useSwitchLocalePath(nuxtApp?: NuxtApp): SwitchLocalePathFunction;
/**
 * Return the browser locale based on `navigator.languages` (client-side) or `accept-language` header (server-side).
 *
 * @returns the browser locale or `null` if none detected.
 */
export declare function useBrowserLocale(nuxtApp?: NuxtApp): string | null;
/**
 * Returns the locale cookie based on `document.cookie` (client-side) or `cookie` header (server-side).
 *
 * @returns a ref with the detected cookie or an empty string if none is detected or if `detectBrowserLanguage.useCookie` is disabled.
 */
export declare function useCookieLocale(nuxtApp?: NuxtApp): Ref<string>;
/**
 * The i18n custom route for page components
 */
export interface I18nRoute {
    /**
     * Customize page component routes per locale, you can specify static and dynamic paths.
     */
    paths?: Partial<Record<Locale, `/${string}`>>;
    /**
     * Locales in which the page component should be localized.
     */
    locales?: Locale[];
}
/**
 * Define custom route for page component
 *
 * @param route - The custom route
 */
export declare function defineI18nRoute(route: I18nRoute | false): void;
/**
 * Register translation keys for preloading
 *
 * This is used to track keys to include in the preloaded messages which
 * otherwise would not be included during SSR.
 *
 * Examples of keys to register are dynamically or conditionally rendered translations (e.g. inside `v-if` or using computed keys).
 *
 * @param keys - The translation keys to preload
 *
 * @example
 * ```ts
 * useI18nPreloadKeys(['my-dynamic-key', 'nested.dynamic.key'])
 * ```
 */
export declare function useI18nPreloadKeys(keys: string[]): void;
