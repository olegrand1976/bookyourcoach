import { ref, computed, mergeProps, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrInterpolate, ssrRenderAttr, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual, ssrRenderList, ssrRenderComponent, ssrRenderClass } from 'vue/server-renderer';
import { _ as _sfc_main$2 } from './AddTeacherModal-BKhXm82n.mjs';
import { j as useRuntimeConfig, c as useCookie } from './server.mjs';
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

const _sfc_main$1 = {
  __name: "AddTeacherAdvancedModal",
  __ssrInlineRender: true,
  emits: ["close", "success"],
  setup(__props, { emit: __emit }) {
    const activeTab = ref("qr");
    const loading = ref(false);
    const qrCodeInput = ref("");
    const scannedUser = ref(null);
    const searchQuery = ref("");
    const searchResults = ref([]);
    const isSearching = ref(false);
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4" }, _attrs))}><div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden"><div class="bg-gradient-to-r from-blue-500 to-indigo-600 px-6 py-4"><div class="flex items-center justify-between"><div class="flex items-center space-x-3"><div class="bg-white bg-opacity-20 p-2 rounded-lg"><svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path></svg></div><div><h3 class="text-xl font-bold text-white">Ajouter un enseignant</h3><p class="text-blue-100 text-sm">QR code ou recherche manuelle</p></div></div><button class="text-white hover:text-blue-200 transition-colors p-2 hover:bg-white hover:bg-opacity-20 rounded-lg"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button></div></div><div class="overflow-y-auto max-h-[calc(90vh-120px)]"><div class="p-6"><div class="flex space-x-1 mb-6 bg-gray-100 p-1 rounded-lg"><button class="${ssrRenderClass([activeTab.value === "qr" ? "bg-white shadow-sm text-blue-600" : "text-gray-600 hover:text-gray-800", "flex-1 py-2 px-4 text-sm font-medium rounded-md transition-colors"])}"><div class="flex items-center justify-center space-x-2"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg><span>QR Code</span></div></button><button class="${ssrRenderClass([activeTab.value === "search" ? "bg-white shadow-sm text-blue-600" : "text-gray-600 hover:text-gray-800", "flex-1 py-2 px-4 text-sm font-medium rounded-md transition-colors"])}"><div class="flex items-center justify-center space-x-2"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg><span>Recherche</span></div></button><button class="${ssrRenderClass([activeTab.value === "new" ? "bg-white shadow-sm text-blue-600" : "text-gray-600 hover:text-gray-800", "flex-1 py-2 px-4 text-sm font-medium rounded-md transition-colors"])}"><div class="flex items-center justify-center space-x-2"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg><span>Nouveau</span></div></button></div>`);
      if (activeTab.value === "qr") {
        _push(`<div class="space-y-6"><div class="bg-blue-50 rounded-xl p-6"><div class="flex items-center mb-4"><div class="bg-blue-100 p-2 rounded-lg mr-3"><svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg></div><div><h4 class="text-lg font-semibold text-gray-900">Scanner QR Code</h4><p class="text-sm text-gray-600">Demandez \xE0 l&#39;enseignant de pr\xE9senter son QR code</p></div></div><div class="space-y-4"><div><label class="block text-sm font-medium text-gray-700 mb-2">Code QR</label><input${ssrRenderAttr("value", qrCodeInput.value)} type="text" placeholder="Scannez ou saisissez le code QR" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"></div>`);
        if (scannedUser.value) {
          _push(`<div class="bg-white border border-gray-200 rounded-lg p-4"><div class="flex items-center space-x-3"><div class="bg-blue-100 p-2 rounded-lg"><svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg></div><div class="flex-1"><h5 class="font-medium text-gray-900">${ssrInterpolate(scannedUser.value.name)}</h5><p class="text-sm text-gray-600">${ssrInterpolate(scannedUser.value.email)}</p><p class="text-xs text-blue-600">${ssrInterpolate(scannedUser.value.role)}</p></div><button${ssrIncludeBooleanAttr(loading.value) ? " disabled" : ""} class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors"> Ajouter </button></div></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div></div></div>`);
      } else {
        _push(`<!---->`);
      }
      if (activeTab.value === "search") {
        _push(`<div class="space-y-6"><div class="bg-purple-50 rounded-xl p-6"><div class="flex items-center mb-4"><div class="bg-purple-100 p-2 rounded-lg mr-3"><svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg></div><div><h4 class="text-lg font-semibold text-gray-900">Recherche manuelle</h4><p class="text-sm text-gray-600">Recherchez un enseignant existant</p></div></div><div class="space-y-4"><div><label class="block text-sm font-medium text-gray-700 mb-2">Rechercher</label><div class="relative"><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg></div><input${ssrRenderAttr("value", searchQuery.value)} type="text" placeholder="Nom ou email de l&#39;enseignant" class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"></div></div>`);
        if (searchResults.value.length > 0) {
          _push(`<div class="space-y-2"><h5 class="text-sm font-medium text-gray-700">R\xE9sultats</h5><div class="space-y-2"><!--[-->`);
          ssrRenderList(searchResults.value, (user) => {
            _push(`<div class="bg-white border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors"><div class="flex items-center justify-between"><div class="flex items-center space-x-3"><div class="bg-blue-100 p-2 rounded-lg"><svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg></div><div><h6 class="font-medium text-gray-900">${ssrInterpolate(user.name)}</h6><p class="text-sm text-gray-600">${ssrInterpolate(user.email)}</p><p class="text-xs text-blue-600">${ssrInterpolate(user.role)}</p></div></div><button${ssrIncludeBooleanAttr(loading.value) ? " disabled" : ""} class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors"> Ajouter </button></div></div>`);
          });
          _push(`<!--]--></div></div>`);
        } else {
          _push(`<!---->`);
        }
        if (searchQuery.value && searchResults.value.length === 0 && !isSearching.value) {
          _push(`<div class="text-center py-8 text-gray-500"><svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg><p>Aucun enseignant trouv\xE9</p></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div></div></div>`);
      } else {
        _push(`<!---->`);
      }
      if (activeTab.value === "new") {
        _push(`<div class="space-y-6"><div class="bg-emerald-50 rounded-xl p-6"><div class="flex items-center mb-4"><div class="bg-emerald-100 p-2 rounded-lg mr-3"><svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg></div><div><h4 class="text-lg font-semibold text-gray-900">Cr\xE9er un nouvel enseignant</h4><p class="text-sm text-gray-600">Ajouter un enseignant qui n&#39;existe pas encore</p></div></div><div class="text-center py-8"><svg class="mx-auto h-12 w-12 text-emerald-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg><h5 class="text-lg font-medium text-gray-900 mb-2">Cr\xE9er un nouvel enseignant</h5><p class="text-gray-600 mb-6">Utilisez le formulaire de cr\xE9ation d&#39;enseignant</p><button class="bg-emerald-600 text-white px-6 py-3 rounded-lg hover:bg-emerald-700 transition-colors font-medium"> Ouvrir le formulaire </button></div></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div></div></div>`);
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AddTeacherAdvancedModal.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const _sfc_main = {
  __name: "teachers",
  __ssrInlineRender: true,
  setup(__props) {
    const teachers = ref([]);
    const showAddTeacherModal = ref(false);
    const showNewTeacherModal = ref(false);
    const searchQuery = ref("");
    const selectedSpecialization = ref("");
    const sortBy = ref("name");
    const activeTeachers = computed(() => teachers.value.length);
    const averageRate = computed(() => {
      if (teachers.value.length === 0) return 0;
      const total = teachers.value.reduce((sum, teacher) => sum + (teacher.hourly_rate || 0), 0);
      return Math.round(total / teachers.value.length);
    });
    const averageExperience = computed(() => {
      if (teachers.value.length === 0) return 0;
      const total = teachers.value.reduce((sum, teacher) => sum + (teacher.experience_years || 0), 0);
      return Math.round(total / teachers.value.length);
    });
    const filteredTeachers = computed(() => {
      let filtered = teachers.value;
      if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(
          (teacher) => teacher.name.toLowerCase().includes(query) || teacher.email.toLowerCase().includes(query)
        );
      }
      if (selectedSpecialization.value) {
        filtered = filtered.filter(
          (teacher) => teacher.specializations && teacher.specializations.includes(selectedSpecialization.value)
        );
      }
      filtered.sort((a, b) => {
        switch (sortBy.value) {
          case "name":
            return a.name.localeCompare(b.name);
          case "name_desc":
            return b.name.localeCompare(a.name);
          case "experience":
            return (a.experience_years || 0) - (b.experience_years || 0);
          case "experience_desc":
            return (b.experience_years || 0) - (a.experience_years || 0);
          case "rate":
            return (a.hourly_rate || 0) - (b.hourly_rate || 0);
          case "rate_desc":
            return (b.hourly_rate || 0) - (a.hourly_rate || 0);
          default:
            return 0;
        }
      });
      return filtered;
    });
    const getSpecializationLabel = (spec) => {
      const labels = {
        dressage: "\u{1F3C7} Dressage",
        obstacle: "\u{1F3C6} Obstacle",
        cross: "\u{1F332} Cross",
        complet: "\u{1F3AF} Complet",
        voltige: "\u{1F938} Voltige",
        pony: "\u{1F434} Poney"
      };
      return labels[spec] || spec;
    };
    const loadTeachers = async () => {
      try {
        const config = useRuntimeConfig();
        const tokenCookie = useCookie("auth-token");
        const response = await $fetch(`${config.public.apiBase}/club/dashboard-test`);
        if (response.success && response.data && response.data.recentTeachers) {
          teachers.value = response.data.recentTeachers;
        }
      } catch (error) {
        console.error("\u274C Erreur lors du chargement des enseignants:", error);
      }
    };
    const openNewTeacherForm = () => {
      showNewTeacherModal.value = true;
    };
    return (_ctx, _push, _parent, _attrs) => {
      const _component_AddTeacherAdvancedModal = _sfc_main$1;
      const _component_AddTeacherModal = _sfc_main$2;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-50" }, _attrs))}><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8"><div class="mb-8"><div class="flex items-center justify-between"><div><h1 class="text-3xl font-bold text-gray-900">Enseignants</h1><p class="mt-2 text-gray-600"> G\xE9rez vos enseignants et leurs informations </p></div><div class="flex space-x-3"><button class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white px-6 py-3 rounded-lg hover:from-blue-600 hover:to-indigo-700 transition-all duration-200 font-medium flex items-center space-x-2"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg><span>Nouvel enseignant</span></button><button class="bg-gradient-to-r from-purple-500 to-pink-600 text-white px-6 py-3 rounded-lg hover:from-purple-600 hover:to-pink-700 transition-all duration-200 font-medium flex items-center space-x-2"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg><span>Enseignant existant</span></button></div></div></div><div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8"><div class="bg-white rounded-xl shadow p-6"><div class="flex items-center"><div class="p-3 bg-blue-100 rounded-lg"><svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path></svg></div><div class="ml-4"><p class="text-sm font-medium text-gray-600">Total</p><p class="text-2xl font-semibold text-gray-900">${ssrInterpolate(teachers.value.length)}</p></div></div></div><div class="bg-white rounded-xl shadow p-6"><div class="flex items-center"><div class="p-3 bg-green-100 rounded-lg"><svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></div><div class="ml-4"><p class="text-sm font-medium text-gray-600">Actifs</p><p class="text-2xl font-semibold text-gray-900">${ssrInterpolate(activeTeachers.value)}</p></div></div></div><div class="bg-white rounded-xl shadow p-6"><div class="flex items-center"><div class="p-3 bg-yellow-100 rounded-lg"><svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path></svg></div><div class="ml-4"><p class="text-sm font-medium text-gray-600">Tarif moyen</p><p class="text-2xl font-semibold text-gray-900">${ssrInterpolate(averageRate.value)}\u20AC</p></div></div></div><div class="bg-white rounded-xl shadow p-6"><div class="flex items-center"><div class="p-3 bg-purple-100 rounded-lg"><svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg></div><div class="ml-4"><p class="text-sm font-medium text-gray-600">Exp\xE9rience moy.</p><p class="text-2xl font-semibold text-gray-900">${ssrInterpolate(averageExperience.value)} ans</p></div></div></div></div><div class="bg-white rounded-xl shadow p-6 mb-8"><div class="grid grid-cols-1 md:grid-cols-3 gap-4"><div><label class="block text-sm font-medium text-gray-700 mb-2">Rechercher</label><div class="relative"><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg></div><input${ssrRenderAttr("value", searchQuery.value)} type="text" placeholder="Nom, email..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div></div><div><label class="block text-sm font-medium text-gray-700 mb-2">Sp\xE9cialisation</label><select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"><option value=""${ssrIncludeBooleanAttr(Array.isArray(selectedSpecialization.value) ? ssrLooseContain(selectedSpecialization.value, "") : ssrLooseEqual(selectedSpecialization.value, "")) ? " selected" : ""}>Toutes les sp\xE9cialisations</option><option value="dressage"${ssrIncludeBooleanAttr(Array.isArray(selectedSpecialization.value) ? ssrLooseContain(selectedSpecialization.value, "dressage") : ssrLooseEqual(selectedSpecialization.value, "dressage")) ? " selected" : ""}>\u{1F3C7} Dressage</option><option value="obstacle"${ssrIncludeBooleanAttr(Array.isArray(selectedSpecialization.value) ? ssrLooseContain(selectedSpecialization.value, "obstacle") : ssrLooseEqual(selectedSpecialization.value, "obstacle")) ? " selected" : ""}>\u{1F3C6} Obstacle</option><option value="cross"${ssrIncludeBooleanAttr(Array.isArray(selectedSpecialization.value) ? ssrLooseContain(selectedSpecialization.value, "cross") : ssrLooseEqual(selectedSpecialization.value, "cross")) ? " selected" : ""}>\u{1F332} Cross</option><option value="complet"${ssrIncludeBooleanAttr(Array.isArray(selectedSpecialization.value) ? ssrLooseContain(selectedSpecialization.value, "complet") : ssrLooseEqual(selectedSpecialization.value, "complet")) ? " selected" : ""}>\u{1F3AF} Complet</option><option value="voltige"${ssrIncludeBooleanAttr(Array.isArray(selectedSpecialization.value) ? ssrLooseContain(selectedSpecialization.value, "voltige") : ssrLooseEqual(selectedSpecialization.value, "voltige")) ? " selected" : ""}>\u{1F938} Voltige</option><option value="pony"${ssrIncludeBooleanAttr(Array.isArray(selectedSpecialization.value) ? ssrLooseContain(selectedSpecialization.value, "pony") : ssrLooseEqual(selectedSpecialization.value, "pony")) ? " selected" : ""}>\u{1F434} Poney</option></select></div><div><label class="block text-sm font-medium text-gray-700 mb-2">Tri</label><select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"><option value="name"${ssrIncludeBooleanAttr(Array.isArray(sortBy.value) ? ssrLooseContain(sortBy.value, "name") : ssrLooseEqual(sortBy.value, "name")) ? " selected" : ""}>Nom (A-Z)</option><option value="name_desc"${ssrIncludeBooleanAttr(Array.isArray(sortBy.value) ? ssrLooseContain(sortBy.value, "name_desc") : ssrLooseEqual(sortBy.value, "name_desc")) ? " selected" : ""}>Nom (Z-A)</option><option value="experience"${ssrIncludeBooleanAttr(Array.isArray(sortBy.value) ? ssrLooseContain(sortBy.value, "experience") : ssrLooseEqual(sortBy.value, "experience")) ? " selected" : ""}>Exp\xE9rience (croissant)</option><option value="experience_desc"${ssrIncludeBooleanAttr(Array.isArray(sortBy.value) ? ssrLooseContain(sortBy.value, "experience_desc") : ssrLooseEqual(sortBy.value, "experience_desc")) ? " selected" : ""}>Exp\xE9rience (d\xE9croissant)</option><option value="rate"${ssrIncludeBooleanAttr(Array.isArray(sortBy.value) ? ssrLooseContain(sortBy.value, "rate") : ssrLooseEqual(sortBy.value, "rate")) ? " selected" : ""}>Tarif (croissant)</option><option value="rate_desc"${ssrIncludeBooleanAttr(Array.isArray(sortBy.value) ? ssrLooseContain(sortBy.value, "rate_desc") : ssrLooseEqual(sortBy.value, "rate_desc")) ? " selected" : ""}>Tarif (d\xE9croissant)</option></select></div></div></div><div class="bg-white rounded-xl shadow overflow-hidden"><div class="px-6 py-4 border-b border-gray-200"><h3 class="text-lg font-medium text-gray-900"> Liste des enseignants (${ssrInterpolate(filteredTeachers.value.length)}) </h3></div>`);
      if (filteredTeachers.value.length === 0) {
        _push(`<div class="text-center py-12"><svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path></svg><h3 class="mt-2 text-sm font-medium text-gray-900">Aucun enseignant</h3><p class="mt-1 text-sm text-gray-500">Commencez par ajouter votre premier enseignant.</p><div class="mt-6"><button class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors"> Ajouter un enseignant </button></div></div>`);
      } else {
        _push(`<div class="divide-y divide-gray-200"><!--[-->`);
        ssrRenderList(filteredTeachers.value, (teacher) => {
          _push(`<div class="p-6 hover:bg-gray-50 transition-colors"><div class="flex items-center justify-between"><div class="flex items-center space-x-4"><div class="bg-blue-100 p-3 rounded-full"><svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg></div><div class="flex-1"><div class="flex items-center space-x-3"><h4 class="text-lg font-medium text-gray-900">${ssrInterpolate(teacher.name)}</h4><span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full"> Actif </span></div><div class="mt-1 flex items-center space-x-4 text-sm text-gray-600"><span class="flex items-center"><svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg> ${ssrInterpolate(teacher.email)}</span>`);
          if (teacher.phone) {
            _push(`<span class="flex items-center"><svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg> ${ssrInterpolate(teacher.phone)}</span>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div><div class="mt-2 flex items-center space-x-4 text-sm"><span class="flex items-center text-blue-600"><svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path></svg> ${ssrInterpolate(teacher.hourly_rate)}\u20AC/h </span><span class="flex items-center text-purple-600"><svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg> ${ssrInterpolate(teacher.experience_years)} ans d&#39;exp\xE9rience </span></div>`);
          if (teacher.specializations && teacher.specializations.length > 0) {
            _push(`<div class="mt-2"><div class="flex flex-wrap gap-2"><!--[-->`);
            ssrRenderList(teacher.specializations, (spec) => {
              _push(`<span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">${ssrInterpolate(getSpecializationLabel(spec))}</span>`);
            });
            _push(`<!--]--></div></div>`);
          } else {
            _push(`<!---->`);
          }
          if (teacher.bio) {
            _push(`<div class="mt-2 text-sm text-gray-600">${ssrInterpolate(teacher.bio.substring(0, 150))}${ssrInterpolate(teacher.bio.length > 150 ? "..." : "")}</div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div></div><div class="flex items-center space-x-2"><button class="text-blue-600 hover:text-blue-800 p-2 hover:bg-blue-50 rounded-lg transition-colors"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg></button><button class="text-red-600 hover:text-red-800 p-2 hover:bg-red-50 rounded-lg transition-colors"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button></div></div></div>`);
        });
        _push(`<!--]--></div>`);
      }
      _push(`</div></div>`);
      if (showAddTeacherModal.value) {
        _push(ssrRenderComponent(_component_AddTeacherAdvancedModal, {
          onClose: ($event) => showAddTeacherModal.value = false,
          onSuccess: loadTeachers,
          onOpenNewTeacher: openNewTeacherForm
        }, null, _parent));
      } else {
        _push(`<!---->`);
      }
      if (showNewTeacherModal.value) {
        _push(ssrRenderComponent(_component_AddTeacherModal, {
          onClose: ($event) => showNewTeacherModal.value = false,
          onSuccess: loadTeachers
        }, null, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/club/teachers.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=teachers-sL3uatUs.mjs.map
