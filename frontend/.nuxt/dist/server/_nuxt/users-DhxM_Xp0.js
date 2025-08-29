import { _ as __nuxt_component_3 } from "./EquestrianIcon-DSrCvKCR.js";
import { ref, computed, watch, mergeProps, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderAttr, ssrInterpolate, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual, ssrRenderList, ssrRenderClass } from "vue/server-renderer";
import { d as useNuxtApp } from "../server.mjs";
import "./_plugin-vue_export-helper-1tPrXgE0.js";
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
  __name: "users",
  __ssrInlineRender: true,
  setup(__props) {
    const loading = ref(true);
    const showCreateModal = ref(false);
    const showEditModal = ref(false);
    const users = ref([]);
    const currentPage = ref(1);
    const perPage = ref(10);
    const totalUsers = ref(0);
    const totalPages = ref(0);
    const filters = ref({
      search: "",
      role: "",
      status: ""
    });
    const userForm = ref({
      id: null,
      name: "",
      email: "",
      role: "student",
      password: "",
      password_confirmation: ""
    });
    const visiblePages = computed(() => {
      const pages = [];
      const start = Math.max(1, currentPage.value - 2);
      const end = Math.min(totalPages.value, currentPage.value + 2);
      for (let i = start; i <= end; i++) {
        pages.push(i);
      }
      return pages;
    });
    const getRoleClass = (role) => {
      const classes = {
        admin: "bg-red-100 text-red-800",
        teacher: "bg-green-100 text-green-800",
        student: "bg-blue-100 text-blue-800"
      };
      return classes[role] || "bg-gray-100 text-gray-800";
    };
    const getRoleLabel = (role) => {
      const labels = {
        admin: "Admin",
        teacher: "Enseignant",
        student: "Élève"
      };
      return labels[role] || role;
    };
    const formatDate = (dateString) => {
      return new Date(dateString).toLocaleDateString("fr-FR");
    };
    const loadUsers = async () => {
      var _a;
      loading.value = true;
      try {
        const { $api } = useNuxtApp();
        const params = new URLSearchParams({
          page: currentPage.value,
          per_page: perPage.value
        });
        if (filters.value.search && filters.value.search.trim()) {
          params.append("search", filters.value.search.trim());
        }
        if (filters.value.role && filters.value.role.trim()) {
          params.append("role", filters.value.role.trim());
        }
        if (filters.value.status && filters.value.status.trim()) {
          params.append("status", filters.value.status.trim());
        }
        console.log("Paramètres envoyés:", params.toString());
        const response = await $api.get(`/admin/users?${params}`);
        console.log("Response complète:", response);
        console.log("Response.data:", response.data);
        const responseData = response.data || response;
        if (responseData.success && responseData.data) {
          users.value = responseData.data;
          totalUsers.value = responseData.data.length;
          totalPages.value = Math.ceil(totalUsers.value / perPage.value);
          console.log("Utilisateurs chargés:", users.value.length);
        } else if (Array.isArray(responseData)) {
          users.value = responseData;
          totalUsers.value = responseData.length;
          totalPages.value = Math.ceil(totalUsers.value / perPage.value);
          console.log("Utilisateurs chargés (tableau direct):", users.value.length);
        } else {
          console.warn("Structure de réponse inattendue:", responseData);
          users.value = [];
          totalUsers.value = 0;
          totalPages.value = 0;
        }
      } catch (error) {
        console.error("Erreur lors du chargement des utilisateurs:", error);
        console.error("Détails de l'erreur:", (_a = error.response) == null ? void 0 : _a.data);
        users.value = [];
        totalUsers.value = 0;
        totalPages.value = 0;
      } finally {
        loading.value = false;
      }
    };
    watch(filters, () => {
      currentPage.value = 1;
      loadUsers();
    }, { deep: true });
    return (_ctx, _push, _parent, _attrs) => {
      const _component_EquestrianIcon = __nuxt_component_3;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-50" }, _attrs))}><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8"><div class="mb-8"><div class="flex items-center justify-between"><div><h1 class="text-3xl font-bold text-grayconst createUser = async () =&gt; { try { const { $api } = useNuxtApp() await $api.post(&#39;/admin/users&#39;, userForm.value) closeModal() await loadUsers() alert(&#39;Utilisateur créé avec succès!&#39;) } catch (error) { console.error(&#39;Erreur lors de la création:&#39;, error) alert(&#39;Erreur lors de la création de l\\&#39;utilisateur&#39;) } }center">`);
      _push(ssrRenderComponent(_component_EquestrianIcon, {
        icon: "helmet",
        class: "mr-3 text-primary-600",
        size: 32
      }, null, _parent));
      _push(` Gestion des Utilisateurs </h1><p class="mt-2 text-gray-600">Gérez tous les utilisateurs de la plateforme</p></div><button class="btn-primary flex items-center">`);
      _push(ssrRenderComponent(_component_EquestrianIcon, {
        icon: "horseshoe",
        class: "mr-2",
        size: 16
      }, null, _parent));
      _push(` Nouvel utilisateur </button></div></div><div class="bg-white rounded-lg shadow p-6 mb-6"><div class="grid grid-cols-1 md:grid-cols-4 gap-4"><div><label class="block text-sm font-medium text-gray-700 mb-1">Rechercher</label><input${ssrRenderAttr("value", filters.value.search)} type="text" placeholder="Nom ou email..." class="input-field"></div><div><label class="block text-sm font-medium text-gray-700 mb-1">Rôle</label><select class="input-field"><option value=""${ssrIncludeBooleanAttr(Array.isArray(filters.value.role) ? ssrLooseContain(filters.value.role, "") : ssrLooseEqual(filters.value.role, "")) ? " selected" : ""}>Tous les rôles</option><option value="admin"${ssrIncludeBooleanAttr(Array.isArray(filters.value.role) ? ssrLooseContain(filters.value.role, "admin") : ssrLooseEqual(filters.value.role, "admin")) ? " selected" : ""}>Administrateur</option><option value="teacher"${ssrIncludeBooleanAttr(Array.isArray(filters.value.role) ? ssrLooseContain(filters.value.role, "teacher") : ssrLooseEqual(filters.value.role, "teacher")) ? " selected" : ""}>Enseignant</option><option value="student"${ssrIncludeBooleanAttr(Array.isArray(filters.value.role) ? ssrLooseContain(filters.value.role, "student") : ssrLooseEqual(filters.value.role, "student")) ? " selected" : ""}>Élève</option></select></div><div><label class="block text-sm font-medium text-gray-700 mb-1">Statut</label><select class="input-field"><option value=""${ssrIncludeBooleanAttr(Array.isArray(filters.value.status) ? ssrLooseContain(filters.value.status, "") : ssrLooseEqual(filters.value.status, "")) ? " selected" : ""}>Tous les statuts</option><option value="active"${ssrIncludeBooleanAttr(Array.isArray(filters.value.status) ? ssrLooseContain(filters.value.status, "active") : ssrLooseEqual(filters.value.status, "active")) ? " selected" : ""}>Actif</option><option value="inactive"${ssrIncludeBooleanAttr(Array.isArray(filters.value.status) ? ssrLooseContain(filters.value.status, "inactive") : ssrLooseEqual(filters.value.status, "inactive")) ? " selected" : ""}>Inactif</option></select></div><div class="flex items-end"><button class="btn-outline w-full"> Filtrer </button></div></div></div><div class="bg-white rounded-lg shadow overflow-hidden"><div class="px-6 py-4 border-b border-gray-200"><h2 class="text-lg font-semibold text-gray-900"> Liste des utilisateurs (${ssrInterpolate(users.value.length)}) </h2></div>`);
      if (loading.value) {
        _push(`<div class="p-8 text-center"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600 mx-auto"></div><p class="mt-2 text-gray-500">Chargement...</p></div>`);
      } else {
        _push(`<div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200"><thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"> Utilisateur </th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"> Rôle </th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"> Statut </th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"> Inscription </th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"> Actions </th></tr></thead><tbody class="bg-white divide-y divide-gray-200"><!--[-->`);
        ssrRenderList(users.value, (user) => {
          _push(`<tr class="hover:bg-gray-50"><td class="px-6 py-4 whitespace-nowrap"><div class="flex items-center"><div class="flex-shrink-0 h-10 w-10"><div class="h-10 w-10 rounded-full bg-primary-100 flex items-center justify-center"><span class="text-sm font-medium text-primary-600">${ssrInterpolate(user.name.charAt(0).toUpperCase())}</span></div></div><div class="ml-4"><div class="text-sm font-medium text-gray-900">${ssrInterpolate(user.name)}</div><div class="text-sm text-gray-500">${ssrInterpolate(user.email)}</div></div></div></td><td class="px-6 py-4 whitespace-nowrap"><span class="${ssrRenderClass([getRoleClass(user.role), "px-2 inline-flex text-xs leading-5 font-semibold rounded-full"])}">${ssrInterpolate(getRoleLabel(user.role))}</span></td><td class="px-6 py-4 whitespace-nowrap"><span class="${ssrRenderClass([user.is_active ? "text-green-800 bg-green-100" : "text-red-800 bg-red-100", "px-2 inline-flex text-xs leading-5 font-semibold rounded-full"])}">${ssrInterpolate(user.is_active ? "Actif" : "Inactif")}</span></td><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${ssrInterpolate(formatDate(user.created_at))}</td><td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"><div class="flex space-x-2"><button class="text-indigo-600 hover:text-indigo-900"> Modifier </button><button class="${ssrRenderClass(user.is_active ? "text-red-600 hover:text-red-900" : "text-green-600 hover:text-green-900")}">${ssrInterpolate(user.is_active ? "Désactiver" : "Activer")}</button></div></td></tr>`);
        });
        _push(`<!--]--></tbody></table></div>`);
      }
      _push(`<div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6"><div class="flex-1 flex justify-between sm:hidden"><button${ssrIncludeBooleanAttr(currentPage.value <= 1) ? " disabled" : ""} class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"> Précédent </button><button${ssrIncludeBooleanAttr(currentPage.value >= totalPages.value) ? " disabled" : ""} class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"> Suivant </button></div><div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between"><div><p class="text-sm text-gray-700"> Affichage de <span class="font-medium">${ssrInterpolate((currentPage.value - 1) * perPage.value + 1)}</span> à <span class="font-medium">${ssrInterpolate(Math.min(currentPage.value * perPage.value, totalUsers.value))}</span> sur <span class="font-medium">${ssrInterpolate(totalUsers.value)}</span> résultats </p></div><div><nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px"><!--[-->`);
      ssrRenderList(visiblePages.value, (page) => {
        _push(`<button class="${ssrRenderClass([page === currentPage.value ? "bg-primary-50 border-primary-500 text-primary-600" : "bg-white border-gray-300 text-gray-500 hover:bg-gray-50", "relative inline-flex items-center px-4 py-2 border text-sm font-medium"])}">${ssrInterpolate(page)}</button>`);
      });
      _push(`<!--]--></nav></div></div></div></div></div>`);
      if (showCreateModal.value || showEditModal.value) {
        _push(`<div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"><div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white"><div class="mt-3"><h3 class="text-lg font-medium text-gray-900 mb-4">${ssrInterpolate(showEditModal.value ? "Modifier l'utilisateur" : "Créer un nouvel utilisateur")}</h3><form class="space-y-4"><div><label class="block text-sm font-medium text-gray-700">Nom</label><input${ssrRenderAttr("value", userForm.value.name)} type="text" class="input-field" required></div><div><label class="block text-sm font-medium text-gray-700">Email</label><input${ssrRenderAttr("value", userForm.value.email)} type="email" class="input-field" required></div><div><label class="block text-sm font-medium text-gray-700">Rôle</label><select class="input-field" required><option value="student"${ssrIncludeBooleanAttr(Array.isArray(userForm.value.role) ? ssrLooseContain(userForm.value.role, "student") : ssrLooseEqual(userForm.value.role, "student")) ? " selected" : ""}>Élève</option><option value="teacher"${ssrIncludeBooleanAttr(Array.isArray(userForm.value.role) ? ssrLooseContain(userForm.value.role, "teacher") : ssrLooseEqual(userForm.value.role, "teacher")) ? " selected" : ""}>Enseignant</option><option value="admin"${ssrIncludeBooleanAttr(Array.isArray(userForm.value.role) ? ssrLooseContain(userForm.value.role, "admin") : ssrLooseEqual(userForm.value.role, "admin")) ? " selected" : ""}>Administrateur</option></select></div>`);
        if (!showEditModal.value) {
          _push(`<div><label class="block text-sm font-medium text-gray-700">Mot de passe</label><input${ssrRenderAttr("value", userForm.value.password)} type="password" class="input-field" required></div>`);
        } else {
          _push(`<!---->`);
        }
        if (!showEditModal.value) {
          _push(`<div><label class="block text-sm font-medium text-gray-700">Confirmer le mot de passe</label><input${ssrRenderAttr("value", userForm.value.password_confirmation)} type="password" class="input-field" required></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<div class="flex justify-end space-x-3"><button type="button" class="btn-outline">Annuler</button><button type="submit" class="btn-primary">${ssrInterpolate(showEditModal.value ? "Modifier" : "Créer")}</button></div></form></div></div></div>`);
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/admin/users.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
//# sourceMappingURL=users-DhxM_Xp0.js.map
