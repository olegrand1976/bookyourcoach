import { ref, mergeProps, unref, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrInterpolate } from "vue/server-renderer";
import { a as useAuthStore, c as useCookie } from "../server.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/hookable/dist/index.mjs";
import "ofetch";
import "#internal/nuxt/paths";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/unctx/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/h3/dist/index.mjs";
import "vue-router";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/radix3/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/defu/dist/defu.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/ufo/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/klona/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/nuxt/node_modules/cookie-es/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/destr/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/ohash/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/@unhead/vue/dist/index.mjs";
const _sfc_main = {
  __name: "test-auth-complete",
  __ssrInlineRender: true,
  setup(__props) {
    const authStore = useAuthStore();
    const authToken = useCookie("auth-token");
    const apiResult = ref(null);
    const navigationResult = ref(null);
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "p-8" }, _attrs))}><h1 class="text-2xl font-bold mb-4">Test d&#39;authentification complet</h1><div class="space-y-4"><div class="p-4 border rounded"><h3 class="font-semibold">État du store d&#39;authentification :</h3><p><strong>isAuthenticated:</strong> ${ssrInterpolate(unref(authStore).isAuthenticated)}</p><p><strong>Token présent:</strong> ${ssrInterpolate(!!unref(authStore).token)}</p><p><strong>User:</strong> ${ssrInterpolate(unref(authStore).user ? unref(authStore).user.email : "Aucun")}</p><p><strong>canActAsTeacher:</strong> ${ssrInterpolate(unref(authStore).canActAsTeacher)}</p><p><strong>canActAsStudent:</strong> ${ssrInterpolate(unref(authStore).canActAsStudent)}</p><p><strong>isAdmin:</strong> ${ssrInterpolate(unref(authStore).isAdmin)}</p></div><div class="p-4 border rounded"><h3 class="font-semibold">Cookies :</h3><p><strong>auth-token:</strong> ${ssrInterpolate(unref(authToken) ? "Présent" : "Absent")}</p><p><strong>Valeur:</strong> ${ssrInterpolate(unref(authToken) ? unref(authToken).substring(0, 20) + "..." : "N/A")}</p></div><div class="p-4 border rounded"><h3 class="font-semibold">Test API :</h3><button class="px-4 py-2 bg-blue-500 text-white rounded"> Tester l&#39;API </button>`);
      if (unref(apiResult)) {
        _push(`<div class="mt-2 p-2 bg-gray-100 rounded"><pre>${ssrInterpolate(JSON.stringify(unref(apiResult), null, 2))}</pre></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="p-4 border rounded"><h3 class="font-semibold">Test de navigation :</h3><button class="px-4 py-2 bg-green-500 text-white rounded mr-2"> Test Teacher Dashboard </button><button class="px-4 py-2 bg-purple-500 text-white rounded"> Test Student Dashboard </button>`);
      if (unref(navigationResult)) {
        _push(`<div class="mt-2 p-2 bg-gray-100 rounded"><pre>${ssrInterpolate(unref(navigationResult))}</pre></div>`);
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/test-auth-complete.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
//# sourceMappingURL=test-auth-complete-nlbSAKbO.js.map
