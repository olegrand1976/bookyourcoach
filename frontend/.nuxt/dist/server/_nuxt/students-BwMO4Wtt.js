import { _ as __nuxt_component_0 } from "./nuxt-link-3RFQ5DMr.js";
import { mergeProps, withCtx, createTextVNode, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrRenderComponent } from "vue/server-renderer";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/hookable/dist/index.mjs";
import { a as useAuthStore, g as createError } from "../server.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/ufo/dist/index.mjs";
import "ofetch";
import "#internal/nuxt/paths";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/unctx/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/h3/dist/index.mjs";
import "vue-router";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/radix3/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/defu/dist/defu.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/klona/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/nuxt/node_modules/cookie-es/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/destr/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/ohash/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/@unhead/vue/dist/index.mjs";
const _sfc_main = {
  __name: "students",
  __ssrInlineRender: true,
  setup(__props) {
    const authStore = useAuthStore();
    if (!authStore.canActAsTeacher) {
      throw createError({
        statusCode: 403,
        statusMessage: "Accès refusé - Droits enseignant requis"
      });
    }
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-50" }, _attrs))}><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8"><div class="mb-8"><h1 class="text-3xl font-bold text-gray-900"> Mes Élèves </h1><p class="mt-2 text-gray-600"> Suivez la progression de vos élèves </p></div><div class="bg-white rounded-lg shadow p-8 text-center"><div class="text-6xl mb-4">👨‍🎓</div><h2 class="text-2xl font-bold text-gray-900 mb-4">Gestion des élèves en cours de développement</h2><p class="text-gray-600 mb-6"> Cette fonctionnalité sera bientôt disponible pour gérer vos élèves. </p>`);
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/teacher/students.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
//# sourceMappingURL=students-BwMO4Wtt.js.map
