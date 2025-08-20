import { defineComponent, ref, mergeProps, unref, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrRenderAttr, ssrInterpolate, ssrIncludeBooleanAttr } from "vue/server-renderer";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/hookable/dist/index.mjs";
import { u as useHead } from "../server.mjs";
import "ofetch";
import "#internal/nuxt/paths";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/unctx/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/h3/dist/index.mjs";
import "vue-router";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/radix3/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/defu/dist/defu.mjs";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/ufo/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/klona/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/@unhead/vue/dist/index.mjs";
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "settings",
  __ssrInlineRender: true,
  setup(__props) {
    useHead({
      title: "Paramètres Système"
    });
    const loading = ref(false);
    const settings = ref({
      platform_name: "BookYourCoach",
      contact_email: "contact@bookyourcoach.fr",
      contact_phone: "+33 1 23 45 67 89",
      timezone: "Europe/Brussels",
      company_address: "BookYourCoach\nBelgique",
      logo_url: "/logo.svg"
    });
    const stats = ref({
      users: 0,
      teachers: 0,
      students: 0
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-50" }, _attrs))}><div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8"><h1 class="text-3xl font-bold text-gray-900 mb-2">Paramètres Système</h1><p class="mb-6 text-gray-600">Configuration générale de la plateforme</p><div class="bg-white rounded-lg shadow-lg p-6 mb-8"><h2 class="text-xl font-semibold mb-4">Paramètres généraux</h2><form><div class="grid grid-cols-1 md:grid-cols-2 gap-4"><div><label class="block text-gray-700 mb-1">Nom de la plateforme</label><input${ssrRenderAttr("value", unref(settings).platform_name)} class="input-field" required></div><div><label class="block text-gray-700 mb-1">Email de contact</label><input${ssrRenderAttr("value", unref(settings).contact_email)} class="input-field" required type="email"></div><div><label class="block text-gray-700 mb-1">Téléphone de contact</label><input${ssrRenderAttr("value", unref(settings).contact_phone)} class="input-field"></div><div><label class="block text-gray-700 mb-1">Fuseau horaire</label><input${ssrRenderAttr("value", unref(settings).timezone)} class="input-field"></div><div class="md:col-span-2"><label class="block text-gray-700 mb-1">Adresse de la société</label><textarea class="input-field" rows="3">${ssrInterpolate(unref(settings).company_address)}</textarea></div></div><div class="mt-6 border-t pt-6"><h3 class="text-lg font-semibold mb-4">Logo de la plateforme</h3><div class="grid grid-cols-1 md:grid-cols-2 gap-6"><div><label class="block text-gray-700 mb-1">URL du logo</label><input${ssrRenderAttr("value", unref(settings).logo_url)} class="input-field" placeholder="https://..."><div class="mt-2"><label class="block text-gray-700 mb-1">Uploader un logo</label><input type="file" accept="image/*" class="input-field"></div></div>`);
      if (unref(settings).logo_url) {
        _push(`<div class="flex flex-col items-center"><span class="text-sm text-gray-600 mb-2">Aperçu actuel</span><div class="border rounded-lg p-4 bg-gray-50"><img${ssrRenderAttr("src", unref(settings).logo_url)} alt="Logo" class="h-16 w-auto max-w-full"></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div><div class="mt-6 flex justify-end"><button type="submit" class="btn-primary"${ssrIncludeBooleanAttr(unref(loading)) ? " disabled" : ""}>${ssrInterpolate(unref(loading) ? "Sauvegarde..." : "Sauvegarder")}</button></div></form></div><div class="bg-white rounded-lg shadow-lg p-6"><h2 class="text-xl font-semibold mb-4">Informations système</h2><div class="grid grid-cols-1 md:grid-cols-3 gap-4"><div class="bg-blue-50 p-4 rounded"><div class="text-2xl font-bold text-blue-600">${ssrInterpolate(unref(stats).users || 0)}</div><div class="text-sm text-gray-600">Utilisateurs total</div></div><div class="bg-green-50 p-4 rounded"><div class="text-2xl font-bold text-green-600">${ssrInterpolate(unref(stats).teachers || 0)}</div><div class="text-sm text-gray-600">Enseignants</div></div><div class="bg-orange-50 p-4 rounded"><div class="text-2xl font-bold text-orange-600">${ssrInterpolate(unref(stats).students || 0)}</div><div class="text-sm text-gray-600">Étudiants</div></div></div></div></div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/admin/settings.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
//# sourceMappingURL=settings-HYM4LFhB.js.map
