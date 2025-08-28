import { getRequestURL, getRequestHeader } from "h3";
import { normalizedLocales } from "#build/i18n-options.mjs";
import { getLocaleFromRoute, getLocaleFromRoutePath } from "#i18n-kit/routing";
import { findBrowserLocale } from "#i18n-kit/browser";
import { parseAcceptLanguage } from "@intlify/utils";
import { matchDomainLocale } from "./domain.js";
import { useRuntimeI18n } from "../shared/utils.js";
import { parse } from "cookie-es";
const getCookieLocale = (event, cookieName) => {
  const cookieValue = import.meta.client ? document.cookie : getRequestHeader(event, "cookie") || "";
  return parse(cookieValue)[cookieName];
};
const getRouteLocale = (event, route) => getLocaleFromRoute(route);
const getHeaderLocale = (event) => {
  return findBrowserLocale(normalizedLocales, parseAcceptLanguage(getRequestHeader(event, "accept-language") || ""));
};
const getNavigatorLocale = (event) => {
  return findBrowserLocale(normalizedLocales, navigator.languages);
};
const getHostLocale = (event, path, domainLocales) => {
  const host = import.meta.client ? new URL(window.location.href).host : getRequestURL(event, { xForwardedHost: true }).host;
  const locales = normalizedLocales.map((l) => ({
    ...l,
    domain: domainLocales[l.code]?.domain ?? l.domain
  }));
  return matchDomainLocale(locales, host, getLocaleFromRoutePath(path));
};
export const useDetectors = (event, config, nuxtApp) => {
  if (import.meta.server && !event) {
    throw new Error("H3Event is required for server-side locale detection");
  }
  const runtimeI18n = useRuntimeI18n(nuxtApp);
  return {
    cookie: () => getCookieLocale(event, config.cookieKey),
    header: () => import.meta.server ? getHeaderLocale(event) : void 0,
    navigator: () => import.meta.client ? getNavigatorLocale(event) : void 0,
    host: (path) => getHostLocale(event, path, runtimeI18n.domainLocales),
    route: (path) => getRouteLocale(event, path)
  };
};
