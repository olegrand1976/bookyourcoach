import type { LocaleObject } from '#internal-i18n-types';
export declare function matchDomainLocale(locales: LocaleObject[], host: string, pathLocale: string): string | undefined;
export declare function domainFromLocale(domainLocales: Record<string, {
    domain: string | undefined;
}>, url: {
    host: string;
    protocol: string;
}, locale: string): string | undefined;
