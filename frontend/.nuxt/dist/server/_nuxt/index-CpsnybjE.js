import { u as useSettings, _ as _sfc_main$1 } from "./Logo-Bv7gA69-.js";
import { _ as __nuxt_component_0 } from "./nuxt-link-BC-lyQ5x.js";
import { _ as __nuxt_component_2 } from "./EquestrianIcon-DypLCVJ6.js";
import { ref, watchEffect, computed, unref, withCtx, createTextVNode, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate } from "vue/server-renderer";
import { CalendarIcon } from "@heroicons/vue/24/outline";
import { u as useAuthStore } from "./auth-yP0r1OGC.js";
import { n as navigateTo, u as useHead } from "../server.mjs";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/ufo/dist/index.mjs";
import "./_plugin-vue_export-helper-1tPrXgE0.js";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/nuxt/node_modules/cookie-es/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/h3/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/destr/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/ohash/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/klona/dist/index.mjs";
import "./ssr-B4FXEZKR.js";
import "ofetch";
import "#internal/nuxt/paths";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/hookable/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/unctx/dist/index.mjs";
import "vue-router";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/radix3/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/defu/dist/defu.mjs";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/@unhead/vue/dist/index.mjs";
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
      title: computed(() => `${settings.settings.platform_name} - Trouvez votre instructeur équestre idéal`),
      meta: [
        {
          name: "description",
          content: "Plateforme de réservation de cours équestres avec des instructeurs certifiés. Réservez facilement vos leçons d'équitation en ligne. Dressage, obstacle, cross-country."
        },
        {
          name: "keywords",
          content: "équitation, cours équestre, instructeur, cheval, dressage, obstacle, centre équestre, réservation"
        }
      ]
    });
    return (_ctx, _push, _parent, _attrs) => {
      const _component_Logo = _sfc_main$1;
      const _component_NuxtLink = __nuxt_component_0;
      const _component_EquestrianIcon = __nuxt_component_2;
      _push(`<div${ssrRenderAttrs(_attrs)}><section class="bg-stable-gradient text-white relative overflow-hidden"><div class="absolute inset-0 bg-horse-pattern opacity-10"></div><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 relative z-10"><div class="text-center"><div class="flex justify-center mb-8"><div class="bg-white/10 backdrop-blur-sm p-6 rounded-xl">`);
      _push(ssrRenderComponent(_component_Logo, { size: "lg" }, null, _parent));
      _push(`</div></div><h1 class="text-4xl md:text-6xl font-bold mb-6 font-serif">${ssrInterpolate(unref(settings).settings.platform_name)}</h1><p class="text-xl md:text-2xl mb-8 text-equestrian-cream"> Trouvez votre instructeur équestre idéal et réservez vos cours d&#39;équitation </p><div class="flex flex-col sm:flex-row gap-4 justify-center">`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/register",
        class: "btn-primary bg-equestrian-cream text-equestrian-darkBrown hover:bg-white hover:shadow-lg transform hover:scale-105 transition-all duration-200 font-semibold"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` 🏇 Commencer l&#39;aventure `);
          } else {
            return [
              createTextVNode(" 🏇 Commencer l'aventure ")
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
            _push2(` 🐎 Découvrir les coaches `);
          } else {
            return [
              createTextVNode(" 🐎 Découvrir les coaches ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></div></div></section><section class="py-24 bg-equestrian-cream"><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"><div class="text-center mb-16"><h2 class="text-3xl md:text-4xl font-bold text-equestrian-darkBrown mb-4 font-serif"> Pourquoi choisir ${ssrInterpolate(unref(settings).settings.platform_name)} ? </h2><p class="text-xl text-equestrian-brown max-w-3xl mx-auto"> La plateforme de référence pour l&#39;équitation moderne </p></div><div class="grid grid-cols-1 md:grid-cols-3 gap-8"><div class="text-center bg-white rounded-xl p-8 shadow-lg hover:shadow-xl transition-shadow duration-300"><div class="w-20 h-20 bg-gradient-to-br from-equestrian-leather to-equestrian-gold rounded-full flex items-center justify-center mx-auto mb-6">`);
      _push(ssrRenderComponent(_component_EquestrianIcon, {
        name: "trophy",
        size: 40,
        class: "text-white"
      }, null, _parent));
      _push(`</div><h3 class="text-xl font-semibold mb-4 text-equestrian-darkBrown">Instructeurs Certifiés</h3><p class="text-equestrian-brown"> Tous nos instructeurs sont diplômés d&#39;État et possèdent une expérience reconnue en équitation </p></div><div class="text-center bg-white rounded-xl p-8 shadow-lg hover:shadow-xl transition-shadow duration-300"><div class="w-20 h-20 bg-gradient-to-br from-equestrian-leather to-equestrian-gold rounded-full flex items-center justify-center mx-auto mb-6">`);
      _push(ssrRenderComponent(unref(CalendarIcon), { class: "w-10 h-10 text-white" }, null, _parent));
      _push(`</div><h3 class="text-xl font-semibold mb-4 text-equestrian-darkBrown">Réservation Flexible</h3><p class="text-equestrian-brown"> Réservez vos cours selon vos disponibilités, en carrière, en manège ou en extérieur </p></div><div class="text-center bg-white rounded-xl p-8 shadow-lg hover:shadow-xl transition-shadow duration-300"><div class="w-20 h-20 bg-gradient-to-br from-equestrian-leather to-equestrian-gold rounded-full flex items-center justify-center mx-auto mb-6">`);
      _push(ssrRenderComponent(_component_EquestrianIcon, {
        name: "helmet",
        size: 40,
        class: "text-white"
      }, null, _parent));
      _push(`</div><h3 class="text-xl font-semibold mb-4 text-equestrian-darkBrown">Sécurité Garantie</h3><p class="text-equestrian-brown"> Équipements certifiés, chevaux bien dressés et encadrement professionnel pour votre sécurité </p></div></div></div></section><section class="py-24 bg-gradient-to-r from-equestrian-brown to-equestrian-leather text-white"><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"><div class="text-center mb-12"><h2 class="text-3xl font-bold mb-4 font-serif">Notre Communauté Équestre</h2>`);
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
      _push(` Instructeurs Certifiés </div></div><div class="bg-white/10 backdrop-blur-sm rounded-lg p-6"><div class="text-4xl font-bold text-equestrian-gold mb-2">${ssrInterpolate(unref(platformStats).students)}+</div><div class="text-equestrian-cream">Cavaliers Satisfaits</div></div><div class="bg-white/10 backdrop-blur-sm rounded-lg p-6"><div class="text-4xl font-bold text-equestrian-gold mb-2">${ssrInterpolate(unref(platformStats).lessons)}+</div><div class="text-equestrian-cream">Cours Dispensés</div></div><div class="bg-white/10 backdrop-blur-sm rounded-lg p-6"><div class="text-4xl font-bold text-equestrian-gold mb-2">${ssrInterpolate(unref(platformStats).locations)}+</div><div class="text-equestrian-cream flex items-center justify-center gap-2">`);
      _push(ssrRenderComponent(_component_EquestrianIcon, {
        name: "saddle",
        size: 20
      }, null, _parent));
      _push(` Centres Équestres </div></div></div></div></section><section class="py-24 bg-equestrian-forest text-white relative overflow-hidden"><div class="absolute inset-0 bg-horse-pattern opacity-5"></div><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">`);
      _push(ssrRenderComponent(_component_EquestrianIcon, {
        name: "jump",
        size: 80,
        class: "text-equestrian-gold mx-auto mb-8"
      }, null, _parent));
      _push(`<h2 class="text-3xl md:text-4xl font-bold mb-6 font-serif"> Prêt à Galoper vers l&#39;Excellence ? </h2><p class="text-xl mb-8 text-green-100 max-w-2xl mx-auto"> Rejoignez notre communauté de passionnés d&#39;équitation et découvrez le plaisir d&#39;apprendre avec les meilleurs instructeurs </p><div class="flex flex-col sm:flex-row gap-4 justify-center">`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/register",
        class: "btn-primary bg-equestrian-gold text-equestrian-darkBrown hover:bg-equestrian-cream hover:shadow-lg transform hover:scale-105 transition-all duration-200 font-semibold text-lg px-8 py-4"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` 🏆 S&#39;inscrire Gratuitement `);
          } else {
            return [
              createTextVNode(" 🏆 S'inscrire Gratuitement ")
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
            _push2(` 📖 En Savoir Plus `);
          } else {
            return [
              createTextVNode(" 📖 En Savoir Plus ")
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
export {
  _sfc_main as default
};
//# sourceMappingURL=index-CpsnybjE.js.map
