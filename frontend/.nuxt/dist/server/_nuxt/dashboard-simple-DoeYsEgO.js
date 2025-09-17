import { ref, mergeProps, unref, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrInterpolate } from "vue/server-renderer";
import "/workspace/frontend/node_modules/hookable/dist/index.mjs";
import { a as useAuthStore } from "../server.mjs";
import "/workspace/frontend/node_modules/klona/dist/index.mjs";
import "ofetch";
import "#internal/nuxt/paths";
import "/workspace/frontend/node_modules/unctx/dist/index.mjs";
import "/workspace/frontend/node_modules/h3/dist/index.mjs";
import "vue-router";
import "/workspace/frontend/node_modules/radix3/dist/index.mjs";
import "/workspace/frontend/node_modules/defu/dist/defu.mjs";
import "/workspace/frontend/node_modules/ufo/dist/index.mjs";
import "/workspace/frontend/node_modules/nuxt/node_modules/cookie-es/dist/index.mjs";
import "/workspace/frontend/node_modules/destr/dist/index.mjs";
import "/workspace/frontend/node_modules/ohash/dist/index.mjs";
import "/workspace/frontend/node_modules/@unhead/vue/dist/index.mjs";
const _sfc_main = {
  __name: "dashboard-simple",
  __ssrInlineRender: true,
  setup(__props) {
    const authStore = useAuthStore();
    const apiResult = ref(null);
    return (_ctx, _push, _parent, _attrs) => {
      var _a;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-50" }, _attrs))}><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8"><div class="mb-8"><h1 class="text-3xl font-bold text-gray-900"> Dashboard Enseignant </h1><p class="mt-2 text-gray-600"> Bonjour ${ssrInterpolate(unref(authStore).userName)}, gérez vos cours et votre planning </p></div><div class="bg-white rounded-lg shadow p-6"><h2 class="text-xl font-semibold mb-4">Test d&#39;accès</h2><p><strong>Authentifié :</strong> ${ssrInterpolate(unref(authStore).isAuthenticated)}</p><p><strong>Peut agir comme enseignant :</strong> ${ssrInterpolate(unref(authStore).canActAsTeacher)}</p><p><strong>Utilisateur :</strong> ${ssrInterpolate((_a = unref(authStore).user) == null ? void 0 : _a.email)}</p><div class="mt-4"><button class="px-4 py-2 bg-blue-500 text-white rounded"> Tester l&#39;API </button>`);
      if (apiResult.value) {
        _push(`<div class="mt-2 p-2 bg-gray-100 rounded"><pre>${ssrInterpolate(JSON.stringify(apiResult.value, null, 2))}</pre></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div></div></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/teacher/dashboard-simple.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
//# sourceMappingURL=dashboard-simple-DoeYsEgO.js.map
