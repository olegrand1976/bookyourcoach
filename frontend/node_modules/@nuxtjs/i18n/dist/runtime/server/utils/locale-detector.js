import { fetchMessages } from "../context.js";
import { deepCopy } from "@intlify/shared";
import { localeDetector } from "#internal/i18n-locale-detector.mjs";
import { tryCookieLocale, tryHeaderLocale, tryQueryLocale } from "@intlify/h3";
import { findBrowserLocale } from "#i18n-kit/browser";
import { parseAcceptLanguage } from "@intlify/utils";
import { normalizedLocales } from "#internal/i18n-options.mjs";
export function createDefaultLocaleDetector(defaultLocale, tryRouteLocale) {
  const normalized = normalizedLocales.map((x) => ({ code: x.code, language: x.language ?? x.code }));
  const headerParser = (value) => [findBrowserLocale(normalized, parseAcceptLanguage(value))];
  function* detect(event) {
    yield tryRouteLocale(event);
    yield tryQueryLocale(event, { lang: "" });
    yield tryCookieLocale(event, { lang: "", name: "i18n_redirected" });
    yield tryHeaderLocale(event, { lang: "", parser: headerParser });
  }
  return (event) => {
    for (const locale of detect(event)) {
      if (locale) {
        return locale.toString();
      }
    }
    return defaultLocale;
  };
}
export function createUserLocaleDetector(defaultLocale, fallbackLocale) {
  return async (event, i18nCtx) => {
    const locale = localeDetector(event, { defaultLocale, fallbackLocale });
    const messages = await fetchMessages(locale);
    for (const locale2 of Object.keys(messages)) {
      i18nCtx.messages[locale2] ??= {};
      deepCopy(messages[locale2], i18nCtx.messages[locale2]);
    }
    return locale;
  };
}
