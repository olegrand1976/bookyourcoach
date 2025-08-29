import { ref, mergeProps, unref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrInterpolate, ssrRenderClass, ssrRenderList } from 'vue/server-renderer';
import { u as useAuthStore } from './auth-BBLAd2fH.mjs';
import { b as useNuxtApp } from './server.mjs';
import '../nitro/nitro.mjs';
import 'node:http';
import 'node:https';
import 'node:events';
import 'node:buffer';
import 'node:fs';
import 'node:path';
import 'node:crypto';
import 'node:url';
import './ssr-B4FXEZKR.mjs';
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/utils';
import 'unhead/plugins';
import 'vue-router';

const _sfc_main = {
  __name: "debug-auth",
  __ssrInlineRender: true,
  setup(__props) {
    const authStore = useAuthStore();
    const { $api } = useNuxtApp();
    const logs = ref([]);
    return (_ctx, _push, _parent, _attrs) => {
      var _a, _b, _c, _d;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "container mx-auto p-8" }, _attrs))}><h1 class="text-3xl font-bold mb-6">\u{1F50D} Debug Authentification</h1><div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6"><h2 class="text-xl font-semibold mb-3">\u{1F4CA} \xC9tat actuel</h2><div class="grid grid-cols-1 md:grid-cols-2 gap-4"><div><strong>Utilisateur:</strong> ${ssrInterpolate(((_a = unref(authStore).user) == null ? void 0 : _a.name) || "Non connect\xE9")}</div><div><strong>Email:</strong> ${ssrInterpolate(((_b = unref(authStore).user) == null ? void 0 : _b.email) || "N/A")}</div><div><strong>R\xF4le:</strong><span class="${ssrRenderClass(((_c = unref(authStore).user) == null ? void 0 : _c.role) === "admin" ? "text-green-600 font-bold" : "text-red-600 font-bold")}">${ssrInterpolate(((_d = unref(authStore).user) == null ? void 0 : _d.role) || "N/A")}</span></div><div><strong>Authentifi\xE9:</strong> ${ssrInterpolate(unref(authStore).isAuthenticated ? "\u2705 Oui" : "\u274C Non")}</div><div><strong>Token pr\xE9sent:</strong> ${ssrInterpolate(unref(authStore).token ? "\u2705 Oui" : "\u274C Non")}</div><div><strong>Admin:</strong> ${ssrInterpolate(unref(authStore).isAdmin ? "\u2705 Oui" : "\u274C Non")}</div></div></div><div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6"><h2 class="text-xl font-semibold mb-3">\u{1F9EA} Tests</h2><div class="space-y-2"><button class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 mr-2"> \u{1F511} Se connecter </button><button class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 mr-2"> \u{1F504} Rafra\xEEchir utilisateur </button><button class="bg-purple-500 text-white px-4 py-2 rounded hover:bg-purple-600 mr-2"> \u{1F4E6} V\xE9rifier localStorage </button><button class="bg-orange-500 text-white px-4 py-2 rounded hover:bg-orange-600 mr-2"> \u{1F3AF} Test API direct </button><button class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600"> \u{1F504} Simuler rafra\xEEchissement </button></div></div><div class="bg-gray-50 border border-gray-200 rounded-lg p-4"><h2 class="text-xl font-semibold mb-3">\u{1F4DD} Logs de debug</h2><div class="bg-black text-green-400 p-4 rounded font-mono text-sm max-h-96 overflow-y-auto"><!--[-->`);
      ssrRenderList(unref(logs), (log, index) => {
        _push(`<div class="mb-1"> [${ssrInterpolate(log.time)}] ${ssrInterpolate(log.message)}</div>`);
      });
      _push(`<!--]--></div><button class="mt-2 bg-gray-500 text-white px-3 py-1 rounded hover:bg-gray-600 text-sm"> \u{1F5D1}\uFE0F Effacer logs </button></div></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/debug-auth.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=debug-auth-2oS5ZKmG.mjs.map
