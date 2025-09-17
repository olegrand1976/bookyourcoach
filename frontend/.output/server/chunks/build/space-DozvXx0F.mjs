import { ref, mergeProps, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate } from 'vue/server-renderer';
import { _ as _sfc_main$1 } from './EquestrianIcon-D77xhcCX.mjs';

const _sfc_main = {
  __name: "space",
  __ssrInlineRender: true,
  setup(__props) {
    ref(null);
    const stats = ref({
      total_teachers: 0,
      total_students: 0,
      total_lessons: 0,
      total_revenue: 0
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-50" }, _attrs))}><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8"><div class="mb-8"><h1 class="text-3xl font-bold text-gray-900"> Espace Club </h1><p class="mt-2 text-gray-600"> G\xE9rez votre club \xE9questre : enseignants, \xE9l\xE8ves, cours et installations </p></div><div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8"><div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow cursor-pointer"><div class="flex items-center mb-4"><div class="p-3 bg-blue-100 rounded-lg">`);
      _push(ssrRenderComponent(_sfc_main$1, {
        icon: "helmet",
        size: 24,
        class: "text-blue-600"
      }, null, _parent));
      _push(`</div><div class="ml-4"><h3 class="text-lg font-semibold text-gray-900">Enseignants</h3><p class="text-sm text-gray-600">G\xE9rer les moniteurs</p></div></div><div class="text-sm text-gray-500">${ssrInterpolate(stats.value.total_teachers)} enseignant(s) actif(s) </div></div><div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow cursor-pointer"><div class="flex items-center mb-4"><div class="p-3 bg-green-100 rounded-lg">`);
      _push(ssrRenderComponent(_sfc_main$1, {
        icon: "horse",
        size: 24,
        class: "text-green-600"
      }, null, _parent));
      _push(`</div><div class="ml-4"><h3 class="text-lg font-semibold text-gray-900">\xC9l\xE8ves</h3><p class="text-sm text-gray-600">G\xE9rer les cavaliers</p></div></div><div class="text-sm text-gray-500">${ssrInterpolate(stats.value.total_students)} \xE9l\xE8ve(s) inscrit(s) </div></div><div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow cursor-pointer"><div class="flex items-center mb-4"><div class="p-3 bg-purple-100 rounded-lg">`);
      _push(ssrRenderComponent(_sfc_main$1, {
        icon: "calendar",
        size: 24,
        class: "text-purple-600"
      }, null, _parent));
      _push(`</div><div class="ml-4"><h3 class="text-lg font-semibold text-gray-900">Planning</h3><p class="text-sm text-gray-600">Organiser les cours</p></div></div><div class="text-sm text-gray-500">${ssrInterpolate(stats.value.total_lessons)} cours programm\xE9s </div></div><div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow cursor-pointer"><div class="flex items-center mb-4"><div class="p-3 bg-yellow-100 rounded-lg">`);
      _push(ssrRenderComponent(_sfc_main$1, {
        icon: "chart",
        size: 24,
        class: "text-yellow-600"
      }, null, _parent));
      _push(`</div><div class="ml-4"><h3 class="text-lg font-semibold text-gray-900">Finances</h3><p class="text-sm text-gray-600">Suivre les revenus</p></div></div><div class="text-sm text-gray-500">${ssrInterpolate(stats.value.total_revenue)}\u20AC de CA total </div></div><div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow cursor-pointer"><div class="flex items-center mb-4"><div class="p-3 bg-indigo-100 rounded-lg">`);
      _push(ssrRenderComponent(_sfc_main$1, {
        icon: "saddle",
        size: 24,
        class: "text-indigo-600"
      }, null, _parent));
      _push(`</div><div class="ml-4"><h3 class="text-lg font-semibold text-gray-900">Installations</h3><p class="text-sm text-gray-600">G\xE9rer les \xE9quipements</p></div></div><div class="text-sm text-gray-500"> Man\xE8ges et carri\xE8res </div></div><div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow cursor-pointer"><div class="flex items-center mb-4"><div class="p-3 bg-gray-100 rounded-lg">`);
      _push(ssrRenderComponent(_sfc_main$1, {
        icon: "settings",
        size: 24,
        class: "text-gray-600"
      }, null, _parent));
      _push(`</div><div class="ml-4"><h3 class="text-lg font-semibold text-gray-900">Param\xE8tres</h3><p class="text-sm text-gray-600">Configuration du club</p></div></div><div class="text-sm text-gray-500"> Personnaliser les fonctionnalit\xE9s </div></div></div><div class="bg-white rounded-lg shadow p-6"><h2 class="text-xl font-semibold text-gray-900 mb-4">Statistiques Rapides</h2><div class="grid grid-cols-2 md:grid-cols-4 gap-4"><div class="text-center"><div class="text-2xl font-bold text-blue-600">${ssrInterpolate(stats.value.total_teachers)}</div><div class="text-sm text-gray-600">Enseignants</div></div><div class="text-center"><div class="text-2xl font-bold text-green-600">${ssrInterpolate(stats.value.total_students)}</div><div class="text-sm text-gray-600">\xC9l\xE8ves</div></div><div class="text-center"><div class="text-2xl font-bold text-purple-600">${ssrInterpolate(stats.value.total_lessons)}</div><div class="text-sm text-gray-600">Cours</div></div><div class="text-center"><div class="text-2xl font-bold text-yellow-600">${ssrInterpolate(stats.value.total_revenue)}\u20AC</div><div class="text-sm text-gray-600">CA Total</div></div></div></div><div class="mt-8 bg-white rounded-lg shadow p-6"><h2 class="text-xl font-semibold text-gray-900 mb-4">Actions Rapides</h2><div class="flex flex-wrap gap-4"><button class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors"> Ajouter un Enseignant </button><button class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors"> Inscrire un \xC9l\xE8ve </button><button class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors"> Programmer un Cours </button><button class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition-colors"> Voir les Finances </button></div></div></div></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/club/space.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=space-DozvXx0F.mjs.map
