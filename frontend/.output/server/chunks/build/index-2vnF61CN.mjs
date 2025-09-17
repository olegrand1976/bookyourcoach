import { _ as __nuxt_component_0 } from './nuxt-link-CWCWeN0_.mjs';
import { mergeProps, withCtx, createTextVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent } from 'vue/server-renderer';
import '../nitro/nitro.mjs';
import 'node:http';
import 'node:https';
import 'node:events';
import 'node:buffer';
import 'node:fs';
import 'node:path';
import 'node:crypto';
import 'node:url';
import './server.mjs';
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/utils';
import 'unhead/plugins';
import 'vue-router';

const _sfc_main = {
  __name: "index",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gradient-to-br from-gray-50 to-blue-50" }, _attrs))}><section class="relative py-20 px-4 sm:px-6 lg:px-8"><div class="max-w-7xl mx-auto text-center"><h1 class="text-4xl md:text-6xl font-bold text-gray-900 mb-6"> \u{1F3CA}\u200D\u2640\uFE0F activibe </h1><p class="text-xl md:text-2xl text-gray-700 mb-8 max-w-3xl mx-auto"> R\xE9servez vos cours d&#39;\xE9quitation et de natation avec les meilleurs instructeurs </p><div class="flex flex-col sm:flex-row gap-4 justify-center">`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/login",
        class: "bg-blue-600 hover:bg-blue-700 text-white font-semibold px-8 py-3 rounded-lg transition-colors"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Connexion `);
          } else {
            return [
              createTextVNode(" Connexion ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/register",
        class: "bg-blue-500 bg-blue-600:bg-yellow-600 text-gray-900 font-semibold px-8 py-3 rounded-lg transition-colors"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Inscription `);
          } else {
            return [
              createTextVNode(" Inscription ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></div></section><section class="py-16 px-4 sm:px-6 lg:px-8 bg-white"><div class="max-w-7xl mx-auto"><h2 class="text-3xl font-bold text-center text-gray-900 mb-12"> Pourquoi choisir activibe ? </h2><div class="grid grid-cols-1 md:grid-cols-3 gap-8"><div class="text-center p-6"><div class="text-4xl mb-4">\u{1F3C7}</div><h3 class="text-xl font-semibold text-gray-900 mb-2">\xC9quitation</h3><p class="text-gray-700">Cours de dressage, obstacles et complet avec des instructeurs qualifi\xE9s</p></div><div class="text-center p-6"><div class="text-4xl mb-4">\u{1F3CA}</div><h3 class="text-xl font-semibold text-gray-900 mb-2">Natation</h3><p class="text-gray-700">Cours particuliers et aquagym pour tous les niveaux</p></div><div class="text-center p-6"><div class="text-4xl mb-4">\u{1F4C5}</div><h3 class="text-xl font-semibold text-gray-900 mb-2">R\xE9servation facile</h3><p class="text-gray-700">R\xE9servez vos cours en quelques clics, 24h/24</p></div></div></div></section><section class="py-16 px-4 sm:px-6 lg:px-8 bg-gray-800 text-gray-100"><div class="max-w-4xl mx-auto text-center"><h2 class="text-3xl font-bold mb-6">Pr\xEAt \xE0 commencer ?</h2><p class="text-xl mb-8"> Rejoignez des milliers d&#39;\xE9l\xE8ves qui ont d\xE9j\xE0 choisi activibe </p>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/register",
        class: "bg-blue-500 bg-blue-600:bg-yellow-600 text-gray-900 font-semibold px-8 py-3 rounded-lg transition-colors inline-block"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Commencer maintenant `);
          } else {
            return [
              createTextVNode(" Commencer maintenant ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></section></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=index-2vnF61CN.mjs.map
