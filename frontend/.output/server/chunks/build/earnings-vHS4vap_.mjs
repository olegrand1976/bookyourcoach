import { _ as __nuxt_component_0 } from './nuxt-link-3RFQ5DMr.mjs';
import { mergeProps, withCtx, createTextVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent } from 'vue/server-renderer';
import { a as useAuthStore, g as createError } from './server.mjs';
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
  __name: "earnings",
  __ssrInlineRender: true,
  setup(__props) {
    const authStore = useAuthStore();
    if (!authStore.canActAsTeacher) {
      throw createError({
        statusCode: 403,
        statusMessage: "Acc\xE8s refus\xE9 - Droits enseignant requis"
      });
    }
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-50" }, _attrs))}><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8"><div class="mb-8"><h1 class="text-3xl font-bold text-gray-900"> Mes Revenus </h1><p class="mt-2 text-gray-600"> Consultez vos revenus et statistiques financi\xE8res </p></div><div class="bg-white rounded-lg shadow p-8 text-center"><div class="text-6xl mb-4">\u{1F4B0}</div><h2 class="text-2xl font-bold text-gray-900 mb-4">Gestion des revenus en cours de d\xE9veloppement</h2><p class="text-gray-600 mb-6"> Cette fonctionnalit\xE9 sera bient\xF4t disponible pour consulter vos revenus. </p>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/teacher/dashboard",
        class: "btn-primary bg-blue-600 text-white"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Retour au dashboard `);
          } else {
            return [
              createTextVNode(" Retour au dashboard ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></div></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/teacher/earnings.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=earnings-vHS4vap_.mjs.map
