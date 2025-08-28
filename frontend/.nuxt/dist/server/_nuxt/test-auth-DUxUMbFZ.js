import { computed, ref, mergeProps, unref, useSSRContext } from "vue";
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
  __name: "test-auth",
  __ssrInlineRender: true,
  setup(__props) {
    const authStore = useAuthStore();
    const { $api } = useNuxtApp();
    const user = computed(() => authStore.user);
    const isAuthenticated = computed(() => authStore.isAuthenticated);
    const logs = ref([]);
    return (_ctx, _push, _parent, _attrs) => {
      var _a, _b, _c, _d;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "p-8" }, _attrs))}><h1 class="text-2xl font-bold mb-4">🔍 Test Authentification Simple</h1><div class="bg-gray-100 p-4 rounded mb-4"><h2 class="text-lg font-semibold mb-2">État actuel</h2><p><strong>Utilisateur:</strong> ${ssrInterpolate(((_a = unref(user)) == null ? void 0 : _a.name) || "Non connecté")}</p><p><strong>Email:</strong> ${ssrInterpolate(((_b = unref(user)) == null ? void 0 : _b.email) || "N/A")}</p><p><strong>Rôle:</strong> <span class="${ssrRenderClass([((_c = unref(user)) == null ? void 0 : _c.role) === "admin" ? "text-green-600" : "text-red-600", "font-bold"])}">${ssrInterpolate(((_d = unref(user)) == null ? void 0 : _d.role) || "N/A")}</span></p><p><strong>Authentifié:</strong> ${ssrInterpolate(unref(isAuthenticated) ? "✅ Oui" : "❌ Non")}</p></div><div class="space-y-2 mb-4"><button class="bg-blue-500 text-white px-4 py-2 rounded mr-2">Se connecter</button><button class="bg-green-500 text-white px-4 py-2 rounded mr-2">Rafraîchir</button><button class="bg-red-500 text-white px-4 py-2 rounded">Se déconnecter</button></div><div class="bg-black text-green-400 p-4 rounded font-mono text-sm"><!--[-->`);
      ssrRenderList(unref(logs), (log) => {
        _push(`<div>${ssrInterpolate(log.message)}</div>`);
      });
      _push(`<!--]--></div></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/test-auth.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
//# sourceMappingURL=test-auth-DUxUMbFZ.js.map
