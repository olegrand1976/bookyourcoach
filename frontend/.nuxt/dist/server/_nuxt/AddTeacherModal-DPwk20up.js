import { ref, mergeProps, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrRenderAttr, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual, ssrRenderList, ssrRenderClass, ssrInterpolate } from "vue/server-renderer";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/hookable/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/klona/dist/index.mjs";
const _sfc_main = {
  __name: "AddTeacherModal",
  __ssrInlineRender: true,
  emits: ["close", "success"],
  setup(__props, { emit: __emit }) {
    const loading = ref(false);
    const form = ref({
      name: "",
      email: "",
      phone: "",
      specializations: [],
      experience_years: 0,
      hourly_rate: 50,
      bio: "",
      contract_type: "freelance"
      // Valeur par défaut
    });
    const availableSpecializations = ref([
      { value: "dressage", label: "Dressage", description: "Équitation classique", icon: "🏇" },
      { value: "obstacle", label: "Obstacle", description: "Saut d'obstacles", icon: "🏆" },
      { value: "cross", label: "Cross", description: "Cross-country", icon: "🌲" },
      { value: "complet", label: "Complet", description: "Concours complet", icon: "🎯" },
      { value: "voltige", label: "Voltige", description: "Voltige équestre", icon: "🤸" },
      { value: "pony", label: "Poney", description: "Cours poney", icon: "🐴" }
    ]);
    const clubSpecializations = ref([]);
    ref(false);
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4" }, _attrs))}><div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-hidden"><div class="bg-gradient-to-r from-blue-500 to-indigo-600 px-6 py-4"><div class="flex items-center justify-between"><div class="flex items-center space-x-3"><div class="bg-white bg-opacity-20 p-2 rounded-lg"><svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path></svg></div><div><h3 class="text-xl font-bold text-white">Ajouter un nouvel enseignant</h3><p class="text-blue-100 text-sm">Remplissez les informations ci-dessous</p></div></div><button class="text-white hover:text-blue-200 transition-colors p-2 hover:bg-white hover:bg-opacity-20 rounded-lg"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button></div></div><div class="overflow-y-auto max-h-[calc(90vh-120px)]"><form class="p-6 space-y-8"><div class="bg-gray-50 rounded-xl p-6"><div class="flex items-center mb-4"><div class="bg-blue-100 p-2 rounded-lg mr-3"><svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg></div><h4 class="text-lg font-semibold text-gray-900">Informations personnelles</h4></div><div class="grid grid-cols-1 md:grid-cols-2 gap-6"><div class="space-y-2"><label class="block text-sm font-medium text-gray-700"> Nom complet <span class="text-red-500">*</span></label><input${ssrRenderAttr("value", form.value.name)} type="text" required placeholder="Ex: Marie Dubois" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"></div><div class="space-y-2"><label class="block text-sm font-medium text-gray-700"> Email <span class="text-red-500">*</span></label><input${ssrRenderAttr("value", form.value.email)} type="email" required placeholder="Ex: marie.dubois@email.com" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"></div><div class="space-y-2"><label class="block text-sm font-medium text-gray-700">Téléphone</label><input${ssrRenderAttr("value", form.value.phone)} type="tel" placeholder="Ex: 06 12 34 56 78" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"></div><div class="space-y-2"><label class="block text-sm font-medium text-gray-700">Années d&#39;expérience</label><input${ssrRenderAttr("value", form.value.experience_years)} type="number" min="0" placeholder="Ex: 5" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"></div><div class="space-y-2"><label class="block text-sm font-medium text-gray-700"> Type de contrat <span class="text-red-500">*</span></label><select required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"><option value="freelance"${ssrIncludeBooleanAttr(Array.isArray(form.value.contract_type) ? ssrLooseContain(form.value.contract_type, "freelance") : ssrLooseEqual(form.value.contract_type, "freelance")) ? " selected" : ""}>Indépendant</option><option value="salaried"${ssrIncludeBooleanAttr(Array.isArray(form.value.contract_type) ? ssrLooseContain(form.value.contract_type, "salaried") : ssrLooseEqual(form.value.contract_type, "salaried")) ? " selected" : ""}>Salarié</option><option value="volunteer"${ssrIncludeBooleanAttr(Array.isArray(form.value.contract_type) ? ssrLooseContain(form.value.contract_type, "volunteer") : ssrLooseEqual(form.value.contract_type, "volunteer")) ? " selected" : ""}>Bénévole</option><option value="student"${ssrIncludeBooleanAttr(Array.isArray(form.value.contract_type) ? ssrLooseContain(form.value.contract_type, "student") : ssrLooseEqual(form.value.contract_type, "student")) ? " selected" : ""}>Étudiant</option><option value="article_17"${ssrIncludeBooleanAttr(Array.isArray(form.value.contract_type) ? ssrLooseContain(form.value.contract_type, "article_17") : ssrLooseEqual(form.value.contract_type, "article_17")) ? " selected" : ""}>Article 17</option></select></div></div></div><div class="bg-purple-50 rounded-xl p-6"><div class="flex items-center mb-4"><div class="bg-purple-100 p-2 rounded-lg mr-3"><svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg></div><div><h4 class="text-lg font-semibold text-gray-900">Spécialisations</h4><p class="text-sm text-gray-600">`);
      if (clubSpecializations.value.length > 0) {
        _push(`<span> Spécialisations disponibles du club (non sélectionnées par défaut) </span>`);
      } else {
        _push(`<span> Sélectionnez les spécialisations de l&#39;enseignant </span>`);
      }
      _push(`</p></div></div>`);
      if (clubSpecializations.value.length > 0) {
        _push(`<div class="mb-6"><h5 class="text-sm font-medium text-gray-700 mb-3 flex items-center"><span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs mr-2">Club</span> Spécialisations du club </h5><div class="grid grid-cols-2 md:grid-cols-3 gap-4"><!--[-->`);
        ssrRenderList(clubSpecializations.value, (specialization) => {
          _push(`<div class="${ssrRenderClass([form.value.specializations.includes(specialization.value) ? "border-blue-500 bg-blue-50 shadow-md" : "border-gray-200 bg-white hover:border-gray-300", "flex items-center p-4 border-2 rounded-xl cursor-pointer transition-all duration-200 hover:shadow-md"])}"><input${ssrRenderAttr("id", "club-specialization-" + specialization.value)}${ssrIncludeBooleanAttr(Array.isArray(form.value.specializations) ? ssrLooseContain(form.value.specializations, specialization.value) : form.value.specializations) ? " checked" : ""}${ssrRenderAttr("value", specialization.value)} type="checkbox" class="h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"><label${ssrRenderAttr("for", "club-specialization-" + specialization.value)} class="ml-4 flex items-center cursor-pointer flex-1"><span class="text-2xl mr-3">${ssrInterpolate(specialization.icon)}</span><div><div class="font-medium text-gray-900">${ssrInterpolate(specialization.label)}</div><div class="text-sm text-gray-500">${ssrInterpolate(specialization.description)}</div></div></label></div>`);
        });
        _push(`<!--]--></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div><h5 class="text-sm font-medium text-gray-700 mb-3 flex items-center"><span class="bg-gray-100 text-gray-800 px-2 py-1 rounded-full text-xs mr-2">Toutes</span> Autres spécialisations disponibles </h5><div class="grid grid-cols-2 md:grid-cols-3 gap-4"><!--[-->`);
      ssrRenderList(availableSpecializations.value, (specialization) => {
        _push(`<div class="${ssrRenderClass([form.value.specializations.includes(specialization.value) ? "border-blue-500 bg-blue-50 shadow-md" : "border-gray-200 bg-white hover:border-gray-300", "flex items-center p-4 border-2 rounded-xl cursor-pointer transition-all duration-200 hover:shadow-md"])}"><input${ssrRenderAttr("id", "specialization-" + specialization.value)}${ssrIncludeBooleanAttr(Array.isArray(form.value.specializations) ? ssrLooseContain(form.value.specializations, specialization.value) : form.value.specializations) ? " checked" : ""}${ssrRenderAttr("value", specialization.value)} type="checkbox" class="h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"><label${ssrRenderAttr("for", "specialization-" + specialization.value)} class="ml-4 flex items-center cursor-pointer flex-1"><span class="text-2xl mr-3">${ssrInterpolate(specialization.icon)}</span><div><div class="font-medium text-gray-900">${ssrInterpolate(specialization.label)}</div><div class="text-sm text-gray-500">${ssrInterpolate(specialization.description)}</div></div></label></div>`);
      });
      _push(`<!--]--></div></div>`);
      if (form.value.specializations.length === 0) {
        _push(`<div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg"><div class="flex items-center"><svg class="w-5 h-5 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path></svg><p class="text-sm text-yellow-800"> Aucune spécialisation sélectionnée. L&#39;enseignant pourra enseigner toutes les disciplines. </p></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="bg-emerald-50 rounded-xl p-6"><div class="flex items-center mb-4"><div class="bg-emerald-100 p-2 rounded-lg mr-3"><svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path></svg></div><h4 class="text-lg font-semibold text-gray-900">Tarifs et présentation</h4></div><div class="grid grid-cols-1 md:grid-cols-2 gap-6"><div class="space-y-2"><label class="block text-sm font-medium text-gray-700">Tarif horaire (€)</label><div class="relative"><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><span class="text-gray-500 sm:text-sm">€</span></div><input${ssrRenderAttr("value", form.value.hourly_rate)} type="number" min="0" step="0.01" placeholder="50.00" class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"></div></div><div class="space-y-2"><label class="block text-sm font-medium text-gray-700">Bio / Présentation</label><textarea rows="4" placeholder="Décrivez votre expérience et votre approche pédagogique..." class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors resize-none">${ssrInterpolate(form.value.bio)}</textarea></div></div></div><div class="flex justify-end space-x-4 pt-6 border-t border-gray-200"><button type="button" class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors font-medium"> Annuler </button><button type="submit"${ssrIncludeBooleanAttr(loading.value) ? " disabled" : ""} class="px-6 py-3 bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-lg hover:from-blue-600 hover:to-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 font-medium flex items-center space-x-2">`);
      if (loading.value) {
        _push(`<svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<span>${ssrInterpolate(loading.value ? "Ajout en cours..." : "Ajouter l'enseignant")}</span></button></div></form></div></div></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AddTeacherModal.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as _
};
//# sourceMappingURL=AddTeacherModal-DPwk20up.js.map
