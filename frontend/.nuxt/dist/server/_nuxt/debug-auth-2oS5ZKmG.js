import { ref, mergeProps, unref, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrInterpolate, ssrRenderClass, ssrRenderList } from "vue/server-renderer";
import { u as useAuthStore } from "./auth-BBLAd2fH.js";
import { b as useNuxtApp } from "../server.mjs";
import "/workspace/frontend/node_modules/nuxt/node_modules/cookie-es/dist/index.mjs";
import "/workspace/frontend/node_modules/h3/dist/index.mjs";
import "/workspace/frontend/node_modules/destr/dist/index.mjs";
import "/workspace/frontend/node_modules/ohash/dist/index.mjs";
import "/workspace/frontend/node_modules/klona/dist/index.mjs";
import "./ssr-B4FXEZKR.js";
import "ofetch";
import "#internal/nuxt/paths";
import "/workspace/frontend/node_modules/hookable/dist/index.mjs";
import "/workspace/frontend/node_modules/unctx/dist/index.mjs";
import "vue-router";
import "/workspace/frontend/node_modules/radix3/dist/index.mjs";
import "/workspace/frontend/node_modules/defu/dist/defu.mjs";
import "/workspace/frontend/node_modules/ufo/dist/index.mjs";
import "/workspace/frontend/node_modules/@unhead/vue/dist/index.mjs";
const _sfc_main = {
  __name: "debug-auth",
  __ssrInlineRender: true,
  setup(__props) {
    const authStore = useAuthStore();
    const { $api } = useNuxtApp();
    const logs = ref([]);
    return (_ctx, _push, _parent, _attrs) => {
      var _a, _b, _c, _d;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "container mx-auto p-8" }, _attrs))}><h1 class="text-3xl font-bold mb-6">🔍 Debug Authentification</h1><div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6"><h2 class="text-xl font-semibold mb-3">📊 État actuel</h2><div class="grid grid-cols-1 md:grid-cols-2 gap-4"><div><strong>Utilisateur:</strong> ${ssrInterpolate(((_a = unref(authStore).user) == null ? void 0 : _a.name) || "Non connecté")}</div><div><strong>Email:</strong> ${ssrInterpolate(((_b = unref(authStore).user) == null ? void 0 : _b.email) || "N/A")}</div><div><strong>Rôle:</strong><span class="${ssrRenderClass(((_c = unref(authStore).user) == null ? void 0 : _c.role) === "admin" ? "text-green-600 font-bold" : "text-red-600 font-bold")}">${ssrInterpolate(((_d = unref(authStore).user) == null ? void 0 : _d.role) || "N/A")}</span></div><div><strong>Authentifié:</strong> ${ssrInterpolate(unref(authStore).isAuthenticated ? "✅ Oui" : "❌ Non")}</div><div><strong>Token présent:</strong> ${ssrInterpolate(unref(authStore).token ? "✅ Oui" : "❌ Non")}</div><div><strong>Admin:</strong> ${ssrInterpolate(unref(authStore).isAdmin ? "✅ Oui" : "❌ Non")}</div></div></div><div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6"><h2 class="text-xl font-semibold mb-3">🧪 Tests</h2><div class="space-y-2"><button class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 mr-2"> 🔑 Se connecter </button><button class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 mr-2"> 🔄 Rafraîchir utilisateur </button><button class="bg-purple-500 text-white px-4 py-2 rounded hover:bg-purple-600 mr-2"> 📦 Vérifier localStorage </button><button class="bg-orange-500 text-white px-4 py-2 rounded hover:bg-orange-600 mr-2"> 🎯 Test API direct </button><button class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600"> 🔄 Simuler rafraîchissement </button></div></div><div class="bg-gray-50 border border-gray-200 rounded-lg p-4"><h2 class="text-xl font-semibold mb-3">📝 Logs de debug</h2><div class="bg-black text-green-400 p-4 rounded font-mono text-sm max-h-96 overflow-y-auto"><!--[-->`);
      ssrRenderList(unref(logs), (log, index) => {
        _push(`<div class="mb-1"> [${ssrInterpolate(log.time)}] ${ssrInterpolate(log.message)}</div>`);
      });
      _push(`<!--]--></div><button class="mt-2 bg-gray-500 text-white px-3 py-1 rounded hover:bg-gray-600 text-sm"> 🗑️ Effacer logs </button></div></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/debug-auth.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
//# sourceMappingURL=debug-auth-2oS5ZKmG.js.map
