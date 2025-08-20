import { _ as __nuxt_component_0 } from './nuxt-link-BC-lyQ5x.mjs';
import { ref, mergeProps, unref, withCtx, createTextVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrInterpolate, ssrRenderComponent, ssrRenderList } from 'vue/server-renderer';
import { CalendarIcon, CheckCircleIcon, ClockIcon } from '@heroicons/vue/24/outline';
import { b as useNuxtApp } from './server.mjs';
import { u as useAuthStore } from './auth-yP0r1OGC.mjs';
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
import './ssr-B4FXEZKR.mjs';

const _sfc_main = {
  __name: "dashboard",
  __ssrInlineRender: true,
  setup(__props) {
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
      return date.toLocaleDateString("fr-FR", {
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
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" }, _attrs))}><div class="mb-8"><h1 class="text-3xl font-bold text-gray-900"> Bienvenue, ${ssrInterpolate(unref(authStore).userName)}</h1><p class="mt-2 text-gray-600"> Voici votre tableau de bord personnel </p></div><div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8"><div class="card"><div class="flex items-center"><div class="p-2 bg-primary-100 rounded-lg">`);
      _push(ssrRenderComponent(unref(CalendarIcon), { class: "w-6 h-6 text-primary-600" }, null, _parent));
      _push(`</div><div class="ml-4"><p class="text-sm font-medium text-gray-600">Prochains cours</p><p class="text-2xl font-bold text-gray-900">${ssrInterpolate(unref(stats).upcoming_lessons)}</p></div></div></div><div class="card"><div class="flex items-center"><div class="p-2 bg-green-100 rounded-lg">`);
      _push(ssrRenderComponent(unref(CheckCircleIcon), { class: "w-6 h-6 text-green-600" }, null, _parent));
      _push(`</div><div class="ml-4"><p class="text-sm font-medium text-gray-600">Cours termin\xE9s</p><p class="text-2xl font-bold text-gray-900">${ssrInterpolate(unref(stats).completed_lessons)}</p></div></div></div><div class="card"><div class="flex items-center"><div class="p-2 bg-orange-100 rounded-lg">`);
      _push(ssrRenderComponent(unref(ClockIcon), { class: "w-6 h-6 text-orange-600" }, null, _parent));
      _push(`</div><div class="ml-4"><p class="text-sm font-medium text-gray-600">Heures totales</p><p class="text-2xl font-bold text-gray-900">${ssrInterpolate(unref(stats).total_hours)}h</p></div></div></div></div><div class="grid grid-cols-1 lg:grid-cols-2 gap-8"><div class="card"><div class="flex items-center justify-between mb-4"><h2 class="text-lg font-semibold text-gray-900">Prochains cours</h2>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/lessons",
        class: "text-primary-600 hover:text-primary-700 text-sm font-medium"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Voir tous `);
          } else {
            return [
              createTextVNode(" Voir tous ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div>`);
      if (unref(upcomingLessons).length === 0) {
        _push(`<div class="text-center py-8">`);
        _push(ssrRenderComponent(unref(CalendarIcon), { class: "w-12 h-12 text-gray-400 mx-auto mb-4" }, null, _parent));
        _push(`<p class="text-gray-500">Aucun cours planifi\xE9</p>`);
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/lessons/book",
          class: "btn-primary mt-4 inline-block"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(` R\xE9server un cours `);
            } else {
              return [
                createTextVNode(" R\xE9server un cours ")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`</div>`);
      } else {
        _push(`<div class="space-y-4"><!--[-->`);
        ssrRenderList(unref(upcomingLessons), (lesson) => {
          _push(`<div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg"><div><h3 class="font-medium text-gray-900">${ssrInterpolate(lesson.course_type)}</h3><p class="text-sm text-gray-600">${ssrInterpolate(formatDate(lesson.scheduled_at))}</p><p class="text-sm text-gray-600">avec ${ssrInterpolate(lesson.teacher_name)}</p></div><div class="text-right"><p class="font-medium text-gray-900">${ssrInterpolate(lesson.price)}\u20AC</p><span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800"> Confirm\xE9 </span></div></div>`);
        });
        _push(`<!--]--></div>`);
      }
      _push(`</div><div class="card"><h2 class="text-lg font-semibold text-gray-900 mb-4">Actions rapides</h2><div class="space-y-3">`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/lessons/book",
        class: "block w-full btn-primary text-center"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` R\xE9server un nouveau cours `);
          } else {
            return [
              createTextVNode(" R\xE9server un nouveau cours ")
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
            _push2(` Parcourir les enseignants `);
          } else {
            return [
              createTextVNode(" Parcourir les enseignants ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/profile",
        class: "block w-full btn-secondary text-center"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Modifier mon profil `);
          } else {
            return [
              createTextVNode(" Modifier mon profil ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/lessons",
        class: "block w-full btn-secondary text-center"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Historique des cours `);
          } else {
            return [
              createTextVNode(" Historique des cours ")
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

export { _sfc_main as default };
//# sourceMappingURL=dashboard-CnXea0q9.mjs.map
