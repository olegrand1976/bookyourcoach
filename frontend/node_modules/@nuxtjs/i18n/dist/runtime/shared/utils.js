import { useRuntimeConfig } from "#app";
import { isString } from "@intlify/shared";
export function useRuntimeI18n(nuxtApp) {
  if (!nuxtApp) {
    return useRuntimeConfig().public.i18n;
  }
  return nuxtApp.$config.public.i18n;
}
export function useI18nDetection(nuxtApp) {
  const detectBrowserLanguage = useRuntimeI18n(nuxtApp).detectBrowserLanguage;
  const detect = detectBrowserLanguage || {};
  return {
    ...detect,
    enabled: !!detectBrowserLanguage,
    cookieKey: detect.cookieKey || __DEFAULT_COOKIE_KEY__
  };
}
export function resolveRootRedirect(config) {
  if (!config) return void 0;
  return {
    path: "/" + (isString(config) ? config : config.path).replace(/^\//, ""),
    code: !isString(config) && config.statusCode || 302
  };
}
export function toArray(value) {
  return Array.isArray(value) ? value : [value];
}
