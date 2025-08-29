import type { LocaleMessages } from '@intlify/core';
import type { DefineLocaleMessage } from '@intlify/h3';
/**
 * Load messages for the specified locale and merge with fallback locales in the shape of `{ [locale]: { ... } }`
 * @param locale - The locale to load messages for
 * @param fallbackLocales - The fallback locales to merge with
 */
export declare const getMergedMessages: (locale: string, fallbackLocales: string[]) => Promise<LocaleMessages<DefineLocaleMessage> | undefined>;
export declare const getAllMergedMessages: (locales: string[]) => Promise<LocaleMessages<DefineLocaleMessage> | undefined>;
