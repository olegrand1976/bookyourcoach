import { defineComponent, ref, computed, mergeProps, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual, ssrRenderList, ssrRenderAttr, ssrInterpolate, ssrRenderClass } from "vue/server-renderer";
import "/workspace/frontend/node_modules/hookable/dist/index.mjs";
import { _ as _export_sfc } from "./_plugin-vue_export-helper-1tPrXgE0.js";
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "lessons",
  __ssrInlineRender: true,
  setup(__props) {
    const lessons2 = ref([]);
    const disciplines = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const filters = ref({
      discipline: "",
      courseType: "",
      format: "",
      date: ""
    });
    const filteredCourseTypes = computed(() => {
      if (!filters.value.discipline) return [];
      const discipline = disciplines.value.find((d) => d.id === parseInt(filters.value.discipline));
      return (discipline == null ? void 0 : discipline.course_types) || [];
    });
    const filteredLessons = computed(() => {
      let filtered = lessons2.value;
      if (filters.value.discipline) {
        filtered = filtered.filter(
          (lesson) => {
            var _a;
            return ((_a = lesson.course_type) == null ? void 0 : _a.discipline_id) === parseInt(filters.value.discipline);
          }
        );
      }
      if (filters.value.courseType) {
        filtered = filtered.filter(
          (lesson) => {
            var _a;
            return ((_a = lesson.course_type) == null ? void 0 : _a.id) === parseInt(filters.value.courseType);
          }
        );
      }
      if (filters.value.format) {
        filtered = filtered.filter((lesson) => {
          var _a, _b;
          if (filters.value.format === "individual") {
            return ((_a = lesson.course_type) == null ? void 0 : _a.is_individual) === true;
          } else if (filters.value.format === "group") {
            return ((_b = lesson.course_type) == null ? void 0 : _b.is_individual) === false;
          }
          return true;
        });
      }
      if (filters.value.date) {
        const filterDate = new Date(filters.value.date);
        filtered = filtered.filter((lesson) => {
          const lessonDate = new Date(lesson.start_time);
          return lessonDate.toDateString() === filterDate.toDateString();
        });
      }
      return filtered;
    });
    const getStatusClass = (status) => {
      switch (status) {
        case "available":
          return "bg-green-100 text-green-800";
        case "pending":
          return "bg-yellow-100 text-yellow-800";
        case "confirmed":
          return "bg-blue-100 text-blue-800";
        case "completed":
          return "bg-gray-100 text-gray-800";
        case "cancelled":
          return "bg-red-100 text-red-800";
        default:
          return "bg-gray-100 text-gray-800";
      }
    };
    const getStatusText = (status) => {
      switch (status) {
        case "available":
          return "Disponible";
        case "pending":
          return "En attente";
        case "confirmed":
          return "Confirmé";
        case "completed":
          return "Terminé";
        case "cancelled":
          return "Annulé";
        default:
          return status;
      }
    };
    const formatDateTime = (dateString) => {
      const date = new Date(dateString);
      return date.toLocaleDateString("fr-FR", {
        weekday: "short",
        day: "numeric",
        month: "short",
        hour: "2-digit",
        minute: "2-digit"
      });
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "lessons-page" }, _attrs))} data-v-8d87c433><div class="container mx-auto px-4 py-8" data-v-8d87c433><div class="mb-8" data-v-8d87c433><h1 class="text-3xl font-bold text-gray-900 mb-2" data-v-8d87c433> Leçons Disponibles </h1><p class="text-gray-600" data-v-8d87c433> Découvrez et réservez les cours qui vous intéressent </p></div><div class="bg-white rounded-lg shadow-md p-6 mb-8" data-v-8d87c433><h2 class="text-lg font-semibold text-gray-900 mb-4" data-v-8d87c433>Filtres</h2><div class="grid grid-cols-1 md:grid-cols-4 gap-4" data-v-8d87c433><div data-v-8d87c433><label class="block text-sm font-medium text-gray-700 mb-2" data-v-8d87c433>Discipline</label><select class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" data-v-8d87c433><option value="" data-v-8d87c433${ssrIncludeBooleanAttr(Array.isArray(filters.value.discipline) ? ssrLooseContain(filters.value.discipline, "") : ssrLooseEqual(filters.value.discipline, "")) ? " selected" : ""}>Toutes les disciplines</option><!--[-->`);
      ssrRenderList(disciplines.value, (discipline) => {
        _push(`<option${ssrRenderAttr("value", discipline.id)} data-v-8d87c433${ssrIncludeBooleanAttr(Array.isArray(filters.value.discipline) ? ssrLooseContain(filters.value.discipline, discipline.id) : ssrLooseEqual(filters.value.discipline, discipline.id)) ? " selected" : ""}>${ssrInterpolate(discipline.name)}</option>`);
      });
      _push(`<!--]--></select></div><div data-v-8d87c433><label class="block text-sm font-medium text-gray-700 mb-2" data-v-8d87c433>Type de cours</label><select class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" data-v-8d87c433><option value="" data-v-8d87c433${ssrIncludeBooleanAttr(Array.isArray(filters.value.courseType) ? ssrLooseContain(filters.value.courseType, "") : ssrLooseEqual(filters.value.courseType, "")) ? " selected" : ""}>Tous les types</option><!--[-->`);
      ssrRenderList(filteredCourseTypes.value, (courseType) => {
        _push(`<option${ssrRenderAttr("value", courseType.id)} data-v-8d87c433${ssrIncludeBooleanAttr(Array.isArray(filters.value.courseType) ? ssrLooseContain(filters.value.courseType, courseType.id) : ssrLooseEqual(filters.value.courseType, courseType.id)) ? " selected" : ""}>${ssrInterpolate(courseType.name)}</option>`);
      });
      _push(`<!--]--></select></div><div data-v-8d87c433><label class="block text-sm font-medium text-gray-700 mb-2" data-v-8d87c433>Format</label><select class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" data-v-8d87c433><option value="" data-v-8d87c433${ssrIncludeBooleanAttr(Array.isArray(filters.value.format) ? ssrLooseContain(filters.value.format, "") : ssrLooseEqual(filters.value.format, "")) ? " selected" : ""}>Tous les formats</option><option value="individual" data-v-8d87c433${ssrIncludeBooleanAttr(Array.isArray(filters.value.format) ? ssrLooseContain(filters.value.format, "individual") : ssrLooseEqual(filters.value.format, "individual")) ? " selected" : ""}>Individuel</option><option value="group" data-v-8d87c433${ssrIncludeBooleanAttr(Array.isArray(filters.value.format) ? ssrLooseContain(filters.value.format, "group") : ssrLooseEqual(filters.value.format, "group")) ? " selected" : ""}>Collectif</option></select></div><div data-v-8d87c433><label class="block text-sm font-medium text-gray-700 mb-2" data-v-8d87c433>Date</label><input${ssrRenderAttr("value", filters.value.date)} type="date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" data-v-8d87c433></div></div><div class="mt-4 flex justify-end" data-v-8d87c433><button class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors" data-v-8d87c433> Appliquer les filtres </button></div></div>`);
      if (loading.value) {
        _push(`<div class="flex justify-center items-center py-12" data-v-8d87c433><div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600" data-v-8d87c433></div></div>`);
      } else if (error.value) {
        _push(`<div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-6" data-v-8d87c433><div class="flex items-center" data-v-8d87c433><div class="flex-shrink-0" data-v-8d87c433><svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor" data-v-8d87c433><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" data-v-8d87c433></path></svg></div><div class="ml-3" data-v-8d87c433><h3 class="text-sm font-medium text-red-800" data-v-8d87c433>Erreur</h3><div class="mt-2 text-sm text-red-700" data-v-8d87c433>${ssrInterpolate(error.value)}</div></div></div></div>`);
      } else {
        _push(`<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" data-v-8d87c433><!--[-->`);
        ssrRenderList(filteredLessons.value, (lesson) => {
          var _a, _b, _c, _d;
          _push(`<div class="bg-white rounded-lg shadow-md border border-gray-200 hover:shadow-lg transition-shadow" data-v-8d87c433><div class="p-6" data-v-8d87c433><div class="flex items-start justify-between mb-4" data-v-8d87c433><div data-v-8d87c433><h3 class="text-lg font-semibold text-gray-900 mb-1" data-v-8d87c433>${ssrInterpolate(lesson.title || "Leçon")}</h3><p class="text-sm text-gray-600" data-v-8d87c433>${ssrInterpolate(((_a = lesson.course_type) == null ? void 0 : _a.name) || "Type non spécifié")}</p></div><span class="${ssrRenderClass([
            "px-2 py-1 text-xs font-medium rounded-full",
            getStatusClass(lesson.status)
          ])}" data-v-8d87c433>${ssrInterpolate(getStatusText(lesson.status))}</span></div><div class="flex items-center mb-4" data-v-8d87c433><div class="flex-shrink-0" data-v-8d87c433><div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center" data-v-8d87c433><svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" data-v-8d87c433><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" data-v-8d87c433></path></svg></div></div><div class="ml-3" data-v-8d87c433><p class="text-sm font-medium text-gray-900" data-v-8d87c433>${ssrInterpolate(((_c = (_b = lesson.teacher) == null ? void 0 : _b.user) == null ? void 0 : _c.name) || "Enseignant")}</p><p class="text-xs text-gray-500" data-v-8d87c433>Enseignant</p></div></div><div class="space-y-2 mb-4" data-v-8d87c433><div class="flex items-center text-sm text-gray-600" data-v-8d87c433><svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" data-v-8d87c433><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" data-v-8d87c433></path></svg> ${ssrInterpolate(formatDateTime(lesson.start_time))}</div><div class="flex items-center text-sm text-gray-600" data-v-8d87c433><svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" data-v-8d87c433><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" data-v-8d87c433></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" data-v-8d87c433></path></svg> ${ssrInterpolate(((_d = lesson.location) == null ? void 0 : _d.name) || "Lieu non spécifié")}</div><div class="flex items-center text-sm text-gray-600" data-v-8d87c433><svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" data-v-8d87c433><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" data-v-8d87c433></path></svg> ${ssrInterpolate(lesson.price ? `${lesson.price}€` : "Prix non spécifié")}</div></div>`);
          if (lesson.description) {
            _push(`<p class="text-sm text-gray-600 mb-4 line-clamp-2" data-v-8d87c433>${ssrInterpolate(lesson.description)}</p>`);
          } else {
            _push(`<!---->`);
          }
          _push(`<div class="flex space-x-2" data-v-8d87c433>`);
          if (lesson.status === "available") {
            _push(`<button class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors text-sm font-medium" data-v-8d87c433> Réserver </button>`);
          } else {
            _push(`<!---->`);
          }
          _push(`<button class="flex-1 bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-colors text-sm font-medium" data-v-8d87c433> Détails </button></div></div></div>`);
        });
        _push(`<!--]--></div>`);
      }
      if (!loading.value && !error.value && filteredLessons.value.length === 0) {
        _push(`<div class="text-center py-12" data-v-8d87c433><svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" data-v-8d87c433><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" data-v-8d87c433></path></svg><h3 class="mt-2 text-sm font-medium text-gray-900" data-v-8d87c433>Aucune leçon trouvée</h3><p class="mt-1 text-sm text-gray-500" data-v-8d87c433>Essayez de modifier vos filtres de recherche.</p></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/student/lessons.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const lessons = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-8d87c433"]]);
export {
  lessons as default
};
//# sourceMappingURL=lessons-r0d1lspp.js.map
