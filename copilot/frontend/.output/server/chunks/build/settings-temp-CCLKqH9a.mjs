import { defineComponent, mergeProps, useSSRContext } from 'vue';
import { ssrRenderAttrs } from 'vue/server-renderer';
import { u as useHead } from './server.mjs';
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

const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "settings-temp",
  __ssrInlineRender: true,
  setup(__props) {
    useHead({
      title: "Param\xE8tres Syst\xE8me"
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-50" }, _attrs))}><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8"><h1 class="text-3xl font-bold text-gray-900"> Param\xE8tres Syst\xE8me </h1><p class="mt-2 text-gray-600">Configuration temporaire - reconstruction en cours</p><div class="mt-8 bg-white rounded-lg shadow-lg p-6"><p class="text-gray-700"> Le syst\xE8me de param\xE8tres est en cours de reconstruction apr\xE8s le nettoyage du cache. Veuillez patienter quelques instants. </p></div></div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/admin/settings-temp.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=settings-temp-CCLKqH9a.mjs.map
