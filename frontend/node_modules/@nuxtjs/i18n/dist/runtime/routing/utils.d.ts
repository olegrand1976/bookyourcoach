import type { Locale } from 'vue-i18n';
import type { RouteLocationPathRaw, RouteLocationResolvedGeneric, Router, RouteRecordNameGeneric } from 'vue-router';
/**
 * Returns a getter function which returns a localized route name for the given route and locale.
 * The returned function can vary based on the strategy and domain configuration.
 */
export declare function createLocaleRouteNameGetter(defaultLocale: string): (name: RouteRecordNameGeneric | null, locale: string) => string;
/**
 * Factory function which returns a resolver function based on the routing strategy.
 */
export declare function createLocalizedRouteByPathResolver(router: Router): (route: RouteLocationPathRaw, locale: Locale) => RouteLocationPathRaw | RouteLocationResolvedGeneric;
