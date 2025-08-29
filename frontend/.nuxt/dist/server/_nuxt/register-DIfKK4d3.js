import { _ as __nuxt_component_0 } from "./nuxt-link-4z5Qc0yN.js";
import { reactive, ref, watchEffect, mergeProps, unref, withCtx, createTextVNode, toDisplayString, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrInterpolate, ssrRenderComponent, ssrRenderAttr, ssrIncludeBooleanAttr, ssrLooseContain, ssrRenderList } from "vue/server-renderer";
import "/workspace/frontend/node_modules/hookable/dist/index.mjs";
import { u as useAuthStore } from "./auth-SYtdBTeW.js";
import { b as useI18n, n as navigateTo } from "../server.mjs";
import "ofetch";
import "#internal/nuxt/paths";
import "/workspace/frontend/node_modules/unctx/dist/index.mjs";
import "/workspace/frontend/node_modules/h3/dist/index.mjs";
import "vue-router";
import "/workspace/frontend/node_modules/radix3/dist/index.mjs";
import "/workspace/frontend/node_modules/defu/dist/defu.mjs";
import "/workspace/frontend/node_modules/klona/dist/index.mjs";
import "/workspace/frontend/node_modules/nuxt/node_modules/cookie-es/dist/index.mjs";
import "/workspace/frontend/node_modules/destr/dist/index.mjs";
import "/workspace/frontend/node_modules/ohash/dist/index.mjs";
import "@vue/devtools-api";
import "/workspace/frontend/node_modules/@unhead/vue/dist/index.mjs";
const _sfc_main = {
  __name: "register",
  __ssrInlineRender: true,
  setup(__props) {
    const authStore = useAuthStore();
    const { t } = useI18n();
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
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8" }, _attrs))}><div class="max-w-md w-full space-y-8"><div><h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">${ssrInterpolate(unref(t)("registerPage.title"))}</h2><p class="mt-2 text-center text-sm text-gray-600">${ssrInterpolate(unref(t)("registerPage.or"))} `);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/login",
        class: "font-medium text-primary-600 hover:text-primary-500"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`${ssrInterpolate(unref(t)("registerPage.login"))}`);
          } else {
            return [
              createTextVNode(toDisplayString(unref(t)("registerPage.login")), 1)
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</p></div><form class="mt-8 space-y-6"><div class="space-y-4"><div><label for="name" class="block text-sm font-medium text-gray-700">${ssrInterpolate(unref(t)("auth.name"))}</label><input id="name"${ssrRenderAttr("value", unref(form).name)} name="name" type="text" required class="input-field"${ssrRenderAttr("placeholder", unref(t)("auth.name"))}></div><div><label for="email" class="block text-sm font-medium text-gray-700">${ssrInterpolate(unref(t)("auth.email"))}</label><input id="email"${ssrRenderAttr("value", unref(form).email)} name="email" type="email" autocomplete="email" required class="input-field"${ssrRenderAttr("placeholder", unref(t)("auth.email"))}></div><div><label for="password" class="block text-sm font-medium text-gray-700">${ssrInterpolate(unref(t)("auth.password"))}</label><input id="password"${ssrRenderAttr("value", unref(form).password)} name="password" type="password" required class="input-field"${ssrRenderAttr("placeholder", unref(t)("auth.password"))}></div><div><label for="password_confirmation" class="block text-sm font-medium text-gray-700">${ssrInterpolate(unref(t)("auth.confirmPassword"))}</label><input id="password_confirmation"${ssrRenderAttr("value", unref(form).password_confirmation)} name="password_confirmation" type="password" required class="input-field"${ssrRenderAttr("placeholder", unref(t)("auth.confirmPassword"))}></div></div><div class="flex items-center"><input id="terms"${ssrIncludeBooleanAttr(Array.isArray(unref(form).terms) ? ssrLooseContain(unref(form).terms, null) : unref(form).terms) ? " checked" : ""} name="terms" type="checkbox" required class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"><label for="terms" class="ml-2 block text-sm text-gray-900">${ssrInterpolate(unref(t)("registerPage.terms"))} <a href="#" class="text-primary-600 hover:text-primary-500">${ssrInterpolate(unref(t)("registerPage.termsLink"))}</a></label></div><div><button type="submit"${ssrIncludeBooleanAttr(unref(loading)) ? " disabled" : ""} class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50">`);
      if (unref(loading)) {
        _push(`<span>${ssrInterpolate(unref(t)("registerPage.creatingAccount"))}</span>`);
      } else {
        _push(`<span>${ssrInterpolate(unref(t)("auth.createAccount"))}</span>`);
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
export {
  _sfc_main as default
};
//# sourceMappingURL=register-DIfKK4d3.js.map
