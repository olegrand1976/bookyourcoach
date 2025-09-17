import { _ as __nuxt_component_0 } from "./nuxt-link-3RFQ5DMr.js";
import { ref, mergeProps, withCtx, createTextVNode, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrInterpolate, ssrRenderComponent, ssrRenderList, ssrRenderClass, ssrRenderAttr, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual } from "vue/server-renderer";
import { d as useNuxtApp } from "../server.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/ufo/dist/index.mjs";
import "ofetch";
import "#internal/nuxt/paths";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/hookable/dist/index.mjs";
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
      clubs: 0
    });
    const recentUsers = ref([]);
    const systemStatus = ref([
      { name: "API Backend", status: "online" },
      { name: "Base de données", status: "online" },
      { name: "Serveur Frontend", status: "online" }
    ]);
    const showCreateUserModal = ref(false);
    const showCreateClubModal = ref(false);
    const newUser = ref({
      first_name: "",
      last_name: "",
      email: "",
      phone: "",
      birth_date: "",
      street: "",
      street_number: "",
      postal_code: "",
      city: "",
      country: "Belgium",
      role: "student",
      password: "",
      password_confirmation: ""
    });
    const newClub = ref({
      name: "",
      email: "",
      phone: "",
      street: "",
      street_number: "",
      street_box: "",
      postal_code: "",
      city: "",
      country: "France",
      description: "",
      website: ""
    });
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
        student: "Élève"
      };
      return labels[role] || "N/A";
    }
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "p-8" }, _attrs))}><h1 class="text-3xl font-bold text-gray-900 mb-2">Dashboard Administrateur</h1><p class="text-gray-600 mb-8">Vue d&#39;ensemble et gestion de la plateforme Acti&#39;Vibe.</p>`);
      if (loading.value) {
        _push(`<div class="text-center py-12"><div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div><p class="mt-4 text-gray-500">Chargement des données...</p></div>`);
      } else {
        _push(`<div class="space-y-8"><div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6"><div class="bg-white p-6 rounded-2xl shadow-lg border border-gray-200 flex items-center space-x-4"><div class="bg-blue-100 p-3 rounded-xl"><svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M15 21a6 6 0 00-9-5.197m0 0A5.995 5.995 0 0112 13a5.995 5.995 0 013 5.197M15 21a6 6 0 00-9-5.197"></path></svg></div><div><p class="text-sm text-gray-500">Utilisateurs</p><p class="text-2xl font-bold text-gray-800">${ssrInterpolate(stats.value.users)}</p></div></div><div class="bg-white p-6 rounded-2xl shadow-lg border border-gray-200 flex items-center space-x-4"><div class="bg-green-100 p-3 rounded-xl"><svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg></div><div><p class="text-sm text-gray-500">Enseignants</p><p class="text-2xl font-bold text-gray-800">${ssrInterpolate(stats.value.teachers)}</p></div></div><div class="bg-white p-6 rounded-2xl shadow-lg border border-gray-200 flex items-center space-x-4"><div class="bg-indigo-100 p-3 rounded-xl"><svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 14l9-5-9-5-9 5 9 5z"></path><path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-5.998 12.078 12.078 0 01.665-6.479L12 14z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-5.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222 4 2.222V20M1 14.55v-7.5l4-2.222m0 16.444v-7.5l-4-2.222"></path></svg></div><div><p class="text-sm text-gray-500">Élèves</p><p class="text-2xl font-bold text-gray-800">${ssrInterpolate(stats.value.students)}</p></div></div><div class="bg-white p-6 rounded-2xl shadow-lg border border-gray-200 flex items-center space-x-4"><div class="bg-orange-100 p-3 rounded-xl"><svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg></div><div><p class="text-sm text-gray-500">Clubs</p><p class="text-2xl font-bold text-gray-800">${ssrInterpolate(stats.value.clubs)}</p></div></div></div><div class="grid grid-cols-1 lg:grid-cols-3 gap-8"><div class="lg:col-span-2 bg-white p-8 rounded-2xl shadow-lg border border-gray-200"><div class="flex justify-between items-center mb-6"><h2 class="text-xl font-bold text-gray-800">Derniers utilisateurs inscrits</h2>`);
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/admin/users",
          class: "text-sm font-medium text-blue-600 hover:text-blue-800"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(` Voir tout → `);
            } else {
              return [
                createTextVNode(" Voir tout → ")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`</div><div class="overflow-x-auto"><table class="min-w-full"><thead class="border-b border-gray-200"><tr><th class="py-3 text-left text-xs font-medium text-gray-500 uppercase">Nom</th><th class="py-3 text-left text-xs font-medium text-gray-500 uppercase">Rôle</th><th class="py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th></tr></thead><tbody><!--[-->`);
        ssrRenderList(recentUsers.value, (user) => {
          _push(`<tr class="border-b border-gray-100"><td class="py-4 text-sm font-medium text-gray-900">${ssrInterpolate(user.name)}</td><td class="py-4 text-sm"><span class="${ssrRenderClass([getRoleClass(user.role), "px-2 py-1 text-xs font-semibold rounded-full"])}">${ssrInterpolate(getRoleLabel(user.role))}</span></td><td class="py-4 text-sm"><span class="${ssrRenderClass([user.is_active ? "text-green-600" : "text-red-600", "flex items-center"])}"><div class="${ssrRenderClass([user.is_active ? "bg-green-400" : "bg-red-400", "w-2 h-2 rounded-full mr-2"])}"></div> ${ssrInterpolate(user.is_active ? "Actif" : "Inactif")}</span></td></tr>`);
        });
        _push(`<!--]--></tbody></table></div></div><div class="space-y-8"><div class="bg-white p-8 rounded-2xl shadow-lg border border-gray-200"><h2 class="text-xl font-bold text-gray-800 mb-6">État du système</h2><div class="space-y-4"><!--[-->`);
        ssrRenderList(systemStatus.value, (status) => {
          _push(`<div class="flex items-center justify-between"><span class="text-sm font-medium text-gray-700">${ssrInterpolate(status.name)}</span><span class="${ssrRenderClass([status.status === "online" ? "text-green-700 bg-green-100" : "text-red-700 bg-red-100", "px-2.5 py-1 text-xs font-semibold rounded-full"])}">${ssrInterpolate(status.status === "online" ? "En ligne" : "Hors ligne")}</span></div>`);
        });
        _push(`<!--]--></div></div><div class="bg-white p-8 rounded-2xl shadow-lg border border-gray-200"><h2 class="text-xl font-bold text-gray-800 mb-6">Actions rapides</h2><div class="space-y-3"><button class="w-full flex items-center justify-center px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"><svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg> Créer un utilisateur </button><button class="w-full flex items-center justify-center px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"><svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg> Créer un club </button></div></div></div></div></div>`);
      }
      if (showCreateUserModal.value) {
        _push(`<div class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center z-50 p-4"><div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto"><div class="p-8"><h3 class="text-xl font-bold text-gray-800 mb-6">Créer un nouvel utilisateur</h3><form class="space-y-6"><div class="grid grid-cols-1 md:grid-cols-2 gap-4"><div><label class="block text-sm font-medium text-gray-700">Prénom *</label><input${ssrRenderAttr("value", newUser.value.first_name)} type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required></div><div><label class="block text-sm font-medium text-gray-700">Nom *</label><input${ssrRenderAttr("value", newUser.value.last_name)} type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required></div></div><div class="grid grid-cols-1 md:grid-cols-2 gap-4"><div><label class="block text-sm font-medium text-gray-700">Email *</label><input${ssrRenderAttr("value", newUser.value.email)} type="email" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required></div><div><label class="block text-sm font-medium text-gray-700">Téléphone</label><input${ssrRenderAttr("value", newUser.value.phone)} type="tel" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div></div><div><label class="block text-sm font-medium text-gray-700">Date de naissance</label><input${ssrRenderAttr("value", newUser.value.birth_date)} type="date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div><div><label class="block text-sm font-medium text-gray-700">Adresse</label><div class="grid grid-cols-1 md:grid-cols-2 gap-4"><div><label class="block text-xs text-gray-500 mb-1">Rue</label><input${ssrRenderAttr("value", newUser.value.street)} type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Nom de la rue"></div><div><label class="block text-xs text-gray-500 mb-1">Numéro</label><input${ssrRenderAttr("value", newUser.value.street_number)} type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="92, 92/A, 92B..."></div></div><div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4"><div><label class="block text-xs text-gray-500 mb-1">Code postal</label><input${ssrRenderAttr("value", newUser.value.postal_code)} type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="1000"></div><div><label class="block text-xs text-gray-500 mb-1">Ville</label><input${ssrRenderAttr("value", newUser.value.city)} type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Bruxelles"></div><div><label class="block text-xs text-gray-500 mb-1">Pays</label><select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"><option value="Belgium"${ssrIncludeBooleanAttr(Array.isArray(newUser.value.country) ? ssrLooseContain(newUser.value.country, "Belgium") : ssrLooseEqual(newUser.value.country, "Belgium")) ? " selected" : ""}>Belgique</option><option value="France"${ssrIncludeBooleanAttr(Array.isArray(newUser.value.country) ? ssrLooseContain(newUser.value.country, "France") : ssrLooseEqual(newUser.value.country, "France")) ? " selected" : ""}>France</option><option value="Netherlands"${ssrIncludeBooleanAttr(Array.isArray(newUser.value.country) ? ssrLooseContain(newUser.value.country, "Netherlands") : ssrLooseEqual(newUser.value.country, "Netherlands")) ? " selected" : ""}>Pays-Bas</option><option value="Germany"${ssrIncludeBooleanAttr(Array.isArray(newUser.value.country) ? ssrLooseContain(newUser.value.country, "Germany") : ssrLooseEqual(newUser.value.country, "Germany")) ? " selected" : ""}>Allemagne</option><option value="Luxembourg"${ssrIncludeBooleanAttr(Array.isArray(newUser.value.country) ? ssrLooseContain(newUser.value.country, "Luxembourg") : ssrLooseEqual(newUser.value.country, "Luxembourg")) ? " selected" : ""}>Luxembourg</option><option value="Switzerland"${ssrIncludeBooleanAttr(Array.isArray(newUser.value.country) ? ssrLooseContain(newUser.value.country, "Switzerland") : ssrLooseEqual(newUser.value.country, "Switzerland")) ? " selected" : ""}>Suisse</option></select></div></div></div><div><label class="block text-sm font-medium text-gray-700">Rôle *</label><select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required><option value="student"${ssrIncludeBooleanAttr(Array.isArray(newUser.value.role) ? ssrLooseContain(newUser.value.role, "student") : ssrLooseEqual(newUser.value.role, "student")) ? " selected" : ""}>Élève</option><option value="teacher"${ssrIncludeBooleanAttr(Array.isArray(newUser.value.role) ? ssrLooseContain(newUser.value.role, "teacher") : ssrLooseEqual(newUser.value.role, "teacher")) ? " selected" : ""}>Enseignant</option><option value="admin"${ssrIncludeBooleanAttr(Array.isArray(newUser.value.role) ? ssrLooseContain(newUser.value.role, "admin") : ssrLooseEqual(newUser.value.role, "admin")) ? " selected" : ""}>Administrateur</option></select></div><div class="grid grid-cols-1 md:grid-cols-2 gap-4"><div><label class="block text-sm font-medium text-gray-700">Mot de passe *</label><input${ssrRenderAttr("value", newUser.value.password)} type="password" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required></div><div><label class="block text-sm font-medium text-gray-700">Confirmer le mot de passe *</label><input${ssrRenderAttr("value", newUser.value.password_confirmation)} type="password" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required></div></div><div class="form-button-group"><button type="button" class="btn-secondary">Annuler</button><button type="submit" class="btn-primary">Créer l&#39;utilisateur</button></div></form></div></div></div>`);
      } else {
        _push(`<!---->`);
      }
      if (showCreateClubModal.value) {
        _push(`<div class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center z-50"><div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md"><h3 class="text-xl font-bold text-gray-800 mb-6">Créer un nouveau club</h3><form class="space-y-4"><div><label class="block text-sm font-medium text-gray-700">Nom du club</label><input${ssrRenderAttr("value", newClub.value.name)} type="text" class="w-full mt-1 px-4 py-3 border border-gray-300 rounded-lg" required></div><div><label class="block text-sm font-medium text-gray-700">Email</label><input${ssrRenderAttr("value", newClub.value.email)} type="email" class="w-full mt-1 px-4 py-3 border border-gray-300 rounded-lg" required></div><div><label class="block text-sm font-medium text-gray-700">Téléphone</label><input${ssrRenderAttr("value", newClub.value.phone)} type="tel" class="w-full mt-1 px-4 py-3 border border-gray-300 rounded-lg"></div><div class="grid grid-cols-1 md:grid-cols-3 gap-4"><div><label class="block text-sm font-medium text-gray-700">Rue</label><input${ssrRenderAttr("value", newClub.value.street)} type="text" class="w-full mt-1 px-4 py-3 border border-gray-300 rounded-lg" placeholder="Nom de la rue"></div><div><label class="block text-sm font-medium text-gray-700">Numéro</label><input${ssrRenderAttr("value", newClub.value.street_number)} type="text" class="w-full mt-1 px-4 py-3 border border-gray-300 rounded-lg" placeholder="123"></div><div><label class="block text-sm font-medium text-gray-700">Boîte</label><input${ssrRenderAttr("value", newClub.value.street_box)} type="text" class="w-full mt-1 px-4 py-3 border border-gray-300 rounded-lg" placeholder="A, B, 1, 2..."></div></div><div class="grid grid-cols-1 md:grid-cols-3 gap-4"><div><label class="block text-sm font-medium text-gray-700">Code postal</label><input${ssrRenderAttr("value", newClub.value.postal_code)} type="text" class="w-full mt-1 px-4 py-3 border border-gray-300 rounded-lg"></div><div><label class="block text-sm font-medium text-gray-700">Ville</label><input${ssrRenderAttr("value", newClub.value.city)} type="text" class="w-full mt-1 px-4 py-3 border border-gray-300 rounded-lg"></div><div><label class="block text-sm font-medium text-gray-700">Pays</label><input${ssrRenderAttr("value", newClub.value.country)} type="text" class="w-full mt-1 px-4 py-3 border border-gray-300 rounded-lg" placeholder="France"></div></div><div class="modal-button-group"><button type="button" class="btn-secondary">Annuler</button><button type="submit" class="btn-primary">Créer</button></div></form></div></div>`);
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
export {
  _sfc_main as default
};
//# sourceMappingURL=index-DCF8Svrt.js.map
