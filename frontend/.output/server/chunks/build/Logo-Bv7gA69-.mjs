import { defineComponent, ref, readonly, computed, mergeProps, unref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderAttr, ssrRenderClass, ssrInterpolate } from 'vue/server-renderer';
import { b as useNuxtApp } from './server.mjs';

const useSettings = () => {
  const settings = ref({
    platform_name: "BookYourCoach",
    contact_email: "contact@bookyourcoach.fr",
    contact_phone: "+32 2 123 45 67",
    company_address: "Rue de l'\xC9quitation 123\n1000 Bruxelles\nBelgique",
    timezone: "Europe/Brussels",
    logo_url: "/logo.png",
    favicon_url: "/favicon.ico"
  });
  const loadSettings = async () => {
    var _a, _b;
    try {
      const { $api } = useNuxtApp();
      const response = await $api.get("/admin/settings");
      if (((_a = response.data) == null ? void 0 : _a.success) && ((_b = response.data) == null ? void 0 : _b.data)) {
        settings.value = { ...settings.value, ...response.data.data };
      }
    } catch (error) {
      console.warn("Impossible de charger les param\xE8tres:", error);
    }
  };
  const saveSettings = async (newSettings) => {
    var _a;
    try {
      const { $api } = useNuxtApp();
      const response = await $api.post("/admin/settings", newSettings);
      if ((_a = response.data) == null ? void 0 : _a.success) {
        settings.value = { ...settings.value, ...newSettings };
        return true;
      }
      return false;
    } catch (error) {
      console.error("Erreur lors de la sauvegarde:", error);
      return false;
    }
  };
  return {
    settings: readonly(settings),
    loadSettings,
    saveSettings
  };
};
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "Logo",
  __ssrInlineRender: true,
  props: {
    size: { default: "md" }
  },
  setup(__props) {
    const settings = useSettings();
    const showFallback = ref(false);
    const logoUrl = computed(() => settings.settings.logo_url || "/logo.svg");
    const platformName = computed(() => settings.settings.platform_name || "BookYourCoach");
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "flex items-center" }, _attrs))}><img${ssrRenderAttr("src", unref(logoUrl))}${ssrRenderAttr("alt", unref(platformName))} class="${ssrRenderClass([
        "h-auto",
        _ctx.size === "sm" ? "w-24" : _ctx.size === "lg" ? "w-40" : "w-32"
      ])}">`);
      if (unref(showFallback)) {
        _push(`<span class="${ssrRenderClass([
          "font-serif font-bold text-primary-600",
          _ctx.size === "sm" ? "text-lg" : _ctx.size === "lg" ? "text-3xl" : "text-2xl"
        ])}">${ssrInterpolate(unref(platformName))}</span>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Logo.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as _, useSettings as u };
//# sourceMappingURL=Logo-Bv7gA69-.mjs.map
