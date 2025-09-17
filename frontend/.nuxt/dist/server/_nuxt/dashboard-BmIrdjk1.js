import { _ as __nuxt_component_0 } from "./nuxt-link-3RFQ5DMr.js";
import { defineComponent, ref, mergeProps, withCtx, createVNode, createBlock, openBlock, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrInterpolate, ssrRenderComponent, ssrRenderList } from "vue/server-renderer";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/hookable/dist/index.mjs";
import { _ as _export_sfc } from "./_plugin-vue_export-helper-1tPrXgE0.js";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/ufo/dist/index.mjs";
import "../server.mjs";
import "ofetch";
import "#internal/nuxt/paths";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/unctx/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/h3/dist/index.mjs";
import "vue-router";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/radix3/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/defu/dist/defu.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/nuxt/node_modules/cookie-es/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/destr/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/ohash/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/klona/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/@unhead/vue/dist/index.mjs";
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "dashboard",
  __ssrInlineRender: true,
  setup(__props) {
    const stats = ref({
      availableLessons: 0,
      activeBookings: 0,
      completedLessons: 0,
      favoriteTeachers: 0
    });
    const recentActivity = ref([]);
    const formatDate = (dateString) => {
      const date = new Date(dateString);
      return date.toLocaleDateString("fr-FR", {
        day: "numeric",
        month: "short",
        hour: "2-digit",
        minute: "2-digit"
      });
    };
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "student-dashboard" }, _attrs))} data-v-a4b11012><div class="container mx-auto px-4 py-8" data-v-a4b11012><div class="mb-8" data-v-a4b11012><h1 class="text-3xl font-bold text-gray-900 mb-2" data-v-a4b11012> Tableau de Bord Étudiant </h1><p class="text-gray-600" data-v-a4b11012> Gérez vos leçons, réservations et préférences </p></div><div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8" data-v-a4b11012><div class="bg-white rounded-lg shadow-md p-6" data-v-a4b11012><div class="flex items-center" data-v-a4b11012><div class="flex-shrink-0" data-v-a4b11012><div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center" data-v-a4b11012><svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" data-v-a4b11012><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" data-v-a4b11012></path></svg></div></div><div class="ml-4" data-v-a4b11012><p class="text-sm font-medium text-gray-500" data-v-a4b11012>Leçons disponibles</p><p class="text-2xl font-semibold text-gray-900" data-v-a4b11012>${ssrInterpolate(stats.value.availableLessons || 0)}</p></div></div></div><div class="bg-white rounded-lg shadow-md p-6" data-v-a4b11012><div class="flex items-center" data-v-a4b11012><div class="flex-shrink-0" data-v-a4b11012><div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center" data-v-a4b11012><svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" data-v-a4b11012><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" data-v-a4b11012></path></svg></div></div><div class="ml-4" data-v-a4b11012><p class="text-sm font-medium text-gray-500" data-v-a4b11012>Réservations actives</p><p class="text-2xl font-semibold text-gray-900" data-v-a4b11012>${ssrInterpolate(stats.value.activeBookings || 0)}</p></div></div></div><div class="bg-white rounded-lg shadow-md p-6" data-v-a4b11012><div class="flex items-center" data-v-a4b11012><div class="flex-shrink-0" data-v-a4b11012><div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center" data-v-a4b11012><svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" data-v-a4b11012><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" data-v-a4b11012></path></svg></div></div><div class="ml-4" data-v-a4b11012><p class="text-sm font-medium text-gray-500" data-v-a4b11012>Leçons terminées</p><p class="text-2xl font-semibold text-gray-900" data-v-a4b11012>${ssrInterpolate(stats.value.completedLessons || 0)}</p></div></div></div><div class="bg-white rounded-lg shadow-md p-6" data-v-a4b11012><div class="flex items-center" data-v-a4b11012><div class="flex-shrink-0" data-v-a4b11012><div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center" data-v-a4b11012><svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" data-v-a4b11012><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" data-v-a4b11012></path></svg></div></div><div class="ml-4" data-v-a4b11012><p class="text-sm font-medium text-gray-500" data-v-a4b11012>Enseignants favoris</p><p class="text-2xl font-semibold text-gray-900" data-v-a4b11012>${ssrInterpolate(stats.value.favoriteTeachers || 0)}</p></div></div></div></div><div class="bg-white rounded-lg shadow-md p-6 mb-8" data-v-a4b11012><h2 class="text-lg font-semibold text-gray-900 mb-4" data-v-a4b11012>Actions Rapides</h2><div class="grid grid-cols-1 md:grid-cols-3 gap-4" data-v-a4b11012>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/student/lessons",
        class: "flex items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="flex-shrink-0" data-v-a4b11012${_scopeId}><svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" data-v-a4b11012${_scopeId}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" data-v-a4b11012${_scopeId}></path></svg></div><div class="ml-3" data-v-a4b11012${_scopeId}><p class="text-sm font-medium text-blue-900" data-v-a4b11012${_scopeId}>Voir les leçons</p><p class="text-xs text-blue-700" data-v-a4b11012${_scopeId}>Découvrir les cours disponibles</p></div>`);
          } else {
            return [
              createVNode("div", { class: "flex-shrink-0" }, [
                (openBlock(), createBlock("svg", {
                  class: "w-6 h-6 text-blue-600",
                  fill: "none",
                  stroke: "currentColor",
                  viewBox: "0 0 24 24"
                }, [
                  createVNode("path", {
                    "stroke-linecap": "round",
                    "stroke-linejoin": "round",
                    "stroke-width": "2",
                    d: "M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                  })
                ]))
              ]),
              createVNode("div", { class: "ml-3" }, [
                createVNode("p", { class: "text-sm font-medium text-blue-900" }, "Voir les leçons"),
                createVNode("p", { class: "text-xs text-blue-700" }, "Découvrir les cours disponibles")
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/student/bookings",
        class: "flex items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition-colors"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="flex-shrink-0" data-v-a4b11012${_scopeId}><svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" data-v-a4b11012${_scopeId}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" data-v-a4b11012${_scopeId}></path></svg></div><div class="ml-3" data-v-a4b11012${_scopeId}><p class="text-sm font-medium text-green-900" data-v-a4b11012${_scopeId}>Mes réservations</p><p class="text-xs text-green-700" data-v-a4b11012${_scopeId}>Gérer mes cours réservés</p></div>`);
          } else {
            return [
              createVNode("div", { class: "flex-shrink-0" }, [
                (openBlock(), createBlock("svg", {
                  class: "w-6 h-6 text-green-600",
                  fill: "none",
                  stroke: "currentColor",
                  viewBox: "0 0 24 24"
                }, [
                  createVNode("path", {
                    "stroke-linecap": "round",
                    "stroke-linejoin": "round",
                    "stroke-width": "2",
                    d: "M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                  })
                ]))
              ]),
              createVNode("div", { class: "ml-3" }, [
                createVNode("p", { class: "text-sm font-medium text-green-900" }, "Mes réservations"),
                createVNode("p", { class: "text-xs text-green-700" }, "Gérer mes cours réservés")
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/student/preferences",
        class: "flex items-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="flex-shrink-0" data-v-a4b11012${_scopeId}><svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" data-v-a4b11012${_scopeId}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" data-v-a4b11012${_scopeId}></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" data-v-a4b11012${_scopeId}></path></svg></div><div class="ml-3" data-v-a4b11012${_scopeId}><p class="text-sm font-medium text-purple-900" data-v-a4b11012${_scopeId}>Mes préférences</p><p class="text-xs text-purple-700" data-v-a4b11012${_scopeId}>Personnaliser mes choix</p></div>`);
          } else {
            return [
              createVNode("div", { class: "flex-shrink-0" }, [
                (openBlock(), createBlock("svg", {
                  class: "w-6 h-6 text-purple-600",
                  fill: "none",
                  stroke: "currentColor",
                  viewBox: "0 0 24 24"
                }, [
                  createVNode("path", {
                    "stroke-linecap": "round",
                    "stroke-linejoin": "round",
                    "stroke-width": "2",
                    d: "M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                  }),
                  createVNode("path", {
                    "stroke-linecap": "round",
                    "stroke-linejoin": "round",
                    "stroke-width": "2",
                    d: "M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                  })
                ]))
              ]),
              createVNode("div", { class: "ml-3" }, [
                createVNode("p", { class: "text-sm font-medium text-purple-900" }, "Mes préférences"),
                createVNode("p", { class: "text-xs text-purple-700" }, "Personnaliser mes choix")
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></div><div class="bg-white rounded-lg shadow-md p-6" data-v-a4b11012><h2 class="text-lg font-semibold text-gray-900 mb-4" data-v-a4b11012>Activité récente</h2>`);
      if (recentActivity.value.length === 0) {
        _push(`<div class="text-center py-8" data-v-a4b11012><svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" data-v-a4b11012><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" data-v-a4b11012></path></svg><h3 class="mt-2 text-sm font-medium text-gray-900" data-v-a4b11012>Aucune activité récente</h3><p class="mt-1 text-sm text-gray-500" data-v-a4b11012>Vos activités apparaîtront ici.</p></div>`);
      } else {
        _push(`<div class="space-y-3" data-v-a4b11012><!--[-->`);
        ssrRenderList(recentActivity.value, (activity) => {
          _push(`<div class="flex items-center p-3 bg-gray-50 rounded-lg" data-v-a4b11012><div class="flex-shrink-0" data-v-a4b11012><div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center" data-v-a4b11012><svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" data-v-a4b11012><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" data-v-a4b11012></path></svg></div></div><div class="ml-3 flex-1" data-v-a4b11012><p class="text-sm font-medium text-gray-900" data-v-a4b11012>${ssrInterpolate(activity.title)}</p><p class="text-xs text-gray-500" data-v-a4b11012>${ssrInterpolate(activity.description)}</p></div><div class="flex-shrink-0 text-xs text-gray-400" data-v-a4b11012>${ssrInterpolate(formatDate(activity.date))}</div></div>`);
        });
        _push(`<!--]--></div>`);
      }
      _push(`</div></div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/student/dashboard.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const dashboard = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-a4b11012"]]);
export {
  dashboard as default
};
//# sourceMappingURL=dashboard-BmIrdjk1.js.map
