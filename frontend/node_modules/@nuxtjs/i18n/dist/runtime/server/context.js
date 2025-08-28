export function useI18nContext(event) {
  if (event.context.nuxtI18n == null) {
    throw new Error("Nuxt I18n server context has not been set up yet.");
  }
  return event.context.nuxtI18n;
}
export function tryUseI18nContext(event) {
  return event.context.nuxtI18n;
}
const headers = new Headers({ "x-nuxt-i18n": "internal" });
if (import.meta.dev) {
  headers.set("Cache-Control", "no-cache");
}
export const fetchMessages = async (locale) => await $fetch(`/_i18n/${locale}/messages.json`, { headers });
export function createI18nContext() {
  return {
    messages: {},
    slp: {},
    localeConfigs: {},
    trackMap: {},
    vueI18nOptions: void 0,
    trackKey(key, locale) {
      this.trackMap[locale] ??= /* @__PURE__ */ new Set();
      this.trackMap[locale].add(key);
    }
  };
}
