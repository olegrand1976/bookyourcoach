import { ref, mergeProps, unref, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrRenderAttr, ssrInterpolate, ssrIncludeBooleanAttr } from "vue/server-renderer";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/hookable/dist/index.mjs";
const _sfc_main = {
  __name: "test-login",
  __ssrInlineRender: true,
  setup(__props) {
    const email = ref("sophie.martin@activibe.com");
    const password = ref("password");
    const loading = ref(false);
    const result = ref(null);
    const error = ref("");
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-50 p-8" }, _attrs))}><div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-6"><h1 class="text-2xl font-bold mb-6">Test de Connexion</h1><div class="space-y-4"><div><label class="block text-sm font-medium text-gray-700">Email</label><input${ssrRenderAttr("value", unref(email))} type="email" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="sophie.martin@activibe.com"></div><div><label class="block text-sm font-medium text-gray-700">Mot de passe</label><input${ssrRenderAttr("value", unref(password))} type="password" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="password"></div><button${ssrIncludeBooleanAttr(unref(loading)) ? " disabled" : ""} class="w-full bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 disabled:opacity-50">${ssrInterpolate(unref(loading) ? "Test en cours..." : "Tester la connexion")}</button></div>`);
      if (unref(result)) {
        _push(`<div class="mt-6 p-4 bg-gray-100 rounded-md"><h3 class="font-semibold mb-2">Résultat :</h3><pre class="text-sm">${ssrInterpolate(JSON.stringify(unref(result), null, 2))}</pre></div>`);
      } else {
        _push(`<!---->`);
      }
      if (unref(error)) {
        _push(`<div class="mt-6 p-4 bg-red-100 border border-red-300 rounded-md"><h3 class="font-semibold text-red-800 mb-2">Erreur :</h3><p class="text-red-700">${ssrInterpolate(unref(error))}</p></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/test-login.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
//# sourceMappingURL=test-login-Dwk50oAb.js.map
