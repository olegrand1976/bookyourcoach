import { ref, mergeProps, unref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderAttr, ssrInterpolate, ssrIncludeBooleanAttr } from 'vue/server-renderer';
import { d as useNuxtApp } from './server.mjs';
import '../nitro/nitro.mjs';
import 'node:http';
import 'node:https';
import 'node:events';
import 'node:buffer';
import 'node:fs';
import 'node:path';
import 'node:crypto';
import 'node:url';
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/utils';
import 'unhead/plugins';
import 'vue-router';

const useSettings = () => {
  const settings = ref({
    platform_name: "activibe",
    contact_email: "contact@activibe.fr",
    contact_phone: "+32 2 123 45 67",
    company_address: "Rue de l'\xC9quitation 123\n1000 Bruxelles\nBelgique",
    timezone: "Europe/Brussels",
    logo_url: "/logo-activibe.svg",
    favicon_url: "/favicon.ico"
  });
  const loadSettings = async () => {
    try {
      const { $api } = useNuxtApp();
      const response = await $api.get("/admin/settings/general");
      if (response.data) {
        settings.value = { ...settings.value, ...response.data };
      }
    } catch (error) {
      console.warn("Impossible de charger les param\xE8tres:", error);
    }
  };
  const saveSettings = async (newSettings) => {
    var _a;
    try {
      const { $api } = useNuxtApp();
      const response = await $api.put("/admin/settings/general", newSettings);
      if ((_a = response.data) == null ? void 0 : _a.message) {
        settings.value = { ...settings.value, ...newSettings };
        console.log("\u2705 Param\xE8tres sauvegard\xE9s:", response.data.message);
        return true;
      }
      return false;
    } catch (error) {
      console.error("\u274C Erreur lors de la sauvegarde:", error);
      return false;
    }
  };
  return {
    settings,
    // Retiré: readonly()
    loadSettings,
    saveSettings
  };
};
const _sfc_main = {
  __name: "settings",
  __ssrInlineRender: true,
  setup(__props) {
    const loading = ref(false);
    const { settings } = useSettings();
    const stats = ref({
      users: 0,
      teachers: 0,
      students: 0
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-50" }, _attrs))}><div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8"><h1 class="text-3xl font-bold text-gray-900 mb-2">Param\xE8tres Syst\xE8me</h1><p class="mb-6 text-gray-600">Configuration g\xE9n\xE9rale de la plateforme</p><div class="bg-white rounded-lg shadow-lg p-6 mb-8"><h2 class="text-xl font-semibold mb-4">Param\xE8tres g\xE9n\xE9raux</h2><form><div class="grid grid-cols-1 md:grid-cols-2 gap-4"><div><label class="block text-gray-700 mb-1">Nom de la plateforme</label><input${ssrRenderAttr("value", unref(settings).platform_name)} class="input-field" required></div><div><label class="block text-gray-700 mb-1">Email de contact</label><input${ssrRenderAttr("value", unref(settings).contact_email)} class="input-field" required type="email"></div><div><label class="block text-gray-700 mb-1">T\xE9l\xE9phone de contact</label><input${ssrRenderAttr("value", unref(settings).contact_phone)} class="input-field"></div><div><label class="block text-gray-700 mb-1">Fuseau horaire</label><input${ssrRenderAttr("value", unref(settings).timezone)} class="input-field"></div><div class="md:col-span-2"><label class="block text-gray-700 mb-1">Adresse de la soci\xE9t\xE9</label><div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4"><div><label class="block text-sm text-gray-600 mb-1">Rue</label><input${ssrRenderAttr("value", unref(settings).company_street)} class="input-field" placeholder="Nom de la rue"></div><div><label class="block text-sm text-gray-600 mb-1">Num\xE9ro</label><input${ssrRenderAttr("value", unref(settings).company_street_number)} class="input-field" placeholder="Num\xE9ro"></div></div><div class="grid grid-cols-1 md:grid-cols-3 gap-4"><div><label class="block text-sm text-gray-600 mb-1">Code postal</label><input${ssrRenderAttr("value", unref(settings).company_postal_code)} class="input-field" placeholder="Code postal"></div><div><label class="block text-sm text-gray-600 mb-1">Ville</label><input${ssrRenderAttr("value", unref(settings).company_city)} class="input-field" placeholder="Ville"></div><div><label class="block text-sm text-gray-600 mb-1">Pays</label><input${ssrRenderAttr("value", unref(settings).company_country)} class="input-field" placeholder="France"></div></div></div></div><div class="form-button-group"><button type="submit" class="btn-primary"${ssrIncludeBooleanAttr(unref(loading)) ? " disabled" : ""}>${ssrInterpolate(unref(loading) ? "Sauvegarde..." : "Sauvegarder")}</button></div></form></div><div class="bg-white rounded-lg shadow-lg p-6"><h2 class="text-xl font-semibold mb-4">Informations syst\xE8me</h2><div class="grid grid-cols-1 md:grid-cols-3 gap-4"><div class="bg-blue-50 p-4 rounded"><div class="text-2xl font-bold text-blue-600">${ssrInterpolate(unref(stats).users || 0)}</div><div class="text-sm text-gray-600">Utilisateurs total</div></div><div class="bg-green-50 p-4 rounded"><div class="text-2xl font-bold text-green-600">${ssrInterpolate(unref(stats).teachers || 0)}</div><div class="text-sm text-gray-600">Enseignants</div></div><div class="bg-orange-50 p-4 rounded"><div class="text-2xl font-bold text-orange-600">${ssrInterpolate(unref(stats).students || 0)}</div><div class="text-sm text-gray-600">\xC9tudiants</div></div></div></div></div></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/admin/settings.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=settings-Cr-z7yTo.mjs.map
