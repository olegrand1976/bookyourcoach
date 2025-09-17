import { _ as __nuxt_component_0 } from './nuxt-link-CWCWeN0_.mjs';
import { ref, mergeProps, unref, withCtx, createTextVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrInterpolate, ssrRenderComponent } from 'vue/server-renderer';
import { a as useAuthStore } from './server.mjs';
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
  __name: "test-links",
  __ssrInlineRender: true,
  setup(__props) {
    const authStore = useAuthStore();
    const result = ref("");
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "p-8" }, _attrs))}><h1 class="text-2xl font-bold mb-4">Test des liens du menu</h1><div class="space-y-4"><div class="p-4 border rounded"><h3 class="font-semibold mb-2">\xC9tat d&#39;authentification :</h3><p><strong>isAuthenticated:</strong> ${ssrInterpolate(unref(authStore).isAuthenticated)}</p><p><strong>canActAsTeacher:</strong> ${ssrInterpolate(unref(authStore).canActAsTeacher)}</p><p><strong>isStudent:</strong> ${ssrInterpolate(unref(authStore).isStudent)}</p><p><strong>isAdmin:</strong> ${ssrInterpolate(unref(authStore).isAdmin)}</p><p><strong>User:</strong> ${ssrInterpolate(unref(authStore).user ? unref(authStore).user.email : "Aucun")}</p></div><div class="p-4 border rounded"><h3 class="font-semibold mb-2">Test des liens :</h3><button class="px-4 py-2 bg-blue-500 text-white rounded mr-2"> Test Espace Enseignant </button><button class="px-4 py-2 bg-green-500 text-white rounded mr-2"> Test Espace \xC9tudiant </button><button class="px-4 py-2 bg-purple-500 text-white rounded"> Test Dashboard </button></div><div class="p-4 border rounded"><h3 class="font-semibold mb-2">R\xE9sultat :</h3><p>${ssrInterpolate(unref(result))}</p></div><div class="p-4 border rounded"><h3 class="font-semibold mb-2">Test direct des routes :</h3>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/teacher/dashboard",
        class: "px-4 py-2 bg-red-500 text-white rounded mr-2"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Lien direct Teacher Dashboard `);
          } else {
            return [
              createTextVNode(" Lien direct Teacher Dashboard ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/student/dashboard",
        class: "px-4 py-2 bg-orange-500 text-white rounded mr-2"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Lien direct Student Dashboard `);
          } else {
            return [
              createTextVNode(" Lien direct Student Dashboard ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/dashboard",
        class: "px-4 py-2 bg-gray-500 text-white rounded"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Lien direct Dashboard `);
          } else {
            return [
              createTextVNode(" Lien direct Dashboard ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></div></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/test-links.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=test-links-B0fKMB7U.mjs.map
