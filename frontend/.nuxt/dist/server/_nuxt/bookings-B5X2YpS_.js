import { _ as __nuxt_component_0 } from "./nuxt-link-3RFQ5DMr.js";
import { defineComponent, ref, computed, mergeProps, withCtx, createTextVNode, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrRenderList, ssrRenderClass, ssrInterpolate, ssrRenderComponent } from "vue/server-renderer";
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
  __name: "bookings",
  __ssrInlineRender: true,
  setup(__props) {
    const bookings2 = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const selectedStatus = ref("all");
    const statusOptions = [
      { value: "all", label: "Toutes" },
      { value: "pending", label: "En attente" },
      { value: "confirmed", label: "Confirmées" },
      { value: "completed", label: "Terminées" },
      { value: "cancelled", label: "Annulées" }
    ];
    const filteredBookings = computed(() => {
      if (selectedStatus.value === "all") {
        return bookings2.value;
      }
      return bookings2.value.filter((booking) => booking.status === selectedStatus.value);
    });
    const canCancel = (booking) => {
      var _a;
      return ["pending", "confirmed"].includes(booking.status) && new Date((_a = booking.lesson) == null ? void 0 : _a.start_time) > /* @__PURE__ */ new Date();
    };
    const canRate = (booking) => {
      return booking.status === "completed" && !booking.rating;
    };
    const getStatusClass = (status) => {
      switch (status) {
        case "pending":
          return "bg-yellow-100 text-yellow-800";
        case "confirmed":
          return "bg-blue-100 text-blue-800";
        case "completed":
          return "bg-green-100 text-green-800";
        case "cancelled":
          return "bg-red-100 text-red-800";
        default:
          return "bg-gray-100 text-gray-800";
      }
    };
    const getStatusText = (status) => {
      switch (status) {
        case "pending":
          return "En attente";
        case "confirmed":
          return "Confirmée";
        case "completed":
          return "Terminée";
        case "cancelled":
          return "Annulée";
        default:
          return status;
      }
    };
    const formatDate = (dateString) => {
      const date = new Date(dateString);
      return date.toLocaleDateString("fr-FR", {
        weekday: "long",
        day: "numeric",
        month: "long"
      });
    };
    const formatTime = (dateString) => {
      const date = new Date(dateString);
      return date.toLocaleTimeString("fr-FR", {
        hour: "2-digit",
        minute: "2-digit"
      });
    };
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bookings-page" }, _attrs))} data-v-1c22a316><div class="container mx-auto px-4 py-8" data-v-1c22a316><div class="mb-8" data-v-1c22a316><h1 class="text-3xl font-bold text-gray-900 mb-2" data-v-1c22a316> Mes Réservations </h1><p class="text-gray-600" data-v-1c22a316> Gérez vos cours réservés et votre planning </p></div><div class="bg-white rounded-lg shadow-md p-6 mb-8" data-v-1c22a316><h2 class="text-lg font-semibold text-gray-900 mb-4" data-v-1c22a316>Filtrer par statut</h2><div class="flex flex-wrap gap-2" data-v-1c22a316><!--[-->`);
      ssrRenderList(statusOptions, (status) => {
        _push(`<button class="${ssrRenderClass([
          "px-4 py-2 rounded-md text-sm font-medium transition-colors",
          selectedStatus.value === status.value ? "bg-blue-600 text-white" : "bg-gray-100 text-gray-700 hover:bg-gray-200"
        ])}" data-v-1c22a316>${ssrInterpolate(status.label)}</button>`);
      });
      _push(`<!--]--></div></div>`);
      if (loading.value) {
        _push(`<div class="flex justify-center items-center py-12" data-v-1c22a316><div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600" data-v-1c22a316></div></div>`);
      } else if (error.value) {
        _push(`<div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-6" data-v-1c22a316><div class="flex items-center" data-v-1c22a316><div class="flex-shrink-0" data-v-1c22a316><svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor" data-v-1c22a316><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" data-v-1c22a316></path></svg></div><div class="ml-3" data-v-1c22a316><h3 class="text-sm font-medium text-red-800" data-v-1c22a316>Erreur</h3><div class="mt-2 text-sm text-red-700" data-v-1c22a316>${ssrInterpolate(error.value)}</div></div></div></div>`);
      } else {
        _push(`<div class="space-y-6" data-v-1c22a316><!--[-->`);
        ssrRenderList(filteredBookings.value, (booking) => {
          var _a, _b, _c, _d, _e, _f, _g, _h, _i, _j, _k;
          _push(`<div class="bg-white rounded-lg shadow-md border border-gray-200" data-v-1c22a316><div class="p-6" data-v-1c22a316><div class="flex items-start justify-between mb-4" data-v-1c22a316><div data-v-1c22a316><h3 class="text-lg font-semibold text-gray-900 mb-1" data-v-1c22a316>${ssrInterpolate(((_a = booking.lesson) == null ? void 0 : _a.title) || "Leçon")}</h3><p class="text-sm text-gray-600" data-v-1c22a316>${ssrInterpolate(((_c = (_b = booking.lesson) == null ? void 0 : _b.course_type) == null ? void 0 : _c.name) || "Type non spécifié")}</p></div><span class="${ssrRenderClass([
            "px-3 py-1 text-sm font-medium rounded-full",
            getStatusClass(booking.status)
          ])}" data-v-1c22a316>${ssrInterpolate(getStatusText(booking.status))}</span></div><div class="flex items-center mb-4" data-v-1c22a316><div class="flex-shrink-0" data-v-1c22a316><div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center" data-v-1c22a316><svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" data-v-1c22a316><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" data-v-1c22a316></path></svg></div></div><div class="ml-3" data-v-1c22a316><p class="text-sm font-medium text-gray-900" data-v-1c22a316>${ssrInterpolate(((_f = (_e = (_d = booking.lesson) == null ? void 0 : _d.teacher) == null ? void 0 : _e.user) == null ? void 0 : _f.name) || "Enseignant")}</p><p class="text-xs text-gray-500" data-v-1c22a316>Enseignant</p></div></div><div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4" data-v-1c22a316><div class="flex items-center text-sm text-gray-600" data-v-1c22a316><svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" data-v-1c22a316><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" data-v-1c22a316></path></svg><div data-v-1c22a316><p class="font-medium" data-v-1c22a316>${ssrInterpolate(formatDate((_g = booking.lesson) == null ? void 0 : _g.start_time))}</p><p class="text-xs" data-v-1c22a316>${ssrInterpolate(formatTime((_h = booking.lesson) == null ? void 0 : _h.start_time))} - ${ssrInterpolate(formatTime((_i = booking.lesson) == null ? void 0 : _i.end_time))}</p></div></div><div class="flex items-center text-sm text-gray-600" data-v-1c22a316><svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" data-v-1c22a316><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" data-v-1c22a316></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" data-v-1c22a316></path></svg><div data-v-1c22a316><p class="font-medium" data-v-1c22a316>${ssrInterpolate(((_k = (_j = booking.lesson) == null ? void 0 : _j.location) == null ? void 0 : _k.name) || "Lieu non spécifié")}</p></div></div><div class="flex items-center text-sm text-gray-600" data-v-1c22a316><svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" data-v-1c22a316><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" data-v-1c22a316></path></svg><div data-v-1c22a316><p class="font-medium" data-v-1c22a316>${ssrInterpolate(booking.price ? `${booking.price}€` : "Prix non spécifié")}</p></div></div></div>`);
          if (booking.notes) {
            _push(`<div class="mb-4" data-v-1c22a316><h4 class="text-sm font-medium text-gray-900 mb-1" data-v-1c22a316>Notes</h4><p class="text-sm text-gray-600 bg-gray-50 p-3 rounded-md" data-v-1c22a316>${ssrInterpolate(booking.notes)}</p></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`<div class="flex space-x-2" data-v-1c22a316>`);
          if (canCancel(booking)) {
            _push(`<button class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors text-sm font-medium" data-v-1c22a316> Annuler </button>`);
          } else {
            _push(`<!---->`);
          }
          if (canRate(booking)) {
            _push(`<button class="px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700 transition-colors text-sm font-medium" data-v-1c22a316> Noter </button>`);
          } else {
            _push(`<!---->`);
          }
          _push(`<button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors text-sm font-medium" data-v-1c22a316> Détails </button></div></div></div>`);
        });
        _push(`<!--]-->`);
        if (filteredBookings.value.length === 0) {
          _push(`<div class="text-center py-12" data-v-1c22a316><svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" data-v-1c22a316><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" data-v-1c22a316></path></svg><h3 class="mt-2 text-sm font-medium text-gray-900" data-v-1c22a316>Aucune réservation</h3><p class="mt-1 text-sm text-gray-500" data-v-1c22a316>${ssrInterpolate(selectedStatus.value === "all" ? "Vous n'avez pas encore de réservations." : "Aucune réservation avec ce statut.")}</p>`);
          if (selectedStatus.value === "all") {
            _push(`<div class="mt-4" data-v-1c22a316>`);
            _push(ssrRenderComponent(_component_NuxtLink, {
              to: "/student/lessons",
              class: "inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
            }, {
              default: withCtx((_, _push2, _parent2, _scopeId) => {
                if (_push2) {
                  _push2(` Voir les leçons disponibles `);
                } else {
                  return [
                    createTextVNode(" Voir les leçons disponibles ")
                  ];
                }
              }),
              _: 1
            }, _parent));
            _push(`</div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
      }
      _push(`</div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/student/bookings.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const bookings = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-1c22a316"]]);
export {
  bookings as default
};
//# sourceMappingURL=bookings-B5X2YpS_.js.map
