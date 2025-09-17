import { ref, mergeProps, unref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrInterpolate } from 'vue/server-renderer';
import { a as useAuthStore, c as useCookie } from './server.mjs';
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

const _sfc_main = {
  __name: "test-auth",
  __ssrInlineRender: true,
  setup(__props) {
    const authStore = useAuthStore();
    const authToken = useCookie("auth-token");
    const apiResult = ref(null);
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "p-8" }, _attrs))}><h1 class="text-2xl font-bold mb-4">Test d&#39;authentification</h1><div class="space-y-4"><div class="p-4 border rounded"><h3 class="font-semibold">\xC9tat du store d&#39;authentification :</h3><p><strong>isAuthenticated:</strong> ${ssrInterpolate(unref(authStore).isAuthenticated)}</p><p><strong>Token pr\xE9sent:</strong> ${ssrInterpolate(!!unref(authStore).token)}</p><p><strong>User:</strong> ${ssrInterpolate(unref(authStore).user ? unref(authStore).user.email : "Aucun")}</p><p><strong>canActAsTeacher:</strong> ${ssrInterpolate(unref(authStore).canActAsTeacher)}</p></div><div class="p-4 border rounded"><h3 class="font-semibold">Cookies :</h3><p><strong>auth-token:</strong> ${ssrInterpolate(unref(authToken) ? "Pr\xE9sent" : "Absent")}</p><p><strong>Valeur:</strong> ${ssrInterpolate(unref(authToken) ? unref(authToken).substring(0, 20) + "..." : "N/A")}</p></div><div class="p-4 border rounded"><h3 class="font-semibold">Test API :</h3><button class="px-4 py-2 bg-blue-500 text-white rounded"> Tester l&#39;API </button>`);
      if (unref(apiResult)) {
        _push(`<div class="mt-2 p-2 bg-gray-100 rounded"><pre>${ssrInterpolate(JSON.stringify(unref(apiResult), null, 2))}</pre></div>`);
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/test-auth.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=test-auth-BQzilUr8.mjs.map
