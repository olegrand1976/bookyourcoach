import { type NuxtApp } from '#app';
import type { DetectBrowserLanguageOptions, I18nPublicRuntimeConfig, RootRedirectOptions } from '#internal-i18n-types';
export declare function useRuntimeI18n(nuxtApp?: NuxtApp): I18nPublicRuntimeConfig;
export declare function useI18nDetection(nuxtApp: NuxtApp | undefined): DetectBrowserLanguageOptions & {
    enabled: boolean;
    cookieKey: string;
};
export declare function resolveRootRedirect(config: string | RootRedirectOptions | undefined): {
    path: string;
    code: number;
} | undefined;
export declare function toArray<T>(value: T | T[]): T[];
