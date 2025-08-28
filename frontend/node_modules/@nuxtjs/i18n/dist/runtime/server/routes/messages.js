import { deepCopy } from "@intlify/shared";
import { defineCachedEventHandler, defineCachedFunction } from "nitropack/runtime";
import { getRouterParam, createError, defineEventHandler } from "h3";
import { useI18nContext } from "../context.js";
import { getMergedMessages } from "../utils/messages.js";
const _messagesHandler = defineEventHandler(async (event) => {
  const locale = getRouterParam(event, "locale");
  if (!locale) {
    throw createError({ status: 400, message: "Locale not specified." });
  }
  const ctx = useI18nContext(event);
  if (ctx.localeConfigs && locale in ctx.localeConfigs === false) {
    throw createError({ status: 404, message: `Locale '${locale}' not found.` });
  }
  const messages = await getMergedMessages(locale, ctx.localeConfigs?.[locale]?.fallbacks ?? []);
  deepCopy(messages, ctx.messages);
  return ctx.messages;
});
const _cachedMessageLoader = defineCachedFunction(_messagesHandler, {
  name: "i18n:messages-internal",
  maxAge: !__I18N_CACHE__ ? -1 : 60 * 60 * 24,
  getKey: (event) => getRouterParam(event, "locale") ?? "null",
  shouldBypassCache(event) {
    const locale = getRouterParam(event, "locale");
    if (locale == null) return false;
    return !useI18nContext(event).localeConfigs?.[locale]?.cacheable;
  }
});
const _messagesHandlerCached = defineCachedEventHandler(_cachedMessageLoader, {
  name: "i18n:messages",
  maxAge: !__I18N_CACHE__ ? -1 : 10,
  swr: false,
  getKey: (event) => getRouterParam(event, "locale") ?? "null"
});
export default import.meta.dev ? _messagesHandler : _messagesHandlerCached;
