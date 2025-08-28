import type { I18nOptions } from 'vue-i18n';
type RequireProps<T, K extends keyof T> = Omit<T, K> & Required<Pick<T, K>>;
export type ResolvedI18nOptions = RequireProps<I18nOptions, 'messages' | 'locale' | 'fallbackLocale'> & {
    defaultLocale: string;
};
export declare const setupVueI18nOptions: (defaultLocale: string) => Promise<ResolvedI18nOptions>;
export {};
