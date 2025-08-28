import type { LocaleMessages } from '@intlify/core';
import type { DefineLocaleMessage } from '@intlify/h3';
import type { H3Event, H3EventContext } from 'h3';
import type { ResolvedI18nOptions } from '../shared/vue-i18n.js';
export declare function useI18nContext(event: H3Event): {
    /**
     * Cached locale configurations based on runtime config
     * @internal
     */
    localeConfigs?: Record<string, ServerLocaleConfig>;
    /**
     * SwitchLocalePath dynamic parameters state
     * @internal
     */
    slp: Record<string, unknown>;
    /**
     * The loaded messages for the current request, used to insert into the rendered HTML for hydration
     * @internal
     */
    messages: LocaleMessages<DefineLocaleMessage>;
    /**
     * The list of keys that are tracked for the current request
     * @internal
     */
    trackMap: Record<string, Set<string>>;
    /**
     * Track message key for the current request
     * @internal
     */
    trackKey: (key: string, locale: string) => void;
    detectLocale?: string;
    vueI18nOptions?: ResolvedI18nOptions;
};
export declare function tryUseI18nContext(event: H3Event): {
    /**
     * Cached locale configurations based on runtime config
     * @internal
     */
    localeConfigs?: Record<string, ServerLocaleConfig>;
    /**
     * SwitchLocalePath dynamic parameters state
     * @internal
     */
    slp: Record<string, unknown>;
    /**
     * The loaded messages for the current request, used to insert into the rendered HTML for hydration
     * @internal
     */
    messages: LocaleMessages<DefineLocaleMessage>;
    /**
     * The list of keys that are tracked for the current request
     * @internal
     */
    trackMap: Record<string, Set<string>>;
    /**
     * Track message key for the current request
     * @internal
     */
    trackKey: (key: string, locale: string) => void;
    detectLocale?: string;
    vueI18nOptions?: ResolvedI18nOptions;
} | undefined;
/**
 * Fetches the messages for the specified locale.
 * @internal
 */
export declare const fetchMessages: (locale: string) => Promise<LocaleMessages<DefineLocaleMessage>>;
export declare function createI18nContext(): NonNullable<H3EventContext['nuxtI18n']>;
interface ServerLocaleConfig {
    /**
     * Message files (and its fallback locale message files) are cacheable
     */
    cacheable: boolean;
    /**
     * Fallback locale codes
     */
    fallbacks: string[];
}
declare module 'h3' {
    interface H3EventContext {
        /** @internal */
        nuxtI18n?: {
            /**
             * Cached locale configurations based on runtime config
             * @internal
             */
            localeConfigs?: Record<string, ServerLocaleConfig>;
            /**
             * SwitchLocalePath dynamic parameters state
             * @internal
             */
            slp: Record<string, unknown>;
            /**
             * The loaded messages for the current request, used to insert into the rendered HTML for hydration
             * @internal
             */
            messages: LocaleMessages<DefineLocaleMessage>;
            /**
             * The list of keys that are tracked for the current request
             * @internal
             */
            trackMap: Record<string, Set<string>>;
            /**
             * Track message key for the current request
             * @internal
             */
            trackKey: (key: string, locale: string) => void;
            detectLocale?: string;
            vueI18nOptions?: ResolvedI18nOptions;
        };
    }
}
export {};
