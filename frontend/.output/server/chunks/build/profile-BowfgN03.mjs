import { ref, mergeProps, computed, watch, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderAttr, ssrInterpolate, ssrRenderList, ssrRenderClass, ssrIncludeBooleanAttr, ssrLooseContain, ssrRenderComponent, ssrLooseEqual } from 'vue/server-renderer';
import { a as useAuthStore, u as useHead, j as useRuntimeConfig } from './server.mjs';
import _ from 'lodash';
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

const _sfc_main$2 = {
  __name: "AddCustomSpecialtyForm",
  __ssrInlineRender: true,
  props: {
    availableActivities: {
      type: Array,
      default: () => []
    }
  },
  emits: ["cancel", "success"],
  setup(__props, { emit: __emit }) {
    const isSaving = ref(false);
    const form = ref({});
    const skillLevels = [
      { value: "debutant", label: "\u{1F331} D\xE9butant" },
      { value: "intermediaire", label: "\u{1F4C8} Interm\xE9diaire" },
      { value: "avance", label: "\u2B50 Avanc\xE9" },
      { value: "expert", label: "\u{1F3C6} Expert" }
    ];
    const isFormValid = computed(() => {
      return form.value.activity_id && form.value.name;
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white rounded-2xl shadow-lg border border-gray-200 w-full mt-4" }, _attrs))}><div class="bg-gradient-to-r from-purple-500 to-pink-600 px-6 py-4 rounded-t-xl"><div class="flex items-center justify-between"><div class="flex items-center space-x-3"><div class="bg-white bg-opacity-20 p-2 rounded-lg"><svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg></div><div><h3 class="text-xl font-bold text-white">Ajouter une sp\xE9cialit\xE9 personnalis\xE9e</h3><p class="text-purple-100 text-sm">Cr\xE9ez une sp\xE9cialit\xE9 unique pour votre club</p></div></div></div></div><form class="p-6 space-y-8"><div class="bg-gray-50 rounded-xl p-6"><div class="flex items-center mb-4"><div class="bg-purple-100 p-2 rounded-lg mr-3"><svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></div><h4 class="text-lg font-semibold text-gray-900">Informations de base</h4></div><div class="space-y-6"><div class="space-y-2"><label class="block text-sm font-medium text-gray-700"> Activit\xE9 <span class="text-red-500">*</span></label><select required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors"><option value=""${ssrIncludeBooleanAttr(Array.isArray(form.value.activity_id) ? ssrLooseContain(form.value.activity_id, "") : ssrLooseEqual(form.value.activity_id, "")) ? " selected" : ""}>S\xE9lectionnez une activit\xE9</option><!--[-->`);
      ssrRenderList(__props.availableActivities, (activity) => {
        _push(`<option${ssrRenderAttr("value", activity.id)}${ssrIncludeBooleanAttr(Array.isArray(form.value.activity_id) ? ssrLooseContain(form.value.activity_id, activity.id) : ssrLooseEqual(form.value.activity_id, activity.id)) ? " selected" : ""}>${ssrInterpolate(activity.icon)} ${ssrInterpolate(activity.name)}</option>`);
      });
      _push(`<!--]--></select></div><div class="space-y-2"><label class="block text-sm font-medium text-gray-700"> Nom de la sp\xE9cialit\xE9 <span class="text-red-500">*</span></label><input${ssrRenderAttr("value", form.value.name)} type="text" required placeholder="Ex: Cours particuliers, Initiation poney..." class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors"></div><div class="space-y-2"><label class="block text-sm font-medium text-gray-700"> Description </label><textarea rows="3" placeholder="D\xE9crivez cette sp\xE9cialit\xE9..." class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors resize-none">${ssrInterpolate(form.value.description)}</textarea></div></div></div><div class="bg-gray-50 rounded-xl p-6"><div class="flex items-center mb-4"><div class="bg-pink-100 p-2 rounded-lg mr-3"><svg class="w-5 h-5 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></div><h4 class="text-lg font-semibold text-gray-900">Configuration</h4></div><div class="grid grid-cols-1 md:grid-cols-2 gap-6"><div class="space-y-2"><label class="block text-sm font-medium text-gray-700"> Dur\xE9e (minutes) </label><input${ssrRenderAttr("value", form.value.duration_minutes)} type="number" min="15" max="180" step="15" placeholder="60" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors"></div><div class="space-y-2"><label class="block text-sm font-medium text-gray-700"> Prix de base (\u20AC) </label><input${ssrRenderAttr("value", form.value.base_price)} type="number" min="0" step="0.01" placeholder="25.00" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors"></div></div></div><div class="bg-gray-50 rounded-xl p-6"><div class="flex items-center mb-4"><div class="bg-blue-100 p-2 rounded-lg mr-3"><svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg></div><h4 class="text-lg font-semibold text-gray-900">Niveaux et Participants</h4></div><div class="space-y-6"><div class="space-y-3"><label class="block text-sm font-medium text-gray-700"> Niveaux propos\xE9s </label><div class="grid grid-cols-2 gap-3"><!--[-->`);
      ssrRenderList(skillLevels, (level) => {
        _push(`<label class="flex items-center p-3 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors"><input${ssrIncludeBooleanAttr(Array.isArray(form.value.skill_levels) ? ssrLooseContain(form.value.skill_levels, level.value) : form.value.skill_levels) ? " checked" : ""}${ssrRenderAttr("value", level.value)} type="checkbox" class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded mr-3"><span class="text-sm text-gray-700">${ssrInterpolate(level.label)}</span></label>`);
      });
      _push(`<!--]--></div></div><div class="grid grid-cols-1 md:grid-cols-2 gap-6"><div class="space-y-2"><label class="block text-sm font-medium text-gray-700"> Participants minimum </label><input${ssrRenderAttr("value", form.value.min_participants)} type="number" min="1" placeholder="1" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors"></div><div class="space-y-2"><label class="block text-sm font-medium text-gray-700"> Participants maximum </label><input${ssrRenderAttr("value", form.value.max_participants)} type="number" min="1" placeholder="8" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors"></div></div></div></div><div class="bg-gray-50 rounded-xl p-6"><div class="flex items-center mb-4"><div class="bg-green-100 p-2 rounded-lg mr-3"><svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></div><h4 class="text-lg font-semibold text-gray-900">\xC9quipement requis</h4></div><div class="space-y-3"><!--[-->`);
      ssrRenderList(form.value.equipment_required, (equipment, index) => {
        _push(`<div class="flex items-center space-x-3"><input${ssrRenderAttr("value", form.value.equipment_required[index])} type="text" placeholder="Ex: Casque, Bottes..." class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors"><button type="button"${ssrIncludeBooleanAttr(form.value.equipment_required.length <= 1) ? " disabled" : ""} class="p-3 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button></div>`);
      });
      _push(`<!--]--><button type="button" class="flex items-center px-4 py-2 text-purple-600 hover:text-purple-800 hover:bg-purple-50 rounded-lg transition-colors text-sm"><svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg> Ajouter un \xE9quipement </button></div></div><div class="bg-gray-50 px-6 py-4 border-t border-gray-200 rounded-b-xl"><div class="flex items-center justify-end space-x-4"><button type="button"${ssrIncludeBooleanAttr(isSaving.value) ? " disabled" : ""} class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-purple-500 transition-colors disabled:opacity-50"> Annuler </button><button type="submit"${ssrIncludeBooleanAttr(!isFormValid.value || isSaving.value) ? " disabled" : ""} class="${ssrRenderClass([
        "px-8 py-3 rounded-lg font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-purple-500",
        isFormValid.value && !isSaving.value ? "bg-gradient-to-r from-purple-500 to-pink-600 text-white hover:from-purple-600 hover:to-pink-700" : "bg-gray-300 text-gray-500 cursor-not-allowed"
      ])}">`);
      if (isSaving.value) {
        _push(`<span class="flex items-center space-x-2"><div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div><span>Ajout...</span></span>`);
      } else {
        _push(`<span>Ajouter la sp\xE9cialit\xE9</span>`);
      }
      _push(`</button></div></div></form></div>`);
    };
  }
};
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AddCustomSpecialtyForm.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const _sfc_main$1 = {
  __name: "EditCustomSpecialtyForm",
  __ssrInlineRender: true,
  props: {
    specialty: {
      type: Object,
      required: true
    },
    availableActivities: {
      type: Array,
      default: () => []
    }
  },
  emits: ["cancel", "success"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const isSaving = ref(false);
    const form = ref({});
    const skillLevels = [
      { value: "debutant", label: "\u{1F331} D\xE9butant" },
      { value: "intermediaire", label: "\u{1F4C8} Interm\xE9diaire" },
      { value: "avance", label: "\u2B50 Avanc\xE9" },
      { value: "expert", label: "\u{1F3C6} Expert" }
    ];
    const isFormValid = computed(() => {
      return form.value.name && form.value.name.trim() !== "";
    });
    const selectedActivityName = computed(() => {
      const activity = props.availableActivities.find((a) => a.id === props.specialty.activity_type_id);
      return activity ? `${activity.icon} ${activity.name}` : "Activit\xE9 inconnue";
    });
    const initializeForm = () => {
      form.value = _.cloneDeep(props.specialty);
    };
    watch(() => props.specialty, () => {
      initializeForm();
    }, { immediate: true, deep: true });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white rounded-2xl shadow-lg border border-gray-200 w-full mt-4" }, _attrs))}><div class="bg-gradient-to-r from-blue-500 to-teal-600 px-6 py-4 rounded-t-xl"><div class="flex items-center justify-between"><div class="flex items-center space-x-3"><div class="bg-white bg-opacity-20 p-2 rounded-lg"><svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg></div><div><h3 class="text-xl font-bold text-white">Modifier la sp\xE9cialit\xE9</h3><p class="text-blue-100 text-sm">Mettez \xE0 jour les informations ci-dessous</p></div></div></div></div><form class="p-6 space-y-8"><div class="bg-gray-50 rounded-xl p-6"><h4 class="text-lg font-semibold text-gray-900 mb-4">Informations de base</h4><div class="space-y-6"><div class="space-y-2"><label class="block text-sm font-medium text-gray-700">Activit\xE9</label><p class="w-full px-4 py-3 bg-gray-100 border border-gray-300 rounded-lg text-gray-500">${ssrInterpolate(selectedActivityName.value)}</p></div><div class="space-y-2"><label class="block text-sm font-medium text-gray-700"> Nom de la sp\xE9cialit\xE9 <span class="text-red-500">*</span></label><input${ssrRenderAttr("value", form.value.name)} type="text" required class="w-full px-4 py-3 border border-gray-300 rounded-lg"></div><div class="space-y-2"><label class="block text-sm font-medium text-gray-700">Description</label><textarea rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg">${ssrInterpolate(form.value.description)}</textarea></div></div></div><div class="bg-gray-50 rounded-xl p-6"><div class="flex items-center mb-4"><div class="bg-pink-100 p-2 rounded-lg mr-3"><svg class="w-5 h-5 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></div><h4 class="text-lg font-semibold text-gray-900">Configuration</h4></div><div class="grid grid-cols-1 md:grid-cols-2 gap-6"><div class="space-y-2"><label class="block text-sm font-medium text-gray-700"> Dur\xE9e (minutes) </label><input${ssrRenderAttr("value", form.value.duration_minutes)} type="number" min="15" max="180" step="15" placeholder="60" class="w-full px-4 py-3 border border-gray-300 rounded-lg"></div><div class="space-y-2"><label class="block text-sm font-medium text-gray-700"> Prix de base (\u20AC) </label><input${ssrRenderAttr("value", form.value.base_price)} type="number" min="0" step="0.01" placeholder="25.00" class="w-full px-4 py-3 border border-gray-300 rounded-lg"></div></div></div><div class="bg-gray-50 rounded-xl p-6"><div class="flex items-center mb-4"><div class="bg-blue-100 p-2 rounded-lg mr-3"><svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg></div><h4 class="text-lg font-semibold text-gray-900">Niveaux et Participants</h4></div><div class="space-y-6"><div class="space-y-3"><label class="block text-sm font-medium text-gray-700"> Niveaux propos\xE9s </label><div class="grid grid-cols-2 gap-3"><!--[-->`);
      ssrRenderList(skillLevels, (level) => {
        _push(`<label class="flex items-center p-3 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer"><input${ssrIncludeBooleanAttr(Array.isArray(form.value.skill_levels) ? ssrLooseContain(form.value.skill_levels, level.value) : form.value.skill_levels) ? " checked" : ""}${ssrRenderAttr("value", level.value)} type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 rounded mr-3"><span class="text-sm text-gray-700">${ssrInterpolate(level.label)}</span></label>`);
      });
      _push(`<!--]--></div></div><div class="grid grid-cols-1 md:grid-cols-2 gap-6"><div class="space-y-2"><label class="block text-sm font-medium text-gray-700"> Participants minimum </label><input${ssrRenderAttr("value", form.value.min_participants)} type="number" min="1" placeholder="1" class="w-full px-4 py-3 border border-gray-300 rounded-lg"></div><div class="space-y-2"><label class="block text-sm font-medium text-gray-700"> Participants maximum </label><input${ssrRenderAttr("value", form.value.max_participants)} type="number" min="1" placeholder="8" class="w-full px-4 py-3 border border-gray-300 rounded-lg"></div></div></div></div><div class="bg-gray-50 rounded-xl p-6"><div class="flex items-center mb-4"><div class="bg-green-100 p-2 rounded-lg mr-3"><svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></div><h4 class="text-lg font-semibold text-gray-900">\xC9quipement requis</h4></div><div class="space-y-3"><!--[-->`);
      ssrRenderList(form.value.equipment_required, (equipment, index) => {
        _push(`<div class="flex items-center space-x-3"><input${ssrRenderAttr("value", form.value.equipment_required[index])} type="text" placeholder="Ex: Casque, Bottes..." class="flex-1 px-4 py-3 border border-gray-300 rounded-lg"><button type="button"${ssrIncludeBooleanAttr(form.value.equipment_required.length <= 1) ? " disabled" : ""} class="p-3 text-red-500 hover:text-red-700 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button></div>`);
      });
      _push(`<!--]--><button type="button" class="flex items-center px-4 py-2 text-blue-600 hover:text-blue-800"><svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg> Ajouter un \xE9quipement </button></div></div><div class="bg-gray-50 px-6 py-4 border-t border-gray-200 rounded-b-xl"><div class="flex items-center justify-end space-x-4"><button type="button"${ssrIncludeBooleanAttr(isSaving.value) ? " disabled" : ""} class="px-6 py-3 border border-gray-300 rounded-lg"> Annuler </button><button type="submit"${ssrIncludeBooleanAttr(!isFormValid.value || isSaving.value) ? " disabled" : ""} class="px-8 py-3 rounded-lg font-medium bg-blue-600 text-white">`);
      if (isSaving.value) {
        _push(`<span>Mise \xE0 jour...</span>`);
      } else {
        _push(`<span>Enregistrer les modifications</span>`);
      }
      _push(`</button></div></div></form></div>`);
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/EditCustomSpecialtyForm.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const _sfc_main = {
  __name: "profile",
  __ssrInlineRender: true,
  setup(__props) {
    useAuthStore();
    const loading = ref(false);
    const form = ref({
      name: "",
      email: "",
      phone: "",
      website: "",
      description: "",
      address: "",
      city: "",
      postal_code: "",
      country: "",
      is_active: true
    });
    const availableActivities = ref([]);
    const availableDisciplines = ref([]);
    const selectedActivities = ref([]);
    const selectedDisciplines = ref([]);
    const customSpecialties = ref([]);
    const selectedCustomSpecialties = ref([]);
    const showAddSpecialtyForm = ref(false);
    const editingSpecialtyId = ref(null);
    const getActivityById = (id) => {
      return availableActivities.value.find((activity) => activity.id === id);
    };
    const getDisciplinesByActivity = (activityId) => {
      return availableDisciplines.value.filter((discipline) => discipline.activity_type_id === activityId);
    };
    const getCustomSpecialtiesByActivity = (activityId) => {
      return customSpecialties.value.filter((specialty) => specialty.activity_type_id === activityId);
    };
    const loadCustomSpecialties = async () => {
      try {
        const config = useRuntimeConfig();
        const response = await $fetch(`${config.public.apiBase}/club/custom-specialties`);
        if (response.success) {
          customSpecialties.value = response.data;
        }
      } catch (error) {
        console.error("Erreur lors du chargement des sp\xE9cialit\xE9s personnalis\xE9es:", error);
      }
    };
    const handleAddSpecialtySuccess = (newSpecialty) => {
      loadCustomSpecialties();
      showAddSpecialtyForm.value = false;
    };
    const handleEditSpecialtySuccess = (updatedSpecialty) => {
      loadCustomSpecialties();
      editingSpecialtyId.value = null;
    };
    useHead({
      title: "Profil du Club | activibe",
      meta: [
        { name: "description", content: "G\xE9rez les informations et activit\xE9s de votre club sur activibe" }
      ]
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-50" }, _attrs))}><div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8"><div class="mb-8"><h1 class="text-3xl font-bold text-gray-900 flex items-center"><span class="text-4xl mr-3">\u{1F3E2}</span> Profil du Club </h1><p class="mt-2 text-gray-600">G\xE9rez les informations et activit\xE9s de votre club</p></div><div class="bg-white shadow-lg rounded-lg border border-gray-200"><form class="space-y-6 p-6"><div class="border-b border-gray-200 pb-6"><h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center"><span class="text-xl mr-2">\u{1F4CB}</span> Informations g\xE9n\xE9rales </h2><div class="grid grid-cols-1 md:grid-cols-2 gap-6"><div><label class="block text-sm font-medium text-gray-700 mb-2">Nom du club</label><input${ssrRenderAttr("value", form.value.name)} type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Nom de votre club" required></div><div><label class="block text-sm font-medium text-gray-700 mb-2">Email de contact</label><input${ssrRenderAttr("value", form.value.email)} type="email" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="contact@votreclub.com" required></div><div><label class="block text-sm font-medium text-gray-700 mb-2">T\xE9l\xE9phone</label><input${ssrRenderAttr("value", form.value.phone)} type="tel" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="+33 1 23 45 67 89"></div><div><label class="block text-sm font-medium text-gray-700 mb-2">Site web</label><input${ssrRenderAttr("value", form.value.website)} type="url" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="https://votreclub.com"></div></div><div class="mt-6"><label class="block text-sm font-medium text-gray-700 mb-2">Description</label><textarea rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="D\xE9crivez votre club, ses valeurs et ses services...">${ssrInterpolate(form.value.description)}</textarea></div></div><div class="border-b border-gray-200 pb-6"><h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center"><span class="text-xl mr-2">\u{1F4CD}</span> Adresse </h2><div class="grid grid-cols-1 md:grid-cols-3 gap-6"><div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-2">Adresse</label><input${ssrRenderAttr("value", form.value.address)} type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="123 Rue de l&#39;\xC9quitation"></div><div><label class="block text-sm font-medium text-gray-700 mb-2">Code postal</label><input${ssrRenderAttr("value", form.value.postal_code)} type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="75001"></div><div><label class="block text-sm font-medium text-gray-700 mb-2">Ville</label><input${ssrRenderAttr("value", form.value.city)} type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Paris"></div><div><label class="block text-sm font-medium text-gray-700 mb-2">Pays</label><input${ssrRenderAttr("value", form.value.country)} type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="France"></div></div></div><div class="border-b border-gray-200 pb-6"><h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center"><span class="text-xl mr-2">\u{1F3C3}\u200D\u2640\uFE0F</span> Activit\xE9s propos\xE9es </h2><div class="mb-4"><p class="text-sm text-gray-600 mb-4">S\xE9lectionnez les activit\xE9s que votre club propose :</p><div class="grid grid-cols-1 md:grid-cols-2 gap-4"><!--[-->`);
      ssrRenderList(availableActivities.value, (activity) => {
        _push(`<div class="${ssrRenderClass([selectedActivities.value.includes(activity.id) ? "border-blue-500 bg-blue-50" : "border-gray-200", "flex items-center p-4 border rounded-lg hover:bg-gray-50 transition-colors"])}"><input${ssrRenderAttr("id", "activity-" + activity.id)}${ssrIncludeBooleanAttr(Array.isArray(selectedActivities.value) ? ssrLooseContain(selectedActivities.value, activity.id) : selectedActivities.value) ? " checked" : ""}${ssrRenderAttr("value", activity.id)} type="checkbox" class="h-4 w-4 text-blue-500 focus:ring-blue-500 border-gray-300 rounded"><label${ssrRenderAttr("for", "activity-" + activity.id)} class="ml-3 flex items-center cursor-pointer"><span class="text-2xl mr-2">${ssrInterpolate(activity.icon)}</span><div><div class="font-medium text-gray-900">${ssrInterpolate(activity.name)}</div><div class="text-sm text-gray-500">${ssrInterpolate(activity.description)}</div></div></label></div>`);
      });
      _push(`<!--]--></div></div>`);
      if (selectedActivities.value.length > 0) {
        _push(`<div class="mt-6"><div class="flex items-center justify-between mb-3"><h3 class="text-md font-medium text-gray-900">Sp\xE9cialit\xE9s par activit\xE9</h3>`);
        if (!showAddSpecialtyForm.value) {
          _push(`<button class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center space-x-2 text-sm"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg><span>Ajouter une sp\xE9cialit\xE9</span></button>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
        if (showAddSpecialtyForm.value) {
          _push(ssrRenderComponent(_sfc_main$2, {
            "available-activities": availableActivities.value,
            onCancel: ($event) => showAddSpecialtyForm.value = false,
            onSuccess: handleAddSpecialtySuccess
          }, null, _parent));
        } else {
          _push(`<!---->`);
        }
        _push(`<!--[-->`);
        ssrRenderList(selectedActivities.value, (activityId) => {
          var _a, _b;
          _push(`<div class="mb-4"><div class="bg-gray-50 p-4 rounded-lg"><h4 class="font-medium text-gray-900 mb-2 flex items-center"><span class="text-lg mr-2">${ssrInterpolate((_a = getActivityById(activityId)) == null ? void 0 : _a.icon)}</span> ${ssrInterpolate((_b = getActivityById(activityId)) == null ? void 0 : _b.name)}</h4><div class="grid grid-cols-2 md:grid-cols-3 gap-2"><!--[-->`);
          ssrRenderList(getDisciplinesByActivity(activityId), (discipline) => {
            _push(`<label class="flex items-center p-2 text-sm"><input${ssrRenderAttr("id", "discipline-" + discipline.id)}${ssrIncludeBooleanAttr(Array.isArray(selectedDisciplines.value) ? ssrLooseContain(selectedDisciplines.value, discipline.id) : selectedDisciplines.value) ? " checked" : ""}${ssrRenderAttr("value", discipline.id)} type="checkbox" class="h-3 w-3 text-blue-500 focus:ring-blue-500 border-gray-300 rounded mr-2"><span class="text-gray-700">${ssrInterpolate(discipline.name)}</span></label>`);
          });
          _push(`<!--]--><!--[-->`);
          ssrRenderList(getCustomSpecialtiesByActivity(activityId), (customSpecialty) => {
            _push(`<!--[-->`);
            if (editingSpecialtyId.value === customSpecialty.id) {
              _push(`<div class="col-span-2 md:col-span-3">`);
              _push(ssrRenderComponent(_sfc_main$1, {
                specialty: customSpecialty,
                "available-activities": availableActivities.value,
                onCancel: ($event) => editingSpecialtyId.value = null,
                onSuccess: handleEditSpecialtySuccess
              }, null, _parent));
              _push(`</div>`);
            } else {
              _push(`<div class="${ssrRenderClass([
                "flex items-center justify-between p-2 text-sm rounded border",
                customSpecialty.is_active ? "bg-blue-50 border-blue-200" : "bg-gray-100 border-gray-200 opacity-60"
              ])}"><div class="flex items-center"><input${ssrRenderAttr("id", "custom-specialty-" + customSpecialty.id)}${ssrIncludeBooleanAttr(Array.isArray(selectedCustomSpecialties.value) ? ssrLooseContain(selectedCustomSpecialties.value, customSpecialty.id) : selectedCustomSpecialties.value) ? " checked" : ""}${ssrRenderAttr("value", customSpecialty.id)} type="checkbox" class="h-3 w-3 text-blue-500 focus:ring-blue-500 border-gray-300 rounded mr-2"><span class="${ssrRenderClass([[customSpecialty.is_active ? "text-gray-700" : "text-gray-500 line-through"], "font-medium"])}">${ssrInterpolate(customSpecialty.name)}</span><span class="text-xs text-blue-600 ml-1">(personnalis\xE9e)</span></div><div class="flex items-center space-x-2"><button type="button" class="p-1 text-gray-500 hover:text-blue-700 hover:bg-blue-100 rounded" title="Modifier"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg></button><button type="button" class="${ssrRenderClass([
                "px-2 py-1 text-xs rounded transition-colors",
                customSpecialty.is_active ? "bg-red-100 text-red-700 hover:bg-red-200" : "bg-green-100 text-green-700 hover:bg-green-200"
              ])}"${ssrRenderAttr("title", customSpecialty.is_active ? "D\xE9sactiver" : "Activer")}>${ssrInterpolate(customSpecialty.is_active ? "D\xE9sactiver" : "Activer")}</button></div></div>`);
            }
            _push(`<!--]-->`);
          });
          _push(`<!--]--></div></div></div>`);
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="pb-6"><h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center"><span class="text-xl mr-2">\u2699\uFE0F</span> Param\xE8tres </h2><div class="flex items-center"><input${ssrIncludeBooleanAttr(Array.isArray(form.value.is_active) ? ssrLooseContain(form.value.is_active, null) : form.value.is_active) ? " checked" : ""} type="checkbox" id="is_active" class="h-4 w-4 text-blue-500 focus:ring-blue-500 border-gray-300 rounded"><label for="is_active" class="ml-2 text-sm text-gray-700"> Club actif (visible sur la plateforme) </label></div></div><div class="flex justify-end space-x-4 pt-6 border-t border-gray-200"><button type="button" class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"> Annuler </button><button type="submit"${ssrIncludeBooleanAttr(loading.value) ? " disabled" : ""} class="px-6 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 flex items-center justify-center w-48">`);
      if (loading.value) {
        _push(`<svg class="animate-spin h-4 w-4 text-white mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<span class="text-center">Enregistrer les modifications</span></button></div></form></div></div></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/club/profile.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=profile-BowfgN03.mjs.map
