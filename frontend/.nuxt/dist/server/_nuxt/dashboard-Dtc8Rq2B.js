import { _ as __nuxt_component_0 } from "./nuxt-link-4z5Qc0yN.js";
import { ref, mergeProps, unref, withCtx, createTextVNode, toDisplayString, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrInterpolate, ssrRenderComponent, ssrRenderList } from "vue/server-renderer";
import { CalendarIcon, CheckCircleIcon, ClockIcon } from "@heroicons/vue/24/outline";
import { b as useI18n, d as useNuxtApp } from "../server.mjs";
import { u as useAuthStore } from "./auth-SYtdBTeW.js";
import "ofetch";
import "#internal/nuxt/paths";
import "/workspace/frontend/node_modules/hookable/dist/index.mjs";
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
  __name: "dashboard",
  __ssrInlineRender: true,
  setup(__props) {
    const { t, locale } = useI18n();
    const authStore = useAuthStore();
    const { $api } = useNuxtApp();
    const stats = ref({
      upcoming_lessons: 0,
      completed_lessons: 0,
      total_hours: 0
    });
    const upcomingLessons = ref([]);
    ref(true);
    const formatDate = (dateString) => {
      const date = new Date(dateString);
      return date.toLocaleDateString(locale.value, {
        weekday: "long",
        year: "numeric",
        month: "long",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit"
      });
    };
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" }, _attrs))}><div class="mb-8"><h1 class="text-3xl font-bold text-gray-900">${ssrInterpolate(unref(t)("dashboard.title", { name: unref(authStore).userName }))}</h1><p class="mt-2 text-gray-600">${ssrInterpolate(unref(t)("dashboard.subtitle"))}</p></div><div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8"><div class="card"><div class="flex items-center"><div class="p-2 bg-primary-100 rounded-lg">`);
      _push(ssrRenderComponent(unref(CalendarIcon), { class: "w-6 h-6 text-primary-600" }, null, _parent));
      _push(`</div><div class="ml-4"><p class="text-sm font-medium text-gray-600">${ssrInterpolate(unref(t)("dashboard.upcomingLessons"))}</p><p class="text-2xl font-bold text-gray-900">${ssrInterpolate(unref(stats).upcoming_lessons)}</p></div></div></div><div class="card"><div class="flex items-center"><div class="p-2 bg-green-100 rounded-lg">`);
      _push(ssrRenderComponent(unref(CheckCircleIcon), { class: "w-6 h-6 text-green-600" }, null, _parent));
      _push(`</div><div class="ml-4"><p class="text-sm font-medium text-gray-600">${ssrInterpolate(unref(t)("dashboard.completedLessons"))}</p><p class="text-2xl font-bold text-gray-900">${ssrInterpolate(unref(stats).completed_lessons)}</p></div></div></div><div class="card"><div class="flex items-center"><div class="p-2 bg-orange-100 rounded-lg">`);
      _push(ssrRenderComponent(unref(ClockIcon), { class: "w-6 h-6 text-orange-600" }, null, _parent));
      _push(`</div><div class="ml-4"><p class="text-sm font-medium text-gray-600">${ssrInterpolate(unref(t)("dashboard.totalHours"))}</p><p class="text-2xl font-bold text-gray-900">${ssrInterpolate(unref(stats).total_hours)}h</p></div></div></div></div><div class="grid grid-cols-1 lg:grid-cols-2 gap-8"><div class="card"><div class="flex items-center justify-between mb-4"><h2 class="text-lg font-semibold text-gray-900">${ssrInterpolate(unref(t)("dashboard.upcomingLessons"))}</h2>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/lessons",
        class: "text-primary-600 hover:text-primary-700 text-sm font-medium"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`${ssrInterpolate(unref(t)("dashboard.viewAll"))}`);
          } else {
            return [
              createTextVNode(toDisplayString(unref(t)("dashboard.viewAll")), 1)
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div>`);
      if (unref(upcomingLessons).length === 0) {
        _push(`<div class="text-center py-8">`);
        _push(ssrRenderComponent(unref(CalendarIcon), { class: "w-12 h-12 text-gray-400 mx-auto mb-4" }, null, _parent));
        _push(`<p class="text-gray-500">${ssrInterpolate(unref(t)("dashboard.noLessons"))}</p>`);
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/lessons/book",
          class: "btn-primary mt-4 inline-block"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`${ssrInterpolate(unref(t)("dashboard.bookLesson"))}`);
            } else {
              return [
                createTextVNode(toDisplayString(unref(t)("dashboard.bookLesson")), 1)
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`</div>`);
      } else {
        _push(`<div class="space-y-4"><!--[-->`);
        ssrRenderList(unref(upcomingLessons), (lesson) => {
          _push(`<div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg"><div><h3 class="font-medium text-gray-900">${ssrInterpolate(lesson.course_type)}</h3><p class="text-sm text-gray-600">${ssrInterpolate(formatDate(lesson.scheduled_at))}</p><p class="text-sm text-gray-600">${ssrInterpolate(unref(t)("dashboard.with"))} ${ssrInterpolate(lesson.teacher_name)}</p></div><div class="text-right"><p class="font-medium text-gray-900">${ssrInterpolate(lesson.price)}€</p><span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">${ssrInterpolate(unref(t)("dashboard.confirmed"))}</span></div></div>`);
        });
        _push(`<!--]--></div>`);
      }
      _push(`</div><div class="card"><h2 class="text-lg font-semibold text-gray-900 mb-4">${ssrInterpolate(unref(t)("dashboard.quickActions"))}</h2><div class="space-y-3">`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/lessons/book",
        class: "block w-full btn-primary text-center"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`${ssrInterpolate(unref(t)("dashboard.bookLesson"))}`);
          } else {
            return [
              createTextVNode(toDisplayString(unref(t)("dashboard.bookLesson")), 1)
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/teachers",
        class: "block w-full btn-secondary text-center"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`${ssrInterpolate(unref(t)("dashboard.browseTeachers"))}`);
          } else {
            return [
              createTextVNode(toDisplayString(unref(t)("dashboard.browseTeachers")), 1)
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></div></div></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/dashboard.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
//# sourceMappingURL=dashboard-Dtc8Rq2B.js.map
