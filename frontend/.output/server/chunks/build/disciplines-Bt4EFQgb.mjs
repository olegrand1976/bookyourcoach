import { _ as __nuxt_component_0 } from './nuxt-link-3RFQ5DMr.mjs';
import { mergeProps, withCtx, createTextVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent } from 'vue/server-renderer';
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

const _sfc_main = {
  __name: "disciplines",
  __ssrInlineRender: true,
  setup(__props) {
    useHead({
      title: "Disciplines | activibe",
      meta: [
        { name: "description", content: "Explorez toutes les disciplines disponibles sur activibe" }
      ]
    });
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-50" }, _attrs))}><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8"><div class="text-center"><h1 class="text-3xl font-bold text-gray-900 mb-4"> \u{1F3C7} Disciplines </h1><p class="text-gray-700 mb-8"> Explorez toutes les disciplines disponibles </p><div class="bg-white rounded-lg shadow-lg p-6"><p class="text-gray-700"> Cette page sera bient\xF4t disponible avec la liste compl\xE8te des disciplines. </p>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/",
        class: "btn-primary mt-4 inline-block"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Retour \xE0 l&#39;accueil `);
          } else {
            return [
              createTextVNode(" Retour \xE0 l'accueil ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></div></div></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/disciplines.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=disciplines-Bt4EFQgb.mjs.map
