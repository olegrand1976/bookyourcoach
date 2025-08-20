import { defineComponent, mergeProps, useSSRContext } from "vue";
import { ssrRenderAttrs } from "vue/server-renderer";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/hookable/dist/index.mjs";
import { u as useHead } from "../server.mjs";
import "ofetch";
import "#internal/nuxt/paths";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/unctx/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/h3/dist/index.mjs";
import "vue-router";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/radix3/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/defu/dist/defu.mjs";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/ufo/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/klona/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/@unhead/vue/dist/index.mjs";
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "settings-temp",
  __ssrInlineRender: true,
  setup(__props) {
    useHead({
      title: "Paramètres Système"
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-50" }, _attrs))}><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8"><h1 class="text-3xl font-bold text-gray-900"> Paramètres Système </h1><p class="mt-2 text-gray-600">Configuration temporaire - reconstruction en cours</p><div class="mt-8 bg-white rounded-lg shadow-lg p-6"><p class="text-gray-700"> Le système de paramètres est en cours de reconstruction après le nettoyage du cache. Veuillez patienter quelques instants. </p></div></div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/admin/settings-temp.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
//# sourceMappingURL=settings-temp-CCLKqH9a.js.map
