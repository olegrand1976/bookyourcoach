import { computed, ref, mergeProps, unref, useSSRContext } from 'vue';
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
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "p-8" }, _attrs))}><h1 class="text-2xl font-bold mb-4">\u{1F50D} Test Authentification Simple</h1><div class="bg-gray-100 p-4 rounded mb-4"><h2 class="text-lg font-semibold mb-2">\xC9tat actuel</h2><p><strong>Utilisateur:</strong> ${ssrInterpolate(((_a = unref(user)) == null ? void 0 : _a.name) || "Non connect\xE9")}</p><p><strong>Email:</strong> ${ssrInterpolate(((_b = unref(user)) == null ? void 0 : _b.email) || "N/A")}</p><p><strong>R\xF4le:</strong> <span class="${ssrRenderClass([((_c = unref(user)) == null ? void 0 : _c.role) === "admin" ? "text-green-600" : "text-red-600", "font-bold"])}">${ssrInterpolate(((_d = unref(user)) == null ? void 0 : _d.role) || "N/A")}</span></p><p><strong>Authentifi\xE9:</strong> ${ssrInterpolate(unref(isAuthenticated) ? "\u2705 Oui" : "\u274C Non")}</p></div><div class="space-y-2 mb-4"><button class="bg-blue-500 text-white px-4 py-2 rounded mr-2">Se connecter</button><button class="bg-green-500 text-white px-4 py-2 rounded mr-2">Rafra\xEEchir</button><button class="bg-red-500 text-white px-4 py-2 rounded">Se d\xE9connecter</button></div><div class="bg-black text-green-400 p-4 rounded font-mono text-sm"><!--[-->`);
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

export { _sfc_main as default };
//# sourceMappingURL=test-auth-DUxUMbFZ.mjs.map
