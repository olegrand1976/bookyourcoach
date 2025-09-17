import { _ as __nuxt_component_0 } from "./nuxt-link-3RFQ5DMr.js";
import { ref, computed, mergeProps, withCtx, createVNode, unref, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderAttr, ssrInterpolate, ssrRenderSlot } from "vue/server-renderer";
import { _ as _imports_0 } from "./virtual_public-Ru22WhcQ.js";
import { ChevronDownIcon } from "@heroicons/vue/24/outline";
import { a as useAuthStore } from "../server.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/ufo/dist/index.mjs";
import "#internal/nuxt/paths";
import "ofetch";
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
  __name: "admin",
  __ssrInlineRender: true,
  setup(__props) {
    const authStore = useAuthStore();
    const userMenuOpen = ref(false);
    const userName = computed(() => authStore.userName || "Admin");
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-50" }, _attrs))}><nav class="bg-white shadow-lg border-b-4 border-red-500"><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"><div class="flex justify-between h-20"><div class="flex items-center">`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/admin",
        class: "flex items-center space-x-3 text-xl font-bold text-gray-900"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<img${ssrRenderAttr("src", _imports_0)} alt="Acti&#39;Vibe" class="h-12 w-auto"${_scopeId}><span class="bg-red-100 text-red-700 text-sm font-semibold px-2.5 py-1 rounded-full"${_scopeId}>Admin</span>`);
          } else {
            return [
              createVNode("img", {
                src: _imports_0,
                alt: "Acti'Vibe",
                class: "h-12 w-auto"
              }),
              createVNode("span", { class: "bg-red-100 text-red-700 text-sm font-semibold px-2.5 py-1 rounded-full" }, "Admin")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div><div class="flex items-center space-x-6"><div class="relative"><button class="flex items-center space-x-2 text-gray-900 bg-gray-50 px-4 py-2 rounded-lg"><span class="font-medium">${ssrInterpolate(userName.value)}</span>`);
      _push(ssrRenderComponent(unref(ChevronDownIcon), { class: "w-4 h-4" }, null, _parent));
      _push(`</button>`);
      if (userMenuOpen.value) {
        _push(`<div class="absolute right-0 mt-2 w-64 bg-white rounded-xl shadow-xl py-2 z-50 border border-gray-200"><p class="px-4 py-2 text-xs text-gray-500">Menu Administration</p>`);
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/admin",
          class: "flex items-center space-x-3 w-full px-4 py-2 text-gray-700 hover:bg-gray-100"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<span${_scopeId}>📊</span><span${_scopeId}>Dashboard</span>`);
            } else {
              return [
                createVNode("span", null, "📊"),
                createVNode("span", null, "Dashboard")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/admin/users",
          class: "flex items-center space-x-3 w-full px-4 py-2 text-gray-700 hover:bg-gray-100"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<span${_scopeId}>👥</span><span${_scopeId}>Utilisateurs</span>`);
            } else {
              return [
                createVNode("span", null, "👥"),
                createVNode("span", null, "Utilisateurs")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/admin/contracts",
          class: "flex items-center space-x-3 w-full px-4 py-2 text-gray-700 hover:bg-gray-100"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<span${_scopeId}>📄</span><span${_scopeId}>Contrats</span>`);
            } else {
              return [
                createVNode("span", null, "📄"),
                createVNode("span", null, "Contrats")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/admin/settings",
          class: "flex items-center space-x-3 w-full px-4 py-2 text-gray-700 hover:bg-gray-100"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<span${_scopeId}>⚙️</span><span${_scopeId}>Paramètres</span>`);
            } else {
              return [
                createVNode("span", null, "⚙️"),
                createVNode("span", null, "Paramètres")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/admin/graph-analysis",
          class: "flex items-center space-x-3 w-full px-4 py-2 text-gray-700 hover:bg-gray-100"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<span${_scopeId}>🔗</span><span${_scopeId}>Analyse Graphique</span>`);
            } else {
              return [
                createVNode("span", null, "🔗"),
                createVNode("span", null, "Analyse Graphique")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`<hr class="my-2 border-gray-200">`);
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/",
          class: "flex items-center space-x-3 w-full px-4 py-2 text-gray-700 hover:bg-gray-100"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<span${_scopeId}>🌐</span><span${_scopeId}>Retour au site</span>`);
            } else {
              return [
                createVNode("span", null, "🌐"),
                createVNode("span", null, "Retour au site")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`<button class="flex items-center space-x-3 w-full text-left px-4 py-2 text-red-600 hover:bg-red-50"><span>🚪</span><span>Déconnexion</span></button></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div></div></div></nav><main>`);
      ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent);
      _push(`</main></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("layouts/admin.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
//# sourceMappingURL=admin-x2Qv4JSt.js.map
