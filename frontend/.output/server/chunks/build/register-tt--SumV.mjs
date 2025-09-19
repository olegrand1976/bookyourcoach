import { _ as __nuxt_component_0 } from './nuxt-link-3RFQ5DMr.mjs';
import { ref, reactive, watchEffect, mergeProps, withCtx, createTextVNode, unref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderAttr, ssrRenderList, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual } from 'vue/server-renderer';
import { a as useAuthStore, n as navigateTo } from './server.mjs';
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
  __name: "register",
  __ssrInlineRender: true,
  setup(__props) {
    const authStore = useAuthStore();
    const selectedProfile = ref(null);
    const loading = ref(false);
    const errors = ref([]);
    const specialties = ref([
      "\xC9quitation",
      "Natation",
      "Dressage",
      "Saut d'obstacles",
      "Cross-country",
      "Voltige",
      "Pony-games",
      "Aquagym",
      "Aquabike",
      "Nage libre"
    ]);
    const form = reactive({
      first_name: "",
      last_name: "",
      email: "",
      phone: "",
      birth_date: "",
      street: "",
      street_number: "",
      postal_code: "",
      city: "",
      country: "Belgium",
      password: "",
      password_confirmation: "",
      terms: false,
      // Champs spécifiques
      club_name: "",
      club_description: "",
      specialties: [],
      experience_years: ""
    });
    const getProfileLabel = (profile) => {
      const labels = {
        student: "\xC9l\xE8ve",
        teacher: "Enseignant",
        club: "Club"
      };
      return labels[profile] || "";
    };
    watchEffect(() => {
      if (authStore.isAuthenticated) {
        navigateTo("/dashboard");
      }
    });
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-50" }, _attrs))}><div class="flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8"><div class="max-w-md w-full space-y-8"><div><h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900"> Inscription </h2><p class="mt-2 text-center text-sm text-gray-600"> D\xE9j\xE0 un compte ? `);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/login",
        class: "font-medium text-primary-600 hover:text-primary-500"
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
      _push(`</p></div>`);
      if (!unref(selectedProfile)) {
        _push(`<div class="space-y-4"><h3 class="text-lg font-medium text-gray-900 text-center">Choisissez votre profil</h3><div class="grid grid-cols-1 gap-4"><button class="p-4 border-2 border-gray-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-colors text-left"><div class="flex items-center space-x-3"><span class="text-2xl">\u{1F393}</span><div><h4 class="font-medium text-gray-900">\xC9l\xE8ve</h4><p class="text-sm text-gray-600">R\xE9servez des cours d&#39;\xE9quitation et de natation</p></div></div></button><button class="p-4 border-2 border-gray-200 rounded-lg hover:border-green-500 hover:bg-green-50 transition-colors text-left"><div class="flex items-center space-x-3"><span class="text-2xl">\u{1F468}\u200D\u{1F3EB}</span><div><h4 class="font-medium text-gray-900">Enseignant</h4><p class="text-sm text-gray-600">Proposez vos services et g\xE9rez vos cours</p></div></div></button><button class="p-4 border-2 border-gray-200 rounded-lg hover:border-purple-500 hover:bg-purple-50 transition-colors text-left"><div class="flex items-center space-x-3"><span class="text-2xl">\u{1F3E2}</span><div><h4 class="font-medium text-gray-900">Club</h4><p class="text-sm text-gray-600">G\xE9rez votre centre \xE9questre ou piscine</p></div></div></button></div></div>`);
      } else {
        _push(`<form class="mt-8 space-y-6"><div class="space-y-4"><div class="flex items-center justify-between"><h3 class="text-lg font-medium text-gray-900"> Inscription ${ssrInterpolate(getProfileLabel(unref(selectedProfile)))}</h3><button type="button" class="text-sm text-gray-500 hover:text-gray-700"> \u2190 Changer de profil </button></div><div class="grid grid-cols-1 md:grid-cols-2 gap-4"><div><label for="first_name" class="block text-sm font-medium text-gray-700"> Pr\xE9nom * </label><input id="first_name"${ssrRenderAttr("value", unref(form).first_name)} name="first_name" type="text" required class="input-field" placeholder="Pr\xE9nom"></div><div><label for="last_name" class="block text-sm font-medium text-gray-700"> Nom * </label><input id="last_name"${ssrRenderAttr("value", unref(form).last_name)} name="last_name" type="text" required class="input-field" placeholder="Nom"></div></div><div><label for="email" class="block text-sm font-medium text-gray-700"> Adresse email * </label><input id="email"${ssrRenderAttr("value", unref(form).email)} name="email" type="email" autocomplete="email" required class="input-field" placeholder="Adresse email"></div><div><label for="phone" class="block text-sm font-medium text-gray-700"> T\xE9l\xE9phone </label><input id="phone"${ssrRenderAttr("value", unref(form).phone)} name="phone" type="tel" class="input-field" placeholder="T\xE9l\xE9phone"></div><div><label for="birth_date" class="block text-sm font-medium text-gray-700"> Date de naissance </label><input id="birth_date"${ssrRenderAttr("value", unref(form).birth_date)} name="birth_date" type="date" class="input-field"></div>`);
        if (unref(selectedProfile) === "club") {
          _push(`<div class="space-y-4"><div><label for="club_name" class="block text-sm font-medium text-gray-700"> Nom du club * </label><input id="club_name"${ssrRenderAttr("value", unref(form).club_name)} name="club_name" type="text" required class="input-field" placeholder="Nom du club"></div><div><label for="club_description" class="block text-sm font-medium text-gray-700"> Description du club </label><textarea id="club_description" name="club_description" rows="3" class="input-field" placeholder="D\xE9crivez votre club...">${ssrInterpolate(unref(form).club_description)}</textarea></div></div>`);
        } else {
          _push(`<!---->`);
        }
        if (unref(selectedProfile) === "teacher") {
          _push(`<div class="space-y-4"><div><label for="specialties" class="block text-sm font-medium text-gray-700"> Sp\xE9cialit\xE9s </label><div class="space-y-2"><!--[-->`);
          ssrRenderList(unref(specialties), (specialty) => {
            _push(`<label class="flex items-center"><input${ssrIncludeBooleanAttr(Array.isArray(unref(form).specialties) ? ssrLooseContain(unref(form).specialties, specialty) : unref(form).specialties) ? " checked" : ""}${ssrRenderAttr("value", specialty)} type="checkbox" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"><span class="ml-2 text-sm text-gray-700">${ssrInterpolate(specialty)}</span></label>`);
          });
          _push(`<!--]--></div></div><div><label for="experience_years" class="block text-sm font-medium text-gray-700"> Ann\xE9es d&#39;exp\xE9rience </label><input id="experience_years"${ssrRenderAttr("value", unref(form).experience_years)} name="experience_years" type="number" min="0" class="input-field" placeholder="Nombre d&#39;ann\xE9es"></div></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<div class="space-y-4"><h4 class="text-md font-medium text-gray-700">Adresse</h4><div class="grid grid-cols-1 md:grid-cols-3 gap-4"><div class="md:col-span-2"><label for="street" class="block text-sm text-gray-600 mb-1">Rue</label><input id="street"${ssrRenderAttr("value", unref(form).street)} name="street" type="text" class="input-field" placeholder="Nom de la rue"></div><div><label for="street_number" class="block text-sm text-gray-600 mb-1">Num\xE9ro</label><input id="street_number"${ssrRenderAttr("value", unref(form).street_number)} name="street_number" type="text" class="input-field" placeholder="92, 92/A, 92B..."></div></div><div class="grid grid-cols-1 md:grid-cols-3 gap-4"><div><label for="postal_code" class="block text-sm text-gray-600 mb-1">Code postal</label><input id="postal_code"${ssrRenderAttr("value", unref(form).postal_code)} name="postal_code" type="text" class="input-field" placeholder="1000"></div><div><label for="city" class="block text-sm text-gray-600 mb-1">Ville</label><input id="city"${ssrRenderAttr("value", unref(form).city)} name="city" type="text" class="input-field" placeholder="Bruxelles"></div><div><label for="country" class="block text-sm text-gray-600 mb-1">Pays</label><select id="country" name="country" class="input-field"><option value="Belgium"${ssrIncludeBooleanAttr(Array.isArray(unref(form).country) ? ssrLooseContain(unref(form).country, "Belgium") : ssrLooseEqual(unref(form).country, "Belgium")) ? " selected" : ""}>Belgique</option><option value="France"${ssrIncludeBooleanAttr(Array.isArray(unref(form).country) ? ssrLooseContain(unref(form).country, "France") : ssrLooseEqual(unref(form).country, "France")) ? " selected" : ""}>France</option><option value="Netherlands"${ssrIncludeBooleanAttr(Array.isArray(unref(form).country) ? ssrLooseContain(unref(form).country, "Netherlands") : ssrLooseEqual(unref(form).country, "Netherlands")) ? " selected" : ""}>Pays-Bas</option><option value="Germany"${ssrIncludeBooleanAttr(Array.isArray(unref(form).country) ? ssrLooseContain(unref(form).country, "Germany") : ssrLooseEqual(unref(form).country, "Germany")) ? " selected" : ""}>Allemagne</option><option value="Luxembourg"${ssrIncludeBooleanAttr(Array.isArray(unref(form).country) ? ssrLooseContain(unref(form).country, "Luxembourg") : ssrLooseEqual(unref(form).country, "Luxembourg")) ? " selected" : ""}>Luxembourg</option><option value="Switzerland"${ssrIncludeBooleanAttr(Array.isArray(unref(form).country) ? ssrLooseContain(unref(form).country, "Switzerland") : ssrLooseEqual(unref(form).country, "Switzerland")) ? " selected" : ""}>Suisse</option></select></div></div></div><div class="grid grid-cols-1 md:grid-cols-2 gap-4"><div><label for="password" class="block text-sm font-medium text-gray-700"> Mot de passe * </label><input id="password"${ssrRenderAttr("value", unref(form).password)} name="password" type="password" required class="input-field" placeholder="Mot de passe"></div><div><label for="password_confirmation" class="block text-sm font-medium text-gray-700"> Confirmer le mot de passe * </label><input id="password_confirmation"${ssrRenderAttr("value", unref(form).password_confirmation)} name="password_confirmation" type="password" required class="input-field" placeholder="Confirmer le mot de passe"></div></div></div><div class="flex items-start"><input id="terms"${ssrIncludeBooleanAttr(Array.isArray(unref(form).terms) ? ssrLooseContain(unref(form).terms, null) : unref(form).terms) ? " checked" : ""} name="terms" type="checkbox" required class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded mt-1"><label for="terms" class="ml-2 block text-sm text-gray-900"> J&#39;accepte les <a href="#" class="text-primary-600 hover:text-primary-500">conditions d&#39;utilisation</a> et la <a href="#" class="text-primary-600 hover:text-primary-500">politique de confidentialit\xE9</a></label></div>`);
        if (unref(selectedProfile) === "club") {
          _push(`<div class="bg-yellow-50 border border-yellow-200 rounded-md p-4"><div class="flex"><div class="flex-shrink-0"><span class="text-yellow-400">\u26A0\uFE0F</span></div><div class="ml-3"><h3 class="text-sm font-medium text-yellow-800"> Validation requise </h3><div class="mt-2 text-sm text-yellow-700"><p>Votre inscription sera soumise \xE0 validation par un administrateur. Vous recevrez un email de confirmation une fois approuv\xE9e.</p></div></div></div></div>`);
        } else {
          _push(`<!---->`);
        }
        if (unref(selectedProfile) === "teacher") {
          _push(`<div class="bg-blue-50 border border-blue-200 rounded-md p-4"><div class="flex"><div class="flex-shrink-0"><span class="text-blue-400">\u2139\uFE0F</span></div><div class="ml-3"><h3 class="text-sm font-medium text-blue-800"> Validation par un club </h3><div class="mt-2 text-sm text-blue-700"><p>Votre inscription sera soumise \xE0 validation par un club partenaire. Vous pourrez ensuite proposer vos services.</p></div></div></div></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<div><button type="submit"${ssrIncludeBooleanAttr(unref(loading)) ? " disabled" : ""} class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50">`);
        if (unref(loading)) {
          _push(`<span>Cr\xE9ation du compte...</span>`);
        } else {
          _push(`<span>S&#39;inscrire comme ${ssrInterpolate(getProfileLabel(unref(selectedProfile)))}</span>`);
        }
        _push(`</button></div>`);
        if (unref(errors).length > 0) {
          _push(`<div class="bg-red-50 border border-red-200 rounded-md p-4"><div class="text-sm text-red-600"><ul class="list-disc list-inside space-y-1"><!--[-->`);
          ssrRenderList(unref(errors), (error) => {
            _push(`<li>${ssrInterpolate(error)}</li>`);
          });
          _push(`<!--]--></ul></div></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</form>`);
      }
      _push(`</div></div></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/register.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=register-tt--SumV.mjs.map
