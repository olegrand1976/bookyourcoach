import { _ as __nuxt_component_0 } from "./nuxt-link-3RFQ5DMr.js";
import { mergeProps, withCtx, createTextVNode, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderSlot } from "vue/server-renderer";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/ufo/dist/index.mjs";
import "../server.mjs";
import "ofetch";
import "#internal/nuxt/paths";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/hookable/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/unctx/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/h3/dist/index.mjs";
import "vue-router";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/radix3/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/defu/dist/defu.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/nuxt/node_modules/cookie-es/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/destr/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/ohash/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/klona/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/@unhead/vue/dist/index.mjs";
const _sfc_main = {
  __name: "minimal",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-50" }, _attrs))}><nav class="bg-white shadow-lg border-b-4 border-blue-500"><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"><div class="flex justify-between h-20"><div class="flex items-center">`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/",
        class: "flex items-center space-x-3 text-xl font-bold text-gray-900 hover:text-gray-700 transition-colors"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` 🏊‍♀️ activibe `);
          } else {
            return [
              createTextVNode(" 🏊‍♀️ activibe ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div><div class="flex items-center space-x-6">`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/login",
        class: "text-gray-900 hover:text-gray-700 font-medium px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors"
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
        class: "btn-primary bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2 rounded-lg transition-colors"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` 🏇 Inscription `);
          } else {
            return [
              createTextVNode(" 🏇 Inscription ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></div></div></nav><main>`);
      ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent);
      _push(`</main><footer class="bg-gray-800 text-gray-100 border-t-4 border-blue-500 mt-auto"><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8"><div class="text-center text-gray-100/60"><p>© 2025 activibe. Tous droits réservés. 🏊‍♀️</p></div></div></footer></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("layouts/minimal.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
//# sourceMappingURL=minimal-CkA7RsZ8.js.map
