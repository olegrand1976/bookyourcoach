import { _ as __nuxt_component_0 } from "./nuxt-link-BC-lyQ5x.js";
import { u as useSettings, _ as _sfc_main$1 } from "./Logo-Bv7gA69-.js";
import { _ as __nuxt_component_2 } from "./EquestrianIcon-DypLCVJ6.js";
import { ref, computed, mergeProps, withCtx, createVNode, unref, createTextVNode, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderSlot } from "vue/server-renderer";
import { ChevronDownIcon } from "@heroicons/vue/24/outline";
import { u as useAuthStore } from "./auth-yP0r1OGC.js";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/ufo/dist/index.mjs";
import "../server.mjs";
import "ofetch";
import "#internal/nuxt/paths";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/hookable/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/unctx/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/h3/dist/index.mjs";
import "vue-router";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/radix3/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/defu/dist/defu.mjs";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/klona/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/@unhead/vue/dist/index.mjs";
import "./_plugin-vue_export-helper-1tPrXgE0.js";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/nuxt/node_modules/cookie-es/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/destr/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/ohash/dist/index.mjs";
import "./ssr-B4FXEZKR.js";
const _sfc_main = {
  __name: "default",
  __ssrInlineRender: true,
  setup(__props) {
    const authStore = useAuthStore();
    const settings = useSettings();
    const userMenuOpen = ref(false);
    const showUserMenu = computed(() => authStore.isAuthenticated);
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      const _component_Logo = _sfc_main$1;
      const _component_EquestrianIcon = __nuxt_component_2;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-equestrian-cream" }, _attrs))}><nav class="bg-white shadow-lg border-b-4 border-equestrian-gold"><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"><div class="flex justify-between h-20"><div class="flex items-center">`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/",
        class: "flex items-center space-x-3 text-xl font-bold text-equestrian-darkBrown hover:text-equestrian-brown transition-colors"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(_component_Logo, { size: "md" }, null, _parent2, _scopeId));
          } else {
            return [
              createVNode(_component_Logo, { size: "md" })
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div><div class="flex items-center space-x-6">`);
      if (unref(authStore).isAuthenticated) {
        _push(`<!--[-->`);
        if (unref(showUserMenu)) {
          _push(`<div class="relative"><button class="flex items-center space-x-2 text-equestrian-darkBrown hover:text-equestrian-brown bg-equestrian-cream px-4 py-2 rounded-lg transition-colors">`);
          _push(ssrRenderComponent(_component_EquestrianIcon, {
            name: "helmet",
            size: 20
          }, null, _parent));
          _push(`<span class="font-medium">${ssrInterpolate(unref(authStore).userName)}</span>`);
          _push(ssrRenderComponent(unref(ChevronDownIcon), { class: "w-4 h-4" }, null, _parent));
          _push(`</button>`);
          if (unref(userMenuOpen)) {
            _push(`<div class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-xl py-2 z-50 border border-equestrian-gold/20">`);
            _push(ssrRenderComponent(_component_NuxtLink, {
              to: "/profile",
              class: "flex items-center space-x-2 px-4 py-3 text-equestrian-darkBrown hover:bg-equestrian-cream transition-colors"
            }, {
              default: withCtx((_, _push2, _parent2, _scopeId) => {
                if (_push2) {
                  _push2(ssrRenderComponent(_component_EquestrianIcon, {
                    name: "helmet",
                    size: 16
                  }, null, _parent2, _scopeId));
                  _push2(`<span${_scopeId}>Mon profil</span>`);
                } else {
                  return [
                    createVNode(_component_EquestrianIcon, {
                      name: "helmet",
                      size: 16
                    }),
                    createVNode("span", null, "Mon profil")
                  ];
                }
              }),
              _: 1
            }, _parent));
            if (unref(authStore).isAdmin) {
              _push(ssrRenderComponent(_component_NuxtLink, {
                to: "/admin",
                class: "flex items-center space-x-2 px-4 py-3 text-equestrian-darkBrown hover:bg-equestrian-cream transition-colors"
              }, {
                default: withCtx((_, _push2, _parent2, _scopeId) => {
                  if (_push2) {
                    _push2(ssrRenderComponent(_component_EquestrianIcon, {
                      name: "trophy",
                      size: 16
                    }, null, _parent2, _scopeId));
                    _push2(`<span${_scopeId}>Administration</span>`);
                  } else {
                    return [
                      createVNode(_component_EquestrianIcon, {
                        name: "trophy",
                        size: 16
                      }),
                      createVNode("span", null, "Administration")
                    ];
                  }
                }),
                _: 1
              }, _parent));
            } else {
              _push(`<!---->`);
            }
            _push(`<hr class="my-2 border-equestrian-gold/20"><button class="flex items-center space-x-2 w-full text-left px-4 py-3 text-red-600 hover:bg-red-50 transition-colors"><span>🚪 Se déconnecter</span></button></div>`);
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
          class: "text-equestrian-darkBrown hover:text-equestrian-brown font-medium px-4 py-2 rounded-lg hover:bg-equestrian-cream transition-colors"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(` Se connecter `);
            } else {
              return [
                createTextVNode(" Se connecter ")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/register",
          class: "btn-primary bg-equestrian-leather hover:bg-equestrian-brown text-white font-semibold px-6 py-2 rounded-lg transition-colors"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(` 🏇 S&#39;inscrire `);
            } else {
              return [
                createTextVNode(" 🏇 S'inscrire ")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`<!--]-->`);
      }
      _push(`</div></div></div></nav><main>`);
      ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent);
      _push(`</main><footer class="bg-equestrian-darkBrown text-equestrian-cream border-t-4 border-equestrian-gold mt-auto"><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12"><div class="grid grid-cols-1 md:grid-cols-4 gap-8"><div class="md:col-span-2"><div class="flex items-center space-x-3 mb-4">`);
      _push(ssrRenderComponent(_component_Logo, { size: "sm" }, null, _parent));
      _push(`</div><p class="text-equestrian-cream/80 mb-4"> La plateforme de référence pour réserver vos cours d&#39;équitation avec des instructeurs certifiés. </p><div class="flex space-x-4">`);
      _push(ssrRenderComponent(_component_EquestrianIcon, {
        name: "trophy",
        size: 24,
        class: "text-equestrian-gold"
      }, null, _parent));
      _push(ssrRenderComponent(_component_EquestrianIcon, {
        name: "saddle",
        size: 24,
        class: "text-equestrian-gold"
      }, null, _parent));
      _push(ssrRenderComponent(_component_EquestrianIcon, {
        name: "helmet",
        size: 24,
        class: "text-equestrian-gold"
      }, null, _parent));
      _push(`</div></div><div><h4 class="font-semibold text-equestrian-gold mb-4">Liens Rapides</h4><ul class="space-y-2"><li>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/coaches",
        class: "text-equestrian-cream/80 hover:text-equestrian-gold transition-colors"
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
        class: "text-equestrian-cream/80 hover:text-equestrian-gold transition-colors"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Centres Équestres`);
          } else {
            return [
              createTextVNode(" Centres Équestres")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</li><li>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/disciplines",
        class: "text-equestrian-cream/80 hover:text-equestrian-gold transition-colors"
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
      _push(`</li></ul></div><div><h4 class="font-semibold text-equestrian-gold mb-4">Contact</h4><ul class="space-y-2 text-equestrian-cream/80"><li>📧 ${ssrInterpolate(unref(settings).settings.contact_email)}</li><li>📞 ${ssrInterpolate(unref(settings).settings.contact_phone)}</li>`);
      if (unref(settings).settings.company_address) {
        _push(`<li>🏠 ${ssrInterpolate(unref(settings).settings.company_address.split("\n")[0])}</li>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</ul></div></div><hr class="border-equestrian-gold/30 my-8"><div class="text-center text-equestrian-cream/60"><p>© 2025 ${ssrInterpolate(unref(settings).settings.platform_name)}. Tous droits réservés. 🐎</p></div></div></footer></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("layouts/default.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
//# sourceMappingURL=default-D_YUrgnN.js.map
