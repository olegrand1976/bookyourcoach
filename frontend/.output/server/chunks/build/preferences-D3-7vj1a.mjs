import { defineComponent, ref, mergeProps, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrInterpolate, ssrRenderClass } from 'vue/server-renderer';
import { _ as _export_sfc } from './_plugin-vue_export-helper-1tPrXgE0.mjs';

const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "preferences",
  __ssrInlineRender: true,
  setup(__props) {
    const loading = ref(false);
    const error = ref("");
    const disciplinePreferences = ref({
      equitation: false,
      natation: false
    });
    const courseTypePreferences = ref({
      equitation: {
        dressage_particulier: false,
        dressage_collectif: false,
        obstacles_particulier: false,
        obstacles_collectif: false
      },
      natation: {
        cours_particulier: false,
        aquagym: false
      }
    });
    const hasPreferenceForDiscipline = (discipline) => {
      return disciplinePreferences.value[discipline] || false;
    };
    const hasPreferenceForCourseType = (discipline, courseType) => {
      var _a;
      return ((_a = courseTypePreferences.value[discipline]) == null ? void 0 : _a[courseType]) || false;
    };
    const hasAnyPreference = () => {
      return Object.values(disciplinePreferences.value).some(Boolean) || Object.values(courseTypePreferences.value).some(
        (discipline) => Object.values(discipline).some(Boolean)
      );
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "preferences-page" }, _attrs))} data-v-a5a1cd9d><div class="container mx-auto px-4 py-8" data-v-a5a1cd9d><div class="mb-8" data-v-a5a1cd9d><h1 class="text-3xl font-bold text-gray-900 mb-2" data-v-a5a1cd9d> \u{1F3AF} Mes Pr\xE9f\xE9rences </h1><p class="text-gray-700" data-v-a5a1cd9d> S\xE9lectionnez vos disciplines et types de cours pr\xE9f\xE9r\xE9s </p></div>`);
      if (loading.value) {
        _push(`<div class="flex justify-center items-center py-12" data-v-a5a1cd9d><div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500" data-v-a5a1cd9d></div></div>`);
      } else if (error.value) {
        _push(`<div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-6" data-v-a5a1cd9d><div class="flex items-center" data-v-a5a1cd9d><div class="flex-shrink-0" data-v-a5a1cd9d><svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor" data-v-a5a1cd9d><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" data-v-a5a1cd9d></path></svg></div><div class="ml-3" data-v-a5a1cd9d><h3 class="text-sm font-medium text-red-800" data-v-a5a1cd9d>Erreur</h3><div class="mt-2 text-sm text-red-700" data-v-a5a1cd9d>${ssrInterpolate(error.value)}</div><div class="mt-4" data-v-a5a1cd9d><button class="bg-red-100 bg-blue-600:bg-red-200 text-red-800 px-3 py-2 rounded-md text-sm font-medium transition-colors" data-v-a5a1cd9d> R\xE9essayer </button></div></div></div></div>`);
      } else {
        _push(`<div class="space-y-6" data-v-a5a1cd9d><div class="bg-white rounded-lg shadow-md border border-blue-500/20" data-v-a5a1cd9d><div class="p-6 border-b border-blue-500/20 bg-gradient-to-r from-gray-50 to-blue-50" data-v-a5a1cd9d><div class="flex items-center justify-between" data-v-a5a1cd9d><div class="flex items-center space-x-3" data-v-a5a1cd9d><div class="flex-shrink-0" data-v-a5a1cd9d><span class="text-3xl" data-v-a5a1cd9d>\u{1F3C7}</span></div><div data-v-a5a1cd9d><h3 class="text-xl font-semibold text-gray-900" data-v-a5a1cd9d> \xC9quitation </h3><p class="text-sm text-gray-700" data-v-a5a1cd9d> Dressage, obstacles et complet </p></div></div><div class="flex items-center space-x-2" data-v-a5a1cd9d><button class="${ssrRenderClass([
          "px-4 py-2 rounded-md text-sm font-medium transition-colors",
          hasPreferenceForDiscipline("equitation") ? "bg-blue-500 text-gray-900 bg-blue-600:bg-yellow-600" : "bg-gray-100 text-gray-800 bg-blue-600:bg-gray-200"
        ])}" data-v-a5a1cd9d>${ssrInterpolate(hasPreferenceForDiscipline("equitation") ? "S\xE9lectionn\xE9" : "S\xE9lectionner")}</button>`);
        if (hasPreferenceForDiscipline("equitation")) {
          _push(`<div class="flex-shrink-0" data-v-a5a1cd9d><svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20" data-v-a5a1cd9d><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" data-v-a5a1cd9d></path></svg></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div></div></div>`);
        if (hasPreferenceForDiscipline("equitation")) {
          _push(`<div class="p-6" data-v-a5a1cd9d><h4 class="text-md font-medium text-gray-900 mb-4" data-v-a5a1cd9d> Types de cours pr\xE9f\xE9r\xE9s : </h4><div class="grid gap-3" data-v-a5a1cd9d><div class="flex items-center justify-between p-3 bg-gray-50/50 rounded-lg hover:bg-gray-50 transition-colors" data-v-a5a1cd9d><div class="flex items-center space-x-3" data-v-a5a1cd9d><button class="${ssrRenderClass([
            "flex-shrink-0 w-5 h-5 rounded border-2 flex items-center justify-center transition-colors",
            hasPreferenceForCourseType("equitation", "dressage_particulier") ? "bg-blue-500 border-blue-500" : "border-gray-300 bg-blue-600:border-blue-500"
          ])}" data-v-a5a1cd9d>`);
          if (hasPreferenceForCourseType("equitation", "dressage_particulier")) {
            _push(`<svg class="w-3 h-3 text-gray-900" fill="currentColor" viewBox="0 0 20 20" data-v-a5a1cd9d><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" data-v-a5a1cd9d></path></svg>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</button><div data-v-a5a1cd9d><div class="text-sm font-medium text-gray-900" data-v-a5a1cd9d> Dressage Particulier </div><div class="text-xs text-gray-700" data-v-a5a1cd9d> Individuel \u2022 Dur\xE9e variable selon l&#39;enseignant </div></div></div>`);
          if (hasPreferenceForCourseType("equitation", "dressage_particulier")) {
            _push(`<div class="flex-shrink-0" data-v-a5a1cd9d><svg class="h-4 w-4 text-green-500" fill="currentColor" viewBox="0 0 20 20" data-v-a5a1cd9d><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" data-v-a5a1cd9d></path></svg></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div><div class="flex items-center justify-between p-3 bg-gray-50/50 rounded-lg hover:bg-gray-50 transition-colors" data-v-a5a1cd9d><div class="flex items-center space-x-3" data-v-a5a1cd9d><button class="${ssrRenderClass([
            "flex-shrink-0 w-5 h-5 rounded border-2 flex items-center justify-center transition-colors",
            hasPreferenceForCourseType("equitation", "dressage_collectif") ? "bg-blue-500 border-blue-500" : "border-gray-300 bg-blue-600:border-blue-500"
          ])}" data-v-a5a1cd9d>`);
          if (hasPreferenceForCourseType("equitation", "dressage_collectif")) {
            _push(`<svg class="w-3 h-3 text-gray-900" fill="currentColor" viewBox="0 0 20 20" data-v-a5a1cd9d><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" data-v-a5a1cd9d></path></svg>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</button><div data-v-a5a1cd9d><div class="text-sm font-medium text-gray-900" data-v-a5a1cd9d> Dressage Collectif </div><div class="text-xs text-gray-700" data-v-a5a1cd9d> Collectif \u2022 Dur\xE9e variable selon l&#39;enseignant </div></div></div>`);
          if (hasPreferenceForCourseType("equitation", "dressage_collectif")) {
            _push(`<div class="flex-shrink-0" data-v-a5a1cd9d><svg class="h-4 w-4 text-green-500" fill="currentColor" viewBox="0 0 20 20" data-v-a5a1cd9d><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" data-v-a5a1cd9d></path></svg></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div><div class="flex items-center justify-between p-3 bg-gray-50/50 rounded-lg hover:bg-gray-50 transition-colors" data-v-a5a1cd9d><div class="flex items-center space-x-3" data-v-a5a1cd9d><button class="${ssrRenderClass([
            "flex-shrink-0 w-5 h-5 rounded border-2 flex items-center justify-center transition-colors",
            hasPreferenceForCourseType("equitation", "obstacles_particulier") ? "bg-blue-500 border-blue-500" : "border-gray-300 bg-blue-600:border-blue-500"
          ])}" data-v-a5a1cd9d>`);
          if (hasPreferenceForCourseType("equitation", "obstacles_particulier")) {
            _push(`<svg class="w-3 h-3 text-gray-900" fill="currentColor" viewBox="0 0 20 20" data-v-a5a1cd9d><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" data-v-a5a1cd9d></path></svg>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</button><div data-v-a5a1cd9d><div class="text-sm font-medium text-gray-900" data-v-a5a1cd9d> Obstacles Particulier </div><div class="text-xs text-gray-700" data-v-a5a1cd9d> Individuel \u2022 Dur\xE9e variable selon l&#39;enseignant </div></div></div>`);
          if (hasPreferenceForCourseType("equitation", "obstacles_particulier")) {
            _push(`<div class="flex-shrink-0" data-v-a5a1cd9d><svg class="h-4 w-4 text-green-500" fill="currentColor" viewBox="0 0 20 20" data-v-a5a1cd9d><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" data-v-a5a1cd9d></path></svg></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div><div class="flex items-center justify-between p-3 bg-gray-50/50 rounded-lg hover:bg-gray-50 transition-colors" data-v-a5a1cd9d><div class="flex items-center space-x-3" data-v-a5a1cd9d><button class="${ssrRenderClass([
            "flex-shrink-0 w-5 h-5 rounded border-2 flex items-center justify-center transition-colors",
            hasPreferenceForCourseType("equitation", "obstacles_collectif") ? "bg-blue-500 border-blue-500" : "border-gray-300 bg-blue-600:border-blue-500"
          ])}" data-v-a5a1cd9d>`);
          if (hasPreferenceForCourseType("equitation", "obstacles_collectif")) {
            _push(`<svg class="w-3 h-3 text-gray-900" fill="currentColor" viewBox="0 0 20 20" data-v-a5a1cd9d><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" data-v-a5a1cd9d></path></svg>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</button><div data-v-a5a1cd9d><div class="text-sm font-medium text-gray-900" data-v-a5a1cd9d> Obstacles Collectif </div><div class="text-xs text-gray-700" data-v-a5a1cd9d> Collectif \u2022 Dur\xE9e variable selon l&#39;enseignant </div></div></div>`);
          if (hasPreferenceForCourseType("equitation", "obstacles_collectif")) {
            _push(`<div class="flex-shrink-0" data-v-a5a1cd9d><svg class="h-4 w-4 text-green-500" fill="currentColor" viewBox="0 0 20 20" data-v-a5a1cd9d><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" data-v-a5a1cd9d></path></svg></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div></div></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div><div class="bg-white rounded-lg shadow-md border border-blue-200" data-v-a5a1cd9d><div class="p-6 border-b border-blue-200 bg-gradient-to-r from-blue-50 to-blue-100" data-v-a5a1cd9d><div class="flex items-center justify-between" data-v-a5a1cd9d><div class="flex items-center space-x-3" data-v-a5a1cd9d><div class="flex-shrink-0" data-v-a5a1cd9d><span class="text-3xl" data-v-a5a1cd9d>\u{1F3CA}</span></div><div data-v-a5a1cd9d><h3 class="text-xl font-semibold text-blue-900" data-v-a5a1cd9d> Natation </h3><p class="text-sm text-blue-700" data-v-a5a1cd9d> Cours particuliers et aquagym </p></div></div><div class="flex items-center space-x-2" data-v-a5a1cd9d><button class="${ssrRenderClass([
          "px-4 py-2 rounded-md text-sm font-medium transition-colors",
          hasPreferenceForDiscipline("natation") ? "bg-blue-600 text-white bg-blue-600:bg-blue-700" : "bg-gray-100 text-gray-800 bg-blue-600:bg-gray-200"
        ])}" data-v-a5a1cd9d>${ssrInterpolate(hasPreferenceForDiscipline("natation") ? "S\xE9lectionn\xE9" : "S\xE9lectionner")}</button>`);
        if (hasPreferenceForDiscipline("natation")) {
          _push(`<div class="flex-shrink-0" data-v-a5a1cd9d><svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20" data-v-a5a1cd9d><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" data-v-a5a1cd9d></path></svg></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div></div></div>`);
        if (hasPreferenceForDiscipline("natation")) {
          _push(`<div class="p-6" data-v-a5a1cd9d><h4 class="text-md font-medium text-blue-900 mb-4" data-v-a5a1cd9d> Types de cours pr\xE9f\xE9r\xE9s : </h4><div class="grid gap-3" data-v-a5a1cd9d><div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg bg-blue-600:bg-blue-100 transition-colors" data-v-a5a1cd9d><div class="flex items-center space-x-3" data-v-a5a1cd9d><button class="${ssrRenderClass([
            "flex-shrink-0 w-5 h-5 rounded border-2 flex items-center justify-center transition-colors",
            hasPreferenceForCourseType("natation", "cours_particulier") ? "bg-blue-600 border-blue-600" : "border-gray-300 bg-blue-600:border-blue-400"
          ])}" data-v-a5a1cd9d>`);
          if (hasPreferenceForCourseType("natation", "cours_particulier")) {
            _push(`<svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20" data-v-a5a1cd9d><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" data-v-a5a1cd9d></path></svg>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</button><div data-v-a5a1cd9d><div class="text-sm font-medium text-blue-900" data-v-a5a1cd9d> Cours Particulier </div><div class="text-xs text-blue-700" data-v-a5a1cd9d> Individuel \u2022 20 minutes </div></div></div>`);
          if (hasPreferenceForCourseType("natation", "cours_particulier")) {
            _push(`<div class="flex-shrink-0" data-v-a5a1cd9d><svg class="h-4 w-4 text-green-500" fill="currentColor" viewBox="0 0 20 20" data-v-a5a1cd9d><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" data-v-a5a1cd9d></path></svg></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div><div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg bg-blue-600:bg-blue-100 transition-colors" data-v-a5a1cd9d><div class="flex items-center space-x-3" data-v-a5a1cd9d><button class="${ssrRenderClass([
            "flex-shrink-0 w-5 h-5 rounded border-2 flex items-center justify-center transition-colors",
            hasPreferenceForCourseType("natation", "aquagym") ? "bg-blue-600 border-blue-600" : "border-gray-300 bg-blue-600:border-blue-400"
          ])}" data-v-a5a1cd9d>`);
          if (hasPreferenceForCourseType("natation", "aquagym")) {
            _push(`<svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20" data-v-a5a1cd9d><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" data-v-a5a1cd9d></path></svg>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</button><div data-v-a5a1cd9d><div class="text-sm font-medium text-blue-900" data-v-a5a1cd9d> Aquagym </div><div class="text-xs text-blue-700" data-v-a5a1cd9d> Collectif \u2022 1 heure </div></div></div>`);
          if (hasPreferenceForCourseType("natation", "aquagym")) {
            _push(`<div class="flex-shrink-0" data-v-a5a1cd9d><svg class="h-4 w-4 text-green-500" fill="currentColor" viewBox="0 0 20 20" data-v-a5a1cd9d><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" data-v-a5a1cd9d></path></svg></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div></div></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
        if (hasAnyPreference()) {
          _push(`<div class="bg-green-50 border border-green-200 rounded-lg p-6" data-v-a5a1cd9d><div class="flex items-center" data-v-a5a1cd9d><div class="flex-shrink-0" data-v-a5a1cd9d><svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20" data-v-a5a1cd9d><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" data-v-a5a1cd9d></path></svg></div><div class="ml-3" data-v-a5a1cd9d><h3 class="text-sm font-medium text-green-800" data-v-a5a1cd9d> Pr\xE9f\xE9rences sauvegard\xE9es </h3><div class="mt-2 text-sm text-green-700" data-v-a5a1cd9d> Vos pr\xE9f\xE9rences ont \xE9t\xE9 enregistr\xE9es avec succ\xE8s. </div></div></div></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
      }
      _push(`</div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/student/preferences.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const preferences = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-a5a1cd9d"]]);

export { preferences as default };
//# sourceMappingURL=preferences-D3-7vj1a.mjs.map
