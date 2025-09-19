import { ref, mergeProps, unref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrInterpolate, ssrRenderList } from 'vue/server-renderer';
import { d as useNuxtApp, j as useRuntimeConfig } from './server.mjs';
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
  __name: "test-api-direct",
  __ssrInlineRender: true,
  setup(__props) {
    const config = useRuntimeConfig();
    const { $api } = useNuxtApp();
    const logs = ref([]);
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "p-8" }, _attrs))}><h1 class="text-2xl font-bold mb-4">\u{1F527} Test Direct API</h1><div class="bg-gray-100 p-4 rounded mb-4"><h2 class="text-lg font-semibold mb-2">Configuration</h2><p><strong>API Base:</strong> ${ssrInterpolate(unref(config).public.apiBase)}</p><p><strong>Endpoint:</strong> /auth/login</p></div><div class="space-y-2 mb-4"><button class="bg-blue-500 text-white px-4 py-2 rounded mr-2">Test API Direct</button><button class="bg-green-500 text-white px-4 py-2 rounded mr-2">Test avec Axios</button><button class="bg-purple-500 text-white px-4 py-2 rounded">Test avec Fetch</button></div><div class="bg-black text-green-400 p-4 rounded font-mono text-sm max-h-96 overflow-y-auto"><!--[-->`);
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/test-api-direct.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=test-api-direct-CalNXHhJ.mjs.map
