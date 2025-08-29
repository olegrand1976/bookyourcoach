import { vueI18nConfigs } from "#build/i18n-options.mjs";
import { defineNuxtPlugin, useNuxtApp } from "#imports";
import { getComposer } from "../compatibility.js";
import { useNuxtI18nContext } from "../context.js";
import { loadVueI18nOptions } from "../shared/messages.js";
export default defineNuxtPlugin({
  name: "i18n:dev",
  dependsOn: ["i18n:plugin"],
  setup(_nuxt) {
    if (!import.meta.dev) return;
    const nuxt = useNuxtApp(_nuxt._id);
    const ctx = useNuxtI18nContext(nuxt);
    const composer = getComposer(ctx.vueI18n);
    async function resetI18nProperties(locale) {
      const opts = await loadVueI18nOptions(vueI18nConfigs);
      const messageLocales = uniqueKeys(opts.messages, composer.messages.value);
      for (const k of messageLocales) {
        if (locale && k !== locale) continue;
        composer.setLocaleMessage(k, opts?.messages?.[k] ?? {});
        await ctx.loadMessages(k);
      }
      if (locale != null) return;
      const numberFormatLocales = uniqueKeys(opts.numberFormats || {}, composer.numberFormats.value);
      for (const k of numberFormatLocales) {
        composer.setNumberFormat(k, opts.numberFormats?.[k] || {});
      }
      const datetimeFormatsLocales = uniqueKeys(opts.datetimeFormats || {}, composer.datetimeFormats.value);
      for (const k of datetimeFormatsLocales) {
        composer.setDateTimeFormat(k, opts.datetimeFormats?.[k] || {});
      }
    }
    nuxt._nuxtI18n.dev = {
      resetI18nProperties,
      deepEqual
    };
  }
});
function uniqueKeys(...objects) {
  const keySet = /* @__PURE__ */ new Set();
  for (const obj of objects) {
    for (const key of Object.keys(obj)) {
      keySet.add(key);
    }
  }
  return Array.from(keySet);
}
function deepEqual(a, b, ignoreKeys = []) {
  if (a === b) return true;
  if (a == null || b == null || typeof a !== "object" || typeof b !== "object") return false;
  const keysA = Object.keys(a).filter((k) => !ignoreKeys.includes(k));
  const keysB = Object.keys(b).filter((k) => !ignoreKeys.includes(k));
  if (keysA.length !== keysB.length) return false;
  for (const key of keysA) {
    if (!keysB.includes(key)) return false;
    const valA = a[key];
    const valB = b[key];
    if (typeof valA === "function" && typeof valB === "function") {
      if (valA.toString() !== valB.toString()) {
        return false;
      }
    } else if (typeof valA === "object" && typeof valB === "object") {
      if (!deepEqual(valA, valB)) {
        return false;
      }
    } else if (valA !== valB) {
      return false;
    }
  }
  return true;
}
