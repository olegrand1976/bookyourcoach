import { _ as __nuxt_component_0 } from './nuxt-link-BxC6TOEk.mjs';
import { _ as _sfc_main$2 } from './Logo-BidJ-H0e.mjs';
import { ref, computed, mergeProps, withCtx, createVNode, unref, toDisplayString, createTextVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderSlot, ssrRenderList, ssrRenderClass } from 'vue/server-renderer';
import { ChevronDownIcon } from '@heroicons/vue/24/outline';
import { b as useNuxtApp } from './server.mjs';
import { _ as __nuxt_component_3 } from './EquestrianIcon-DSrCvKCR.mjs';
import { u as useAuthStore } from './auth-BBLAd2fH.mjs';
import { u as useSettings } from './useSettings-DsXo8ctA.mjs';
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
import './_plugin-vue_export-helper-1tPrXgE0.mjs';
import './ssr-B4FXEZKR.mjs';

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
        fr: "\u{1F1EB}\u{1F1F7}",
        en: "\u{1F1FA}\u{1F1F8}",
        nl: "\u{1F1F3}\u{1F1F1}",
        de: "\u{1F1E9}\u{1F1EA}",
        it: "\u{1F1EE}\u{1F1F9}",
        es: "\u{1F1EA}\u{1F1F8}",
        pt: "\u{1F1F5}\u{1F1F9}",
        hu: "\u{1F1ED}\u{1F1FA}",
        pl: "\u{1F1F5}\u{1F1F1}",
        zh: "\u{1F1E8}\u{1F1F3}",
        ja: "\u{1F1EF}\u{1F1F5}",
        sv: "\u{1F1F8}\u{1F1EA}",
        no: "\u{1F1F3}\u{1F1F4}",
        fi: "\u{1F1EB}\u{1F1EE}",
        da: "\u{1F1E9}\u{1F1F0}"
      };
      return flags[code] || "\u{1F310}";
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
            _push(`<hr class="my-2 border-equestrian-gold/20"><button class="flex items-center space-x-2 w-full text-left px-4 py-3 text-red-600 hover:bg-red-50 transition-colors"><span>\u{1F6AA} ${ssrInterpolate(_ctx.$t("nav.logout"))}</span></button></div>`);
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
          class: "btn-primary bg-equestrian-leather hover:bg-equestrian-brown text-white font-semibold px-6 py-2 rounded-lg transition-colors"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(` \u{1F3C7} ${ssrInterpolate(_ctx.$t("nav.register"))}`);
            } else {
              return [
                createTextVNode(" \u{1F3C7} " + toDisplayString(_ctx.$t("nav.register")), 1)
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
      _push(`</li></ul></div><div><h4 class="font-semibold text-equestrian-gold mb-4">Contact</h4><ul class="space-y-2 text-equestrian-cream/80"><li>\u{1F4E7} ${ssrInterpolate(unref(settings).settings.contact_email)}</li><li>\u{1F4DE} ${ssrInterpolate(unref(settings).settings.contact_phone)}</li>`);
      if (unref(settings).settings.company_address) {
        _push(`<li>\u{1F3E0} ${ssrInterpolate(unref(settings).settings.company_address.split("\n")[0])}</li>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</ul></div></div><hr class="border-equestrian-gold/30 my-8"><div class="text-center text-equestrian-cream/60"><p>\xA9 2025 ${ssrInterpolate(unref(settings).settings.platform_name)}. Tous droits r\xE9serv\xE9s. \u{1F40E}</p></div></div></footer></div>`);
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
//# sourceMappingURL=default-DT-HqPEV.mjs.map
