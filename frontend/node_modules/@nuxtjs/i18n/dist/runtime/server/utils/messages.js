import { deepCopy } from "@intlify/shared";
import { localeLoaders } from "#internal/i18n-options.mjs";
import { getLocaleMessagesMerged } from "../../shared/messages.js";
import { cachedFunctionI18n } from "./cache.js";
import { isLocaleCacheable, isLocaleWithFallbacksCacheable } from "../../shared/locales.js";
const _getMessages = async (locale) => {
  return { [locale]: await getLocaleMessagesMerged(locale, localeLoaders[locale]) };
};
const _getMessagesCached = cachedFunctionI18n(_getMessages, {
  name: "messages",
  maxAge: !__I18N_CACHE__ ? -1 : 60 * 60 * 24,
  getKey: (locale) => locale,
  shouldBypassCache: (locale) => !isLocaleCacheable(locale)
});
const getMessages = import.meta.dev ? _getMessages : _getMessagesCached;
const _getMergedMessages = async (locale, fallbackLocales) => {
  const merged = {};
  try {
    if (fallbackLocales.length > 0) {
      const messages = await Promise.all(fallbackLocales.map(getMessages));
      for (const message2 of messages) {
        deepCopy(message2, merged);
      }
    }
    const message = await getMessages(locale);
    deepCopy(message, merged);
    return merged;
  } catch (e) {
    throw new Error("Failed to merge messages: " + e.message);
  }
};
export const getMergedMessages = cachedFunctionI18n(_getMergedMessages, {
  name: "merged-single",
  maxAge: !__I18N_CACHE__ ? -1 : 60 * 60 * 24,
  getKey: (locale, fallbackLocales) => `${locale}-[${[...new Set(fallbackLocales)].sort().join("-")}]`,
  shouldBypassCache: (locale, fallbackLocales) => !isLocaleWithFallbacksCacheable(locale, fallbackLocales)
});
const _getAllMergedMessages = async (locales) => {
  const merged = {};
  try {
    const messages = await Promise.all(locales.map(getMessages));
    for (const message of messages) {
      deepCopy(message, merged);
    }
    return merged;
  } catch (e) {
    throw new Error("Failed to merge messages: " + e.message);
  }
};
export const getAllMergedMessages = cachedFunctionI18n(_getAllMergedMessages, {
  name: "merged-all",
  maxAge: !__I18N_CACHE__ ? -1 : 60 * 60 * 24,
  getKey: (locales) => locales.join("-"),
  shouldBypassCache: (locales) => !locales.every((locale) => isLocaleCacheable(locale))
});
