import { _ as __nuxt_component_0 } from './nuxt-link-3RFQ5DMr.mjs';
import { ref, computed, mergeProps, withCtx, createVNode, unref, createTextVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderAttr, ssrInterpolate, ssrRenderSlot } from 'vue/server-renderer';
import { _ as _imports_0 } from './virtual_public-lEjwge2O.mjs';
import { ChevronDownIcon } from '@heroicons/vue/24/outline';
import { a as useAuthStore } from './server.mjs';
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
  __name: "default",
  __ssrInlineRender: true,
  setup(__props) {
    const authStore = useAuthStore();
    const userMenuOpen = ref(false);
    const isAuthenticated = computed(() => authStore.isAuthenticated);
    const userName = computed(() => authStore.userName);
    const canActAsTeacher = computed(() => authStore.canActAsTeacher);
    const isStudent = computed(() => authStore.isStudent);
    const isAdmin = computed(() => authStore.isAdmin);
    const isClub = computed(() => {
      var _a;
      return ((_a = authStore.user) == null ? void 0 : _a.role) === "club";
    });
    const showUserMenu = computed(() => isAuthenticated.value);
    return (_ctx, _push, _parent, _attrs) => {
      var _a;
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-50" }, _attrs))}><nav class="bg-white shadow-lg border-b-4 border-blue-500"><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"><div class="flex justify-between h-20"><div class="flex items-center">`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/",
        class: "flex items-center space-x-3 text-xl font-bold text-gray-900 hover:text-gray-700 transition-colors"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<img${ssrRenderAttr("src", _imports_0)} alt="activibe" class="h-12 w-auto"${_scopeId}>`);
          } else {
            return [
              createVNode("img", {
                src: _imports_0,
                alt: "activibe",
                class: "h-12 w-auto"
              })
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div><div class="flex items-center space-x-6">`);
      if (unref(isAuthenticated)) {
        _push(`<!--[-->`);
        if (unref(showUserMenu)) {
          _push(`<div class="relative"><button class="flex items-center space-x-2 text-gray-900 hover:text-gray-700 bg-gray-50 px-4 py-2 rounded-lg transition-colors"><span class="text-lg">\u{1F464}</span><span class="font-medium">${ssrInterpolate(unref(userName))}</span>`);
          _push(ssrRenderComponent(unref(ChevronDownIcon), { class: "w-4 h-4" }, null, _parent));
          _push(`</button>`);
          if (unref(userMenuOpen)) {
            _push(`<div class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-xl py-2 z-50 border border-blue-500/20">`);
            if (unref(isAdmin)) {
              _push(ssrRenderComponent(_component_NuxtLink, {
                to: "/admin",
                class: "flex items-center space-x-2 w-full text-left px-4 py-3 text-gray-700 hover:bg-gray-100 transition-colors"
              }, {
                default: withCtx((_, _push2, _parent2, _scopeId) => {
                  if (_push2) {
                    _push2(`<span${_scopeId}>\u{1F4CA}</span><span${_scopeId}>Tableau de bord</span>`);
                  } else {
                    return [
                      createVNode("span", null, "\u{1F4CA}"),
                      createVNode("span", null, "Tableau de bord")
                    ];
                  }
                }),
                _: 1
              }, _parent));
            } else if (unref(isClub)) {
              _push(ssrRenderComponent(_component_NuxtLink, {
                to: "/club/dashboard",
                class: "flex items-center space-x-2 w-full text-left px-4 py-3 text-gray-700 hover:bg-gray-100 transition-colors"
              }, {
                default: withCtx((_, _push2, _parent2, _scopeId) => {
                  if (_push2) {
                    _push2(`<span${_scopeId}>\u{1F4CA}</span><span${_scopeId}>Tableau de bord</span>`);
                  } else {
                    return [
                      createVNode("span", null, "\u{1F4CA}"),
                      createVNode("span", null, "Tableau de bord")
                    ];
                  }
                }),
                _: 1
              }, _parent));
            } else if (unref(canActAsTeacher)) {
              _push(ssrRenderComponent(_component_NuxtLink, {
                to: "/teacher/dashboard",
                class: "flex items-center space-x-2 w-full text-left px-4 py-3 text-gray-700 hover:bg-gray-100 transition-colors"
              }, {
                default: withCtx((_, _push2, _parent2, _scopeId) => {
                  if (_push2) {
                    _push2(`<span${_scopeId}>\u{1F4CA}</span><span${_scopeId}>Tableau de bord</span>`);
                  } else {
                    return [
                      createVNode("span", null, "\u{1F4CA}"),
                      createVNode("span", null, "Tableau de bord")
                    ];
                  }
                }),
                _: 1
              }, _parent));
            } else if (unref(isStudent)) {
              _push(ssrRenderComponent(_component_NuxtLink, {
                to: "/student/dashboard",
                class: "flex items-center space-x-2 w-full text-left px-4 py-3 text-gray-700 hover:bg-gray-100 transition-colors"
              }, {
                default: withCtx((_, _push2, _parent2, _scopeId) => {
                  if (_push2) {
                    _push2(`<span${_scopeId}>\u{1F4CA}</span><span${_scopeId}>Tableau de bord</span>`);
                  } else {
                    return [
                      createVNode("span", null, "\u{1F4CA}"),
                      createVNode("span", null, "Tableau de bord")
                    ];
                  }
                }),
                _: 1
              }, _parent));
            } else {
              _push(`<!---->`);
            }
            if (unref(canActAsTeacher) && !unref(isAdmin)) {
              _push(ssrRenderComponent(_component_NuxtLink, {
                to: "/teacher/dashboard",
                class: "flex items-center space-x-2 w-full text-left px-4 py-3 text-gray-700 hover:bg-gray-100 transition-colors"
              }, {
                default: withCtx((_, _push2, _parent2, _scopeId) => {
                  if (_push2) {
                    _push2(`<span${_scopeId}>\u{1F3C7}</span><span${_scopeId}>Espace Enseignant</span>`);
                  } else {
                    return [
                      createVNode("span", null, "\u{1F3C7}"),
                      createVNode("span", null, "Espace Enseignant")
                    ];
                  }
                }),
                _: 1
              }, _parent));
            } else {
              _push(`<!---->`);
            }
            if (unref(isStudent)) {
              _push(ssrRenderComponent(_component_NuxtLink, {
                to: "/student/dashboard",
                class: "flex items-center space-x-2 w-full text-left px-4 py-3 text-gray-700 hover:bg-gray-100 transition-colors"
              }, {
                default: withCtx((_, _push2, _parent2, _scopeId) => {
                  if (_push2) {
                    _push2(`<span${_scopeId}>\u{1F468}\u200D\u{1F393}</span><span${_scopeId}>Espace \xC9tudiant</span>`);
                  } else {
                    return [
                      createVNode("span", null, "\u{1F468}\u200D\u{1F393}"),
                      createVNode("span", null, "Espace \xC9tudiant")
                    ];
                  }
                }),
                _: 1
              }, _parent));
            } else {
              _push(`<!---->`);
            }
            if (unref(isClub)) {
              _push(ssrRenderComponent(_component_NuxtLink, {
                to: "/club/teachers",
                class: "flex items-center space-x-2 w-full text-left px-4 py-3 text-gray-700 hover:bg-gray-100 transition-colors"
              }, {
                default: withCtx((_, _push2, _parent2, _scopeId) => {
                  if (_push2) {
                    _push2(`<span${_scopeId}>\u{1F468}\u200D\u{1F3EB}</span><span${_scopeId}>Enseignants</span>`);
                  } else {
                    return [
                      createVNode("span", null, "\u{1F468}\u200D\u{1F3EB}"),
                      createVNode("span", null, "Enseignants")
                    ];
                  }
                }),
                _: 1
              }, _parent));
            } else {
              _push(`<!---->`);
            }
            if (unref(isClub)) {
              _push(ssrRenderComponent(_component_NuxtLink, {
                to: "/club/students",
                class: "flex items-center space-x-2 w-full text-left px-4 py-3 text-gray-700 hover:bg-gray-100 transition-colors"
              }, {
                default: withCtx((_, _push2, _parent2, _scopeId) => {
                  if (_push2) {
                    _push2(`<span${_scopeId}>\u{1F468}\u200D\u{1F393}</span><span${_scopeId}>\xC9l\xE8ves</span>`);
                  } else {
                    return [
                      createVNode("span", null, "\u{1F468}\u200D\u{1F393}"),
                      createVNode("span", null, "\xC9l\xE8ves")
                    ];
                  }
                }),
                _: 1
              }, _parent));
            } else {
              _push(`<!---->`);
            }
            _push(ssrRenderComponent(_component_NuxtLink, {
              to: ((_a = unref(authStore).user) == null ? void 0 : _a.role) === "club" ? "/club/profile" : "/profile",
              class: "flex items-center space-x-2 w-full text-left px-4 py-3 text-gray-700 hover:bg-gray-100 transition-colors"
            }, {
              default: withCtx((_, _push2, _parent2, _scopeId) => {
                if (_push2) {
                  _push2(`<span${_scopeId}>\u{1F464}</span><span${_scopeId}>Profil</span>`);
                } else {
                  return [
                    createVNode("span", null, "\u{1F464}"),
                    createVNode("span", null, "Profil")
                  ];
                }
              }),
              _: 1
            }, _parent));
            if (unref(isAdmin)) {
              _push(ssrRenderComponent(_component_NuxtLink, {
                to: "/admin",
                class: "flex items-center space-x-2 w-full text-left px-4 py-3 text-gray-700 hover:bg-gray-100 transition-colors"
              }, {
                default: withCtx((_, _push2, _parent2, _scopeId) => {
                  if (_push2) {
                    _push2(`<span${_scopeId}>\u2699\uFE0F</span><span${_scopeId}>Administration</span>`);
                  } else {
                    return [
                      createVNode("span", null, "\u2699\uFE0F"),
                      createVNode("span", null, "Administration")
                    ];
                  }
                }),
                _: 1
              }, _parent));
            } else {
              _push(`<!---->`);
            }
            _push(`<hr class="my-2 border-gray-200"><button class="flex items-center space-x-2 w-full text-left px-4 py-3 text-red-600 hover:bg-red-50 transition-colors"><span>\u{1F6AA} D\xE9connexion</span></button></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<!--]-->`);
      } else {
        _push(`<!--[-->`);
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
              _push2(` \u{1F3C7} Inscription `);
            } else {
              return [
                createTextVNode(" \u{1F3C7} Inscription ")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`<!--]-->`);
      }
      _push(`</div></div></div></nav><main>`);
      ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent);
      _push(`</main><footer class="bg-gray-800 text-gray-100 border-t-4 border-blue-500 mt-auto"><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12"><div class="grid grid-cols-1 md:grid-cols-4 gap-8"><div class="md:col-span-2"><div class="flex items-center space-x-3 mb-4"><span class="text-2xl">\u{1F40E}</span><span class="text-xl font-bold">BookYourCoach</span></div><p class="text-gray-100/80 mb-4"> La plateforme de r\xE9f\xE9rence pour r\xE9server vos cours d&#39;\xE9quitation et de natation avec les meilleurs instructeurs. </p><div class="flex space-x-4"><span class="text-2xl">\u{1F3C6}</span><span class="text-2xl">\u{1F3C7}</span><span class="text-2xl">\u26D1\uFE0F</span></div></div><div><h4 class="font-semibold text-blue-400 mb-4">Liens Rapides</h4><ul class="space-y-2"><li>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/coaches",
        class: "text-gray-100/80 hover:text-blue-400 transition-colors"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Nos Instructeurs`);
          } else {
            return [
              createTextVNode(" Nos Instructeurs")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</li><li>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/centers",
        class: "text-gray-100/80 hover:text-blue-400 transition-colors"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Centres \xC9questres`);
          } else {
            return [
              createTextVNode(" Centres \xC9questres")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</li><li>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/disciplines",
        class: "text-gray-100/80 hover:text-blue-400 transition-colors"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Disciplines`);
          } else {
            return [
              createTextVNode(" Disciplines")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</li></ul></div><div><h4 class="font-semibold text-blue-400 mb-4">Contact</h4><ul class="space-y-2 text-gray-100/80"><li>\u{1F4E7} contact@bookyourcoach.com</li><li>\u{1F4DE} +33 1 23 45 67 89</li><li>\u{1F3E0} Paris, France</li></ul></div></div><hr class="border-blue-500/30 my-8"><div class="text-center text-gray-100/60"><p>\xA9 2025 BookYourCoach. Tous droits r\xE9serv\xE9s. \u{1F40E}</p></div></div></footer></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("layouts/default.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=default-CNfPsqWg.mjs.map
