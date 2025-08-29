import { _ as __nuxt_component_3 } from './EquestrianIcon-DSrCvKCR.mjs';
import { _ as __nuxt_component_0 } from './nuxt-link-4z5Qc0yN.mjs';
import { ref, mergeProps, withCtx, createVNode, createTextVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderList, ssrRenderClass, ssrRenderAttr, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual } from 'vue/server-renderer';
import { d as useNuxtApp } from './server.mjs';
import './_plugin-vue_export-helper-1tPrXgE0.mjs';
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
  __name: "index",
  __ssrInlineRender: true,
  setup(__props) {
    const { $api } = useNuxtApp();
    const loading = ref(true);
    const stats = ref({
      users: 0,
      teachers: 0,
      students: 0,
      clubs: 0,
      lessons_today: 0,
      revenue_month: 0
    });
    const recentUsers = ref([]);
    const recentActivities = ref([]);
    const systemStatus = ref([
      { name: "API Backend", status: "online" },
      { name: "Base de donn\xE9es", status: "online" },
      { name: "Serveur Frontend", status: "online" },
      { name: "Service de paiement", status: "online" }
    ]);
    const showCreateUserModal = ref(false);
    const showCreateClubModal = ref(false);
    const newUser = ref({ name: "", email: "", password: "", role: "student" });
    const newClub = ref({ name: "", address: "", city: "", zip_code: "", country: "France" });
    function getRoleClass(role) {
      const classes = {
        admin: "bg-red-100 text-red-800",
        teacher: "bg-green-100 text-green-800",
        student: "bg-blue-100 text-blue-800"
      };
      return classes[role] || "bg-gray-100 text-gray-800";
    }
    function getRoleLabel(role) {
      const labels = {
        admin: "Admin",
        teacher: "Enseignant",
        student: "\xC9l\xE8ve"
      };
      return labels[role] || "N/A";
    }
    return (_ctx, _push, _parent, _attrs) => {
      const _component_EquestrianIcon = __nuxt_component_3;
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-50" }, _attrs))}><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8"><div class="mb-8"><h1 class="text-3xl font-bold text-gray-900 flex items-center">`);
      _push(ssrRenderComponent(_component_EquestrianIcon, {
        icon: "trophy",
        class: "mr-3 text-primary-600",
        size: 32
      }, null, _parent));
      _push(` Dashboard Administrateur </h1><p class="mt-2 text-gray-600">Vue d&#39;ensemble et gestion de la plateforme BookYourCoach</p></div>`);
      if (loading.value) {
        _push(`<div class="flex justify-center items-center py-12"><div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div></div>`);
      } else {
        _push(`<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8"><div class="bg-white rounded-lg shadow-lg p-6 border-l-4 border-blue-500"><div class="flex items-center">`);
        _push(ssrRenderComponent(_component_EquestrianIcon, {
          icon: "helmet",
          class: "text-blue-500 mr-3",
          size: 24
        }, null, _parent));
        _push(`<div><h3 class="text-lg font-semibold text-gray-900">Utilisateurs</h3><p class="text-3xl font-bold text-blue-600">${ssrInterpolate(stats.value.users)}</p><p class="text-sm text-gray-500">Total inscrits</p></div></div></div><div class="bg-white rounded-lg shadow-lg p-6 border-l-4 border-green-500"><div class="flex items-center">`);
        _push(ssrRenderComponent(_component_EquestrianIcon, {
          icon: "saddle",
          class: "text-green-500 mr-3",
          size: 24
        }, null, _parent));
        _push(`<div><h3 class="text-lg font-semibold text-gray-900">Enseignants</h3><p class="text-3xl font-bold text-green-600">${ssrInterpolate(stats.value.teachers)}</p><p class="text-sm text-gray-500">Coaches actifs</p></div></div></div><div class="bg-white rounded-lg shadow-lg p-6 border-l-4 border-purple-500"><div class="flex items-center">`);
        _push(ssrRenderComponent(_component_EquestrianIcon, {
          icon: "horseshoe",
          class: "text-purple-500 mr-3",
          size: 24
        }, null, _parent));
        _push(`<div><h3 class="text-lg font-semibold text-gray-900">\xC9l\xE8ves</h3><p class="text-3xl font-bold text-purple-600">${ssrInterpolate(stats.value.students)}</p><p class="text-sm text-gray-500">Apprenants</p></div></div></div><div class="bg-white rounded-lg shadow-lg p-6 border-l-4 border-orange-500"><div class="flex items-center">`);
        _push(ssrRenderComponent(_component_EquestrianIcon, {
          icon: "horse",
          class: "text-orange-500 mr-3",
          size: 24
        }, null, _parent));
        _push(`<div><h3 class="text-lg font-semibold text-gray-900">Clubs</h3><p class="text-3xl font-bold text-orange-600">${ssrInterpolate(stats.value.clubs)}</p><p class="text-sm text-gray-500">Centres \xE9questres</p></div></div></div></div>`);
      }
      _push(`<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8"><div class="bg-white rounded-lg shadow-lg p-6"><h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">`);
      _push(ssrRenderComponent(_component_EquestrianIcon, {
        icon: "trophy",
        class: "mr-2 text-primary-600",
        size: 20
      }, null, _parent));
      _push(` Actions rapides </h2><div class="grid grid-cols-1 md:grid-cols-2 gap-4"><button class="btn-primary flex items-center justify-center">`);
      _push(ssrRenderComponent(_component_EquestrianIcon, {
        icon: "helmet",
        class: "mr-2",
        size: 16
      }, null, _parent));
      _push(` Cr\xE9er un utilisateur </button><button class="btn-secondary flex items-center justify-center">`);
      _push(ssrRenderComponent(_component_EquestrianIcon, {
        icon: "horse",
        class: "mr-2",
        size: 16
      }, null, _parent));
      _push(` Cr\xE9er un club </button>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/admin/users",
        class: "btn-outline flex items-center justify-center"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(_component_EquestrianIcon, {
              icon: "saddle",
              class: "mr-2",
              size: 16
            }, null, _parent2, _scopeId));
            _push2(` G\xE9rer les utilisateurs `);
          } else {
            return [
              createVNode(_component_EquestrianIcon, {
                icon: "saddle",
                class: "mr-2",
                size: 16
              }),
              createTextVNode(" G\xE9rer les utilisateurs ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/admin/settings",
        class: "btn-outline flex items-center justify-center"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(_component_EquestrianIcon, {
              icon: "horseshoe",
              class: "mr-2",
              size: 16
            }, null, _parent2, _scopeId));
            _push2(` Param\xE8tres syst\xE8me `);
          } else {
            return [
              createVNode(_component_EquestrianIcon, {
                icon: "horseshoe",
                class: "mr-2",
                size: 16
              }),
              createTextVNode(" Param\xE8tres syst\xE8me ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></div><div class="bg-white rounded-lg shadow-lg p-6"><h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">`);
      _push(ssrRenderComponent(_component_EquestrianIcon, {
        icon: "horseshoe",
        class: "mr-2 text-primary-600",
        size: 20
      }, null, _parent));
      _push(` Activit\xE9 r\xE9cente </h2><div class="space-y-3"><!--[-->`);
      ssrRenderList(recentActivities.value, (activity) => {
        _push(`<div class="flex items-center p-3 bg-gray-50 rounded-lg"><div class="flex-shrink-0">`);
        _push(ssrRenderComponent(_component_EquestrianIcon, {
          icon: activity.icon,
          class: "text-primary-600",
          size: 16
        }, null, _parent));
        _push(`</div><div class="ml-3 flex-1"><p class="text-sm font-medium text-gray-900">${ssrInterpolate(activity.message)}</p><p class="text-xs text-gray-500">${ssrInterpolate(activity.time)}</p></div></div>`);
      });
      _push(`<!--]--></div></div></div><div class="grid grid-cols-1 lg:grid-cols-2 gap-8"><div class="bg-white rounded-lg shadow-lg p-6"><h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">`);
      _push(ssrRenderComponent(_component_EquestrianIcon, {
        icon: "helmet",
        class: "mr-2 text-primary-600",
        size: 20
      }, null, _parent));
      _push(` Derniers utilisateurs </h2><div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200"><thead><tr><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"> Nom</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"> R\xF4le</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"> Statut </th></tr></thead><tbody class="divide-y divide-gray-200"><!--[-->`);
      ssrRenderList(recentUsers.value, (user) => {
        _push(`<tr><td class="px-4 py-3 text-sm font-medium text-gray-900">${ssrInterpolate(user.name)}</td><td class="px-4 py-3 text-sm text-gray-500"><span class="${ssrRenderClass([getRoleClass(user.role), "px-2 py-1 text-xs font-semibold rounded-full"])}">${ssrInterpolate(getRoleLabel(user.role))}</span></td><td class="px-4 py-3 text-sm text-gray-500"><span class="${ssrRenderClass([user.is_active ? "text-green-600" : "text-red-600", "flex items-center"])}"><div class="${ssrRenderClass([user.is_active ? "bg-green-400" : "bg-red-400", "w-2 h-2 rounded-full mr-2"])}"></div> ${ssrInterpolate(user.is_active ? "Actif" : "Inactif")}</span></td></tr>`);
      });
      _push(`<!--]--></tbody></table></div><div class="mt-4">`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/admin/users",
        class: "text-primary-600 hover:text-primary-500 text-sm font-medium"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Voir tous les utilisateurs \u2192 `);
          } else {
            return [
              createTextVNode(" Voir tous les utilisateurs \u2192 ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></div><div class="bg-white rounded-lg shadow-lg p-6"><h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">`);
      _push(ssrRenderComponent(_component_EquestrianIcon, {
        icon: "trophy",
        class: "mr-2 text-primary-600",
        size: 20
      }, null, _parent));
      _push(` \xC9tat du syst\xE8me </h2><div class="space-y-4"><!--[-->`);
      ssrRenderList(systemStatus.value, (status) => {
        _push(`<div class="flex items-center justify-between"><span class="text-sm font-medium text-gray-900">${ssrInterpolate(status.name)}</span><span class="${ssrRenderClass([status.status === "online" ? "text-green-600 bg-green-100" : "text-red-600 bg-red-100", "px-2 py-1 text-xs font-semibold rounded-full"])}">${ssrInterpolate(status.status === "online" ? "En ligne" : "Hors ligne")}</span></div>`);
      });
      _push(`<!--]--></div></div></div></div>`);
      if (showCreateUserModal.value) {
        _push(`<div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"><div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white"><div class="mt-3"><h3 class="text-lg font-medium text-gray-900 mb-4">Cr\xE9er un nouvel utilisateur</h3><form class="space-y-4"><div><label class="block text-sm font-medium text-gray-700">Nom</label><input${ssrRenderAttr("value", newUser.value.name)} type="text" class="input-field" required></div><div><label class="block text-sm font-medium text-gray-700">Email</label><input${ssrRenderAttr("value", newUser.value.email)} type="email" class="input-field" required></div><div><label class="block text-sm font-medium text-gray-700">R\xF4le</label><select class="input-field" required><option value="student"${ssrIncludeBooleanAttr(Array.isArray(newUser.value.role) ? ssrLooseContain(newUser.value.role, "student") : ssrLooseEqual(newUser.value.role, "student")) ? " selected" : ""}>\xC9l\xE8ve</option><option value="teacher"${ssrIncludeBooleanAttr(Array.isArray(newUser.value.role) ? ssrLooseContain(newUser.value.role, "teacher") : ssrLooseEqual(newUser.value.role, "teacher")) ? " selected" : ""}>Enseignant</option><option value="admin"${ssrIncludeBooleanAttr(Array.isArray(newUser.value.role) ? ssrLooseContain(newUser.value.role, "admin") : ssrLooseEqual(newUser.value.role, "admin")) ? " selected" : ""}>Administrateur</option></select></div><div><label class="block text-sm font-medium text-gray-700">Mot de passe</label><input${ssrRenderAttr("value", newUser.value.password)} type="password" class="input-field" required></div><div><label class="block text-sm font-medium text-gray-700">Confirmer le mot de passe</label><input${ssrRenderAttr("value", newUser.value.password_confirmation)} type="password" class="input-field" required></div><div class="flex justify-end space-x-3"><button type="button" class="btn-outline">Annuler</button><button type="submit" class="btn-primary">Cr\xE9er</button></div></form></div></div></div>`);
      } else {
        _push(`<!---->`);
      }
      if (showCreateClubModal.value) {
        _push(`<div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"><div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white"><div class="mt-3"><h3 class="text-lg font-medium text-gray-900 mb-4">Cr\xE9er un nouveau club</h3><form class="space-y-4"><div><label class="block text-sm font-medium text-gray-700">Nom du club</label><input${ssrRenderAttr("value", newClub.value.name)} type="text" class="input-field" required></div><div><label class="block text-sm font-medium text-gray-700">Email</label><input${ssrRenderAttr("value", newClub.value.email)} type="email" class="input-field" required></div><div><label class="block text-sm font-medium text-gray-700">T\xE9l\xE9phone</label><input${ssrRenderAttr("value", newClub.value.phone)} type="tel" class="input-field"></div><div><label class="block text-sm font-medium text-gray-700">Adresse</label><textarea class="input-field" rows="3">${ssrInterpolate(newClub.value.address)}</textarea></div><div class="flex justify-end space-x-3"><button type="button" class="btn-outline">Annuler</button><button type="submit" class="btn-primary">Cr\xE9er</button></div></form></div></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/admin/index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=index-2C0cKAAO.mjs.map
