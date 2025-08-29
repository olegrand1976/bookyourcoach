import { _ as __nuxt_component_0 } from "./nuxt-link-4z5Qc0yN.js";
import { _ as _sfc_main$2 } from "./Logo-DB4hCrvh.js";
import { ref, computed, mergeProps, unref, useSSRContext, withCtx, createVNode, toDisplayString, createTextVNode } from "vue";
import { ssrRenderAttrs, ssrInterpolate, ssrRenderComponent, ssrRenderList, ssrRenderClass, ssrRenderSlot } from "vue/server-renderer";
import { ChevronDownIcon } from "@heroicons/vue/24/outline";
import { d as useNuxtApp } from "../server.mjs";
import { _ as __nuxt_component_3 } from "./EquestrianIcon-DSrCvKCR.js";
import { u as useAuthStore } from "./auth-SYtdBTeW.js";
import { u as useSettings } from "./useSettings-jytZSqGU.js";
import "ofetch";
import "#internal/nuxt/paths";
import "/workspace/frontend/node_modules/hookable/dist/index.mjs";
import "/workspace/frontend/node_modules/unctx/dist/index.mjs";
import "/workspace/frontend/node_modules/h3/dist/index.mjs";
import "vue-router";
import "/workspace/frontend/node_modules/radix3/dist/index.mjs";
import "/workspace/frontend/node_modules/defu/dist/defu.mjs";
import "/workspace/frontend/node_modules/klona/dist/index.mjs";
import "/workspace/frontend/node_modules/nuxt/node_modules/cookie-es/dist/index.mjs";
import "/workspace/frontend/node_modules/destr/dist/index.mjs";
import "/workspace/frontend/node_modules/ohash/dist/index.mjs";
import "@vue/devtools-api";
import "/workspace/frontend/node_modules/@unhead/vue/dist/index.mjs";
import "./_plugin-vue_export-helper-1tPrXgE0.js";
const _sfc_main$1 = {
  __name: "LanguageSelector",
  __ssrInlineRender: true,
  setup(__props) {
    const { $i18n } = useNuxtApp();
    const isOpen = ref(false);
    const availableLocales = computed(() => $i18n.locales);
    const currentLocale = computed(
      () => availableLocales.value.find((locale) => locale.code === $i18n.locale) || availableLocales.value[0]
    );
    const getFlagEmoji = (code) => {
      const flags = {
        fr: "🇫🇷",
        en: "🇺🇸",
        nl: "🇳🇱",
        de: "🇩🇪",
        it: "🇮🇹",
        es: "🇪🇸",
        pt: "🇵🇹",
        hu: "🇭🇺",
        pl: "🇵🇱",
        zh: "🇨🇳",
        ja: "🇯🇵",
        sv: "🇸🇪",
        no: "🇳🇴",
        fi: "🇫🇮",
        da: "🇩🇰"
      };
      return flags[code] || "🌐";
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "relative" }, _attrs))}><button class="flex items-center space-x-2 px-3 py-2 rounded-lg bg-white/10 hover:bg-white/20 transition-colors text-equestrian-cream"><span class="text-sm font-medium">${ssrInterpolate(unref(currentLocale).name)}</span>`);
      _push(ssrRenderComponent(unref(ChevronDownIcon), {
        class: ["w-4 h-4", { "rotate-180": unref(isOpen) }]
      }, null, _parent));
      _push(`</button>`);
      if (unref(isOpen)) {
        _push(`<div class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl py-2 z-50 border border-equestrian-gold/20"><!--[-->`);
        ssrRenderList(unref(availableLocales), (locale) => {
          _push(`<button class="${ssrRenderClass([{ "bg-equestrian-cream font-medium": unref($i18n).locale === locale.code }, "flex items-center w-full px-4 py-2 text-sm text-equestrian-darkBrown hover:bg-equestrian-cream transition-colors"])}"><span class="mr-3">${ssrInterpolate(getFlagEmoji(locale.code))}</span><span>${ssrInterpolate(locale.name)}</span></button>`);
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/LanguageSelector.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
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
      const _component_Logo = _sfc_main$2;
      const _component_LanguageSelector = _sfc_main$1;
      const _component_EquestrianIcon = __nuxt_component_3;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-equestrian-cream" }, _attrs))}><nav class="bg-white shadow-lg border-b-4 border-equestrian-gold" data-testid="nav"><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"><div class="flex justify-between h-20"><div class="flex items-center">`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/",
        class: "flex items-center space-x-3 text-xl font-bold text-equestrian-darkBrown hover:text-equestrian-brown transition-colors"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(_component_Logo, {
              size: "md",
              "data-testid": "logo"
            }, null, _parent2, _scopeId));
          } else {
            return [
              createVNode(_component_Logo, {
                size: "md",
                "data-testid": "logo"
              })
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div><div class="flex items-center space-x-6">`);
      _push(ssrRenderComponent(_component_LanguageSelector, null, null, _parent));
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
              to: "/dashboard",
              class: "flex items-center space-x-2 px-4 py-3 text-equestrian-darkBrown hover:bg-equestrian-cream transition-colors"
            }, {
              default: withCtx((_, _push2, _parent2, _scopeId) => {
                if (_push2) {
                  _push2(ssrRenderComponent(_component_EquestrianIcon, {
                    name: "dashboard",
                    size: 16
                  }, null, _parent2, _scopeId));
                  _push2(`<span${_scopeId}>${ssrInterpolate(_ctx.$t("nav.dashboard"))}</span>`);
                } else {
                  return [
                    createVNode(_component_EquestrianIcon, {
                      name: "dashboard",
                      size: 16
                    }),
                    createVNode("span", null, toDisplayString(_ctx.$t("nav.dashboard")), 1)
                  ];
                }
              }),
              _: 1
            }, _parent));
            if (unref(authStore).canActAsTeacher) {
              _push(ssrRenderComponent(_component_NuxtLink, {
                to: "/teacher/dashboard",
                class: "flex items-center space-x-2 px-4 py-3 text-equestrian-darkBrown hover:bg-equestrian-cream transition-colors"
              }, {
                default: withCtx((_, _push2, _parent2, _scopeId) => {
                  if (_push2) {
                    _push2(ssrRenderComponent(_component_EquestrianIcon, {
                      name: "saddle",
                      size: 16
                    }, null, _parent2, _scopeId));
                    _push2(`<span${_scopeId}>${ssrInterpolate(_ctx.$t("nav.teacherSpace"))}</span>`);
                  } else {
                    return [
                      createVNode(_component_EquestrianIcon, {
                        name: "saddle",
                        size: 16
                      }),
                      createVNode("span", null, toDisplayString(_ctx.$t("nav.teacherSpace")), 1)
                    ];
                  }
                }),
                _: 1
              }, _parent));
            } else {
              _push(`<!---->`);
            }
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
                  _push2(`<span${_scopeId}>${ssrInterpolate(_ctx.$t("nav.profile"))}</span>`);
                } else {
                  return [
                    createVNode(_component_EquestrianIcon, {
                      name: "helmet",
                      size: 16
                    }),
                    createVNode("span", null, toDisplayString(_ctx.$t("nav.profile")), 1)
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
                    _push2(`<span${_scopeId}>${ssrInterpolate(_ctx.$t("nav.admin"))}</span>`);
                  } else {
                    return [
                      createVNode(_component_EquestrianIcon, {
                        name: "trophy",
                        size: 16
                      }),
                      createVNode("span", null, toDisplayString(_ctx.$t("nav.admin")), 1)
                    ];
                  }
                }),
                _: 1
              }, _parent));
            } else {
              _push(`<!---->`);
            }
            _push(`<hr class="my-2 border-equestrian-gold/20"><button class="flex items-center space-x-2 w-full text-left px-4 py-3 text-red-600 hover:bg-red-50 transition-colors"><span>🚪 ${ssrInterpolate(_ctx.$t("nav.logout"))}</span></button></div>`);
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
          "data-testid": "login-link",
          class: "text-equestrian-darkBrown hover:text-equestrian-brown font-medium px-4 py-2 rounded-lg hover:bg-equestrian-cream transition-colors"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`${ssrInterpolate(_ctx.$t("nav.login"))}`);
            } else {
              return [
                createTextVNode(toDisplayString(_ctx.$t("nav.login")), 1)
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/register",
          "data-testid": "register-link",
          class: "btn-primary bg-equestrian-leather hover:bg-equestrian-brown text-white font-semibold px-6 py-2 rounded-lg transition-colors"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(` 🏇 ${ssrInterpolate(_ctx.$t("nav.register"))}`);
            } else {
              return [
                createTextVNode(" 🏇 " + toDisplayString(_ctx.$t("nav.register")), 1)
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`<!--]-->`);
      }
      _push(`</div></div></div></nav><main>`);
      ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent);
      _push(`</main><footer class="bg-equestrian-darkBrown text-equestrian-cream border-t-4 border-equestrian-gold mt-auto" data-testid="footer"><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12"><div class="grid grid-cols-1 md:grid-cols-4 gap-8"><div class="md:col-span-2"><div class="flex items-center space-x-3 mb-4">`);
      _push(ssrRenderComponent(_component_Logo, { size: "sm" }, null, _parent));
      _push(`</div><p class="text-equestrian-cream/80 mb-4">${ssrInterpolate(_ctx.$t("footer.description"))}</p><div class="flex space-x-4">`);
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
//# sourceMappingURL=default-DV0JR71x.js.map
