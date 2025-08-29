import type { Locale } from './types.js';
/**
 * Find the browser locale
 *
 * @param locales - The target {@link Locale} list
 * @param browserLocales - The locale code list that is used in browser
 *
 * @returns The matched the locale code
 * @internal
 */
export declare function findBrowserLocale(locales: Locale[], browserLocales: readonly string[]): string;
