import { assign } from "@intlify/shared";
import { normalizeRouteName, getLocalizedRouteName } from "#i18n-kit/routing";
export function createLocaleRouteNameGetter(defaultLocale) {
  if (!__I18N_ROUTING__ && !__DIFFERENT_DOMAINS__) {
    return (routeName) => normalizeRouteName(routeName);
  }
  if (__I18N_STRATEGY__ === "prefix_and_default") {
    return (name, locale) => getLocalizedRouteName(normalizeRouteName(name), locale, locale === defaultLocale);
  }
  return (name, locale) => getLocalizedRouteName(normalizeRouteName(name), locale, false);
}
export function createLocalizedRouteByPathResolver(router) {
  if (!__I18N_ROUTING__) {
    return (route) => route;
  }
  if (__I18N_STRATEGY__ === "prefix") {
    return (route, locale) => {
      const targetPath = "/" + locale + (route.path === "/" ? "" : route.path);
      const _route = router.options.routes.find((r) => r.path === targetPath);
      if (_route == null) {
        return route;
      }
      return router.resolve(assign({}, route, _route, { path: targetPath }));
    };
  }
  return (route) => router.resolve(route);
}
