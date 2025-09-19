import { ref, mergeProps, unref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderAttr, ssrInterpolate, ssrRenderList, ssrRenderClass, ssrIncludeBooleanAttr, ssrLooseContain } from 'vue/server-renderer';
import { a as useAuthStore, u as useHead } from './server.mjs';
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
  __name: "profile-unified",
  __ssrInlineRender: true,
  setup(__props) {
    useAuthStore();
    const loading = ref(false);
    const form = ref({
      name: "",
      email: "",
      phone: "",
      website: "",
      description: "",
      address: "",
      city: "",
      postal_code: "",
      country: "",
      is_active: true
    });
    const availableActivities = ref([]);
    const selectedActivities = ref([]);
    const selectedDisciplines = ref([]);
    const getActivityById = (id) => {
      return availableActivities.value.find((activity) => activity.id === id);
    };
    const getDisciplinesByActivity = (activityId) => {
      const disciplines = {
        1: [
          // Équitation
          { id: 1, name: "Dressage" },
          { id: 2, name: "Saut d'obstacles" },
          { id: 3, name: "Complet" },
          { id: 4, name: "Endurance" }
        ],
        2: [
          // Natation
          { id: 5, name: "Crawl" },
          { id: 6, name: "Brasse" },
          { id: 7, name: "Papillon" },
          { id: 8, name: "Aquagym" }
        ],
        3: [
          // Salle de sport
          { id: 9, name: "Musculation" },
          { id: 10, name: "Cardio" },
          { id: 11, name: "Yoga" },
          { id: 12, name: "Pilates" }
        ],
        4: [
          // Coaching sportif
          { id: 13, name: "Perte de poids" },
          { id: 14, name: "Prise de masse" },
          { id: 15, name: "Pr\xE9paration physique" },
          { id: 16, name: "R\xE9\xE9ducation" }
        ]
      };
      return disciplines[activityId] || [];
    };
    useHead({
      title: "Profil du Club | activibe",
      meta: [
        { name: "description", content: "G\xE9rez les informations et activit\xE9s de votre club sur activibe" }
      ]
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-equestrian-cream" }, _attrs))}><div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8"><div class="mb-8"><h1 class="text-3xl font-bold text-equestrian-darkBrown flex items-center"><span class="text-4xl mr-3">\u{1F3E2}</span> Profil du Club </h1><p class="mt-2 text-equestrian-brown">G\xE9rez les informations et activit\xE9s de votre club</p></div><div class="bg-white shadow-lg rounded-lg border border-equestrian-gold/20"><form class="space-y-6 p-6"><div class="border-b border-gray-200 pb-6"><h2 class="text-lg font-semibold text-equestrian-darkBrown mb-4 flex items-center"><span class="text-xl mr-2">\u{1F4CB}</span> Informations g\xE9n\xE9rales </h2><div class="grid grid-cols-1 md:grid-cols-2 gap-6"><div><label class="block text-sm font-medium text-gray-700 mb-2">Nom du club</label><input${ssrRenderAttr("value", unref(form).name)} type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-equestrian-gold focus:border-transparent" placeholder="Nom de votre club" required></div><div><label class="block text-sm font-medium text-gray-700 mb-2">Email de contact</label><input${ssrRenderAttr("value", unref(form).email)} type="email" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-equestrian-gold focus:border-transparent" placeholder="contact@votreclub.com" required></div><div><label class="block text-sm font-medium text-gray-700 mb-2">T\xE9l\xE9phone</label><input${ssrRenderAttr("value", unref(form).phone)} type="tel" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-equestrian-gold focus:border-transparent" placeholder="+33 1 23 45 67 89"></div><div><label class="block text-sm font-medium text-gray-700 mb-2">Site web</label><input${ssrRenderAttr("value", unref(form).website)} type="url" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-equestrian-gold focus:border-transparent" placeholder="https://votreclub.com"></div></div><div class="mt-6"><label class="block text-sm font-medium text-gray-700 mb-2">Description</label><textarea rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-equestrian-gold focus:border-transparent" placeholder="D\xE9crivez votre club, ses valeurs et ses services...">${ssrInterpolate(unref(form).description)}</textarea></div></div><div class="border-b border-gray-200 pb-6"><h2 class="text-lg font-semibold text-equestrian-darkBrown mb-4 flex items-center"><span class="text-xl mr-2">\u{1F4CD}</span> Adresse </h2><div class="grid grid-cols-1 md:grid-cols-3 gap-6"><div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-2">Adresse</label><input${ssrRenderAttr("value", unref(form).address)} type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-equestrian-gold focus:border-transparent" placeholder="123 Rue de l&#39;\xC9quitation"></div><div><label class="block text-sm font-medium text-gray-700 mb-2">Code postal</label><input${ssrRenderAttr("value", unref(form).postal_code)} type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-equestrian-gold focus:border-transparent" placeholder="75001"></div><div><label class="block text-sm font-medium text-gray-700 mb-2">Ville</label><input${ssrRenderAttr("value", unref(form).city)} type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-equestrian-gold focus:border-transparent" placeholder="Paris"></div><div><label class="block text-sm font-medium text-gray-700 mb-2">Pays</label><input${ssrRenderAttr("value", unref(form).country)} type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-equestrian-gold focus:border-transparent" placeholder="France"></div></div></div><div class="border-b border-gray-200 pb-6"><h2 class="text-lg font-semibold text-equestrian-darkBrown mb-4 flex items-center"><span class="text-xl mr-2">\u{1F3C3}\u200D\u2640\uFE0F</span> Activit\xE9s propos\xE9es </h2><div class="mb-4"><p class="text-sm text-gray-600 mb-4">S\xE9lectionnez les activit\xE9s que votre club propose :</p><div class="grid grid-cols-1 md:grid-cols-2 gap-4"><!--[-->`);
      ssrRenderList(unref(availableActivities), (activity) => {
        _push(`<div class="${ssrRenderClass([unref(selectedActivities).includes(activity.id) ? "border-equestrian-gold bg-equestrian-cream" : "border-gray-200", "flex items-center p-4 border rounded-lg hover:bg-gray-50 transition-colors"])}"><input${ssrRenderAttr("id", "activity-" + activity.id)}${ssrIncludeBooleanAttr(Array.isArray(unref(selectedActivities)) ? ssrLooseContain(unref(selectedActivities), activity.id) : unref(selectedActivities)) ? " checked" : ""}${ssrRenderAttr("value", activity.id)} type="checkbox" class="h-4 w-4 text-equestrian-gold focus:ring-equestrian-gold border-gray-300 rounded"><label${ssrRenderAttr("for", "activity-" + activity.id)} class="ml-3 flex items-center cursor-pointer"><span class="text-2xl mr-2">${ssrInterpolate(activity.icon)}</span><div><div class="font-medium text-gray-900">${ssrInterpolate(activity.name)}</div><div class="text-sm text-gray-500">${ssrInterpolate(activity.description)}</div></div></label></div>`);
      });
      _push(`<!--]--></div></div>`);
      if (unref(selectedActivities).length > 0) {
        _push(`<div class="mt-6"><h3 class="text-md font-medium text-equestrian-darkBrown mb-3">Sp\xE9cialit\xE9s par activit\xE9</h3><!--[-->`);
        ssrRenderList(unref(selectedActivities), (activityId) => {
          var _a, _b;
          _push(`<div class="mb-4"><div class="bg-gray-50 p-4 rounded-lg"><h4 class="font-medium text-gray-900 mb-2 flex items-center"><span class="text-lg mr-2">${ssrInterpolate((_a = getActivityById(activityId)) == null ? void 0 : _a.icon)}</span> ${ssrInterpolate((_b = getActivityById(activityId)) == null ? void 0 : _b.name)}</h4><div class="grid grid-cols-2 md:grid-cols-3 gap-2"><!--[-->`);
          ssrRenderList(getDisciplinesByActivity(activityId), (discipline) => {
            _push(`<label class="flex items-center p-2 text-sm"><input${ssrRenderAttr("id", "discipline-" + discipline.id)}${ssrIncludeBooleanAttr(Array.isArray(unref(selectedDisciplines)) ? ssrLooseContain(unref(selectedDisciplines), discipline.id) : unref(selectedDisciplines)) ? " checked" : ""}${ssrRenderAttr("value", discipline.id)} type="checkbox" class="h-3 w-3 text-equestrian-gold focus:ring-equestrian-gold border-gray-300 rounded mr-2"><span class="text-gray-700">${ssrInterpolate(discipline.name)}</span></label>`);
          });
          _push(`<!--]--></div></div></div>`);
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="pb-6"><h2 class="text-lg font-semibold text-equestrian-darkBrown mb-4 flex items-center"><span class="text-xl mr-2">\u2699\uFE0F</span> Param\xE8tres </h2><div class="flex items-center"><input${ssrIncludeBooleanAttr(Array.isArray(unref(form).is_active) ? ssrLooseContain(unref(form).is_active, null) : unref(form).is_active) ? " checked" : ""} type="checkbox" id="is_active" class="h-4 w-4 text-equestrian-gold focus:ring-equestrian-gold border-gray-300 rounded"><label for="is_active" class="ml-2 text-sm text-gray-700"> Club actif (visible sur la plateforme) </label></div></div><div class="flex justify-end space-x-4 pt-6 border-t border-gray-200"><button type="button" class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-equestrian-gold"> Annuler </button><button type="submit"${ssrIncludeBooleanAttr(unref(loading)) ? " disabled" : ""} class="px-6 py-2 bg-equestrian-gold text-white rounded-md hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-equestrian-gold disabled:opacity-50">`);
      if (unref(loading)) {
        _push(`<span>Enregistrement...</span>`);
      } else {
        _push(`<span>Enregistrer les modifications</span>`);
      }
      _push(`</button></div></form></div></div></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/club/profile-unified.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=profile-unified-B5xYC0hU.mjs.map
