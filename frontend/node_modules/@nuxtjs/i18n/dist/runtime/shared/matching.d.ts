export declare function isExistingNuxtRoute(path: string): import("vue-router").MatcherLocation | undefined;
/**
 * Match a localized path against the resolved path and return the new path if it differs.
 * The passed path can be localized or not but should not include any prefix.
 */
export declare function matchLocalized(path: string, locale: string, defaultLocale: string): string | undefined;
