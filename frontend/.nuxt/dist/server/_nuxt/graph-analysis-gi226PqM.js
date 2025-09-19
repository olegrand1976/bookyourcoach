import { ref, computed, mergeProps, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrInterpolate, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual, ssrRenderList, ssrRenderAttr, ssrRenderClass, ssrRenderComponent } from "vue/server-renderer";
import { _ as _export_sfc } from "./_plugin-vue_export-helper-1tPrXgE0.js";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/hookable/dist/index.mjs";
const _sfc_main$1 = {
  __name: "GraphVisualizationSimple",
  __ssrInlineRender: true,
  props: {
    initialEntity: {
      type: String,
      default: ""
    },
    initialItem: {
      type: [String, Number],
      default: ""
    }
  },
  setup(__props) {
    const props = __props;
    ref(null);
    ref(null);
    const isLoading = ref(false);
    const graphData = ref(null);
    const graphStats = ref(null);
    const cytoscapeLoaded = ref(false);
    const selectedEntity = ref(props.initialEntity);
    const selectedItem = ref(props.initialItem);
    const searchDepth = ref(2);
    const statusFilter = ref("");
    const cityFilter = ref("");
    const entityItems = ref([]);
    const cities = ref([]);
    const entityLabels = {
      club: "Club",
      teacher: "Enseignant",
      user: "Utilisateur",
      contract: "Contrat"
    };
    const entityLabel = computed(() => entityLabels[selectedEntity.value] || "Élément");
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "graph-visualization" }, _attrs))} data-v-0309d8b5><div class="graph-controls mb-6 p-4 bg-white rounded-lg shadow" data-v-0309d8b5><div class="grid grid-cols-1 md:grid-cols-4 gap-4" data-v-0309d8b5><div data-v-0309d8b5><label class="block text-sm font-medium text-gray-700 mb-2" data-v-0309d8b5> Entité de départ </label><select class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" data-v-0309d8b5><option value="" data-v-0309d8b5${ssrIncludeBooleanAttr(Array.isArray(selectedEntity.value) ? ssrLooseContain(selectedEntity.value, "") : ssrLooseEqual(selectedEntity.value, "")) ? " selected" : ""}>Sélectionner une entité</option><option value="club" data-v-0309d8b5${ssrIncludeBooleanAttr(Array.isArray(selectedEntity.value) ? ssrLooseContain(selectedEntity.value, "club") : ssrLooseEqual(selectedEntity.value, "club")) ? " selected" : ""}>Club</option><option value="teacher" data-v-0309d8b5${ssrIncludeBooleanAttr(Array.isArray(selectedEntity.value) ? ssrLooseContain(selectedEntity.value, "teacher") : ssrLooseEqual(selectedEntity.value, "teacher")) ? " selected" : ""}>Enseignant</option><option value="user" data-v-0309d8b5${ssrIncludeBooleanAttr(Array.isArray(selectedEntity.value) ? ssrLooseContain(selectedEntity.value, "user") : ssrLooseEqual(selectedEntity.value, "user")) ? " selected" : ""}>Utilisateur</option><option value="contract" data-v-0309d8b5${ssrIncludeBooleanAttr(Array.isArray(selectedEntity.value) ? ssrLooseContain(selectedEntity.value, "contract") : ssrLooseEqual(selectedEntity.value, "contract")) ? " selected" : ""}>Contrat</option></select></div><div data-v-0309d8b5><label class="block text-sm font-medium text-gray-700 mb-2" data-v-0309d8b5>${ssrInterpolate(entityLabel.value)}</label><select${ssrIncludeBooleanAttr(!selectedEntity.value || isLoading.value) ? " disabled" : ""} class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100" data-v-0309d8b5><option value="" data-v-0309d8b5${ssrIncludeBooleanAttr(Array.isArray(selectedItem.value) ? ssrLooseContain(selectedItem.value, "") : ssrLooseEqual(selectedItem.value, "")) ? " selected" : ""}>${ssrInterpolate(isLoading.value ? "Chargement..." : `Sélectionner ${entityLabel.value.toLowerCase()}`)}</option><!--[-->`);
      ssrRenderList(entityItems.value, (item) => {
        _push(`<option${ssrRenderAttr("value", item.id)}${ssrIncludeBooleanAttr(item.id === "error") ? " disabled" : ""} data-v-0309d8b5${ssrIncludeBooleanAttr(Array.isArray(selectedItem.value) ? ssrLooseContain(selectedItem.value, item.id) : ssrLooseEqual(selectedItem.value, item.id)) ? " selected" : ""}>${ssrInterpolate(item.name)}</option>`);
      });
      _push(`<!--]--></select>`);
      if (entityItems.value.length === 0 && selectedEntity.value && !isLoading.value) {
        _push(`<div class="text-sm text-red-600 mt-1" data-v-0309d8b5> Aucun ${ssrInterpolate(entityLabel.value.toLowerCase())} trouvé </div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div data-v-0309d8b5><label class="block text-sm font-medium text-gray-700 mb-2" data-v-0309d8b5> Profondeur </label><select class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" data-v-0309d8b5><option value="1" data-v-0309d8b5${ssrIncludeBooleanAttr(Array.isArray(searchDepth.value) ? ssrLooseContain(searchDepth.value, "1") : ssrLooseEqual(searchDepth.value, "1")) ? " selected" : ""}>1 niveau</option><option value="2" data-v-0309d8b5${ssrIncludeBooleanAttr(Array.isArray(searchDepth.value) ? ssrLooseContain(searchDepth.value, "2") : ssrLooseEqual(searchDepth.value, "2")) ? " selected" : ""}>2 niveaux</option><option value="3" data-v-0309d8b5${ssrIncludeBooleanAttr(Array.isArray(searchDepth.value) ? ssrLooseContain(searchDepth.value, "3") : ssrLooseEqual(searchDepth.value, "3")) ? " selected" : ""}>3 niveaux</option></select></div><div class="flex items-end" data-v-0309d8b5><button${ssrIncludeBooleanAttr(!selectedItem.value) ? " disabled" : ""} class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed" data-v-0309d8b5> 🔍 Analyser </button></div></div><div class="mt-4 pt-4 border-t border-gray-200" data-v-0309d8b5><div class="grid grid-cols-1 md:grid-cols-3 gap-4" data-v-0309d8b5><div data-v-0309d8b5><label class="block text-sm font-medium text-gray-700 mb-2" data-v-0309d8b5> Filtrer par statut </label><select class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" data-v-0309d8b5><option value="" data-v-0309d8b5${ssrIncludeBooleanAttr(Array.isArray(statusFilter.value) ? ssrLooseContain(statusFilter.value, "") : ssrLooseEqual(statusFilter.value, "")) ? " selected" : ""}>Tous les statuts</option><option value="active" data-v-0309d8b5${ssrIncludeBooleanAttr(Array.isArray(statusFilter.value) ? ssrLooseContain(statusFilter.value, "active") : ssrLooseEqual(statusFilter.value, "active")) ? " selected" : ""}>Actif</option><option value="inactive" data-v-0309d8b5${ssrIncludeBooleanAttr(Array.isArray(statusFilter.value) ? ssrLooseContain(statusFilter.value, "inactive") : ssrLooseEqual(statusFilter.value, "inactive")) ? " selected" : ""}>Inactif</option><option value="pending" data-v-0309d8b5${ssrIncludeBooleanAttr(Array.isArray(statusFilter.value) ? ssrLooseContain(statusFilter.value, "pending") : ssrLooseEqual(statusFilter.value, "pending")) ? " selected" : ""}>En attente</option></select></div><div data-v-0309d8b5><label class="block text-sm font-medium text-gray-700 mb-2" data-v-0309d8b5> Filtrer par ville </label><select class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" data-v-0309d8b5><option value="" data-v-0309d8b5${ssrIncludeBooleanAttr(Array.isArray(cityFilter.value) ? ssrLooseContain(cityFilter.value, "") : ssrLooseEqual(cityFilter.value, "")) ? " selected" : ""}>Toutes les villes</option><!--[-->`);
      ssrRenderList(cities.value, (city) => {
        _push(`<option${ssrRenderAttr("value", city)} data-v-0309d8b5${ssrIncludeBooleanAttr(Array.isArray(cityFilter.value) ? ssrLooseContain(cityFilter.value, city) : ssrLooseEqual(cityFilter.value, city)) ? " selected" : ""}>${ssrInterpolate(city)}</option>`);
      });
      _push(`<!--]--></select></div><div class="flex items-end" data-v-0309d8b5><button class="w-full px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700" data-v-0309d8b5> 🔄 Réinitialiser </button></div></div></div></div>`);
      if (graphStats.value) {
        _push(`<div class="mb-6 p-4 bg-blue-50 rounded-lg" data-v-0309d8b5><h3 class="text-lg font-semibold text-blue-900 mb-2" data-v-0309d8b5>📊 Statistiques du graphe</h3><div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm" data-v-0309d8b5><div data-v-0309d8b5><span class="font-medium" data-v-0309d8b5>Nœuds:</span> ${ssrInterpolate(graphStats.value.nodes)}</div><div data-v-0309d8b5><span class="font-medium" data-v-0309d8b5>Relations:</span> ${ssrInterpolate(graphStats.value.edges)}</div><div data-v-0309d8b5><span class="font-medium" data-v-0309d8b5>Clubs:</span> ${ssrInterpolate(graphStats.value.clubs)}</div><div data-v-0309d8b5><span class="font-medium" data-v-0309d8b5>Enseignants:</span> ${ssrInterpolate(graphStats.value.teachers)}</div></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="graph-container" data-v-0309d8b5><div class="${ssrRenderClass([{ "loading": isLoading.value }, "w-full h-96 border border-gray-300 rounded-lg bg-gray-50"])}" data-v-0309d8b5>`);
      if (isLoading.value) {
        _push(`<div class="flex items-center justify-center h-full" data-v-0309d8b5><div class="text-center" data-v-0309d8b5><div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4" data-v-0309d8b5></div><p class="text-gray-600" data-v-0309d8b5>Chargement du graphe...</p></div></div>`);
      } else if (!graphData.value) {
        _push(`<div class="flex items-center justify-center h-full" data-v-0309d8b5><div class="text-center text-gray-500" data-v-0309d8b5><div class="text-6xl mb-4" data-v-0309d8b5>🔍</div><p data-v-0309d8b5>Sélectionnez une entité pour visualiser ses relations</p></div></div>`);
      } else if (graphData.value && !cytoscapeLoaded.value) {
        _push(`<div class="flex items-center justify-center h-full" data-v-0309d8b5><div class="text-center text-gray-500" data-v-0309d8b5><div class="text-6xl mb-4" data-v-0309d8b5>⏳</div><p data-v-0309d8b5>Chargement de la bibliothèque de visualisation...</p></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div><div class="mt-4 p-4 bg-gray-50 rounded-lg" data-v-0309d8b5><h4 class="font-semibold text-gray-800 mb-2" data-v-0309d8b5>🎨 Légende</h4><div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm" data-v-0309d8b5><div class="flex items-center" data-v-0309d8b5><div class="w-4 h-4 bg-blue-500 rounded-full mr-2" data-v-0309d8b5></div><span data-v-0309d8b5>Clubs</span></div><div class="flex items-center" data-v-0309d8b5><div class="w-4 h-4 bg-green-500 rounded-full mr-2" data-v-0309d8b5></div><span data-v-0309d8b5>Enseignants</span></div><div class="flex items-center" data-v-0309d8b5><div class="w-4 h-4 bg-purple-500 rounded-full mr-2" data-v-0309d8b5></div><span data-v-0309d8b5>Utilisateurs</span></div><div class="flex items-center" data-v-0309d8b5><div class="w-4 h-4 bg-orange-500 rounded-full mr-2" data-v-0309d8b5></div><span data-v-0309d8b5>Contrats</span></div></div></div></div>`);
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/GraphVisualizationSimple.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const GraphVisualizationSimple = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["__scopeId", "data-v-0309d8b5"]]);
const _sfc_main = {
  __name: "graph-analysis",
  __ssrInlineRender: true,
  setup(__props) {
    const activeTab = ref("visualization");
    const isLoading = ref(false);
    const isSyncing = ref(false);
    const globalMetrics = ref(null);
    const userClubRelations = ref([]);
    const teachersBySpecialty = ref([]);
    const geographicDistribution = ref([]);
    const clubPerformance = ref([]);
    const mostDemandedSpecialties = ref([]);
    const syncStats = ref(null);
    const syncLogs = ref([]);
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-50" }, _attrs))} data-v-7527abb0><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" data-v-7527abb0><div class="mb-8" data-v-7527abb0><div class="flex items-center justify-between" data-v-7527abb0><div data-v-7527abb0><h1 class="text-3xl font-bold text-gray-900 text-center" data-v-7527abb0><span class="mr-3 text-primary-600" data-v-7527abb0>📊</span> Analyse Graphique </h1><p class="mt-2 text-gray-600" data-v-7527abb0>Visualisez et analysez les relations entre les entités de votre plateforme</p></div></div></div><div class="mb-8" data-v-7527abb0><nav class="flex space-x-8" data-v-7527abb0><button class="${ssrRenderClass([activeTab.value === "visualization" ? "border-blue-500 text-blue-600" : "border-transparent text-gray-500 hover:text-gray-700", "whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"])}" data-v-7527abb0> Visualisation Interactive </button><button class="${ssrRenderClass([activeTab.value === "analytics" ? "border-blue-500 text-blue-600" : "border-transparent text-gray-500 hover:text-gray-700", "whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"])}" data-v-7527abb0> Analyses Prédéfinies </button><button class="${ssrRenderClass([activeTab.value === "sync" ? "border-blue-500 text-blue-600" : "border-transparent text-gray-500 hover:text-gray-700", "whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"])}" data-v-7527abb0> Synchronisation </button></nav></div>`);
      if (isLoading.value) {
        _push(`<div class="text-center py-8" data-v-7527abb0><div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600" data-v-7527abb0></div><p class="mt-2 text-gray-600" data-v-7527abb0>Chargement...</p></div>`);
      } else {
        _push(`<!---->`);
      }
      if (activeTab.value === "visualization") {
        _push(`<div class="bg-white rounded-lg shadow" data-v-7527abb0><div class="p-6" data-v-7527abb0><h2 class="text-xl font-semibold text-gray-900 mb-4" data-v-7527abb0>Visualisation Interactive du Graphe</h2><p class="text-gray-600 mb-6" data-v-7527abb0>Sélectionnez une entité pour visualiser ses relations dans le graphe</p>`);
        _push(ssrRenderComponent(GraphVisualizationSimple, {
          "initial-entity": _ctx.selectedEntity,
          "initial-item": _ctx.selectedItem
        }, null, _parent));
        _push(`</div></div>`);
      } else {
        _push(`<!---->`);
      }
      if (activeTab.value === "analytics") {
        _push(`<div class="bg-white rounded-lg shadow" data-v-7527abb0><div class="p-6" data-v-7527abb0><h2 class="text-xl font-semibold text-gray-900 mb-4" data-v-7527abb0>Analyses Prédéfinies</h2><p class="text-gray-600 mb-6" data-v-7527abb0>Consultez des analyses prédéfinies sur les données de votre plateforme</p><div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" data-v-7527abb0><div class="bg-gray-50 rounded-lg p-4" data-v-7527abb0><h3 class="font-semibold text-gray-900 mb-2" data-v-7527abb0>📈 Métriques Globales</h3>`);
        if (globalMetrics.value) {
          _push(`<div class="space-y-2 mb-3" data-v-7527abb0><div class="flex justify-between text-sm" data-v-7527abb0><span data-v-7527abb0>Utilisateurs:</span><span class="font-medium" data-v-7527abb0>${ssrInterpolate(globalMetrics.value.total_users)}</span></div><div class="flex justify-between text-sm" data-v-7527abb0><span data-v-7527abb0>Clubs:</span><span class="font-medium" data-v-7527abb0>${ssrInterpolate(globalMetrics.value.total_clubs)}</span></div><div class="flex justify-between text-sm" data-v-7527abb0><span data-v-7527abb0>Enseignants:</span><span class="font-medium" data-v-7527abb0>${ssrInterpolate(globalMetrics.value.total_teachers)}</span></div><div class="flex justify-between text-sm" data-v-7527abb0><span data-v-7527abb0>Contrats:</span><span class="font-medium" data-v-7527abb0>${ssrInterpolate(globalMetrics.value.total_contracts)}</span></div></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<button class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700" data-v-7527abb0> 🔄 Actualiser </button></div><div class="bg-gray-50 rounded-lg p-4" data-v-7527abb0><h3 class="font-semibold text-gray-900 mb-2" data-v-7527abb0>👥 Relations Utilisateurs-Clubs</h3>`);
        if (userClubRelations.value.length > 0) {
          _push(`<div class="space-y-2 max-h-32 overflow-y-auto mb-3" data-v-7527abb0><!--[-->`);
          ssrRenderList(userClubRelations.value.slice(0, 3), (relation) => {
            _push(`<div class="text-sm" data-v-7527abb0><div class="font-medium" data-v-7527abb0>${ssrInterpolate(relation.club_name)}</div><div class="text-gray-600" data-v-7527abb0>${ssrInterpolate(relation.member_count)} membres</div></div>`);
          });
          _push(`<!--]--></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<button class="w-full px-4 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700" data-v-7527abb0> 📈 Analyser </button></div><div class="bg-gray-50 rounded-lg p-4" data-v-7527abb0><h3 class="font-semibold text-gray-900 mb-2" data-v-7527abb0>🎯 Enseignants par Spécialité</h3>`);
        if (teachersBySpecialty.value.length > 0) {
          _push(`<div class="space-y-2 max-h-32 overflow-y-auto mb-3" data-v-7527abb0><!--[-->`);
          ssrRenderList(teachersBySpecialty.value.slice(0, 3), (specialty) => {
            _push(`<div class="text-sm" data-v-7527abb0><div class="font-medium" data-v-7527abb0>${ssrInterpolate(specialty.specialty)}</div><div class="text-gray-600" data-v-7527abb0>${ssrInterpolate(specialty.teacher_count)} enseignants</div></div>`);
          });
          _push(`<!--]--></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<button class="w-full px-4 py-2 bg-purple-600 text-white rounded-lg font-medium hover:bg-purple-700" data-v-7527abb0> 🎯 Analyser </button></div><div class="bg-gray-50 rounded-lg p-4" data-v-7527abb0><h3 class="font-semibold text-gray-900 mb-2" data-v-7527abb0>🌍 Répartition Géographique</h3>`);
        if (geographicDistribution.value.length > 0) {
          _push(`<div class="space-y-2 max-h-32 overflow-y-auto mb-3" data-v-7527abb0><!--[-->`);
          ssrRenderList(geographicDistribution.value.slice(0, 3), (location) => {
            _push(`<div class="text-sm" data-v-7527abb0><div class="font-medium" data-v-7527abb0>${ssrInterpolate(location.club_city)}</div><div class="text-gray-600" data-v-7527abb0>${ssrInterpolate(location.clubs_count)} clubs</div></div>`);
          });
          _push(`<!--]--></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<button class="w-full px-4 py-2 bg-orange-600 text-white rounded-lg font-medium hover:bg-orange-700" data-v-7527abb0> 🌍 Analyser </button></div><div class="bg-gray-50 rounded-lg p-4" data-v-7527abb0><h3 class="font-semibold text-gray-900 mb-2" data-v-7527abb0>🏆 Performance des Clubs</h3>`);
        if (clubPerformance.value.length > 0) {
          _push(`<div class="space-y-2 max-h-32 overflow-y-auto mb-3" data-v-7527abb0><!--[-->`);
          ssrRenderList(clubPerformance.value.slice(0, 3), (club) => {
            _push(`<div class="text-sm" data-v-7527abb0><div class="font-medium" data-v-7527abb0>${ssrInterpolate(club.club_name)}</div><div class="text-gray-600" data-v-7527abb0>${ssrInterpolate(club.members_count)} membres, ${ssrInterpolate(club.teachers_count)} enseignants</div></div>`);
          });
          _push(`<!--]--></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<button class="w-full px-4 py-2 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700" data-v-7527abb0> 🏆 Analyser </button></div><div class="bg-gray-50 rounded-lg p-4" data-v-7527abb0><h3 class="font-semibold text-gray-900 mb-2" data-v-7527abb0>🔥 Spécialités Populaires</h3>`);
        if (mostDemandedSpecialties.value.length > 0) {
          _push(`<div class="space-y-2 max-h-32 overflow-y-auto mb-3" data-v-7527abb0><!--[-->`);
          ssrRenderList(mostDemandedSpecialties.value.slice(0, 3), (specialty) => {
            _push(`<div class="text-sm" data-v-7527abb0><div class="font-medium" data-v-7527abb0>${ssrInterpolate(specialty.specialty)}</div><div class="text-gray-600" data-v-7527abb0>${ssrInterpolate(specialty.contracts_count)} contrats</div></div>`);
          });
          _push(`<!--]--></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<button class="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700" data-v-7527abb0> 🔥 Analyser </button></div></div></div></div>`);
      } else {
        _push(`<!---->`);
      }
      if (activeTab.value === "sync") {
        _push(`<div class="bg-white rounded-lg shadow" data-v-7527abb0><div class="p-6" data-v-7527abb0><h2 class="text-xl font-semibold text-gray-900 mb-4" data-v-7527abb0>Synchronisation Neo4j</h2><p class="text-gray-600 mb-6" data-v-7527abb0>Gérez la synchronisation des données MySQL vers Neo4j</p>`);
        if (syncStats.value) {
          _push(`<div class="mb-6" data-v-7527abb0><h3 class="font-medium text-gray-900 mb-3" data-v-7527abb0>Statut de synchronisation</h3><div class="grid grid-cols-2 md:grid-cols-4 gap-4" data-v-7527abb0><!--[-->`);
          ssrRenderList(syncStats.value.sync_status, (status, entity) => {
            _push(`<div class="${ssrRenderClass([status.synced ? "bg-green-50" : "bg-red-50", "text-center p-3 rounded-lg"])}" data-v-7527abb0><div class="font-medium capitalize" data-v-7527abb0>${ssrInterpolate(entity)}</div><div class="text-sm text-gray-600" data-v-7527abb0>${ssrInterpolate(status.neo4j_count)} / ${ssrInterpolate(status.mysql_count)}</div><div class="${ssrRenderClass([status.synced ? "text-green-600" : "text-red-600", "text-xs"])}" data-v-7527abb0>${ssrInterpolate(status.percentage)}% </div></div>`);
          });
          _push(`<!--]--></div></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6" data-v-7527abb0><button${ssrIncludeBooleanAttr(isSyncing.value) ? " disabled" : ""} class="px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 disabled:bg-gray-400" data-v-7527abb0>${ssrInterpolate(isSyncing.value ? "⏳ Synchronisation..." : "🔄 Synchronisation complète")}</button><button class="px-4 py-2 bg-gray-600 text-white rounded-lg font-medium hover:bg-gray-700" data-v-7527abb0> 📊 Actualiser le statut </button></div>`);
        if (syncLogs.value.length > 0) {
          _push(`<div data-v-7527abb0><h3 class="font-medium text-gray-900 mb-3" data-v-7527abb0>Logs de synchronisation</h3><div class="bg-gray-50 p-4 rounded-lg max-h-64 overflow-y-auto" data-v-7527abb0><!--[-->`);
          ssrRenderList(syncLogs.value, (log) => {
            _push(`<div class="${ssrRenderClass([log.type === "error" ? "text-red-600" : "text-gray-700", "text-sm font-mono"])}" data-v-7527abb0> [${ssrInterpolate(log.timestamp)}] ${ssrInterpolate(log.message)}</div>`);
          });
          _push(`<!--]--></div></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/admin/graph-analysis.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const graphAnalysis = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-7527abb0"]]);
export {
  graphAnalysis as default
};
//# sourceMappingURL=graph-analysis-gi226PqM.js.map
