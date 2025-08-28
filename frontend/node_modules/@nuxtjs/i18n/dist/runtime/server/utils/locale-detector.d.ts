import type { H3Event } from 'h3';
import type { CoreOptions, FallbackLocale, Locale } from '@intlify/core';
/**
 * Detects the locale from the request event.
 */
export declare function createDefaultLocaleDetector(defaultLocale: string, tryRouteLocale: (event: H3Event) => string | null): (event: H3Event) => string;
export declare function createUserLocaleDetector(defaultLocale: string, fallbackLocale: FallbackLocale): (event: H3Event, i18nCtx: CoreOptions) => Promise<Locale>;
