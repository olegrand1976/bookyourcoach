import { _ as __nuxt_component_0 } from './nuxt-link-BC-lyQ5x.mjs';
import { reactive, ref, watchEffect, mergeProps, withCtx, createTextVNode, unref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderAttr, ssrIncludeBooleanAttr, ssrLooseContain, ssrRenderList, ssrInterpolate } from 'vue/server-renderer';
import { u as useAuthStore } from './auth-yP0r1OGC.mjs';
import { a as useRouter, n as navigateTo } from './server.mjs';
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
  __name: "login",
  __ssrInlineRender: true,
  setup(__props) {
    const authStore = useAuthStore();
    useRouter();
    const form = reactive({
      email: "",
      password: "",
      remember: false
    });
    const loading = ref(false);
    const error = ref("");
    const validationErrors = ref([]);
    watchEffect(() => {
      if (authStore.isAuthenticated) {
        navigateTo("/dashboard");
      }
    });
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8" }, _attrs))}><div class="max-w-md w-full space-y-8"><div><h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900"> Connexion \xE0 votre compte </h2><p class="mt-2 text-center text-sm text-gray-600"> Ou `);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/register",
        class: "font-medium text-primary-600 hover:text-primary-500"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` cr\xE9ez un nouveau compte `);
          } else {
            return [
              createTextVNode(" cr\xE9ez un nouveau compte ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</p></div><form class="mt-8 space-y-6"><div class="rounded-md shadow-sm -space-y-px"><div><label for="email" class="sr-only">Adresse email</label><input id="email"${ssrRenderAttr("value", unref(form).email)} name="email" type="email" autocomplete="email" required class="relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-primary-500 focus:border-primary-500 focus:z-10 sm:text-sm" placeholder="Adresse email"></div><div><label for="password" class="sr-only">Mot de passe</label><input id="password"${ssrRenderAttr("value", unref(form).password)} name="password" type="password" autocomplete="current-password" required class="relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-primary-500 focus:border-primary-500 focus:z-10 sm:text-sm" placeholder="Mot de passe"></div></div><div class="flex items-center justify-between"><div class="flex items-center"><input id="remember-me"${ssrIncludeBooleanAttr(Array.isArray(unref(form).remember) ? ssrLooseContain(unref(form).remember, null) : unref(form).remember) ? " checked" : ""} name="remember-me" type="checkbox" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"><label for="remember-me" class="ml-2 block text-sm text-gray-900"> Se souvenir de moi </label></div><div class="text-sm"><a href="#" class="font-medium text-primary-600 hover:text-primary-500"> Mot de passe oubli\xE9 ? </a></div></div><div><button type="submit"${ssrIncludeBooleanAttr(unref(loading)) ? " disabled" : ""} class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50">`);
      if (unref(loading)) {
        _push(`<span>Connexion en cours...</span>`);
      } else {
        _push(`<span>Se connecter</span>`);
      }
      _push(`</button></div>`);
      if (unref(validationErrors).length > 0) {
        _push(`<div class="bg-yellow-50 border border-yellow-200 rounded-md p-4"><div class="text-sm text-yellow-700"><ul class="list-disc list-inside"><!--[-->`);
        ssrRenderList(unref(validationErrors), (validationError) => {
          _push(`<li>${ssrInterpolate(validationError)}</li>`);
        });
        _push(`<!--]--></ul></div></div>`);
      } else {
        _push(`<!---->`);
      }
      if (unref(error)) {
        _push(`<div class="bg-red-50 border border-red-200 rounded-md p-4"><div class="text-sm text-red-600">${ssrInterpolate(unref(error))}</div></div>`);
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/login.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=login-BWUzNeFl.mjs.map
