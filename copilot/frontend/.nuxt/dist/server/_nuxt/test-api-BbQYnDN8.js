import { ref, mergeProps, unref, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrInterpolate, ssrRenderAttr, ssrIncludeBooleanAttr, ssrRenderClass } from "vue/server-renderer";
import { b as useNuxtApp, g as useRuntimeConfig } from "../server.mjs";
import "ofetch";
import "#internal/nuxt/paths";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/hookable/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/unctx/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/h3/dist/index.mjs";
import "vue-router";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/radix3/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/defu/dist/defu.mjs";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/ufo/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/klona/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/@unhead/vue/dist/index.mjs";
const _sfc_main = {
  __name: "test-api",
  __ssrInlineRender: true,
  setup(__props) {
    const config = useRuntimeConfig();
    const { $api } = useNuxtApp();
    const apiStatus = ref("Non testé");
    const testing = ref(false);
    const loginTesting = ref(false);
    const result = ref(null);
    const email = ref("admin@bookyourcoach.com");
    const password = ref("admin123");
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-100 flex items-center justify-center" }, _attrs))}><div class="max-w-md w-full bg-white p-8 rounded-lg shadow-md"><h1 class="text-2xl font-bold mb-6 text-center">Test de Configuration API</h1><div class="space-y-4"><div class="p-4 bg-blue-50 rounded-lg"><h3 class="font-semibold text-blue-800">Configuration Runtime</h3><p class="text-sm text-blue-600">API Base: ${ssrInterpolate(unref(config).public.apiBase)}</p><p class="text-sm text-blue-600">App Name: ${ssrInterpolate(unref(config).public.appName)}</p></div><div class="p-4 bg-green-50 rounded-lg"><h3 class="font-semibold text-green-800">État API</h3><p class="text-sm text-green-600">Status: ${ssrInterpolate(unref(apiStatus))}</p><button class="mt-2 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"${ssrIncludeBooleanAttr(unref(testing)) ? " disabled" : ""}>${ssrInterpolate(unref(testing) ? "Test en cours..." : "Tester API")}</button></div><div class="p-4 bg-yellow-50 rounded-lg"><h3 class="font-semibold text-yellow-800">Test de Connexion</h3><form class="space-y-3"><input${ssrRenderAttr("value", unref(email))} type="email" placeholder="Email" class="w-full p-2 border rounded"><input${ssrRenderAttr("value", unref(password))} type="password" placeholder="Mot de passe" class="w-full p-2 border rounded"><button type="submit" class="w-full py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700"${ssrIncludeBooleanAttr(unref(loginTesting)) ? " disabled" : ""}>${ssrInterpolate(unref(loginTesting) ? "Connexion..." : "Se connecter")}</button></form></div>`);
      if (unref(result)) {
        _push(`<div class="${ssrRenderClass([unref(result).success ? "bg-green-50" : "bg-red-50", "p-4 rounded-lg"])}"><h3 class="${ssrRenderClass([unref(result).success ? "text-green-800" : "text-red-800", "font-semibold"])}"> Résultat du Test </h3><pre class="${ssrRenderClass([unref(result).success ? "text-green-600" : "text-red-600", "text-xs mt-2 overflow-auto"])}">${ssrInterpolate(JSON.stringify(unref(result).data, null, 2))}</pre></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/test-api.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
//# sourceMappingURL=test-api-BbQYnDN8.js.map
