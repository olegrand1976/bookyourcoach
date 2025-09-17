import { _ as __nuxt_component_0 } from "./nuxt-link-CWCWeN0_.js";
import { mergeProps, withCtx, createTextVNode, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrRenderComponent } from "vue/server-renderer";
import "/workspace/frontend/node_modules/hookable/dist/index.mjs";
import "/workspace/frontend/node_modules/ufo/dist/index.mjs";
import "../server.mjs";
import "ofetch";
import "#internal/nuxt/paths";
import "/workspace/frontend/node_modules/unctx/dist/index.mjs";
import "/workspace/frontend/node_modules/h3/dist/index.mjs";
import "vue-router";
import "/workspace/frontend/node_modules/radix3/dist/index.mjs";
import "/workspace/frontend/node_modules/defu/dist/defu.mjs";
import "/workspace/frontend/node_modules/nuxt/node_modules/cookie-es/dist/index.mjs";
import "/workspace/frontend/node_modules/destr/dist/index.mjs";
import "/workspace/frontend/node_modules/ohash/dist/index.mjs";
import "/workspace/frontend/node_modules/klona/dist/index.mjs";
import "/workspace/frontend/node_modules/@unhead/vue/dist/index.mjs";
const _sfc_main = {
  __name: "index",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gradient-to-br from-gray-50 to-blue-50" }, _attrs))}><section class="relative py-20 px-4 sm:px-6 lg:px-8"><div class="max-w-7xl mx-auto text-center"><h1 class="text-4xl md:text-6xl font-bold text-gray-900 mb-6"> 🏊‍♀️ activibe </h1><p class="text-xl md:text-2xl text-gray-700 mb-8 max-w-3xl mx-auto"> Réservez vos cours d&#39;équitation et de natation avec les meilleurs instructeurs </p><div class="flex flex-col sm:flex-row gap-4 justify-center">`);
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
      _push(`</div></div></section><section class="py-16 px-4 sm:px-6 lg:px-8 bg-white"><div class="max-w-7xl mx-auto"><h2 class="text-3xl font-bold text-center text-gray-900 mb-12"> Pourquoi choisir activibe ? </h2><div class="grid grid-cols-1 md:grid-cols-3 gap-8"><div class="text-center p-6"><div class="text-4xl mb-4">🏇</div><h3 class="text-xl font-semibold text-gray-900 mb-2">Équitation</h3><p class="text-gray-700">Cours de dressage, obstacles et complet avec des instructeurs qualifiés</p></div><div class="text-center p-6"><div class="text-4xl mb-4">🏊</div><h3 class="text-xl font-semibold text-gray-900 mb-2">Natation</h3><p class="text-gray-700">Cours particuliers et aquagym pour tous les niveaux</p></div><div class="text-center p-6"><div class="text-4xl mb-4">📅</div><h3 class="text-xl font-semibold text-gray-900 mb-2">Réservation facile</h3><p class="text-gray-700">Réservez vos cours en quelques clics, 24h/24</p></div></div></div></section><section class="py-16 px-4 sm:px-6 lg:px-8 bg-gray-800 text-gray-100"><div class="max-w-4xl mx-auto text-center"><h2 class="text-3xl font-bold mb-6">Prêt à commencer ?</h2><p class="text-xl mb-8"> Rejoignez des milliers d&#39;élèves qui ont déjà choisi activibe </p>`);
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
export {
  _sfc_main as default
};
//# sourceMappingURL=index-2vnF61CN.js.map
