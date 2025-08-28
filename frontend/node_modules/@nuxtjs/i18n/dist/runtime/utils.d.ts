import { useHead } from '#imports';
import { type RouteLike } from './routing/routing.js';
import type { Locale } from 'vue-i18n';
import type { NuxtApp } from '#app';
import type { RouteLocationPathRaw, Router, RouteRecordNameGeneric } from 'vue-router';
import type { BaseUrlResolveHandler, I18nHeadMetaInfo, I18nHeadOptions, LocaleObject } from '#internal-i18n-types';
import type { NuxtI18nContext } from './context.js';
import type { CompatRoute, I18nRouteMeta, RouteLocationGenericPath } from './types.js';
/**
 * Common options used internally by composable functions, these
 * are initialized on request at the start of i18n:plugin.
 *
 * @internal
 */
export type ComposableContext = {
    router: Router;
    routingOptions: {
        defaultLocale: string;
        /** Use `canonicalQueries` for alternate links */
        strictCanonicals: boolean;
        /** Enable/disable hreflangLinks */
        hreflangLinks: boolean;
    };
    head: ReturnType<typeof useHead>;
    metaState: Required<I18nHeadMetaInfo>;
    seoSettings: I18nHeadOptions;
    localePathPayload: Record<string, Record<string, string> | false>;
    getLocale: () => string;
    getLocales: () => LocaleObject[];
    getBaseUrl: () => string;
    /** Extracts the route base name (without locale suffix) */
    getRouteBaseName: (route: RouteRecordNameGeneric | RouteLocationGenericPath | null) => string | undefined;
    /** Modifies the resolved localized path. Middleware for `switchLocalePath` */
    afterSwitchLocalePath: (path: string, locale: string) => string;
    /** Provides localized dynamic parameters for the current route */
    getLocalizedDynamicParams: (locale: string) => Record<string, unknown> | false | undefined;
    /** Prepares a route object to be resolved as a localized route */
    resolveLocalizedRouteObject: (route: RouteLike, locale: string) => RouteLike;
    getRouteLocalizedParams: () => Partial<I18nRouteMeta>;
};
export declare const isRouteLocationPathRaw: (val: RouteLike) => val is RouteLocationPathRaw;
export declare function useComposableContext(nuxtApp: NuxtApp): ComposableContext;
export declare function createComposableContext(ctx: NuxtI18nContext, nuxtApp?: NuxtApp): ComposableContext;
declare global {
    interface Window {
        _i18nSlp: Record<string, Record<string, unknown> | false> | undefined;
    }
}
export declare function loadAndSetLocale(nuxtApp: NuxtApp, locale: Locale): Promise<string>;
export declare function detectLocale(nuxtApp: NuxtApp, route: string | CompatRoute): string;
export declare function navigate(nuxtApp: NuxtApp, to: CompatRoute, locale: string): string | false | void | import("vue-router").RouteLocationAsRelativeGeneric | import("vue-router").RouteLocationAsPathGeneric | Promise<false | void | import("vue-router").NavigationFailure>;
export declare function prefixable(currentLocale: string, defaultLocale: string): boolean;
/**
 * Returns a getter function which returns the baseUrl
 */
export declare function createBaseUrlGetter(nuxt: NuxtApp, baseUrl: string | BaseUrlResolveHandler<unknown> | undefined, defaultLocale: string, getDomainFromLocale: (locale: string) => string | undefined): () => string;
