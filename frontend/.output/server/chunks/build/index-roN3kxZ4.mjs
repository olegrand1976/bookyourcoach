import { defineComponent, mergeProps, useSSRContext } from 'vue';
import { ssrRenderAttrs } from 'vue/server-renderer';
import { _ as _export_sfc } from './_plugin-vue_export-helper-1tPrXgE0.mjs';

const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "index",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "student-index" }, _attrs))} data-v-28e64488><div class="min-h-screen bg-gray-50 flex items-center justify-center" data-v-28e64488><div class="max-w-md w-full space-y-8" data-v-28e64488><div class="text-center" data-v-28e64488><div class="mx-auto h-12 w-12 bg-blue-600 rounded-lg flex items-center justify-center" data-v-28e64488><svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" data-v-28e64488><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" data-v-28e64488></path></svg></div><h2 class="mt-6 text-3xl font-extrabold text-gray-900" data-v-28e64488> Espace \xC9tudiant </h2><p class="mt-2 text-sm text-gray-600" data-v-28e64488> Redirection vers votre tableau de bord... </p></div><div class="flex justify-center" data-v-28e64488><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600" data-v-28e64488></div></div></div></div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/student/index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const index = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-28e64488"]]);

export { index as default };
//# sourceMappingURL=index-roN3kxZ4.mjs.map
