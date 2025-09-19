import { _ as __nuxt_component_0 } from './nuxt-link-3RFQ5DMr.mjs';
import { ref, mergeProps, withCtx, createTextVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderAttr, ssrRenderList, ssrRenderClass, ssrInterpolate, ssrIncludeBooleanAttr, ssrLooseContain, ssrRenderComponent } from 'vue/server-renderer';
import { p as publicAssetsURL } from '../routes/renderer.mjs';
import '../nitro/nitro.mjs';
import 'node:http';
import 'node:https';
import 'node:events';
import 'node:buffer';
import 'node:fs';
import 'node:path';
import 'node:crypto';
import 'node:url';
import './server.mjs';
import 'vue-router';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/utils';
import 'unhead/plugins';

const _imports_0 = publicAssetsURL("/images/logo-activibe.svg");
const _sfc_main = {
  __name: "register-teacher",
  __ssrInlineRender: true,
  setup(__props) {
    const loading = ref(false);
    const form = ref({
      name: "",
      email: "",
      password: "",
      password_confirmation: "",
      phone: "",
      specializations: [],
      // Aucune spécialisation par défaut pour l'auto-inscription
      experience_years: 0,
      hourly_rate: 50,
      bio: ""
    });
    const availableSpecializations = ref([
      { value: "dressage", label: "Dressage", description: "\xC9quitation classique", icon: "\u{1F3C7}" },
      { value: "obstacle", label: "Obstacle", description: "Saut d'obstacles", icon: "\u{1F3C6}" },
      { value: "cross", label: "Cross", description: "Cross-country", icon: "\u{1F332}" },
      { value: "complet", label: "Complet", description: "Concours complet", icon: "\u{1F3AF}" },
      { value: "voltige", label: "Voltige", description: "Voltige \xE9questre", icon: "\u{1F938}" },
      { value: "pony", label: "Poney", description: "Cours poney", icon: "\u{1F434}" }
    ]);
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8" }, _attrs))}><div class="sm:mx-auto sm:w-full sm:max-w-md"><div class="flex justify-center"><img${ssrRenderAttr("src", _imports_0)} alt="Acti&#39;Vibe" class="h-16 w-auto"></div><h2 class="mt-6 text-center text-3xl font-bold text-gray-900"> Inscription Enseignant </h2><p class="mt-2 text-center text-sm text-gray-600"> Rejoignez notre plateforme en tant qu&#39;enseignant </p></div><div class="mt-8 sm:mx-auto sm:w-full sm:max-w-2xl"><div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10"><form class="space-y-6"><div class="bg-gray-50 rounded-xl p-6"><div class="flex items-center mb-4"><div class="bg-blue-100 p-2 rounded-lg mr-3"><svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg></div><h3 class="text-lg font-semibold text-gray-900">Informations personnelles</h3></div><div class="grid grid-cols-1 md:grid-cols-2 gap-6"><div class="space-y-2"><label class="block text-sm font-medium text-gray-700"> Nom complet <span class="text-red-500">*</span></label><input${ssrRenderAttr("value", form.value.name)} type="text" required placeholder="Ex: Marie Dubois" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"></div><div class="space-y-2"><label class="block text-sm font-medium text-gray-700"> Email <span class="text-red-500">*</span></label><input${ssrRenderAttr("value", form.value.email)} type="email" required placeholder="Ex: marie.dubois@email.com" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"></div><div class="space-y-2"><label class="block text-sm font-medium text-gray-700"> Mot de passe <span class="text-red-500">*</span></label><input${ssrRenderAttr("value", form.value.password)} type="password" required placeholder="Minimum 8 caract\xE8res" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"></div><div class="space-y-2"><label class="block text-sm font-medium text-gray-700"> Confirmer le mot de passe <span class="text-red-500">*</span></label><input${ssrRenderAttr("value", form.value.password_confirmation)} type="password" required placeholder="R\xE9p\xE9tez le mot de passe" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"></div><div class="space-y-2"><label class="block text-sm font-medium text-gray-700">T\xE9l\xE9phone</label><input${ssrRenderAttr("value", form.value.phone)} type="tel" placeholder="Ex: 06 12 34 56 78" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"></div><div class="space-y-2"><label class="block text-sm font-medium text-gray-700">Ann\xE9es d&#39;exp\xE9rience</label><input${ssrRenderAttr("value", form.value.experience_years)} type="number" min="0" placeholder="Ex: 5" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"></div></div></div><div class="bg-purple-50 rounded-xl p-6"><div class="flex items-center mb-4"><div class="bg-purple-100 p-2 rounded-lg mr-3"><svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg></div><div><h3 class="text-lg font-semibold text-gray-900">Sp\xE9cialisations</h3><p class="text-sm text-gray-600"> S\xE9lectionnez vos domaines d&#39;expertise (optionnel) </p></div></div><div class="grid grid-cols-2 md:grid-cols-3 gap-4"><!--[-->`);
      ssrRenderList(availableSpecializations.value, (specialization) => {
        _push(`<div class="${ssrRenderClass([form.value.specializations.includes(specialization.value) ? "border-blue-500 bg-blue-50 shadow-md" : "border-gray-200 bg-white hover:border-gray-300", "flex items-center p-4 border-2 rounded-xl cursor-pointer transition-all duration-200 hover:shadow-md"])}"><input${ssrRenderAttr("id", "specialization-" + specialization.value)}${ssrIncludeBooleanAttr(Array.isArray(form.value.specializations) ? ssrLooseContain(form.value.specializations, specialization.value) : form.value.specializations) ? " checked" : ""}${ssrRenderAttr("value", specialization.value)} type="checkbox" class="h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"><label${ssrRenderAttr("for", "specialization-" + specialization.value)} class="ml-4 flex items-center cursor-pointer flex-1"><span class="text-2xl mr-3">${ssrInterpolate(specialization.icon)}</span><div><div class="font-medium text-gray-900">${ssrInterpolate(specialization.label)}</div><div class="text-sm text-gray-500">${ssrInterpolate(specialization.description)}</div></div></label></div>`);
      });
      _push(`<!--]--></div>`);
      if (form.value.specializations.length === 0) {
        _push(`<div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg"><div class="flex items-center"><svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg><p class="text-sm text-blue-800"> Aucune sp\xE9cialisation s\xE9lectionn\xE9e. Vous pourrez les ajouter plus tard dans votre profil. </p></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="bg-emerald-50 rounded-xl p-6"><div class="flex items-center mb-4"><div class="bg-emerald-100 p-2 rounded-lg mr-3"><svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path></svg></div><h3 class="text-lg font-semibold text-gray-900">Tarifs et pr\xE9sentation</h3></div><div class="grid grid-cols-1 md:grid-cols-2 gap-6"><div class="space-y-2"><label class="block text-sm font-medium text-gray-700">Tarif horaire (\u20AC)</label><div class="relative"><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><span class="text-gray-500 sm:text-sm">\u20AC</span></div><input${ssrRenderAttr("value", form.value.hourly_rate)} type="number" min="0" step="0.01" placeholder="50.00" class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"></div></div><div class="space-y-2"><label class="block text-sm font-medium text-gray-700">Bio / Pr\xE9sentation</label><textarea rows="4" placeholder="D\xE9crivez votre exp\xE9rience et votre approche p\xE9dagogique..." class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors resize-none">${ssrInterpolate(form.value.bio)}</textarea></div></div></div><div class="flex justify-between items-center pt-6 border-t border-gray-200">`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/login",
        class: "text-gray-600 hover:text-gray-800 font-medium"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u2190 Retour \xE0 la connexion `);
          } else {
            return [
              createTextVNode(" \u2190 Retour \xE0 la connexion ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`<button type="submit"${ssrIncludeBooleanAttr(loading.value || form.value.password !== form.value.password_confirmation) ? " disabled" : ""} class="px-6 py-3 bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-lg hover:from-blue-600 hover:to-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 font-medium flex items-center space-x-2">`);
      if (loading.value) {
        _push(`<svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<span>${ssrInterpolate(loading.value ? "Inscription en cours..." : "S'inscrire")}</span></button></div></form></div></div></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/register-teacher.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=register-teacher-CurMsEEr.mjs.map
