import { _ as _sfc_main$1 } from './Logo-DB4hCrvh.mjs';
import { _ as __nuxt_component_0 } from './nuxt-link-4z5Qc0yN.mjs';
import { _ as __nuxt_component_3 } from './EquestrianIcon-DSrCvKCR.mjs';
import { ref, watchEffect, computed, unref, withCtx, createTextVNode, toDisplayString, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate } from 'vue/server-renderer';
import { CalendarIcon } from '@heroicons/vue/24/outline';
import { u as useAuthStore } from './auth-SYtdBTeW.mjs';
import { u as useSettings } from './useSettings-jytZSqGU.mjs';
import { n as navigateTo, u as useHead } from './server.mjs';
import './_plugin-vue_export-helper-1tPrXgE0.mjs';
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
  __name: "index",
  __ssrInlineRender: true,
  setup(__props) {
    const platformStats = ref({
      coaches: 150,
      students: 2500,
      lessons: 8500,
      locations: 45
    });
    const authStore = useAuthStore();
    const settings = useSettings();
    watchEffect(() => {
      if (authStore.isAuthenticated) {
        navigateTo("/dashboard");
      }
    });
    useHead({
      title: computed(() => `${settings.settings.platform_name} - Trouvez votre instructeur \xE9questre id\xE9al`),
      meta: [
        {
          name: "description",
          content: "Plateforme de r\xE9servation de cours \xE9questres avec des instructeurs certifi\xE9s. R\xE9servez facilement vos le\xE7ons d'\xE9quitation en ligne. Dressage, obstacle, cross-country."
        },
        {
          name: "keywords",
          content: "\xE9quitation, cours \xE9questre, instructeur, cheval, dressage, obstacle, centre \xE9questre, r\xE9servation"
        }
      ]
    });
    return (_ctx, _push, _parent, _attrs) => {
      const _component_Logo = _sfc_main$1;
      const _component_NuxtLink = __nuxt_component_0;
      const _component_EquestrianIcon = __nuxt_component_3;
      _push(`<div${ssrRenderAttrs(_attrs)}><section class="bg-stable-gradient text-white relative overflow-hidden" data-testid="home-hero"><div class="absolute inset-0 bg-horse-pattern opacity-10"></div><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 relative z-10"><div class="text-center"><div class="flex justify-center mb-8"><div class="bg-white/10 backdrop-blur-sm p-6 rounded-xl">`);
      _push(ssrRenderComponent(_component_Logo, { size: "lg" }, null, _parent));
      _push(`</div></div><h1 class="text-4xl md:text-6xl font-bold mb-6 font-serif">${ssrInterpolate(unref(settings).settings.platform_name)}</h1><p class="text-xl md:text-2xl mb-8 text-equestrian-cream">${ssrInterpolate(_ctx.$t("home.hero.tagline"))}</p><div class="flex flex-col sm:flex-row gap-4 justify-center">`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/register",
        "data-testid": "cta-register",
        class: "btn-primary bg-equestrian-cream text-equestrian-darkBrown hover:bg-white hover:shadow-lg transform hover:scale-105 transition-all duration-200 font-semibold"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u{1F3C7} ${ssrInterpolate(_ctx.$t("home.hero.ctaStart"))}`);
          } else {
            return [
              createTextVNode(" \u{1F3C7} " + toDisplayString(_ctx.$t("home.hero.ctaStart")), 1)
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/teachers",
        "data-testid": "cta-discover",
        class: "btn-secondary border-equestrian-cream text-equestrian-cream hover:bg-equestrian-cream hover:text-equestrian-darkBrown transition-all duration-200 font-semibold"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u{1F40E} ${ssrInterpolate(_ctx.$t("home.hero.ctaDiscover"))}`);
          } else {
            return [
              createTextVNode(" \u{1F40E} " + toDisplayString(_ctx.$t("home.hero.ctaDiscover")), 1)
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></div></div></section><section class="py-24 bg-equestrian-cream" data-testid="home-features"><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"><div class="text-center mb-16"><h2 class="text-3xl md:text-4xl font-bold text-equestrian-darkBrown mb-4 font-serif">${ssrInterpolate(_ctx.$t("home.features.title", { platform: unref(settings).settings.platform_name }))}</h2><p class="text-xl text-equestrian-brown max-w-3xl mx-auto">${ssrInterpolate(_ctx.$t("home.features.subtitle"))}</p></div><div class="grid grid-cols-1 md:grid-cols-3 gap-8"><div class="text-center bg-white rounded-xl p-8 shadow-lg hover:shadow-xl transition-shadow duration-300"><div class="w-20 h-20 bg-gradient-to-br from-equestrian-leather to-equestrian-gold rounded-full flex items-center justify-center mx-auto mb-6">`);
      _push(ssrRenderComponent(_component_EquestrianIcon, {
        name: "trophy",
        size: 40,
        class: "text-white"
      }, null, _parent));
      _push(`</div><h3 class="text-xl font-semibold mb-4 text-equestrian-darkBrown">${ssrInterpolate(_ctx.$t("home.features.items.certified.title"))}</h3><p class="text-equestrian-brown">${ssrInterpolate(_ctx.$t("home.features.items.certified.desc"))}</p></div><div class="text-center bg-white rounded-xl p-8 shadow-lg hover:shadow-xl transition-shadow duration-300"><div class="w-20 h-20 bg-gradient-to-br from-equestrian-leather to-equestrian-gold rounded-full flex items-center justify-center mx-auto mb-6">`);
      _push(ssrRenderComponent(unref(CalendarIcon), { class: "w-10 h-10 text-white" }, null, _parent));
      _push(`</div><h3 class="text-xl font-semibold mb-4 text-equestrian-darkBrown">${ssrInterpolate(_ctx.$t("home.features.items.flexible.title"))}</h3><p class="text-equestrian-brown">${ssrInterpolate(_ctx.$t("home.features.items.flexible.desc"))}</p></div><div class="text-center bg-white rounded-xl p-8 shadow-lg hover:shadow-xl transition-shadow duration-300"><div class="w-20 h-20 bg-gradient-to-br from-equestrian-leather to-equestrian-gold rounded-full flex items-center justify-center mx-auto mb-6">`);
      _push(ssrRenderComponent(_component_EquestrianIcon, {
        name: "helmet",
        size: 40,
        class: "text-white"
      }, null, _parent));
      _push(`</div><h3 class="text-xl font-semibold mb-4 text-equestrian-darkBrown">${ssrInterpolate(_ctx.$t("home.features.items.safety.title"))}</h3><p class="text-equestrian-brown">${ssrInterpolate(_ctx.$t("home.features.items.safety.desc"))}</p></div></div></div></section><section class="py-24 bg-gradient-to-r from-equestrian-brown to-equestrian-leather text-white" data-testid="home-stats"><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"><div class="text-center mb-12"><h2 class="text-3xl font-bold mb-4 font-serif">${ssrInterpolate(_ctx.$t("home.stats.title"))}</h2>`);
      _push(ssrRenderComponent(_component_EquestrianIcon, {
        name: "horseshoe",
        size: 60,
        class: "text-equestrian-gold mx-auto"
      }, null, _parent));
      _push(`</div><div class="grid grid-cols-1 md:grid-cols-4 gap-8 text-center"><div class="bg-white/10 backdrop-blur-sm rounded-lg p-6"><div class="text-4xl font-bold text-equestrian-gold mb-2">${ssrInterpolate(unref(platformStats).coaches)}+</div><div class="text-equestrian-cream flex items-center justify-center gap-2">`);
      _push(ssrRenderComponent(_component_EquestrianIcon, {
        name: "horse",
        size: 20
      }, null, _parent));
      _push(` ${ssrInterpolate(_ctx.$t("home.stats.coachesLabel"))}</div></div><div class="bg-white/10 backdrop-blur-sm rounded-lg p-6"><div class="text-4xl font-bold text-equestrian-gold mb-2">${ssrInterpolate(unref(platformStats).students)}+</div><div class="text-equestrian-cream">${ssrInterpolate(_ctx.$t("home.stats.studentsLabel"))}</div></div><div class="bg-white/10 backdrop-blur-sm rounded-lg p-6"><div class="text-4xl font-bold text-equestrian-gold mb-2">${ssrInterpolate(unref(platformStats).lessons)}+</div><div class="text-equestrian-cream">${ssrInterpolate(_ctx.$t("home.stats.lessonsLabel"))}</div></div><div class="bg-white/10 backdrop-blur-sm rounded-lg p-6"><div class="text-4xl font-bold text-equestrian-gold mb-2">${ssrInterpolate(unref(platformStats).locations)}+</div><div class="text-equestrian-cream flex items-center justify-center gap-2">`);
      _push(ssrRenderComponent(_component_EquestrianIcon, {
        name: "saddle",
        size: 20
      }, null, _parent));
      _push(` ${ssrInterpolate(_ctx.$t("home.stats.locationsLabel"))}</div></div></div></div></section><section class="py-24 bg-equestrian-forest text-white relative overflow-hidden" data-testid="home-cta"><div class="absolute inset-0 bg-horse-pattern opacity-5"></div><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">`);
      _push(ssrRenderComponent(_component_EquestrianIcon, {
        name: "jump",
        size: 80,
        class: "text-equestrian-gold mx-auto mb-8"
      }, null, _parent));
      _push(`<h2 class="text-3xl md:text-4xl font-bold mb-6 font-serif">${ssrInterpolate(_ctx.$t("home.cta.title"))}</h2><p class="text-xl mb-8 text-green-100 max-w-2xl mx-auto">${ssrInterpolate(_ctx.$t("home.cta.subtitle"))}</p><div class="flex flex-col sm:flex-row gap-4 justify-center">`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/register",
        "data-testid": "cta-register-bottom",
        class: "btn-primary bg-equestrian-gold text-equestrian-darkBrown hover:bg-equestrian-cream hover:shadow-lg transform hover:scale-105 transition-all duration-200 font-semibold text-lg px-8 py-4"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u{1F3C6} ${ssrInterpolate(_ctx.$t("home.cta.ctaSignup"))}`);
          } else {
            return [
              createTextVNode(" \u{1F3C6} " + toDisplayString(_ctx.$t("home.cta.ctaSignup")), 1)
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/about",
        class: "btn-secondary border-equestrian-gold text-equestrian-gold hover:bg-equestrian-gold hover:text-equestrian-darkBrown transition-all duration-200 font-semibold text-lg px-8 py-4"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u{1F4D6} ${ssrInterpolate(_ctx.$t("home.cta.ctaAbout"))}`);
          } else {
            return [
              createTextVNode(" \u{1F4D6} " + toDisplayString(_ctx.$t("home.cta.ctaAbout")), 1)
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></div></section></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=index-XQ0VDCoh.mjs.map
