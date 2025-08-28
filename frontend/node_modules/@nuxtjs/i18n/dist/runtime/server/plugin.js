import { stringify } from "devalue";
import { defineI18nMiddleware } from "@intlify/h3";
import { defineNitroPlugin, useStorage } from "nitropack/runtime";
import { tryUseI18nContext, createI18nContext } from "./context.js";
import { createUserLocaleDetector } from "./utils/locale-detector.js";
import { pickNested } from "./utils/messages-utils.js";
import { createLocaleConfigs, getDefaultLocaleForDomain, isSupportedLocale } from "../shared/locales.js";
import { setupVueI18nOptions } from "../shared/vue-i18n.js";
import { joinURL } from "ufo";
import { appId } from "#internal/nuxt.config.mjs";
import { localeDetector } from "#internal/i18n-locale-detector.mjs";
import { resolveRootRedirect, useI18nDetection, useRuntimeI18n } from "../shared/utils.js";
import { isFunction } from "@intlify/shared";
import { getRequestURL, sendRedirect, setCookie } from "h3";
import { useDetectors } from "../shared/detection.js";
import { domainFromLocale } from "../shared/domain.js";
import { isExistingNuxtRoute, matchLocalized } from "../shared/matching.js";
const getHost = (event) => getRequestURL(event, { xForwardedHost: true }).host;
function* detect(detectors, detection, path) {
  if (detection.enabled) {
    yield { locale: detectors.cookie(), source: "cookie" };
    yield { locale: detectors.header(), source: "header" };
  }
  if (__DIFFERENT_DOMAINS__ || __MULTI_DOMAIN_LOCALES__) {
    yield { locale: detectors.host(path), source: "domain" };
  }
  if (__I18N_ROUTING__) {
    yield { locale: detectors.route(path), source: "route" };
  }
  yield { locale: detection.fallbackLocale, source: "fallback" };
}
export default defineNitroPlugin(async (nitro) => {
  const runtimeI18n = useRuntimeI18n();
  const rootRedirect = resolveRootRedirect(runtimeI18n.rootRedirect);
  const _defaultLocale = runtimeI18n.defaultLocale || "";
  try {
    const cacheStorage = useStorage("cache");
    const cachedKeys = await cacheStorage.getKeys("nitro:handlers:i18n");
    await Promise.all(cachedKeys.map((key) => cacheStorage.removeItem(key)));
  } catch {
  }
  const detection = useI18nDetection(void 0);
  const cookieOptions = {
    path: "/",
    domain: detection.cookieDomain || void 0,
    maxAge: 60 * 60 * 24 * 365,
    sameSite: "lax",
    secure: detection.cookieSecure
  };
  const getDomainFromLocale = (event, locale) => {
    if (!__MULTI_DOMAIN_LOCALES__ && !__DIFFERENT_DOMAINS__) return;
    return domainFromLocale(runtimeI18n.domainLocales, getRequestURL(event, { xForwardedHost: true }), locale);
  };
  const createBaseUrlGetter = () => {
    let baseUrl = isFunction(runtimeI18n.baseUrl) ? "" : runtimeI18n.baseUrl || "";
    if (isFunction(runtimeI18n.baseUrl)) {
      import.meta.dev && console.warn("[nuxt-i18n] Configuring baseUrl as a function is deprecated and will be removed in v11.");
      return () => "";
    }
    return (event, defaultLocale) => {
      if ((__MULTI_DOMAIN_LOCALES__ || __DIFFERENT_DOMAINS__) && defaultLocale) {
        return getDomainFromLocale(event, defaultLocale) || baseUrl;
      }
      return "";
    };
  };
  function resolveRedirectPath(event, path, pathLocale, defaultLocale, detector) {
    let locale = "";
    for (const detected of detect(detector, detection, event.path)) {
      if (detected.locale && isSupportedLocale(detected.locale)) {
        locale = detected.locale;
        break;
      }
    }
    locale ||= defaultLocale;
    function getLocalizedMatch(locale2) {
      const res = matchLocalized(path || "/", locale2, defaultLocale);
      if (res && res !== event.path) {
        return res;
      }
    }
    let resolvedPath = void 0;
    let redirectCode = 302;
    const requestURL = getRequestURL(event);
    if (rootRedirect && requestURL.pathname === "/") {
      locale = detection.enabled && locale || defaultLocale;
      resolvedPath = isSupportedLocale(detector.route(rootRedirect.path)) && rootRedirect.path || matchLocalized(rootRedirect.path, locale, defaultLocale);
      redirectCode = rootRedirect.code;
    } else if (runtimeI18n.redirectStatusCode) {
      redirectCode = runtimeI18n.redirectStatusCode;
    }
    switch (detection.redirectOn) {
      case "root":
        if (requestURL.pathname !== "/") break;
      // fallthrough (root has no prefix)
      case "no prefix":
        if (pathLocale) break;
      // fallthrough to resolve
      case "all":
        resolvedPath ??= getLocalizedMatch(locale);
        break;
    }
    if (requestURL.pathname === "/" && __I18N_STRATEGY__ === "prefix") {
      resolvedPath ??= getLocalizedMatch(defaultLocale);
    }
    return { path: resolvedPath, code: redirectCode, locale };
  }
  const baseUrlGetter = createBaseUrlGetter();
  nitro.hooks.hook("request", async (event) => {
    const options = await setupVueI18nOptions(getDefaultLocaleForDomain(getHost(event)) || _defaultLocale);
    const url = getRequestURL(event);
    const ctx = createI18nContext();
    event.context.nuxtI18n = ctx;
    if (__I18N_SERVER_REDIRECT__) {
      const detector = useDetectors(event, detection);
      const localeSegment = detector.route(event.path);
      const pathLocale = isSupportedLocale(localeSegment) && localeSegment || void 0;
      const path = pathLocale && url.pathname.slice(pathLocale.length + 1) || url.pathname;
      if (!url.pathname.includes("/_i18n/") && !isExistingNuxtRoute(path)) {
        return;
      }
      const resolved = resolveRedirectPath(event, path, pathLocale, options.defaultLocale, detector);
      if (resolved.path && resolved.path !== url.pathname) {
        ctx.detectLocale = resolved.locale;
        detection.useCookie && setCookie(event, detection.cookieKey, resolved.locale, cookieOptions);
        await sendRedirect(
          event,
          joinURL(baseUrlGetter(event, options.defaultLocale), resolved.path + url.search),
          resolved.code
        );
        return;
      }
    }
    const localeConfigs = createLocaleConfigs(options.fallbackLocale);
    ctx.vueI18nOptions = options;
    ctx.localeConfigs = localeConfigs;
  });
  nitro.hooks.hook("render:html", (htmlContext, { event }) => {
    const ctx = tryUseI18nContext(event);
    if (__I18N_PRELOAD__) {
      if (ctx == null || Object.keys(ctx.messages ?? {}).length == 0) return;
      if (__I18N_STRIP_UNUSED__ && !__IS_SSG__) {
        const trackedLocales = Object.keys(ctx.trackMap);
        for (const locale of Object.keys(ctx.messages)) {
          if (!trackedLocales.includes(locale)) {
            ctx.messages[locale] = {};
            continue;
          }
          const usedKeys = Array.from(ctx.trackMap[locale]);
          ctx.messages[locale] = pickNested(usedKeys, ctx.messages[locale]);
        }
      }
      try {
        htmlContext.bodyAppend.unshift(
          `<script type="application/json" data-nuxt-i18n="${appId}">${stringify(ctx.messages)}<\/script>`
        );
      } catch (_) {
        console.log(_);
      }
    }
    if (__I18N_STRICT_SEO__) {
      const raw = JSON.stringify(ctx?.slp ?? {});
      const safe = raw.replace(/</g, "\\u003c").replace(/\u2028/g, "\\u2028").replace(/\u2029/g, "\\u2029");
      htmlContext.head.push(`<script type="application/json" data-nuxt-i18n-slp="${appId}">${safe}<\/script>`);
    }
  });
  if (localeDetector != null) {
    const options = await setupVueI18nOptions(_defaultLocale);
    const i18nMiddleware = defineI18nMiddleware({
      ...options,
      locale: createUserLocaleDetector(options.locale, options.fallbackLocale)
    });
    nitro.hooks.hook("request", i18nMiddleware.onRequest);
    nitro.hooks.hook("afterResponse", i18nMiddleware.onAfterResponse);
  }
});
