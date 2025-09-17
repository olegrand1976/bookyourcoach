import { _ as _sfc_main$1 } from "./EquestrianIcon-D77xhcCX.js";
import { _ as __nuxt_component_0 } from "./nuxt-link-CWCWeN0_.js";
import { ref, mergeProps, unref, withCtx, createVNode, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrInterpolate, ssrRenderComponent, ssrRenderList, ssrRenderClass } from "vue/server-renderer";
import { a as useAuthStore, d as useNuxtApp } from "../server.mjs";
import "/workspace/frontend/node_modules/ufo/dist/index.mjs";
import "ofetch";
import "#internal/nuxt/paths";
import "/workspace/frontend/node_modules/hookable/dist/index.mjs";
import "/workspace/frontend/node_modules/unctx/dist/index.mjs";
import "/workspace/frontend/node_modules/h3/dist/index.mjs";
import "vue-router";
import "/workspace/frontend/node_modules/radix3/dist/index.mjs";
import "/workspace/frontend/node_modules/defu/dist/defu.mjs";
import "/workspace/frontend/node_modules/nuxt/node_modules/cookie-es/dist/index.mjs";
import "/workspace/frontend/node_modules/destr/dist/index.mjs";
import "/workspace/frontend/node_modules/ohash/dist/index.mjs";
import "/workspace/frontend/node_modules/klona/dist/index.mjs";
import "/workspace/frontend/node_modules/@unhead/vue/dist/index.mjs";
const _sfc_main = {
  __name: "dashboard",
  __ssrInlineRender: true,
  setup(__props) {
    const authStore = useAuthStore();
    const { $api } = useNuxtApp();
    ref(true);
    const stats = ref({
      today_lessons: 0,
      active_students: 0,
      monthly_earnings: 0,
      average_rating: 0,
      week_lessons: 0,
      week_hours: 0,
      week_earnings: 0,
      new_students: 0
    });
    const upcomingLessons = ref([]);
    const formatDate = (dateString) => {
      const date = new Date(dateString);
      return date.toLocaleDateString("fr-FR", {
        weekday: "long",
        day: "numeric",
        month: "long",
        hour: "2-digit",
        minute: "2-digit"
      });
    };
    const getStatusClass = (status) => {
      const classes = {
        pending: "bg-yellow-100 text-yellow-800",
        confirmed: "bg-green-100 text-green-800",
        completed: "bg-blue-100 text-blue-800",
        cancelled: "bg-red-100 text-red-800"
      };
      return classes[status] || "bg-gray-100 text-gray-800";
    };
    return (_ctx, _push, _parent, _attrs) => {
      const _component_EquestrianIcon = _sfc_main$1;
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-50" }, _attrs))}><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8"><div class="mb-8"><h1 class="text-3xl font-bold text-gray-900"> Dashboard Enseignant </h1><p class="mt-2 text-gray-600"> Bonjour ${ssrInterpolate(unref(authStore).userName)}, gérez vos cours et votre planning </p></div><div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8"><div class="bg-white rounded-lg shadow p-6"><div class="flex items-center"><div class="p-2 bg-blue-100 rounded-lg">`);
      _push(ssrRenderComponent(_component_EquestrianIcon, {
        name: "helmet",
        size: 24,
        class: "text-blue-600"
      }, null, _parent));
      _push(`</div><div class="ml-4"><p class="text-sm font-medium text-gray-600">Cours aujourd&#39;hui</p><p class="text-2xl font-bold text-gray-900">${ssrInterpolate(stats.value.today_lessons)}</p></div></div></div><div class="bg-white rounded-lg shadow p-6"><div class="flex items-center"><div class="p-2 bg-green-100 rounded-lg">`);
      _push(ssrRenderComponent(_component_EquestrianIcon, {
        name: "trophy",
        size: 24,
        class: "text-green-600"
      }, null, _parent));
      _push(`</div><div class="ml-4"><p class="text-sm font-medium text-gray-600">Élèves actifs</p><p class="text-2xl font-bold text-gray-900">${ssrInterpolate(stats.value.active_students)}</p></div></div></div><div class="bg-white rounded-lg shadow p-6"><div class="flex items-center"><div class="p-2 bg-yellow-100 rounded-lg">`);
      _push(ssrRenderComponent(_component_EquestrianIcon, {
        name: "saddle",
        size: 24,
        class: "text-yellow-600"
      }, null, _parent));
      _push(`</div><div class="ml-4"><p class="text-sm font-medium text-gray-600">Revenus ce mois</p><p class="text-2xl font-bold text-gray-900">${ssrInterpolate(stats.value.monthly_earnings)}€</p></div></div></div><div class="bg-white rounded-lg shadow p-6"><div class="flex items-center"><div class="p-2 bg-purple-100 rounded-lg"><span class="text-2xl">⭐</span></div><div class="ml-4"><p class="text-sm font-medium text-gray-600">Note moyenne</p><p class="text-2xl font-bold text-gray-900">${ssrInterpolate(stats.value.average_rating)}/5</p></div></div></div></div><div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8"><div class="bg-white rounded-lg shadow"><div class="p-6 border-b border-gray-200"><h3 class="text-lg font-medium text-gray-900">Prochains cours</h3></div><div class="p-6">`);
      if (upcomingLessons.value.length > 0) {
        _push(`<div class="space-y-4"><!--[-->`);
        ssrRenderList(upcomingLessons.value, (lesson) => {
          _push(`<div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg"><div><p class="font-medium text-gray-900">${ssrInterpolate(lesson.student_name)}</p><p class="text-sm text-gray-600">${ssrInterpolate(lesson.type)} - ${ssrInterpolate(lesson.duration)}min</p><p class="text-sm text-gray-500">${ssrInterpolate(formatDate(lesson.scheduled_at))}</p></div><div class="text-right"><span class="${ssrRenderClass([getStatusClass(lesson.status), "px-2 py-1 text-xs font-semibold rounded-full"])}">${ssrInterpolate(lesson.status)}</span></div></div>`);
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<div class="text-center py-8">`);
        _push(ssrRenderComponent(_component_EquestrianIcon, {
          name: "helmet",
          size: 48,
          class: "mx-auto text-gray-400 mb-4"
        }, null, _parent));
        _push(`<p class="text-gray-500">Aucun cours planifié</p></div>`);
      }
      _push(`</div></div><div class="bg-white rounded-lg shadow"><div class="p-6 border-b border-gray-200"><h3 class="text-lg font-medium text-gray-900">Actions rapides</h3></div><div class="p-6 space-y-4">`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/teacher/schedule",
        class: "flex items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(_component_EquestrianIcon, {
              name: "helmet",
              size: 20,
              class: "text-blue-600 mr-3"
            }, null, _parent2, _scopeId));
            _push2(`<div${_scopeId}><p class="font-medium text-gray-900"${_scopeId}>Gérer mon planning</p><p class="text-sm text-gray-600"${_scopeId}>Définir mes disponibilités</p></div>`);
          } else {
            return [
              createVNode(_component_EquestrianIcon, {
                name: "helmet",
                size: 20,
                class: "text-blue-600 mr-3"
              }),
              createVNode("div", null, [
                createVNode("p", { class: "font-medium text-gray-900" }, "Gérer mon planning"),
                createVNode("p", { class: "text-sm text-gray-600" }, "Définir mes disponibilités")
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/teacher/students",
        class: "flex items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition-colors"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(_component_EquestrianIcon, {
              name: "trophy",
              size: 20,
              class: "text-green-600 mr-3"
            }, null, _parent2, _scopeId));
            _push2(`<div${_scopeId}><p class="font-medium text-gray-900"${_scopeId}>Mes élèves</p><p class="text-sm text-gray-600"${_scopeId}>Suivi et progression</p></div>`);
          } else {
            return [
              createVNode(_component_EquestrianIcon, {
                name: "trophy",
                size: 20,
                class: "text-green-600 mr-3"
              }),
              createVNode("div", null, [
                createVNode("p", { class: "font-medium text-gray-900" }, "Mes élèves"),
                createVNode("p", { class: "text-sm text-gray-600" }, "Suivi et progression")
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/teacher/earnings",
        class: "flex items-center p-4 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition-colors"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<span class="text-xl mr-3"${_scopeId}>💰</span><div${_scopeId}><p class="font-medium text-gray-900"${_scopeId}>Mes revenus</p><p class="text-sm text-gray-600"${_scopeId}>Paiements et statistiques</p></div>`);
          } else {
            return [
              createVNode("span", { class: "text-xl mr-3" }, "💰"),
              createVNode("div", null, [
                createVNode("p", { class: "font-medium text-gray-900" }, "Mes revenus"),
                createVNode("p", { class: "text-sm text-gray-600" }, "Paiements et statistiques")
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/profile",
        class: "flex items-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<span class="text-xl mr-3"${_scopeId}>👤</span><div${_scopeId}><p class="font-medium text-gray-900"${_scopeId}>Mon profil</p><p class="text-sm text-gray-600"${_scopeId}>Informations personnelles</p></div>`);
          } else {
            return [
              createVNode("span", { class: "text-xl mr-3" }, "👤"),
              createVNode("div", null, [
                createVNode("p", { class: "font-medium text-gray-900" }, "Mon profil"),
                createVNode("p", { class: "text-sm text-gray-600" }, "Informations personnelles")
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></div></div><div class="bg-white rounded-lg shadow"><div class="p-6 border-b border-gray-200"><h3 class="text-lg font-medium text-gray-900">Aperçu de la semaine</h3></div><div class="p-6"><div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4"><div class="text-center"><p class="text-2xl font-bold text-blue-600">${ssrInterpolate(stats.value.week_lessons)}</p><p class="text-sm text-gray-600">Cours cette semaine</p></div><div class="text-center"><p class="text-2xl font-bold text-green-600">${ssrInterpolate(stats.value.week_hours)}</p><p class="text-sm text-gray-600">Heures enseignées</p></div><div class="text-center"><p class="text-2xl font-bold text-yellow-600">${ssrInterpolate(stats.value.week_earnings)}€</p><p class="text-sm text-gray-600">Revenus de la semaine</p></div><div class="text-center"><p class="text-2xl font-bold text-purple-600">${ssrInterpolate(stats.value.new_students)}</p><p class="text-sm text-gray-600">Nouveaux élèves</p></div></div></div></div></div></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/teacher/dashboard.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
//# sourceMappingURL=dashboard-DRIn9F-V.js.map
