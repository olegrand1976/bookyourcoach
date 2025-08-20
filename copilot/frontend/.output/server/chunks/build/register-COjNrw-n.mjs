import { _ as __nuxt_component_0 } from './nuxt-link-BC-lyQ5x.mjs';
import { reactive, ref, watchEffect, mergeProps, withCtx, createTextVNode, unref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderAttr, ssrIncludeBooleanAttr, ssrLooseContain, ssrRenderList, ssrInterpolate } from 'vue/server-renderer';
import { u as useAuthStore } from './auth-yP0r1OGC.mjs';
import { n as navigateTo } from './server.mjs';
import '../nitro/nitro.mjs';
import 'node:http';
import 'node:https';
import 'node:events';
import 'node:buffer';
import 'node:fs';
import 'node:path';
import 'node:crypto';
import 'node:url';
import './ssr-B4FXEZKR.mjs';
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
    const form = reactive({
      name: "",
      email: "",
      password: "",
      password_confirmation: "",
      terms: false
    });
    const loading = ref(false);
    const errors = ref([]);
    watchEffect(() => {
      if (authStore.isAuthenticated) {
        navigateTo("/dashboard");
      }
    });
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8" }, _attrs))}><div class="max-w-md w-full space-y-8"><div><h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900"> Cr\xE9er un compte </h2><p class="mt-2 text-center text-sm text-gray-600"> Ou `);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/login",
        class: "font-medium text-primary-600 hover:text-primary-500"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` connectez-vous \xE0 votre compte existant `);
          } else {
            return [
              createTextVNode(" connectez-vous \xE0 votre compte existant ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</p></div><form class="mt-8 space-y-6"><div class="space-y-4"><div><label for="name" class="block text-sm font-medium text-gray-700"> Nom complet </label><input id="name"${ssrRenderAttr("value", unref(form).name)} name="name" type="text" required class="input-field" placeholder="Votre nom complet"></div><div><label for="email" class="block text-sm font-medium text-gray-700"> Adresse email </label><input id="email"${ssrRenderAttr("value", unref(form).email)} name="email" type="email" autocomplete="email" required class="input-field" placeholder="votre@email.com"></div><div><label for="password" class="block text-sm font-medium text-gray-700"> Mot de passe </label><input id="password"${ssrRenderAttr("value", unref(form).password)} name="password" type="password" required class="input-field" placeholder="Au moins 8 caract\xE8res"></div><div><label for="password_confirmation" class="block text-sm font-medium text-gray-700"> Confirmer le mot de passe </label><input id="password_confirmation"${ssrRenderAttr("value", unref(form).password_confirmation)} name="password_confirmation" type="password" required class="input-field" placeholder="Confirmez votre mot de passe"></div></div><div class="flex items-center"><input id="terms"${ssrIncludeBooleanAttr(Array.isArray(unref(form).terms) ? ssrLooseContain(unref(form).terms, null) : unref(form).terms) ? " checked" : ""} name="terms" type="checkbox" required class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"><label for="terms" class="ml-2 block text-sm text-gray-900"> J&#39;accepte les <a href="#" class="text-primary-600 hover:text-primary-500"> conditions d&#39;utilisation </a></label></div><div><button type="submit"${ssrIncludeBooleanAttr(unref(loading)) ? " disabled" : ""} class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50">`);
      if (unref(loading)) {
        _push(`<span>Cr\xE9ation en cours...</span>`);
      } else {
        _push(`<span>Cr\xE9er mon compte</span>`);
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
      _push(`</form></div></div>`);
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
//# sourceMappingURL=register-COjNrw-n.mjs.map
