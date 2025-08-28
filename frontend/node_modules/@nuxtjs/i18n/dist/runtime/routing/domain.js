import { useRouter } from "#imports";
import { defaultRouteNameSuffix, getLocaleFromRouteName } from "#i18n-kit/routing";
export function setupMultiDomainLocales(defaultLocale, router = useRouter()) {
  if (__I18N_STRATEGY__ !== "prefix_except_default" && __I18N_STRATEGY__ !== "prefix_and_default") return;
  for (const route of router.getRoutes()) {
    const routeName = String(route.name);
    if (routeName.endsWith(defaultRouteNameSuffix)) {
      router.removeRoute(routeName);
      continue;
    }
    const locale = getLocaleFromRouteName(routeName);
    if (locale === defaultLocale) {
      router.addRoute({ ...route, path: route.path.replace(new RegExp(`^/${locale}/?`), "/") });
    }
  }
}
