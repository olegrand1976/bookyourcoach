import { useNuxtApp, useCookie, useRequestEvent } from "#imports";
import { ref, watch } from "vue";
import { _useLocaleHead, _useSetI18nParams } from "../routing/head.js";
import { useComposableContext } from "../utils.js";
import { localePath, localeRoute, switchLocalePath } from "../routing/routing.js";
export * from "vue-i18n";
export * from "./shared.js";
export function useSetI18nParams(seo, nuxtApp = useNuxtApp()) {
  const common = useComposableContext(nuxtApp);
  return _useSetI18nParams(common, seo);
}
export function useLocaleHead({ dir = true, lang = true, seo = true } = {}, nuxtApp = useNuxtApp()) {
  if (__I18N_STRICT_SEO__) {
    throw new Error(
      "Strict SEO mode is enabled, `useLocaleHead` should not be used as localized head tags are handled internally by `@nuxtjs/i18n`"
    );
  }
  const common = useComposableContext(nuxtApp);
  common.seoSettings = { dir, lang, seo };
  const head = _useLocaleHead(common, common.seoSettings);
  if (import.meta.client) {
    watch(head, () => common.metaState = head.value);
  }
  common.metaState = head.value;
  return head;
}
export function useRouteBaseName(nuxtApp = useNuxtApp()) {
  const common = useComposableContext(nuxtApp);
  return (route) => {
    if (route == null) return;
    return common.getRouteBaseName(route) || void 0;
  };
}
export function useLocalePath(nuxtApp = useNuxtApp()) {
  const common = useComposableContext(nuxtApp);
  return (route, locale) => localePath(common, route, locale);
}
export function useLocaleRoute(nuxtApp = useNuxtApp()) {
  const common = useComposableContext(nuxtApp);
  return (route, locale) => localeRoute(common, route, locale);
}
export function useSwitchLocalePath(nuxtApp = useNuxtApp()) {
  const common = useComposableContext(nuxtApp);
  return (locale) => switchLocalePath(common, locale);
}
export function useBrowserLocale(nuxtApp = useNuxtApp()) {
  return (nuxtApp || useNuxtApp()).$i18n.getBrowserLocale() || null;
}
export function useCookieLocale(nuxtApp = useNuxtApp()) {
  const locale = ref("");
  const nuxt = nuxtApp || useNuxtApp();
  const detect = nuxt.$config.public.i18n.detectBrowserLanguage;
  if (!detect || !detect.useCookie) {
    return locale;
  }
  const locales = useComposableContext(nuxt).getLocales();
  const code = useCookie(detect.cookieKey).value;
  if (code && locales.some((x) => x.code === code)) {
    locale.value = code;
  }
  return locale;
}
const warnRuntimeUsage = (method) => console.warn(
  method + "() is a compiler-hint helper that is only usable inside the script block of a single file component. Its arguments should be compiled away and passing it at runtime has no effect."
);
export function defineI18nRoute(route) {
  if (import.meta.dev) {
    warnRuntimeUsage("defineI18nRoute");
  }
}
export function useI18nPreloadKeys(keys) {
  if (import.meta.server) {
    const ctx = useRequestEvent()?.context?.nuxtI18n;
    if (ctx == null) {
      console.warn("useI18nPreloadKeys(): `nuxtI18n` server context is accessible.");
      return;
    }
    const locale = useComposableContext(useNuxtApp()).getLocale();
    if (!locale) {
      console.warn("useI18nPreloadKeys(): Could not resolve locale during server-side render.");
      return;
    }
    for (const k of keys) {
      ctx?.trackKey(k, locale);
    }
  }
}
