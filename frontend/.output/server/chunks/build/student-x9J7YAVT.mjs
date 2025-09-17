import { _ as __nuxt_component_0 } from './nuxt-link-3RFQ5DMr.mjs';
import { defineComponent, useSSRContext, ref, mergeProps, withCtx, createVNode, createBlock, openBlock, unref, createTextVNode, resolveDynamicComponent, toDisplayString } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderList, ssrRenderVNode, ssrRenderSlot } from 'vue/server-renderer';
import { a as useAuthStore, e as useRoute } from './server.mjs';
import { _ as _export_sfc } from './_plugin-vue_export-helper-1tPrXgE0.mjs';
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
  __name: "student",
  __ssrInlineRender: true,
  setup(__props) {
    const authStore = useAuthStore();
    const userMenuOpen = ref(false);
    const mobileMenuOpen = ref(false);
    const navigationItems = [
      {
        name: "Tableau de bord",
        href: "/student/dashboard",
        icon: "svg"
      },
      {
        name: "Le\xE7ons disponibles",
        href: "/student/lessons",
        icon: "svg"
      },
      {
        name: "Mes r\xE9servations",
        href: "/student/bookings",
        icon: "svg"
      },
      {
        name: "Mes pr\xE9f\xE9rences",
        href: "/student/preferences",
        icon: "svg"
      },
      {
        name: "Historique",
        href: "/student/history",
        icon: "svg"
      },
      {
        name: "Enseignants",
        href: "/student/teachers",
        icon: "svg"
      }
    ];
    const isActiveRoute = (href) => {
      const route = useRoute();
      return route.path === href;
    };
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "student-layout min-h-screen bg-gray-50" }, _attrs))} data-v-283d9d32><nav class="bg-white shadow-sm border-b border-gray-200" data-v-283d9d32><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" data-v-283d9d32><div class="flex justify-between h-16" data-v-283d9d32><div class="flex items-center" data-v-283d9d32>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/student/dashboard",
        class: "flex items-center space-x-3"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center" data-v-283d9d32${_scopeId}><svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" data-v-283d9d32${_scopeId}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" data-v-283d9d32${_scopeId}></path></svg></div><span class="text-xl font-bold text-gray-900" data-v-283d9d32${_scopeId}>Espace \xC9tudiant</span>`);
          } else {
            return [
              createVNode("div", { class: "w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center" }, [
                (openBlock(), createBlock("svg", {
                  class: "w-5 h-5 text-white",
                  fill: "none",
                  stroke: "currentColor",
                  viewBox: "0 0 24 24"
                }, [
                  createVNode("path", {
                    "stroke-linecap": "round",
                    "stroke-linejoin": "round",
                    "stroke-width": "2",
                    d: "M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                  })
                ]))
              ]),
              createVNode("span", { class: "text-xl font-bold text-gray-900" }, "Espace \xC9tudiant")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div><div class="flex items-center space-x-4" data-v-283d9d32><button class="p-2 text-gray-400 hover:text-gray-500 hover:bg-gray-100 rounded-md" data-v-283d9d32><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" data-v-283d9d32><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4.828 7l2.586 2.586a2 2 0 002.828 0L12 7H4.828z" data-v-283d9d32></path></svg></button><div class="relative" data-v-283d9d32><button class="flex items-center space-x-2 text-sm rounded-md text-gray-700 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" data-v-283d9d32><div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center" data-v-283d9d32><svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" data-v-283d9d32><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" data-v-283d9d32></path></svg></div><span class="font-medium" data-v-283d9d32>${ssrInterpolate(unref(authStore).userName)}</span><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" data-v-283d9d32><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" data-v-283d9d32></path></svg></button>`);
      if (userMenuOpen.value) {
        _push(`<div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 border border-gray-200" data-v-283d9d32>`);
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/profile",
          class: "flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" data-v-283d9d32${_scopeId}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" data-v-283d9d32${_scopeId}></path></svg> Mon profil `);
            } else {
              return [
                (openBlock(), createBlock("svg", {
                  class: "w-4 h-4 mr-3",
                  fill: "none",
                  stroke: "currentColor",
                  viewBox: "0 0 24 24"
                }, [
                  createVNode("path", {
                    "stroke-linecap": "round",
                    "stroke-linejoin": "round",
                    "stroke-width": "2",
                    d: "M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                  })
                ])),
                createTextVNode(" Mon profil ")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`<button class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" data-v-283d9d32><svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" data-v-283d9d32><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" data-v-283d9d32></path></svg> D\xE9connexion </button></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div></div></div></nav><div class="flex" data-v-283d9d32><div class="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 lg:pt-16 lg:pb-0 lg:bg-white lg:border-r lg:border-gray-200" data-v-283d9d32><div class="flex-1 flex flex-col min-h-0" data-v-283d9d32><nav class="flex-1 px-2 py-4 space-y-1" data-v-283d9d32><!--[-->`);
      ssrRenderList(navigationItems, (item) => {
        _push(ssrRenderComponent(_component_NuxtLink, {
          key: item.name,
          to: item.href,
          class: [
            "group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors",
            isActiveRoute(item.href) ? "bg-blue-100 text-blue-900" : "text-gray-600 hover:bg-gray-50 hover:text-gray-900"
          ]
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              ssrRenderVNode(_push2, createVNode(resolveDynamicComponent(item.icon), {
                class: [
                  "mr-3 flex-shrink-0 h-5 w-5",
                  isActiveRoute(item.href) ? "text-blue-500" : "text-gray-400 group-hover:text-gray-500"
                ]
              }, null), _parent2, _scopeId);
              _push2(` ${ssrInterpolate(item.name)}`);
            } else {
              return [
                (openBlock(), createBlock(resolveDynamicComponent(item.icon), {
                  class: [
                    "mr-3 flex-shrink-0 h-5 w-5",
                    isActiveRoute(item.href) ? "text-blue-500" : "text-gray-400 group-hover:text-gray-500"
                  ]
                }, null, 8, ["class"])),
                createTextVNode(" " + toDisplayString(item.name), 1)
              ];
            }
          }),
          _: 2
        }, _parent));
      });
      _push(`<!--]--></nav></div></div><div class="flex-1 lg:pl-64" data-v-283d9d32><main class="py-6" data-v-283d9d32>`);
      ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent);
      _push(`</main></div></div>`);
      if (mobileMenuOpen.value) {
        _push(`<div class="lg:hidden" data-v-283d9d32><div class="fixed inset-0 z-40 flex" data-v-283d9d32><div class="fixed inset-0 bg-gray-600 bg-opacity-75" data-v-283d9d32></div><div class="relative flex-1 flex flex-col max-w-xs w-full bg-white" data-v-283d9d32><div class="absolute top-0 right-0 -mr-12 pt-2" data-v-283d9d32><button class="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white" data-v-283d9d32><svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" data-v-283d9d32><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" data-v-283d9d32></path></svg></button></div><div class="flex-1 h-0 pt-5 pb-4 overflow-y-auto" data-v-283d9d32><nav class="mt-5 px-2 space-y-1" data-v-283d9d32><!--[-->`);
        ssrRenderList(navigationItems, (item) => {
          _push(ssrRenderComponent(_component_NuxtLink, {
            key: item.name,
            to: item.href,
            onClick: ($event) => mobileMenuOpen.value = false,
            class: [
              "group flex items-center px-2 py-2 text-base font-medium rounded-md transition-colors",
              isActiveRoute(item.href) ? "bg-blue-100 text-blue-900" : "text-gray-600 hover:bg-gray-50 hover:text-gray-900"
            ]
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                ssrRenderVNode(_push2, createVNode(resolveDynamicComponent(item.icon), {
                  class: [
                    "mr-4 flex-shrink-0 h-6 w-6",
                    isActiveRoute(item.href) ? "text-blue-500" : "text-gray-400 group-hover:text-gray-500"
                  ]
                }, null), _parent2, _scopeId);
                _push2(` ${ssrInterpolate(item.name)}`);
              } else {
                return [
                  (openBlock(), createBlock(resolveDynamicComponent(item.icon), {
                    class: [
                      "mr-4 flex-shrink-0 h-6 w-6",
                      isActiveRoute(item.href) ? "text-blue-500" : "text-gray-400 group-hover:text-gray-500"
                    ]
                  }, null, 8, ["class"])),
                  createTextVNode(" " + toDisplayString(item.name), 1)
                ];
              }
            }),
            _: 2
          }, _parent));
        });
        _push(`<!--]--></nav></div></div></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("layouts/student.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const student = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-283d9d32"]]);

export { student as default };
//# sourceMappingURL=student-x9J7YAVT.mjs.map
