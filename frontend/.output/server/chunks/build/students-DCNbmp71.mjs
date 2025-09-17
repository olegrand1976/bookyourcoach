import { _ as _sfc_main$2 } from './AddStudentModal-CCquKf-z.mjs';
import { ref, computed, mergeProps, watch, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrInterpolate, ssrRenderAttr, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual, ssrRenderList, ssrRenderComponent, ssrRenderClass } from 'vue/server-renderer';
import { u as useToast } from './useToast-eUAzhXp6.mjs';
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
  __name: "AddStudentAdvancedModal",
  __ssrInlineRender: true,
  props: {
    isOpen: {
      type: Boolean,
      default: false
    },
    clubId: {
      type: Number,
      required: true
    }
  },
  emits: ["close", "success"],
  setup(__props, { emit: __emit }) {
    const { showToast } = useToast();
    const mode = ref("qr");
    const searchQuery = ref("");
    const searchResults = ref([]);
    const selectedUser = ref(null);
    const isSearching = ref(false);
    const isAdding = ref(false);
    const searchUsers = async () => {
      if (searchQuery.value.length < 2) {
        searchResults.value = [];
        return;
      }
      isSearching.value = true;
      try {
        await new Promise((resolve) => setTimeout(resolve, 500));
        const mockResults = [
          {
            id: 1,
            name: "\xC9tudiant Test 1",
            email: "student1@test.com",
            role: "student",
            phone: "0123456789"
          },
          {
            id: 2,
            name: "\xC9tudiant Test 2",
            email: "student2@test.com",
            role: "student",
            phone: "0987654321"
          }
        ].filter(
          (user) => user.name.toLowerCase().includes(searchQuery.value.toLowerCase()) || user.email.toLowerCase().includes(searchQuery.value.toLowerCase())
        );
        searchResults.value = mockResults;
      } catch (error) {
        showToast("Erreur lors de la recherche", "error");
        searchResults.value = [];
      } finally {
        isSearching.value = false;
      }
    };
    watch(searchQuery, () => {
      if (searchQuery.value.length >= 2) {
        searchUsers();
      } else {
        searchResults.value = [];
      }
    });
    return (_ctx, _push, _parent, _attrs) => {
      if (__props.isOpen) {
        _push(`<div${ssrRenderAttrs(mergeProps({ class: "fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" }, _attrs))}><div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto"><div class="flex items-center justify-between p-6 border-b border-gray-200"><h3 class="text-xl font-semibold text-gray-900"> Ajouter un \xE9tudiant existant </h3><button class="text-gray-400 hover:text-gray-600 transition-colors"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button></div><div class="p-6"><div class="mb-6"><div class="flex space-x-4"><button class="${ssrRenderClass([
          "flex-1 py-3 px-4 rounded-lg border-2 transition-all",
          mode.value === "qr" ? "border-blue-500 bg-blue-50 text-blue-700" : "border-gray-200 text-gray-600 hover:border-gray-300"
        ])}"><div class="flex items-center justify-center space-x-2"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg><span>Scanner QR Code</span></div></button><button class="${ssrRenderClass([
          "flex-1 py-3 px-4 rounded-lg border-2 transition-all",
          mode.value === "search" ? "border-blue-500 bg-blue-50 text-blue-700" : "border-gray-200 text-gray-600 hover:border-gray-300"
        ])}"><div class="flex items-center justify-center space-x-2"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg><span>Recherche manuelle</span></div></button></div></div>`);
        if (mode.value === "qr") {
          _push(`<div class="space-y-4"><div class="text-center"><h4 class="text-lg font-medium text-gray-900 mb-2">Scanner le QR Code de l&#39;\xE9tudiant</h4><p class="text-gray-600 text-sm">Demandez \xE0 l&#39;\xE9tudiant d&#39;afficher son QR Code sur son \xE9cran</p></div><div class="bg-gray-50 rounded-lg p-6 text-center"><div class="mb-4"><svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg></div><p class="text-gray-600 mb-4">Fonctionnalit\xE9 de scan QR Code \xE0 impl\xE9menter</p><button class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors"> Simuler le scan (Test) </button></div></div>`);
        } else {
          _push(`<!---->`);
        }
        if (mode.value === "search") {
          _push(`<div class="space-y-4"><div><label class="block text-sm font-medium text-gray-700 mb-2"> Rechercher un \xE9tudiant </label><div class="relative"><input${ssrRenderAttr("value", searchQuery.value)} type="text" placeholder="Nom, email ou t\xE9l\xE9phone..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">`);
          if (isSearching.value) {
            _push(`<div class="absolute right-3 top-1/2 transform -translate-y-1/2"><div class="animate-spin rounded-full h-5 w-5 border-b-2 border-blue-600"></div></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div></div>`);
          if (searchResults.value.length > 0) {
            _push(`<div class="space-y-2"><h4 class="text-sm font-medium text-gray-700">R\xE9sultats de recherche :</h4><div class="max-h-60 overflow-y-auto space-y-2"><!--[-->`);
            ssrRenderList(searchResults.value, (user) => {
              _push(`<div class="p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors"><div class="flex items-center justify-between"><div><p class="font-medium text-gray-900">${ssrInterpolate(user.name)}</p><p class="text-sm text-gray-600">${ssrInterpolate(user.email)}</p>`);
              if (user.phone) {
                _push(`<p class="text-sm text-gray-500">${ssrInterpolate(user.phone)}</p>`);
              } else {
                _push(`<!---->`);
              }
              _push(`</div><div class="text-sm text-gray-500">${ssrInterpolate(user.role === "student" ? "\xC9tudiant" : user.role)}</div></div></div>`);
            });
            _push(`<!--]--></div></div>`);
          } else {
            _push(`<!---->`);
          }
          if (searchQuery.value && !isSearching.value && searchResults.value.length === 0) {
            _push(`<div class="text-center py-4"><p class="text-gray-500">Aucun \xE9tudiant trouv\xE9</p></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div>`);
        } else {
          _push(`<!---->`);
        }
        if (selectedUser.value) {
          _push(`<div class="mt-6 p-4 bg-blue-50 rounded-lg"><h4 class="font-medium text-blue-900 mb-2">\xC9tudiant s\xE9lectionn\xE9 :</h4><div class="flex items-center justify-between"><div><p class="font-medium text-blue-900">${ssrInterpolate(selectedUser.value.name)}</p><p class="text-sm text-blue-700">${ssrInterpolate(selectedUser.value.email)}</p></div><button class="text-blue-600 hover:text-blue-800"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button></div></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div><div class="flex items-center justify-end space-x-3 p-6 border-t border-gray-200"><button class="px-4 py-2 text-gray-600 hover:text-gray-800 transition-colors"> Annuler </button><button${ssrIncludeBooleanAttr(!selectedUser.value || isAdding.value) ? " disabled" : ""} class="${ssrRenderClass([
          "px-6 py-2 rounded-lg font-medium transition-colors",
          selectedUser.value && !isAdding.value ? "bg-blue-600 text-white hover:bg-blue-700" : "bg-gray-300 text-gray-500 cursor-not-allowed"
        ])}">`);
        if (isAdding.value) {
          _push(`<span class="flex items-center space-x-2"><div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div><span>Ajout en cours...</span></span>`);
        } else {
          _push(`<span>Ajouter au club</span>`);
        }
        _push(`</button></div></div></div>`);
      } else {
        _push(`<!---->`);
      }
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AddStudentAdvancedModal.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const _sfc_main = {
  __name: "students",
  __ssrInlineRender: true,
  setup(__props) {
    const students = ref([]);
    const availableDisciplines = ref([]);
    const showAddStudentModal = ref(false);
    const showAddExistingStudentModal = ref(false);
    const searchQuery = ref("");
    const selectedLevel = ref("");
    const selectedDiscipline = ref("");
    const sortBy = ref("name");
    const activeStudents = computed(() => students.value.length);
    const beginnerStudents = computed(
      () => students.value.filter((student) => student.level === "debutant").length
    );
    const studentsWithDocuments = computed(
      () => students.value.filter((student) => student.medical_documents && student.medical_documents.length > 0).length
    );
    const filteredStudents = computed(() => {
      let filtered = students.value;
      if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(
          (student) => student.name.toLowerCase().includes(query) || student.email.toLowerCase().includes(query)
        );
      }
      if (selectedLevel.value) {
        filtered = filtered.filter((student) => student.level === selectedLevel.value);
      }
      if (selectedDiscipline.value) {
        filtered = filtered.filter(
          (student) => student.disciplines && student.disciplines.some((d) => d.id == selectedDiscipline.value)
        );
      }
      filtered.sort((a, b) => {
        switch (sortBy.value) {
          case "name":
            return a.name.localeCompare(b.name);
          case "name_desc":
            return b.name.localeCompare(a.name);
          case "level":
            const levelOrder = { "debutant": 1, "intermediaire": 2, "avance": 3, "expert": 4 };
            return (levelOrder[a.level] || 0) - (levelOrder[b.level] || 0);
          case "level_desc":
            const levelOrderDesc = { "debutant": 1, "intermediaire": 2, "avance": 3, "expert": 4 };
            return (levelOrderDesc[b.level] || 0) - (levelOrderDesc[a.level] || 0);
          case "created":
            return new Date(b.created_at) - new Date(a.created_at);
          default:
            return 0;
        }
      });
      return filtered;
    });
    const getLevelLabel = (level) => {
      const labels = {
        debutant: "\u{1F331} D\xE9butant",
        intermediaire: "\u{1F4C8} Interm\xE9diaire",
        avance: "\u2B50 Avanc\xE9",
        expert: "\u{1F3C6} Expert"
      };
      return labels[level] || level;
    };
    const getActivityIcon = (activityTypeId) => {
      const icons = {
        1: "\u{1F3C7}",
        // Équitation
        2: "\u{1F3CA}\u200D\u2640\uFE0F",
        // Natation
        3: "\u{1F4AA}",
        // Salle de sport
        4: "\u{1F3C3}\u200D\u2642\uFE0F"
        // Coaching sportif
      };
      return icons[activityTypeId] || "\u{1F3AF}";
    };
    const loadStudents = async () => {
      try {
        const config = useRuntimeConfig();
        const tokenCookie = useCookie("auth-token");
        const response = await $fetch(`${config.public.apiBase}/club/dashboard-test`);
        if (response.success && response.data) {
          students.value = response.data.recentStudents || [];
          if (response.data.club && response.data.club.disciplines) {
            availableDisciplines.value = response.data.club.disciplines;
          }
        }
      } catch (error) {
        console.error("\u274C Erreur lors du chargement des \xE9l\xE8ves:", error);
      }
    };
    return (_ctx, _push, _parent, _attrs) => {
      const _component_AddStudentModal = _sfc_main$2;
      const _component_AddStudentAdvancedModal = _sfc_main$1;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-50" }, _attrs))}><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8"><div class="mb-8"><div class="flex items-center justify-between"><div><h1 class="text-3xl font-bold text-gray-900">\xC9l\xE8ves</h1><p class="mt-2 text-gray-600"> G\xE9rez vos \xE9l\xE8ves et leurs informations </p></div><div class="flex space-x-3"><button class="bg-gradient-to-r from-emerald-500 to-teal-600 text-white px-6 py-3 rounded-lg hover:from-emerald-600 hover:to-teal-700 transition-all duration-200 font-medium flex items-center space-x-2"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg><span>Nouvel \xE9l\xE8ve</span></button><button class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white px-6 py-3 rounded-lg hover:from-blue-600 hover:to-indigo-700 transition-all duration-200 font-medium flex items-center space-x-2"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg><span>\xC9l\xE8ve existant</span></button></div></div></div><div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8"><div class="bg-white rounded-xl shadow p-6"><div class="flex items-center"><div class="p-3 bg-emerald-100 rounded-lg"><svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path></svg></div><div class="ml-4"><p class="text-sm font-medium text-gray-600">Total</p><p class="text-2xl font-semibold text-gray-900">${ssrInterpolate(students.value.length)}</p></div></div></div><div class="bg-white rounded-xl shadow p-6"><div class="flex items-center"><div class="p-3 bg-blue-100 rounded-lg"><svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></div><div class="ml-4"><p class="text-sm font-medium text-gray-600">Actifs</p><p class="text-2xl font-semibold text-gray-900">${ssrInterpolate(activeStudents.value)}</p></div></div></div><div class="bg-white rounded-xl shadow p-6"><div class="flex items-center"><div class="p-3 bg-yellow-100 rounded-lg"><svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg></div><div class="ml-4"><p class="text-sm font-medium text-gray-600">D\xE9butants</p><p class="text-2xl font-semibold text-gray-900">${ssrInterpolate(beginnerStudents.value)}</p></div></div></div><div class="bg-white rounded-xl shadow p-6"><div class="flex items-center"><div class="p-3 bg-purple-100 rounded-lg"><svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg></div><div class="ml-4"><p class="text-sm font-medium text-gray-600">Documents</p><p class="text-2xl font-semibold text-gray-900">${ssrInterpolate(studentsWithDocuments.value)}</p></div></div></div></div><div class="bg-white rounded-xl shadow p-6 mb-8"><div class="grid grid-cols-1 md:grid-cols-4 gap-4"><div><label class="block text-sm font-medium text-gray-700 mb-2">Rechercher</label><div class="relative"><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg></div><input${ssrRenderAttr("value", searchQuery.value)} type="text" placeholder="Nom, email..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"></div></div><div><label class="block text-sm font-medium text-gray-700 mb-2">Niveau</label><select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"><option value=""${ssrIncludeBooleanAttr(Array.isArray(selectedLevel.value) ? ssrLooseContain(selectedLevel.value, "") : ssrLooseEqual(selectedLevel.value, "")) ? " selected" : ""}>Tous les niveaux</option><option value="debutant"${ssrIncludeBooleanAttr(Array.isArray(selectedLevel.value) ? ssrLooseContain(selectedLevel.value, "debutant") : ssrLooseEqual(selectedLevel.value, "debutant")) ? " selected" : ""}>\u{1F331} D\xE9butant</option><option value="intermediaire"${ssrIncludeBooleanAttr(Array.isArray(selectedLevel.value) ? ssrLooseContain(selectedLevel.value, "intermediaire") : ssrLooseEqual(selectedLevel.value, "intermediaire")) ? " selected" : ""}>\u{1F4C8} Interm\xE9diaire</option><option value="avance"${ssrIncludeBooleanAttr(Array.isArray(selectedLevel.value) ? ssrLooseContain(selectedLevel.value, "avance") : ssrLooseEqual(selectedLevel.value, "avance")) ? " selected" : ""}>\u2B50 Avanc\xE9</option><option value="expert"${ssrIncludeBooleanAttr(Array.isArray(selectedLevel.value) ? ssrLooseContain(selectedLevel.value, "expert") : ssrLooseEqual(selectedLevel.value, "expert")) ? " selected" : ""}>\u{1F3C6} Expert</option></select></div><div><label class="block text-sm font-medium text-gray-700 mb-2">Sp\xE9cialit\xE9</label><select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"><option value=""${ssrIncludeBooleanAttr(Array.isArray(selectedDiscipline.value) ? ssrLooseContain(selectedDiscipline.value, "") : ssrLooseEqual(selectedDiscipline.value, "")) ? " selected" : ""}>Toutes les sp\xE9cialit\xE9s</option><!--[-->`);
      ssrRenderList(availableDisciplines.value, (discipline) => {
        _push(`<option${ssrRenderAttr("value", discipline.id)}${ssrIncludeBooleanAttr(Array.isArray(selectedDiscipline.value) ? ssrLooseContain(selectedDiscipline.value, discipline.id) : ssrLooseEqual(selectedDiscipline.value, discipline.id)) ? " selected" : ""}>${ssrInterpolate(getActivityIcon(discipline.activity_type_id))} ${ssrInterpolate(discipline.name)}</option>`);
      });
      _push(`<!--]--></select></div><div><label class="block text-sm font-medium text-gray-700 mb-2">Tri</label><select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"><option value="name"${ssrIncludeBooleanAttr(Array.isArray(sortBy.value) ? ssrLooseContain(sortBy.value, "name") : ssrLooseEqual(sortBy.value, "name")) ? " selected" : ""}>Nom (A-Z)</option><option value="name_desc"${ssrIncludeBooleanAttr(Array.isArray(sortBy.value) ? ssrLooseContain(sortBy.value, "name_desc") : ssrLooseEqual(sortBy.value, "name_desc")) ? " selected" : ""}>Nom (Z-A)</option><option value="level"${ssrIncludeBooleanAttr(Array.isArray(sortBy.value) ? ssrLooseContain(sortBy.value, "level") : ssrLooseEqual(sortBy.value, "level")) ? " selected" : ""}>Niveau (croissant)</option><option value="level_desc"${ssrIncludeBooleanAttr(Array.isArray(sortBy.value) ? ssrLooseContain(sortBy.value, "level_desc") : ssrLooseEqual(sortBy.value, "level_desc")) ? " selected" : ""}>Niveau (d\xE9croissant)</option><option value="created"${ssrIncludeBooleanAttr(Array.isArray(sortBy.value) ? ssrLooseContain(sortBy.value, "created") : ssrLooseEqual(sortBy.value, "created")) ? " selected" : ""}>Date d&#39;inscription</option></select></div></div></div><div class="bg-white rounded-xl shadow overflow-hidden"><div class="px-6 py-4 border-b border-gray-200"><h3 class="text-lg font-medium text-gray-900"> Liste des \xE9l\xE8ves (${ssrInterpolate(filteredStudents.value.length)}) </h3></div>`);
      if (filteredStudents.value.length === 0) {
        _push(`<div class="text-center py-12"><svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path></svg><h3 class="mt-2 text-sm font-medium text-gray-900">Aucun \xE9l\xE8ve</h3><p class="mt-1 text-sm text-gray-500">Commencez par ajouter votre premier \xE9l\xE8ve.</p><div class="mt-6"><button class="bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700 transition-colors"> Ajouter un \xE9l\xE8ve </button></div></div>`);
      } else {
        _push(`<div class="divide-y divide-gray-200"><!--[-->`);
        ssrRenderList(filteredStudents.value, (student) => {
          _push(`<div class="p-6 hover:bg-gray-50 transition-colors"><div class="flex items-center justify-between"><div class="flex items-center space-x-4"><div class="bg-emerald-100 p-3 rounded-full"><svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg></div><div class="flex-1"><div class="flex items-center space-x-3"><h4 class="text-lg font-medium text-gray-900">${ssrInterpolate(student.name)}</h4><span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full"> Actif </span>`);
          if (student.level) {
            _push(`<span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">${ssrInterpolate(getLevelLabel(student.level))}</span>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div><div class="mt-1 flex items-center space-x-4 text-sm text-gray-600"><span class="flex items-center"><svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg> ${ssrInterpolate(student.email)}</span>`);
          if (student.phone) {
            _push(`<span class="flex items-center"><svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg> ${ssrInterpolate(student.phone)}</span>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div>`);
          if (student.disciplines && student.disciplines.length > 0) {
            _push(`<div class="mt-2"><div class="flex flex-wrap gap-2"><!--[-->`);
            ssrRenderList(student.disciplines, (discipline) => {
              _push(`<span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">${ssrInterpolate(getActivityIcon(discipline.activity_type_id))} ${ssrInterpolate(discipline.name)}</span>`);
            });
            _push(`<!--]--></div></div>`);
          } else {
            _push(`<!---->`);
          }
          if (student.goals) {
            _push(`<div class="mt-2 text-sm text-gray-600"><span class="font-medium">Objectifs:</span> ${ssrInterpolate(student.goals.substring(0, 100))}${ssrInterpolate(student.goals.length > 100 ? "..." : "")}</div>`);
          } else {
            _push(`<!---->`);
          }
          if (student.medical_info) {
            _push(`<div class="mt-1 text-sm text-amber-600"><span class="font-medium">\u26A0\uFE0F Infos m\xE9dicales:</span> ${ssrInterpolate(student.medical_info.substring(0, 80))}${ssrInterpolate(student.medical_info.length > 80 ? "..." : "")}</div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div></div><div class="flex items-center space-x-2"><button class="text-blue-600 hover:text-blue-800 p-2 hover:bg-blue-50 rounded-lg transition-colors"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg></button><button class="text-emerald-600 hover:text-emerald-800 p-2 hover:bg-emerald-50 rounded-lg transition-colors"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg></button><button class="text-red-600 hover:text-red-800 p-2 hover:bg-red-50 rounded-lg transition-colors"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button></div></div></div>`);
        });
        _push(`<!--]--></div>`);
      }
      _push(`</div></div>`);
      if (showAddStudentModal.value) {
        _push(ssrRenderComponent(_component_AddStudentModal, {
          onClose: ($event) => showAddStudentModal.value = false,
          onSuccess: loadStudents
        }, null, _parent));
      } else {
        _push(`<!---->`);
      }
      if (showAddExistingStudentModal.value) {
        _push(ssrRenderComponent(_component_AddStudentAdvancedModal, {
          "club-id": 1,
          onClose: ($event) => showAddExistingStudentModal.value = false,
          onSuccess: loadStudents
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/club/students.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=students-DCNbmp71.mjs.map
