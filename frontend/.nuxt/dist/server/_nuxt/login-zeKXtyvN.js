import { _ as __nuxt_component_0 } from "./nuxt-link-3RFQ5DMr.js";
import { reactive, ref, watchEffect, mergeProps, withCtx, createTextVNode, unref, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderAttr, ssrIncludeBooleanAttr, ssrLooseContain, ssrRenderList, ssrInterpolate } from "vue/server-renderer";
import { a as useAuthStore, b as useRouter, n as navigateTo } from "../server.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/hookable/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/ufo/dist/index.mjs";
import "ofetch";
import "#internal/nuxt/paths";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/unctx/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/h3/dist/index.mjs";
import "vue-router";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/radix3/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/defu/dist/defu.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/klona/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/nuxt/node_modules/cookie-es/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/destr/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/ohash/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/@unhead/vue/dist/index.mjs";
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
        if (authStore.isAdmin) {
          navigateTo("/admin");
        } else if (authStore.isTeacher) {
          navigateTo("/teacher/dashboard");
        } else if (authStore.isClub) {
          navigateTo("/club/dashboard");
        } else if (authStore.isStudent) {
          navigateTo("/student/dashboard");
        } else {
          navigateTo("/dashboard");
        }
      }
    });
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-50" }, _attrs))}><div class="flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8"><div class="max-w-md w-full space-y-8"><div><h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900"> Connexion </h2><p class="mt-2 text-center text-sm text-gray-700"> Pas encore de compte ? `);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/register",
        class: "font-medium text-blue-400 bg-blue-600:text-yellow-600"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Créer un compte `);
          } else {
            return [
              createTextVNode(" Créer un compte ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</p><p class="mt-1 text-center text-sm text-gray-600"> Vous êtes enseignant ? `);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/register-teacher",
        class: "font-medium text-emerald-600 hover:text-emerald-500"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` S&#39;inscrire comme enseignant `);
          } else {
            return [
              createTextVNode(" S'inscrire comme enseignant ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</p></div><form class="mt-8 space-y-6"><div class="rounded-md shadow-sm -space-y-px"><div><label for="email" class="sr-only">Email</label><input id="email"${ssrRenderAttr("value", unref(form).email)} name="email" type="email" autocomplete="email" required class="relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" placeholder="Adresse email"></div><div><label for="password" class="sr-only">Mot de passe</label><input id="password"${ssrRenderAttr("value", unref(form).password)} name="password" type="password" autocomplete="current-password" required class="relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" placeholder="Mot de passe"></div></div><div class="flex items-center justify-between"><div class="flex items-center"><input id="remember-me"${ssrIncludeBooleanAttr(Array.isArray(unref(form).remember) ? ssrLooseContain(unref(form).remember, null) : unref(form).remember) ? " checked" : ""} name="remember-me" type="checkbox" class="h-4 w-4 text-blue-400 focus:ring-blue-500 border-gray-300 rounded"><label for="remember-me" class="ml-2 block text-sm text-gray-900"> Se souvenir de moi </label></div><div class="text-sm"><a href="#" class="font-medium text-blue-400 bg-blue-600:text-yellow-600"> Mot de passe oublié ? </a></div></div><div><button type="submit"${ssrIncludeBooleanAttr(unref(loading)) ? " disabled" : ""} class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-500 bg-blue-600:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50">`);
      if (unref(loading)) {
        _push(`<span>Connexion en cours...</span>`);
      } else {
        _push(`<span>Connexion</span>`);
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
      _push(`</form></div></div></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/login.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
//# sourceMappingURL=login-zeKXtyvN.js.map
