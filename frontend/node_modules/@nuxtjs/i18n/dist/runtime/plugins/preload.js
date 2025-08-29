import { parse } from "devalue";
import { unref } from "vue";
import { useNuxtApp, defineNuxtPlugin } from "#app";
import { localeCodes, localeLoaders } from "#build/i18n-options.mjs";
import { getLocaleMessagesMergedCached } from "../shared/messages.js";
import { useNuxtI18nContext } from "../context.js";
export default defineNuxtPlugin({
  name: "i18n:plugin:preload",
  dependsOn: ["i18n:plugin"],
  async setup(_nuxt) {
    if (!__I18N_PRELOAD__) return;
    const nuxt = useNuxtApp(_nuxt._id);
    const ctx = useNuxtI18nContext(nuxt);
    if (import.meta.server) {
      for (const locale of localeCodes) {
        try {
          const messages = await $fetch(`/_i18n/${locale}/messages.json`);
          for (const locale2 of Object.keys(messages)) {
            nuxt.$i18n.mergeLocaleMessage(locale2, messages[locale2]);
          }
        } catch (e) {
          console.log("Error loading messages", e);
        }
      }
      ctx.preloaded = true;
      const serverI18n = nuxt.ssrContext?.event.context.nuxtI18n;
      if (serverI18n) {
        const msg = unref(ctx.vueI18n.global.messages);
        serverI18n.messages ??= {};
        for (const k in msg) {
          serverI18n.messages[k] = msg[k];
        }
      }
    }
    if (import.meta.client) {
      await mergePayloadMessages(ctx, ctx.vueI18n.global, nuxt);
      if (ctx.preloaded && __I18N_STRIP_UNUSED__) {
        const unsub = nuxt.$router.beforeResolve(async (to, from) => {
          if (to.path === from.path) return;
          await ctx.loadMessages(ctx.getLocale());
          unsub();
        });
      }
    }
  }
});
async function mergePayloadMessages(ctx, i18n, nuxt = useNuxtApp()) {
  const content = document.querySelector(`[data-nuxt-i18n="${nuxt._id}"]`)?.textContent;
  const preloadedMessages = content && parse(content);
  const preloadedKeys = Object.keys(preloadedMessages || {});
  if (preloadedKeys.length) {
    if (ctx.dynamicResourcesSSG) {
      const getKeyedLocaleMessages = async (locale) => {
        return { [locale]: await getLocaleMessagesMergedCached(locale, localeLoaders[locale]) };
      };
      try {
        const msg = await Promise.all(preloadedKeys.map(getKeyedLocaleMessages));
        for (const m of msg) {
          for (const k in m) {
            i18n.mergeLocaleMessage(k, m[k]);
          }
        }
      } catch (e) {
        console.log("Error loading messages", e);
      }
      ctx.preloaded = true;
    } else {
      for (const locale of preloadedKeys) {
        const messages = preloadedMessages[locale];
        if (messages) {
          i18n.mergeLocaleMessage(locale, messages);
        }
      }
      ctx.preloaded = true;
    }
  }
}
