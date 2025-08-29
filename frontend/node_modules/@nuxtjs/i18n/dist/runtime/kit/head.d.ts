import type { RouteLocationNormalizedLoadedGeneric, RouteLocationResolvedGeneric } from 'vue-router';
import type { HeadLocale } from './types.js';
/**
 * Meta attributes for head properties.
 * @internal
 */
export type MetaAttrs = Record<string, string>;
/**
 * I18n header meta info.
 * @internal
 */
export interface I18nHeadMetaInfo {
    htmlAttrs: MetaAttrs;
    meta: MetaAttrs[];
    link: MetaAttrs[];
}
type SeoAttributesOptions = {
    /**
     * An array of strings corresponding to query params
     * to include in your canonical URL.
     * @default []
     */
    canonicalQueries?: string[];
};
/**
 * @internal
 */
export type HeadContext = {
    key: string;
    dir: boolean;
    lang: boolean;
    seo: boolean | SeoAttributesOptions | undefined;
    baseUrl: string;
    locales: HeadLocale[];
    defaultLocale: string | undefined;
    hreflangLinks: boolean;
    strictCanonicals: boolean;
    canonicalQueries: string[];
    getCurrentLanguage: () => string | undefined;
    getCurrentDirection: () => string;
    getRouteBaseName: (route: RouteLocationResolvedGeneric | RouteLocationNormalizedLoadedGeneric) => string | undefined;
    getLocaleRoute: (route: RouteLocationResolvedGeneric) => RouteLocationResolvedGeneric | undefined;
    getCurrentRoute: () => RouteLocationNormalizedLoadedGeneric;
    getRouteWithoutQuery: () => RouteLocationResolvedGeneric | undefined;
    getLocalizedRoute: (locale: string, route: RouteLocationResolvedGeneric | undefined) => string;
};
/**
 * @internal
 */
export declare function localeHead(options: HeadContext, currentLanguage?: string | undefined, currentDirection?: string): I18nHeadMetaInfo;
export {};
