import { _ as _sfc_main$1 } from "./EquestrianIcon-D77xhcCX.js";
import { ref, reactive, watch, mergeProps, unref, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrRenderAttr, ssrRenderComponent, ssrInterpolate, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual } from "vue/server-renderer";
import { a as useAuthStore, c as useCookie, d as useNuxtApp } from "../server.mjs";
import { u as useToast } from "./useToast-BOgwGdiM.js";
import "ofetch";
import "#internal/nuxt/paths";
import "/workspace/frontend/node_modules/hookable/dist/index.mjs";
import "/workspace/frontend/node_modules/unctx/dist/index.mjs";
import "/workspace/frontend/node_modules/h3/dist/index.mjs";
import "vue-router";
import "/workspace/frontend/node_modules/radix3/dist/index.mjs";
import "/workspace/frontend/node_modules/defu/dist/defu.mjs";
import "/workspace/frontend/node_modules/ufo/dist/index.mjs";
import "/workspace/frontend/node_modules/nuxt/node_modules/cookie-es/dist/index.mjs";
import "/workspace/frontend/node_modules/destr/dist/index.mjs";
import "/workspace/frontend/node_modules/ohash/dist/index.mjs";
import "/workspace/frontend/node_modules/klona/dist/index.mjs";
import "/workspace/frontend/node_modules/@unhead/vue/dist/index.mjs";
const _sfc_main = {
  __name: "profile",
  __ssrInlineRender: true,
  setup(__props) {
    const authStore = useAuthStore();
    const toast = useToast();
    const loading = ref(false);
    const form = reactive({
      name: "",
      email: "",
      phone: "",
      birth_date: "",
      // Teacher specific
      specialties: "",
      experience_years: 0,
      certifications: "",
      hourly_rate: 0,
      // Student specific
      riding_level: "",
      course_preferences: "",
      emergency_contact: ""
    });
    const loadProfileData = async () => {
      console.log("loadProfileData called, user:", authStore.user);
      if (!authStore.user) {
        console.log("No user found in authStore, trying to fetch from API");
        const tokenCookie = useCookie("auth-token");
        if (tokenCookie.value) {
          try {
            const { $api } = useNuxtApp();
            const response = await $api.get("/auth/user");
            authStore.user = response.data;
            authStore.isAuthenticated = true;
            console.log("User fetched from API:", response.data);
          } catch (error) {
            console.error("Erreur lors de la récupération de l'utilisateur:", error);
            return;
          }
        } else {
          return;
        }
      }
      console.log("Preloading user data:", authStore.user.name, authStore.user.email);
      form.name = authStore.user.name || "";
      form.email = authStore.user.email || "";
      try {
        const { $api } = useNuxtApp();
        const response = await $api.get("/profile-test");
        if (response.data.profile) {
          const profile = response.data.profile;
          form.phone = profile.phone || "";
          if (profile.date_of_birth) {
            const date = new Date(profile.date_of_birth);
            form.birth_date = date.toISOString().split("T")[0];
          } else {
            form.birth_date = "";
          }
          if (authStore.isTeacher && response.data.teacher) {
            const teacher = response.data.teacher;
            form.specialties = teacher.specialties || "";
            form.experience_years = teacher.experience_years || 0;
            form.certifications = teacher.certifications || "";
            form.hourly_rate = teacher.hourly_rate || 0;
          }
          if (authStore.isStudent && response.data.student) {
            const student = response.data.student;
            form.riding_level = student.level || "";
            form.course_preferences = student.course_preferences || "";
            form.emergency_contact = student.emergency_contact || "";
          }
        }
      } catch (error) {
        console.error("Erreur lors du chargement du profil:", error);
        toast.error("Erreur lors du chargement du profil");
      }
    };
    watch(() => authStore.user, (newUser, oldUser) => {
      console.log("Watch triggered - newUser:", newUser, "oldUser:", oldUser);
      if (newUser && newUser !== oldUser) {
        loadProfileData();
      }
    }, { immediate: true });
    return (_ctx, _push, _parent, _attrs) => {
      const _component_EquestrianIcon = _sfc_main$1;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-50" }, _attrs))}><div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8"><div class="mb-8"><h1 class="text-3xl font-bold text-gray-900 flex items-center"><span class="text-4xl mr-3">👤</span> Mon Profil </h1><p class="mt-2 text-gray-600">Gérez vos informations personnelles et préférences</p></div><div class="bg-white shadow-lg rounded-lg border border-gray-200"><form class="space-y-6 p-6"><div class="border-b border-gray-200 pb-6"><h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center"><span class="text-xl mr-2">🏇</span> Informations personnelles </h2><div class="grid grid-cols-1 md:grid-cols-2 gap-6"><div><label class="block text-sm font-medium text-gray-700 mb-2">Nom complet</label><input${ssrRenderAttr("value", unref(form).name)} type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Votre nom complet" required></div><div><label class="block text-sm font-medium text-gray-700 mb-2">Email</label><input${ssrRenderAttr("value", unref(form).email)} type="email" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="votre@email.com" required></div><div><label class="block text-sm font-medium text-gray-700 mb-2">Téléphone</label><input${ssrRenderAttr("value", unref(form).phone)} type="tel" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="+33 6 12 34 56 78"></div><div><label class="block text-sm font-medium text-gray-700 mb-2">Date de naissance</label><input${ssrRenderAttr("value", unref(form).birth_date)} type="date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"></div></div></div>`);
      if (unref(authStore).isAdmin) {
        _push(`<div class="border-b border-gray-200 pb-6"><h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">`);
        _push(ssrRenderComponent(_component_EquestrianIcon, {
          icon: "trophy",
          class: "mr-2 text-primary-600",
          size: 20
        }, null, _parent));
        _push(` Administration </h2><div class="bg-blue-50 border border-blue-200 rounded-lg p-4"><p class="text-blue-800"><strong>Rôle :</strong> Administrateur système </p><p class="text-blue-700 text-sm mt-1"> Accès complet aux fonctionnalités de gestion de la plateforme </p></div></div>`);
      } else {
        _push(`<!---->`);
      }
      if (unref(authStore).isTeacher) {
        _push(`<div class="border-b border-gray-200 pb-6"><h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">`);
        _push(ssrRenderComponent(_component_EquestrianIcon, {
          icon: "saddle",
          class: "mr-2 text-primary-600",
          size: 20
        }, null, _parent));
        _push(` Informations d&#39;enseignant </h2><div class="grid grid-cols-1 md:grid-cols-2 gap-6"><div><label class="block text-sm font-medium text-gray-700 mb-2">Spécialités</label><textarea class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" rows="3" placeholder="Dressage, saut d&#39;obstacles, équitation western...">${ssrInterpolate(unref(form).specialties)}</textarea></div><div><label class="block text-sm font-medium text-gray-700 mb-2">Expérience (années)</label><input${ssrRenderAttr("value", unref(form).experience_years)} type="number" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" min="0" placeholder="10"></div><div><label class="block text-sm font-medium text-gray-700 mb-2">Certifications</label><textarea class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" rows="2" placeholder="BPJEPS, Galop 7, FFE...">${ssrInterpolate(unref(form).certifications)}</textarea></div><div><label class="block text-sm font-medium text-gray-700 mb-2">Tarif horaire (€)</label><input${ssrRenderAttr("value", unref(form).hourly_rate)} type="number" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" min="0" step="5" placeholder="45"></div></div></div>`);
      } else {
        _push(`<!---->`);
      }
      if (unref(authStore).isStudent) {
        _push(`<div class="border-b border-gray-200 pb-6"><h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">`);
        _push(ssrRenderComponent(_component_EquestrianIcon, {
          icon: "horseshoe",
          class: "mr-2 text-primary-600",
          size: 20
        }, null, _parent));
        _push(` Informations d&#39;élève </h2><div class="grid grid-cols-1 md:grid-cols-2 gap-6"><div><label class="block text-sm font-medium text-gray-700 mb-2">Niveau équestre</label><select class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"><option value=""${ssrIncludeBooleanAttr(Array.isArray(unref(form).riding_level) ? ssrLooseContain(unref(form).riding_level, "") : ssrLooseEqual(unref(form).riding_level, "")) ? " selected" : ""}>Sélectionnez votre niveau</option><option value="debutant"${ssrIncludeBooleanAttr(Array.isArray(unref(form).riding_level) ? ssrLooseContain(unref(form).riding_level, "debutant") : ssrLooseEqual(unref(form).riding_level, "debutant")) ? " selected" : ""}>Débutant</option><option value="galop1"${ssrIncludeBooleanAttr(Array.isArray(unref(form).riding_level) ? ssrLooseContain(unref(form).riding_level, "galop1") : ssrLooseEqual(unref(form).riding_level, "galop1")) ? " selected" : ""}>Galop 1</option><option value="galop2"${ssrIncludeBooleanAttr(Array.isArray(unref(form).riding_level) ? ssrLooseContain(unref(form).riding_level, "galop2") : ssrLooseEqual(unref(form).riding_level, "galop2")) ? " selected" : ""}>Galop 2</option><option value="galop3"${ssrIncludeBooleanAttr(Array.isArray(unref(form).riding_level) ? ssrLooseContain(unref(form).riding_level, "galop3") : ssrLooseEqual(unref(form).riding_level, "galop3")) ? " selected" : ""}>Galop 3</option><option value="galop4"${ssrIncludeBooleanAttr(Array.isArray(unref(form).riding_level) ? ssrLooseContain(unref(form).riding_level, "galop4") : ssrLooseEqual(unref(form).riding_level, "galop4")) ? " selected" : ""}>Galop 4</option><option value="galop5"${ssrIncludeBooleanAttr(Array.isArray(unref(form).riding_level) ? ssrLooseContain(unref(form).riding_level, "galop5") : ssrLooseEqual(unref(form).riding_level, "galop5")) ? " selected" : ""}>Galop 5</option><option value="galop6"${ssrIncludeBooleanAttr(Array.isArray(unref(form).riding_level) ? ssrLooseContain(unref(form).riding_level, "galop6") : ssrLooseEqual(unref(form).riding_level, "galop6")) ? " selected" : ""}>Galop 6</option><option value="galop7"${ssrIncludeBooleanAttr(Array.isArray(unref(form).riding_level) ? ssrLooseContain(unref(form).riding_level, "galop7") : ssrLooseEqual(unref(form).riding_level, "galop7")) ? " selected" : ""}>Galop 7</option><option value="expert"${ssrIncludeBooleanAttr(Array.isArray(unref(form).riding_level) ? ssrLooseContain(unref(form).riding_level, "expert") : ssrLooseEqual(unref(form).riding_level, "expert")) ? " selected" : ""}>Expert</option></select></div><div><label class="block text-sm font-medium text-gray-700 mb-2">Préférences de cours</label><textarea class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" rows="2" placeholder="Dressage, saut, balade...">${ssrInterpolate(unref(form).course_preferences)}</textarea></div><div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-2">Contact d&#39;urgence</label><input${ssrRenderAttr("value", unref(form).emergency_contact)} type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Nom et téléphone du contact d&#39;urgence"></div></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="flex justify-end space-x-4 pt-6"><button type="button" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"${ssrIncludeBooleanAttr(unref(loading)) ? " disabled" : ""}> Annuler </button><button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent flex items-center"${ssrIncludeBooleanAttr(unref(loading)) ? " disabled" : ""}>`);
      if (unref(loading)) {
        _push(`<span class="mr-2"><svg class="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg></span>`);
      } else {
        _push(`<!---->`);
      }
      _push(` Sauvegarder </button></div></form></div></div></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/profile.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
//# sourceMappingURL=profile-KGPyDPA2.js.map
