import { _ as _sfc_main$1 } from './Logo-BidJ-H0e.mjs';
import { _ as __nuxt_component_0 } from './nuxt-link-BxC6TOEk.mjs';
import { _ as __nuxt_component_3 } from './EquestrianIcon-DSrCvKCR.mjs';
import { ref, watchEffect, computed, unref, withCtx, createTextVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate } from 'vue/server-renderer';
import { CalendarIcon } from '@heroicons/vue/24/outline';
import { u as useAuthStore } from './auth-BBLAd2fH.mjs';
import { u as useSettings } from './useSettings-DsXo8ctA.mjs';
import { n as navigateTo, u as useHead } from './server.mjs';
import '../nitro/nitro.mjs';
import 'node:http';
import 'node:https';
import 'node:events';
import 'node:buffer';
import 'node:fs';
import 'node:path';
import 'node:crypto';
import 'node:url';
import './_plugin-vue_export-helper-1tPrXgE0.mjs';
import './ssr-B4FXEZKR.mjs';
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
      _push(`<div${ssrRenderAttrs(_attrs)}><section class="bg-stable-gradient text-white relative overflow-hidden"><div class="absolute inset-0 bg-horse-pattern opacity-10"></div><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 relative z-10"><div class="text-center"><div class="flex justify-center mb-8"><div class="bg-white/10 backdrop-blur-sm p-6 rounded-xl">`);
      _push(ssrRenderComponent(_component_Logo, { size: "lg" }, null, _parent));
      _push(`</div></div><h1 class="text-4xl md:text-6xl font-bold mb-6 font-serif">${ssrInterpolate(unref(settings).settings.platform_name)}</h1><p class="text-xl md:text-2xl mb-8 text-equestrian-cream"> Trouvez votre instructeur \xE9questre id\xE9al et r\xE9servez vos cours d&#39;\xE9quitation </p><div class="flex flex-col sm:flex-row gap-4 justify-center">`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/register",
        class: "btn-primary bg-equestrian-cream text-equestrian-darkBrown hover:bg-white hover:shadow-lg transform hover:scale-105 transition-all duration-200 font-semibold"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u{1F3C7} Commencer l&#39;aventure `);
          } else {
            return [
              createTextVNode(" \u{1F3C7} Commencer l'aventure ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/teachers",
        class: "btn-secondary border-equestrian-cream text-equestrian-cream hover:bg-equestrian-cream hover:text-equestrian-darkBrown transition-all duration-200 font-semibold"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u{1F40E} D\xE9couvrir les coaches `);
          } else {
            return [
              createTextVNode(" \u{1F40E} D\xE9couvrir les coaches ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></div></div></section><section class="py-24 bg-equestrian-cream"><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"><div class="text-center mb-16"><h2 class="text-3xl md:text-4xl font-bold text-equestrian-darkBrown mb-4 font-serif"> Pourquoi choisir ${ssrInterpolate(unref(settings).settings.platform_name)} ? </h2><p class="text-xl text-equestrian-brown max-w-3xl mx-auto"> La plateforme de r\xE9f\xE9rence pour l&#39;\xE9quitation moderne </p></div><div class="grid grid-cols-1 md:grid-cols-3 gap-8"><div class="text-center bg-white rounded-xl p-8 shadow-lg hover:shadow-xl transition-shadow duration-300"><div class="w-20 h-20 bg-gradient-to-br from-equestrian-leather to-equestrian-gold rounded-full flex items-center justify-center mx-auto mb-6">`);
      _push(ssrRenderComponent(_component_EquestrianIcon, {
        name: "trophy",
        size: 40,
        class: "text-white"
      }, null, _parent));
      _push(`</div><h3 class="text-xl font-semibold mb-4 text-equestrian-darkBrown">Instructeurs Certifi\xE9s</h3><p class="text-equestrian-brown"> Tous nos instructeurs sont dipl\xF4m\xE9s d&#39;\xC9tat et poss\xE8dent une exp\xE9rience reconnue en \xE9quitation </p></div><div class="text-center bg-white rounded-xl p-8 shadow-lg hover:shadow-xl transition-shadow duration-300"><div class="w-20 h-20 bg-gradient-to-br from-equestrian-leather to-equestrian-gold rounded-full flex items-center justify-center mx-auto mb-6">`);
      _push(ssrRenderComponent(unref(CalendarIcon), { class: "w-10 h-10 text-white" }, null, _parent));
      _push(`</div><h3 class="text-xl font-semibold mb-4 text-equestrian-darkBrown">R\xE9servation Flexible</h3><p class="text-equestrian-brown"> R\xE9servez vos cours selon vos disponibilit\xE9s, en carri\xE8re, en man\xE8ge ou en ext\xE9rieur </p></div><div class="text-center bg-white rounded-xl p-8 shadow-lg hover:shadow-xl transition-shadow duration-300"><div class="w-20 h-20 bg-gradient-to-br from-equestrian-leather to-equestrian-gold rounded-full flex items-center justify-center mx-auto mb-6">`);
      _push(ssrRenderComponent(_component_EquestrianIcon, {
        name: "helmet",
        size: 40,
        class: "text-white"
      }, null, _parent));
      _push(`</div><h3 class="text-xl font-semibold mb-4 text-equestrian-darkBrown">S\xE9curit\xE9 Garantie</h3><p class="text-equestrian-brown"> \xC9quipements certifi\xE9s, chevaux bien dress\xE9s et encadrement professionnel pour votre s\xE9curit\xE9 </p></div></div></div></section><section class="py-24 bg-gradient-to-r from-equestrian-brown to-equestrian-leather text-white"><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"><div class="text-center mb-12"><h2 class="text-3xl font-bold mb-4 font-serif">Notre Communaut\xE9 \xC9questre</h2>`);
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
      _push(` Instructeurs Certifi\xE9s </div></div><div class="bg-white/10 backdrop-blur-sm rounded-lg p-6"><div class="text-4xl font-bold text-equestrian-gold mb-2">${ssrInterpolate(unref(platformStats).students)}+</div><div class="text-equestrian-cream">Cavaliers Satisfaits</div></div><div class="bg-white/10 backdrop-blur-sm rounded-lg p-6"><div class="text-4xl font-bold text-equestrian-gold mb-2">${ssrInterpolate(unref(platformStats).lessons)}+</div><div class="text-equestrian-cream">Cours Dispens\xE9s</div></div><div class="bg-white/10 backdrop-blur-sm rounded-lg p-6"><div class="text-4xl font-bold text-equestrian-gold mb-2">${ssrInterpolate(unref(platformStats).locations)}+</div><div class="text-equestrian-cream flex items-center justify-center gap-2">`);
      _push(ssrRenderComponent(_component_EquestrianIcon, {
        name: "saddle",
        size: 20
      }, null, _parent));
      _push(` Centres \xC9questres </div></div></div></div></section><section class="py-24 bg-equestrian-forest text-white relative overflow-hidden"><div class="absolute inset-0 bg-horse-pattern opacity-5"></div><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">`);
      _push(ssrRenderComponent(_component_EquestrianIcon, {
        name: "jump",
        size: 80,
        class: "text-equestrian-gold mx-auto mb-8"
      }, null, _parent));
      _push(`<h2 class="text-3xl md:text-4xl font-bold mb-6 font-serif"> Pr\xEAt \xE0 Galoper vers l&#39;Excellence ? </h2><p class="text-xl mb-8 text-green-100 max-w-2xl mx-auto"> Rejoignez notre communaut\xE9 de passionn\xE9s d&#39;\xE9quitation et d\xE9couvrez le plaisir d&#39;apprendre avec les meilleurs instructeurs </p><div class="flex flex-col sm:flex-row gap-4 justify-center">`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/register",
        class: "btn-primary bg-equestrian-gold text-equestrian-darkBrown hover:bg-equestrian-cream hover:shadow-lg transform hover:scale-105 transition-all duration-200 font-semibold text-lg px-8 py-4"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u{1F3C6} S&#39;inscrire Gratuitement `);
          } else {
            return [
              createTextVNode(" \u{1F3C6} S'inscrire Gratuitement ")
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
            _push2(` \u{1F4D6} En Savoir Plus `);
          } else {
            return [
              createTextVNode(" \u{1F4D6} En Savoir Plus ")
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
//# sourceMappingURL=index-BJpoVmP5.mjs.map
