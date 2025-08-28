import type { I18nOptions, Locale, LocaleMessages, DefineLocaleMessage } from 'vue-i18n';
import type { VueI18nConfig } from '#internal-i18n-types';
type MessageLoaderFunction<T = DefineLocaleMessage> = (locale: Locale) => Promise<LocaleMessages<T>>;
type MessageLoaderResult<T, Result = MessageLoaderFunction<T> | LocaleMessages<T>> = {
    default: Result;
} | Result;
type LocaleLoader<T = LocaleMessages<DefineLocaleMessage>> = {
    key: string;
    cache: boolean;
    load: () => Promise<MessageLoaderResult<T>>;
};
export declare function loadVueI18nOptions(vueI18nConfigs: VueI18nConfig[]): Promise<I18nOptions>;
/**
 * Get locale messages from the loaders of a single locale and merge these
 */
export declare function getLocaleMessagesMerged(locale: string, loaders?: LocaleLoader[]): Promise<LocaleMessages<DefineLocaleMessage>>;
/**
 * Wraps the `getLocaleMessages` function to use cache
 */
export declare function getLocaleMessagesMergedCached(locale: string, loaders?: LocaleLoader[]): Promise<LocaleMessages<DefineLocaleMessage>>;
export {};
