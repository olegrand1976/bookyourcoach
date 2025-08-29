import { isEqual, joinURL, withoutTrailingSlash, withTrailingSlash } from "ufo";
import { isFunction, isString } from "@intlify/shared";
import { navigateTo, useHead, useNuxtApp, useRequestEvent, useRequestURL, useRouter } from "#imports";
import { createLocaleRouteNameGetter, createLocalizedRouteByPathResolver } from "./routing/utils.js";
import { getRouteBaseName } from "#i18n-kit/routing";
import {
  localePath,
  switchLocalePath
} from "./routing/routing.js";
import { useNuxtI18nContext } from "./context.js";
import { getDefaultLocaleForDomain, isSupportedLocale } from "./shared/locales.js";
import { useDetectors } from "./shared/detection.js";
import { useI18nDetection } from "./shared/utils.js";
export const isRouteLocationPathRaw = (val) => !!val.path && !val.name;
export function useComposableContext(nuxtApp) {
  const context = nuxtApp?._nuxtI18n?.composableCtx;
  if (!context) {
    throw new Error(
      "i18n context is not initialized. Ensure the i18n plugin is installed and the composable is used within a Vue component or setup function."
    );
  }
  return context;
}
const formatTrailingSlash = __TRAILING_SLASH__ ? withTrailingSlash : withoutTrailingSlash;
export function createComposableContext(ctx, nuxtApp = useNuxtApp()) {
  const router = useRouter();
  const detectors = useDetectors(useRequestEvent(), useI18nDetection(nuxtApp), nuxtApp);
  const defaultLocale = ctx.getDefaultLocale();
  const getLocalizedRouteName = createLocaleRouteNameGetter(defaultLocale);
  function resolveLocalizedRouteByName(route, locale) {
    route.name ||= getRouteBaseName(router.currentRoute.value);
    const localizedName = getLocalizedRouteName(route.name, locale);
    if (router.hasRoute(localizedName)) {
      route.name = localizedName;
    }
    return route;
  }
  const routeByPathResolver = createLocalizedRouteByPathResolver(router);
  function resolveLocalizedRouteByPath(input, locale) {
    const route = routeByPathResolver(input, locale);
    const baseName = getRouteBaseName(route);
    if (baseName) {
      route.name = getLocalizedRouteName(baseName, locale);
      return route;
    }
    if (prefixable(locale, defaultLocale)) {
      route.path = "/" + locale + route.path;
    }
    route.path = formatTrailingSlash(route.path, true);
    return route;
  }
  const composableCtx = {
    router,
    head: useHead({}),
    metaState: { htmlAttrs: {}, meta: [], link: [] },
    seoSettings: {
      dir: __I18N_STRICT_SEO__,
      lang: __I18N_STRICT_SEO__,
      seo: __I18N_STRICT_SEO__
    },
    localePathPayload: getLocalePathPayload(),
    routingOptions: {
      defaultLocale,
      strictCanonicals: ctx.config.experimental.alternateLinkCanonicalQueries ?? true,
      hreflangLinks: !(!__I18N_ROUTING__ && !__DIFFERENT_DOMAINS__)
    },
    getLocale: ctx.getLocale,
    getLocales: ctx.getLocales,
    getBaseUrl: ctx.getBaseUrl,
    getRouteBaseName,
    getRouteLocalizedParams: () => router.currentRoute.value.meta[__DYNAMIC_PARAMS_KEY__] ?? {},
    getLocalizedDynamicParams: (locale) => {
      if (__I18N_STRICT_SEO__ && import.meta.client && nuxtApp.isHydrating && composableCtx.localePathPayload) {
        return composableCtx.localePathPayload[locale] || {};
      }
      return composableCtx.getRouteLocalizedParams()?.[locale];
    },
    afterSwitchLocalePath: (path, locale) => {
      const params = composableCtx.getRouteLocalizedParams();
      if (__I18N_STRICT_SEO__ && locale && Object.keys(params).length && !params[locale]) {
        return "";
      }
      if (__MULTI_DOMAIN_LOCALES__ && __I18N_STRATEGY__ === "prefix_except_default") {
        const defaultLocale2 = getDefaultLocaleForDomain(useRequestURL({ xForwardedHost: true }).host);
        if (locale !== defaultLocale2 || detectors.route(path) !== defaultLocale2) {
          return path;
        }
        return path.slice(locale.length + 1);
      }
      if (__DIFFERENT_DOMAINS__) {
        return joinURL(ctx.getBaseUrl(locale), path);
      }
      return path;
    },
    resolveLocalizedRouteObject: (route, locale) => {
      return isRouteLocationPathRaw(route) ? resolveLocalizedRouteByPath(route, locale) : resolveLocalizedRouteByName(route, locale);
    }
  };
  return composableCtx;
}
function getLocalePathPayload(nuxtApp = useNuxtApp()) {
  const payload = import.meta.client && document.querySelector(`[data-nuxt-i18n-slp="${nuxtApp._id}"]`)?.textContent;
  return JSON.parse(payload || "{}");
}
export async function loadAndSetLocale(nuxtApp, locale) {
  const ctx = useNuxtI18nContext(nuxtApp);
  const oldLocale = ctx.getLocale();
  if (locale === oldLocale && !ctx.initial) {
    return locale;
  }
  const data = { oldLocale, newLocale: locale, initialSetup: ctx.initial, context: nuxtApp };
  let override = await nuxtApp.callHook("i18n:beforeLocaleSwitch", data);
  if (override != null && import.meta.dev) {
    console.warn("[nuxt-i18n] Do not return in `i18n:beforeLocaleSwitch`, mutate `data.newLocale` instead.");
  }
  override ??= data.newLocale;
  if (isSupportedLocale(override)) {
    locale = override;
  }
  await ctx.loadMessages(locale);
  await ctx.setLocaleSuspend(locale);
  return locale;
}
function skipDetect(detect, path, pathLocale) {
  if (!__I18N_ROUTING__) {
    return false;
  }
  if (detect.redirectOn === "root" && path !== "/") {
    return true;
  }
  if (detect.redirectOn === "no prefix" && !detect.alwaysRedirect && isSupportedLocale(pathLocale)) {
    return true;
  }
  return false;
}
export function detectLocale(nuxtApp, route) {
  const detectConfig = useI18nDetection(nuxtApp);
  const detectors = useDetectors(useRequestEvent(nuxtApp), detectConfig, nuxtApp);
  const ctx = useNuxtI18nContext(nuxtApp);
  const path = isString(route) ? route : route.path;
  function* detect() {
    if (ctx.initial && detectConfig.enabled && !skipDetect(detectConfig, path, detectors.route(path))) {
      yield detectors.cookie();
      yield detectors.header();
      yield detectors.navigator();
      yield detectConfig.fallbackLocale;
    }
    if (__DIFFERENT_DOMAINS__ || __MULTI_DOMAIN_LOCALES__) {
      yield detectors.host(path);
    }
    if (__I18N_ROUTING__) {
      yield detectors.route(route);
    }
  }
  for (const detected of detect()) {
    if (detected && isSupportedLocale(detected)) {
      return detected;
    }
  }
  return ctx.getLocale() || ctx.getDefaultLocale() || "";
}
export function navigate(nuxtApp, to, locale) {
  if (!__I18N_ROUTING__ || __DIFFERENT_DOMAINS__) return;
  const ctx = useNuxtI18nContext(nuxtApp);
  const _ctx = useComposableContext(nuxtApp);
  if (to.path === "/" && ctx.rootRedirect) {
    return navigateTo(localePath(_ctx, ctx.rootRedirect.path, locale), { redirectCode: ctx.rootRedirect.code });
  }
  if (ctx.vueI18n.__pendingLocale && useNuxtApp()._processingMiddleware) {
    return;
  }
  const detectors = useDetectors(useRequestEvent(), useI18nDetection(nuxtApp), nuxtApp);
  if (detectors.route(to) === locale) {
    return;
  }
  const destination = switchLocalePath(_ctx, locale, to) || localePath(_ctx, to.fullPath, locale);
  if (isEqual(destination, to.fullPath)) {
    return;
  }
  return navigateTo(destination, { redirectCode: ctx.redirectStatusCode });
}
export function prefixable(currentLocale, defaultLocale) {
  return !__DIFFERENT_DOMAINS__ && __I18N_ROUTING__ && // only prefix default locale with strategy prefix
  (currentLocale !== defaultLocale || __I18N_STRATEGY__ === "prefix");
}
export function createBaseUrlGetter(nuxt, baseUrl, defaultLocale, getDomainFromLocale) {
  if (isFunction(baseUrl)) {
    import.meta.dev && console.warn("[nuxt-i18n] Configuring baseUrl as a function is deprecated and will be removed in v11.");
    return () => baseUrl(nuxt);
  }
  return () => {
    if (__DIFFERENT_DOMAINS__ && defaultLocale) {
      return (getDomainFromLocale(defaultLocale) || baseUrl) ?? "";
    }
    return baseUrl ?? "";
  };
}
