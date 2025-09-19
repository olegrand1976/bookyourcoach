import { ref, computed, mergeProps, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderClass, ssrInterpolate, ssrIncludeBooleanAttr, ssrLooseContain, ssrRenderAttr, ssrRenderList, ssrLooseEqual } from 'vue/server-renderer';

const _sfc_main = {
  __name: "contracts",
  __ssrInlineRender: true,
  setup(__props) {
    const loading = ref(true);
    const isSaving = ref(false);
    const error = ref(null);
    const activeTab = ref("types");
    const contractTypes = ref({
      volunteer: {
        active: true,
        annual_ceiling: 3900,
        daily_ceiling: 42.31,
        mileage_allowance: 0.4,
        max_annual_mileage: 2e3
      },
      student: {
        active: false,
        annual_ceiling: 0,
        daily_ceiling: 0
      },
      article17: {
        active: false,
        annual_ceiling: 0,
        daily_ceiling: 0
      },
      freelance: {
        active: false,
        annual_ceiling: 0,
        daily_ceiling: 0
      },
      salaried: {
        active: false,
        annual_ceiling: 0,
        daily_ceiling: 0
      }
    });
    const teachers = ref([]);
    const selectedYear = ref((/* @__PURE__ */ new Date()).getFullYear());
    const availableYears = ref([]);
    const selectedPeriod = ref("monthly");
    const totalPayments = ref(0);
    const totalHours = ref(0);
    const activeTeachers = ref(0);
    const exceededContracts = ref(0);
    const exceedanceFilter = ref("all");
    const exceedanceThresholds = ref({
      orange: 80,
      red: 95
    });
    const exceedanceStats = ref({
      green: 0,
      orange: 0,
      red: 0,
      black: 0
    });
    const contractTypeLabels = {
      volunteer: "B\xE9n\xE9vole",
      student: "\xC9tudiant",
      article17: "Article 17",
      freelance: "Ind\xE9pendant",
      salaried: "Salari\xE9"
    };
    const getContractTypeLabel = (type) => {
      return contractTypeLabels[type] || type;
    };
    const getContractStatusClass = (contract) => {
      if (!contract) return "bg-gray-100 text-gray-800";
      const classes = {
        volunteer: "bg-green-100 text-green-800",
        student: "bg-blue-100 text-blue-800",
        article17: "bg-purple-100 text-purple-800",
        freelance: "bg-yellow-100 text-yellow-800",
        salaried: "bg-indigo-100 text-indigo-800"
      };
      return classes[contract.type] || "bg-gray-100 text-gray-800";
    };
    const getExceedanceIndicators = (teacher) => {
      if (!teacher.current_contract) return [];
      const indicators = [];
      const contractType = contractTypes.value[teacher.current_contract.type];
      if (!(contractType == null ? void 0 : contractType.active)) return indicators;
      const annualPercentage = (teacher.annual_amount || 0) / contractType.annual_ceiling * 100;
      const dailyPercentage = (teacher.daily_amount || 0) / contractType.daily_ceiling * 100;
      const mileagePercentage = (teacher.annual_mileage || 0) / (contractType.max_annual_mileage || 1) * 100;
      let status = "green";
      let label = "Dans les limites";
      let tooltip = "Dans les limites";
      if (annualPercentage >= 100 || dailyPercentage >= 100 || mileagePercentage >= 100) {
        status = "black";
        label = "D\xE9pass\xE9";
        tooltip = `D\xE9passement: ${annualPercentage >= 100 ? "Plafond annuel" : ""} ${dailyPercentage >= 100 ? "Plafond journalier" : ""} ${mileagePercentage >= 100 ? "Kilom\xE9trage" : ""}`;
      } else if (annualPercentage >= exceedanceThresholds.value.red || dailyPercentage >= exceedanceThresholds.value.red || mileagePercentage >= exceedanceThresholds.value.red) {
        status = "red";
        label = "Critique";
        tooltip = `Critique: ${annualPercentage >= exceedanceThresholds.value.red ? "Plafond annuel" : ""} ${dailyPercentage >= exceedanceThresholds.value.red ? "Plafond journalier" : ""} ${mileagePercentage >= exceedanceThresholds.value.red ? "Kilom\xE9trage" : ""}`;
      } else if (annualPercentage >= exceedanceThresholds.value.orange || dailyPercentage >= exceedanceThresholds.value.orange || mileagePercentage >= exceedanceThresholds.value.orange) {
        status = "orange";
        label = "Attention";
        tooltip = `Attention: ${annualPercentage >= exceedanceThresholds.value.orange ? "Plafond annuel" : ""} ${dailyPercentage >= exceedanceThresholds.value.orange ? "Plafond journalier" : ""} ${mileagePercentage >= exceedanceThresholds.value.orange ? "Kilom\xE9trage" : ""}`;
      }
      indicators.push({
        type: status,
        status,
        label,
        color: getStatusColor(status),
        tooltip
      });
      return indicators;
    };
    const getStatusColor = (status) => {
      const colors = {
        green: "bg-green-500",
        orange: "bg-orange-500",
        red: "bg-red-500",
        black: "bg-gray-800"
      };
      return colors[status] || "bg-gray-500";
    };
    const getIndicatorBadgeClass = (status) => {
      const classes = {
        green: "bg-green-100 text-green-800 border-green-200",
        orange: "bg-orange-100 text-orange-800 border-orange-200",
        red: "bg-red-100 text-red-800 border-red-200",
        black: "bg-gray-100 text-gray-800 border-gray-200"
      };
      return classes[status] || "bg-gray-100 text-gray-800 border-gray-200";
    };
    const getPaymentStatusClass = (status) => {
      const classes = {
        paid: "bg-green-100 text-green-800",
        pending: "bg-yellow-100 text-yellow-800",
        overdue: "bg-red-100 text-red-800",
        cancelled: "bg-gray-100 text-gray-800"
      };
      return classes[status] || "bg-gray-100 text-gray-800";
    };
    const formatDate = (date) => {
      if (!date) return "";
      return new Date(date).toLocaleDateString("fr-FR");
    };
    const filteredTeachers = computed(() => {
      if (exceedanceFilter.value === "all") {
        return teachers.value;
      }
      return teachers.value.filter((teacher) => {
        const indicators = getExceedanceIndicators(teacher);
        if (indicators.length > 0) {
          return indicators[0].status === exceedanceFilter.value;
        }
        return false;
      });
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "p-8" }, _attrs))}><h1 class="text-3xl font-bold text-gray-900 mb-2">Gestion des Contrats</h1><p class="text-gray-600 mb-8">G\xE9rez les types de contrats et suivez les contrats des enseignants.</p><div class="mb-8"><nav class="flex space-x-8"><button class="${ssrRenderClass([activeTab.value === "types" ? "border-blue-500 text-blue-600" : "border-transparent text-gray-500 hover:text-gray-700", "whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"])}"> Types de Contrats </button><button class="${ssrRenderClass([activeTab.value === "teachers" ? "border-blue-500 text-blue-600" : "border-transparent text-gray-500 hover:text-gray-700", "whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"])}"> Enseignants &amp; Contrats </button><button class="${ssrRenderClass([activeTab.value === "payments" ? "border-blue-500 text-blue-600" : "border-transparent text-gray-500 hover:text-gray-700", "whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"])}"> Paiements &amp; Heures </button><button class="${ssrRenderClass([activeTab.value === "settings" ? "border-blue-500 text-blue-600" : "border-transparent text-gray-500 hover:text-gray-700", "whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"])}"> Param\xE8tres </button></nav></div>`);
      if (loading.value) {
        _push(`<div class="text-center py-8"><div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div><p class="mt-2 text-gray-600">Chargement...</p></div>`);
      } else if (error.value) {
        _push(`<div class="bg-red-50 border border-red-200 text-red-800 p-4 rounded-lg"><p>Une erreur est survenue : ${ssrInterpolate(error.value)}</p></div>`);
      } else if (activeTab.value === "types") {
        _push(`<div class="space-y-6"><div class="bg-white rounded-lg shadow border border-gray-200 p-6"><h2 class="text-xl font-bold text-gray-800 mb-4">Configuration des Types de Contrats</h2><form class="space-y-6"><div class="border border-gray-200 rounded-lg p-6"><div class="flex items-center justify-between mb-4"><div><h3 class="text-lg font-semibold text-gray-800">Contrat B\xE9n\xE9vole</h3><p class="text-gray-500 text-sm">Plafonds et indemnit\xE9s pour les volontaires</p></div><div class="flex items-center space-x-2"><span class="text-sm text-gray-500">Actif</span><label class="relative inline-flex items-center cursor-pointer"><input${ssrIncludeBooleanAttr(Array.isArray(contractTypes.value.volunteer.active) ? ssrLooseContain(contractTypes.value.volunteer.active, null) : contractTypes.value.volunteer.active) ? " checked" : ""} type="checkbox" class="sr-only peer"><div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[&#39;&#39;] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div></label></div></div>`);
        if (contractTypes.value.volunteer.active) {
          _push(`<div class="grid grid-cols-1 md:grid-cols-2 gap-6"><div><label class="block text-sm font-medium text-gray-700 mb-2">Plafond annuel (\u20AC)</label><input${ssrRenderAttr("value", contractTypes.value.volunteer.annual_ceiling)} type="number" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div><div><label class="block text-sm font-medium text-gray-700 mb-2">Plafond journalier (\u20AC)</label><input${ssrRenderAttr("value", contractTypes.value.volunteer.daily_ceiling)} type="number" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div><div><label class="block text-sm font-medium text-gray-700 mb-2">Indemnit\xE9 kilom\xE9trique (\u20AC/km)</label><input${ssrRenderAttr("value", contractTypes.value.volunteer.mileage_allowance)} type="number" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div><div><label class="block text-sm font-medium text-gray-700 mb-2">Plafond kilom\xE9trique annuel (km)</label><input${ssrRenderAttr("value", contractTypes.value.volunteer.max_annual_mileage)} type="number" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div><div class="border border-gray-200 rounded-lg p-6"><div class="flex items-center justify-between mb-4"><div><h3 class="text-lg font-semibold text-gray-800">Contrat \xC9tudiant</h3><p class="text-gray-500 text-sm">Contrat pour \xE9tudiants en formation</p></div><div class="flex items-center space-x-2"><span class="text-sm text-gray-500">Actif</span><label class="relative inline-flex items-center cursor-pointer"><input${ssrIncludeBooleanAttr(Array.isArray(contractTypes.value.student.active) ? ssrLooseContain(contractTypes.value.student.active, null) : contractTypes.value.student.active) ? " checked" : ""} type="checkbox" class="sr-only peer"><div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[&#39;&#39;] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div></label></div></div>`);
        if (contractTypes.value.student.active) {
          _push(`<div class="grid grid-cols-1 md:grid-cols-2 gap-6"><div><label class="block text-sm font-medium text-gray-700 mb-2">Plafond annuel (\u20AC)</label><input${ssrRenderAttr("value", contractTypes.value.student.annual_ceiling)} type="number" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div><div><label class="block text-sm font-medium text-gray-700 mb-2">Plafond journalier (\u20AC)</label><input${ssrRenderAttr("value", contractTypes.value.student.daily_ceiling)} type="number" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div><div class="border border-gray-200 rounded-lg p-6"><div class="flex items-center justify-between mb-4"><div><h3 class="text-lg font-semibold text-gray-800">Contrat Article 17</h3><p class="text-gray-500 text-sm">Contrat sp\xE9cifique Article 17</p></div><div class="flex items-center space-x-2"><span class="text-sm text-gray-500">Actif</span><label class="relative inline-flex items-center cursor-pointer"><input${ssrIncludeBooleanAttr(Array.isArray(contractTypes.value.article17.active) ? ssrLooseContain(contractTypes.value.article17.active, null) : contractTypes.value.article17.active) ? " checked" : ""} type="checkbox" class="sr-only peer"><div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[&#39;&#39;] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div></label></div></div>`);
        if (contractTypes.value.article17.active) {
          _push(`<div class="grid grid-cols-1 md:grid-cols-2 gap-6"><div><label class="block text-sm font-medium text-gray-700 mb-2">Plafond annuel (\u20AC)</label><input${ssrRenderAttr("value", contractTypes.value.article17.annual_ceiling)} type="number" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div><div><label class="block text-sm font-medium text-gray-700 mb-2">Plafond journalier (\u20AC)</label><input${ssrRenderAttr("value", contractTypes.value.article17.daily_ceiling)} type="number" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div><div class="border border-gray-200 rounded-lg p-6"><div class="flex items-center justify-between mb-4"><div><h3 class="text-lg font-semibold text-gray-800">Contrat Ind\xE9pendant</h3><p class="text-gray-500 text-sm">Travailleur ind\xE9pendant</p></div><div class="flex items-center space-x-2"><span class="text-sm text-gray-500">Actif</span><label class="relative inline-flex items-center cursor-pointer"><input${ssrIncludeBooleanAttr(Array.isArray(contractTypes.value.freelance.active) ? ssrLooseContain(contractTypes.value.freelance.active, null) : contractTypes.value.freelance.active) ? " checked" : ""} type="checkbox" class="sr-only peer"><div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[&#39;&#39;] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div></label></div></div>`);
        if (contractTypes.value.freelance.active) {
          _push(`<div class="grid grid-cols-1 md:grid-cols-2 gap-6"><div><label class="block text-sm font-medium text-gray-700 mb-2">Plafond annuel (\u20AC)</label><input${ssrRenderAttr("value", contractTypes.value.freelance.annual_ceiling)} type="number" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div><div><label class="block text-sm font-medium text-gray-700 mb-2">Plafond journalier (\u20AC)</label><input${ssrRenderAttr("value", contractTypes.value.freelance.daily_ceiling)} type="number" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div><div class="border border-gray-200 rounded-lg p-6"><div class="flex items-center justify-between mb-4"><div><h3 class="text-lg font-semibold text-gray-800">Contrat Salari\xE9</h3><p class="text-gray-500 text-sm">Employ\xE9 salari\xE9</p></div><div class="flex items-center space-x-2"><span class="text-sm text-gray-500">Actif</span><label class="relative inline-flex items-center cursor-pointer"><input${ssrIncludeBooleanAttr(Array.isArray(contractTypes.value.salaried.active) ? ssrLooseContain(contractTypes.value.salaried.active, null) : contractTypes.value.salaried.active) ? " checked" : ""} type="checkbox" class="sr-only peer"><div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[&#39;&#39;] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div></label></div></div>`);
        if (contractTypes.value.salaried.active) {
          _push(`<div class="grid grid-cols-1 md:grid-cols-2 gap-6"><div><label class="block text-sm font-medium text-gray-700 mb-2">Plafond annuel (\u20AC)</label><input${ssrRenderAttr("value", contractTypes.value.salaried.annual_ceiling)} type="number" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div><div><label class="block text-sm font-medium text-gray-700 mb-2">Plafond journalier (\u20AC)</label><input${ssrRenderAttr("value", contractTypes.value.salaried.daily_ceiling)} type="number" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div><div class="flex justify-end pt-6 border-t border-gray-200"><button type="submit"${ssrIncludeBooleanAttr(isSaving.value) ? " disabled" : ""} class="px-6 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 disabled:opacity-50">`);
        if (isSaving.value) {
          _push(`<span>Sauvegarde...</span>`);
        } else {
          _push(`<span>Enregistrer les modifications</span>`);
        }
        _push(`</button></div></form></div></div>`);
      } else if (activeTab.value === "teachers") {
        _push(`<div class="space-y-6"><div class="bg-white rounded-lg shadow border border-gray-200 p-6"><div class="flex items-center justify-between mb-6"><h2 class="text-xl font-bold text-gray-800">Enseignants &amp; Contrats</h2><div class="flex space-x-3"><select class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"><!--[-->`);
        ssrRenderList(availableYears.value, (year) => {
          _push(`<option${ssrRenderAttr("value", year)}${ssrIncludeBooleanAttr(Array.isArray(selectedYear.value) ? ssrLooseContain(selectedYear.value, year) : ssrLooseEqual(selectedYear.value, year)) ? " selected" : ""}>${ssrInterpolate(year)}</option>`);
        });
        _push(`<!--]--></select><select class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"><option value="all"${ssrIncludeBooleanAttr(Array.isArray(exceedanceFilter.value) ? ssrLooseContain(exceedanceFilter.value, "all") : ssrLooseEqual(exceedanceFilter.value, "all")) ? " selected" : ""}>Tous les enseignants</option><option value="green"${ssrIncludeBooleanAttr(Array.isArray(exceedanceFilter.value) ? ssrLooseContain(exceedanceFilter.value, "green") : ssrLooseEqual(exceedanceFilter.value, "green")) ? " selected" : ""}>\u{1F7E2} Dans les limites</option><option value="orange"${ssrIncludeBooleanAttr(Array.isArray(exceedanceFilter.value) ? ssrLooseContain(exceedanceFilter.value, "orange") : ssrLooseEqual(exceedanceFilter.value, "orange")) ? " selected" : ""}>\u{1F7E0} Zone d&#39;attention</option><option value="red"${ssrIncludeBooleanAttr(Array.isArray(exceedanceFilter.value) ? ssrLooseContain(exceedanceFilter.value, "red") : ssrLooseEqual(exceedanceFilter.value, "red")) ? " selected" : ""}>\u{1F534} Zone critique</option><option value="black"${ssrIncludeBooleanAttr(Array.isArray(exceedanceFilter.value) ? ssrLooseContain(exceedanceFilter.value, "black") : ssrLooseEqual(exceedanceFilter.value, "black")) ? " selected" : ""}>\u26AB D\xE9passements</option></select><button class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700"> Actualiser </button></div></div><div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6"><h3 class="text-lg font-semibold text-gray-800 mb-4">R\xE9partition par Statut de D\xE9passement</h3><div class="grid grid-cols-1 md:grid-cols-4 gap-4"><div class="${ssrRenderClass([{ "ring-2 ring-green-500": exceedanceFilter.value === "green" }, "bg-green-50 border border-green-200 rounded-lg p-3 cursor-pointer hover:bg-green-100 transition-colors"])}"><div class="flex items-center justify-between"><div class="flex items-center space-x-2"><div class="w-3 h-3 bg-green-500 rounded-full"></div><div><p class="text-sm font-medium text-gray-600">Dans les limites</p><p class="text-xs text-gray-500">&lt; ${ssrInterpolate(exceedanceThresholds.value.orange)}%</p></div></div><div class="text-right"><p class="text-xl font-bold text-green-600">${ssrInterpolate(exceedanceStats.value.green)}</p></div></div></div><div class="${ssrRenderClass([{ "ring-2 ring-orange-500": exceedanceFilter.value === "orange" }, "bg-orange-50 border border-orange-200 rounded-lg p-3 cursor-pointer hover:bg-orange-100 transition-colors"])}"><div class="flex items-center justify-between"><div class="flex items-center space-x-2"><div class="w-3 h-3 bg-orange-500 rounded-full"></div><div><p class="text-sm font-medium text-gray-600">Zone d&#39;attention</p><p class="text-xs text-gray-500">${ssrInterpolate(exceedanceThresholds.value.orange)}% - ${ssrInterpolate(exceedanceThresholds.value.red)}%</p></div></div><div class="text-right"><p class="text-xl font-bold text-orange-600">${ssrInterpolate(exceedanceStats.value.orange)}</p></div></div></div><div class="${ssrRenderClass([{ "ring-2 ring-red-500": exceedanceFilter.value === "red" }, "bg-red-50 border border-red-200 rounded-lg p-3 cursor-pointer hover:bg-red-100 transition-colors"])}"><div class="flex items-center justify-between"><div class="flex items-center space-x-2"><div class="w-3 h-3 bg-red-500 rounded-full"></div><div><p class="text-sm font-medium text-gray-600">Zone critique</p><p class="text-xs text-gray-500">${ssrInterpolate(exceedanceThresholds.value.red)}% - 100%</p></div></div><div class="text-right"><p class="text-xl font-bold text-red-600">${ssrInterpolate(exceedanceStats.value.red)}</p></div></div></div><div class="${ssrRenderClass([{ "ring-2 ring-gray-500": exceedanceFilter.value === "black" }, "bg-gray-50 border border-gray-200 rounded-lg p-3 cursor-pointer hover:bg-gray-100 transition-colors"])}"><div class="flex items-center justify-between"><div class="flex items-center space-x-2"><div class="w-3 h-3 bg-gray-800 rounded-full"></div><div><p class="text-sm font-medium text-gray-600">D\xE9passements</p><p class="text-xs text-gray-500">&gt; 100%</p></div></div><div class="text-right"><p class="text-xl font-bold text-gray-800">${ssrInterpolate(exceedanceStats.value.black)}</p></div></div></div></div></div><div class="space-y-4"><!--[-->`);
        ssrRenderList(filteredTeachers.value, (teacher) => {
          var _a, _b, _c;
          _push(`<div class="border border-gray-200 rounded-lg p-4"><div class="flex items-center justify-between mb-4"><div class="flex items-center space-x-4"><div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center"><span class="text-blue-600 font-semibold">${ssrInterpolate(((_a = teacher.first_name) == null ? void 0 : _a.charAt(0)) || ((_b = teacher.name) == null ? void 0 : _b.charAt(0)))}</span></div><div><h3 class="font-semibold text-gray-900">${ssrInterpolate(teacher.first_name)} ${ssrInterpolate(teacher.last_name)}</h3><p class="text-sm text-gray-500">${ssrInterpolate(teacher.email)}</p></div></div><div class="flex items-center space-x-3"><span class="${ssrRenderClass([getContractStatusClass(teacher.current_contract), "px-3 py-1 rounded-full text-sm font-medium"])}">${ssrInterpolate(getContractTypeLabel((_c = teacher.current_contract) == null ? void 0 : _c.type))}</span><div class="flex items-center space-x-2"><!--[-->`);
          ssrRenderList(getExceedanceIndicators(teacher), (indicator) => {
            _push(`<div class="${ssrRenderClass([getIndicatorBadgeClass(indicator.status), "flex items-center space-x-1 px-2 py-1 rounded-full text-xs font-medium"])}"><div class="${ssrRenderClass([indicator.color, "w-2 h-2 rounded-full"])}"></div><span>${ssrInterpolate(indicator.label)}</span></div>`);
          });
          _push(`<!--]--></div>`);
          if (getExceedanceIndicators(teacher).length > 0) {
            _push(`<div class="flex space-x-1"><!--[-->`);
            ssrRenderList(getExceedanceIndicators(teacher), (indicator) => {
              _push(`<div class="${ssrRenderClass([indicator.color, "w-4 h-4 rounded-full border-2 border-white shadow-sm"])}"${ssrRenderAttr("title", indicator.tooltip)}></div>`);
            });
            _push(`<!--]--></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div></div>`);
          if (teacher.current_contract) {
            _push(`<div class="bg-gray-50 rounded-lg p-4 mb-4"><h4 class="font-medium text-gray-900 mb-3">Contrat Actuel</h4><div class="grid grid-cols-1 md:grid-cols-3 gap-4"><div><span class="text-sm text-gray-500">Type:</span><p class="font-medium">${ssrInterpolate(getContractTypeLabel(teacher.current_contract.type))}</p></div><div><span class="text-sm text-gray-500">D\xE9but:</span><p class="font-medium">${ssrInterpolate(formatDate(teacher.current_contract.start_date))}</p></div><div><span class="text-sm text-gray-500">Fin:</span><p class="font-medium">${ssrInterpolate(teacher.current_contract.end_date ? formatDate(teacher.current_contract.end_date) : "Ind\xE9termin\xE9")}</p></div></div></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`<div class="border-t pt-4"><h4 class="font-medium text-gray-900 mb-3">Historique des Contrats</h4><div class="space-y-2"><!--[-->`);
          ssrRenderList(teacher.contract_history, (contract) => {
            _push(`<div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"><div class="flex items-center space-x-4"><span class="${ssrRenderClass([getContractStatusClass(contract), "px-2 py-1 rounded text-xs font-medium"])}">${ssrInterpolate(getContractTypeLabel(contract.type))}</span><span class="text-sm text-gray-600">${ssrInterpolate(formatDate(contract.start_date))} - ${ssrInterpolate(contract.end_date ? formatDate(contract.end_date) : "En cours")}</span></div><div class="text-sm text-gray-500">${ssrInterpolate(contract.total_hours || 0)}h \u2022 ${ssrInterpolate(contract.total_amount || 0)}\u20AC </div></div>`);
          });
          _push(`<!--]--></div></div></div>`);
        });
        _push(`<!--]--></div></div></div>`);
      } else if (activeTab.value === "payments") {
        _push(`<div class="space-y-6"><div class="bg-white rounded-lg shadow border border-gray-200 p-6"><div class="flex items-center justify-between mb-6"><h2 class="text-xl font-bold text-gray-800">Paiements &amp; Heures</h2><div class="flex space-x-3"><select class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"><option value="monthly"${ssrIncludeBooleanAttr(Array.isArray(selectedPeriod.value) ? ssrLooseContain(selectedPeriod.value, "monthly") : ssrLooseEqual(selectedPeriod.value, "monthly")) ? " selected" : ""}>Mensuel</option><option value="yearly"${ssrIncludeBooleanAttr(Array.isArray(selectedPeriod.value) ? ssrLooseContain(selectedPeriod.value, "yearly") : ssrLooseEqual(selectedPeriod.value, "yearly")) ? " selected" : ""}>Annuel</option></select><select class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"><!--[-->`);
        ssrRenderList(availableYears.value, (year) => {
          _push(`<option${ssrRenderAttr("value", year)}${ssrIncludeBooleanAttr(Array.isArray(selectedYear.value) ? ssrLooseContain(selectedYear.value, year) : ssrLooseEqual(selectedYear.value, year)) ? " selected" : ""}>${ssrInterpolate(year)}</option>`);
        });
        _push(`<!--]--></select><select class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"><option value="all"${ssrIncludeBooleanAttr(Array.isArray(exceedanceFilter.value) ? ssrLooseContain(exceedanceFilter.value, "all") : ssrLooseEqual(exceedanceFilter.value, "all")) ? " selected" : ""}>Tous les enseignants</option><option value="green"${ssrIncludeBooleanAttr(Array.isArray(exceedanceFilter.value) ? ssrLooseContain(exceedanceFilter.value, "green") : ssrLooseEqual(exceedanceFilter.value, "green")) ? " selected" : ""}>\u{1F7E2} Dans les limites</option><option value="orange"${ssrIncludeBooleanAttr(Array.isArray(exceedanceFilter.value) ? ssrLooseContain(exceedanceFilter.value, "orange") : ssrLooseEqual(exceedanceFilter.value, "orange")) ? " selected" : ""}>\u{1F7E0} Zone d&#39;attention</option><option value="red"${ssrIncludeBooleanAttr(Array.isArray(exceedanceFilter.value) ? ssrLooseContain(exceedanceFilter.value, "red") : ssrLooseEqual(exceedanceFilter.value, "red")) ? " selected" : ""}>\u{1F534} Zone critique</option><option value="black"${ssrIncludeBooleanAttr(Array.isArray(exceedanceFilter.value) ? ssrLooseContain(exceedanceFilter.value, "black") : ssrLooseEqual(exceedanceFilter.value, "black")) ? " selected" : ""}>\u26AB D\xE9passements</option></select></div></div><div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8"><div class="bg-blue-50 rounded-lg p-4"><div class="flex items-center"><div class="p-2 bg-blue-100 rounded-lg"><svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path></svg></div><div class="ml-4"><p class="text-sm font-medium text-gray-600">Total Paiements</p><p class="text-2xl font-semibold text-gray-900">${ssrInterpolate(totalPayments.value)}\u20AC</p></div></div></div><div class="bg-green-50 rounded-lg p-4"><div class="flex items-center"><div class="p-2 bg-green-100 rounded-lg"><svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></div><div class="ml-4"><p class="text-sm font-medium text-gray-600">Total Heures</p><p class="text-2xl font-semibold text-gray-900">${ssrInterpolate(totalHours.value)}h</p></div></div></div><div class="bg-purple-50 rounded-lg p-4"><div class="flex items-center"><div class="p-2 bg-purple-100 rounded-lg"><svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg></div><div class="ml-4"><p class="text-sm font-medium text-gray-600">Enseignants Actifs</p><p class="text-2xl font-semibold text-gray-900">${ssrInterpolate(activeTeachers.value)}</p></div></div></div><div class="bg-orange-50 rounded-lg p-4"><div class="flex items-center"><div class="p-2 bg-orange-100 rounded-lg"><svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg></div><div class="ml-4"><p class="text-sm font-medium text-gray-600">D\xE9passements</p><p class="text-2xl font-semibold text-gray-900">${ssrInterpolate(exceededContracts.value)}</p></div></div></div></div><div class="bg-white border border-gray-200 rounded-lg p-6 mb-8"><h3 class="text-lg font-semibold text-gray-800 mb-4">R\xE9partition par Statut de D\xE9passement</h3><div class="grid grid-cols-1 md:grid-cols-4 gap-4"><div class="${ssrRenderClass([{ "ring-2 ring-green-500": exceedanceFilter.value === "green" }, "bg-green-50 border border-green-200 rounded-lg p-4 cursor-pointer hover:bg-green-100 transition-colors"])}"><div class="flex items-center justify-between"><div class="flex items-center space-x-3"><div class="w-4 h-4 bg-green-500 rounded-full"></div><div><p class="text-sm font-medium text-gray-600">Dans les limites</p><p class="text-xs text-gray-500">&lt; ${ssrInterpolate(exceedanceThresholds.value.orange)}%</p></div></div><div class="text-right"><p class="text-2xl font-bold text-green-600">${ssrInterpolate(exceedanceStats.value.green)}</p><p class="text-xs text-gray-500">enseignants</p></div></div></div><div class="${ssrRenderClass([{ "ring-2 ring-orange-500": exceedanceFilter.value === "orange" }, "bg-orange-50 border border-orange-200 rounded-lg p-4 cursor-pointer hover:bg-orange-100 transition-colors"])}"><div class="flex items-center justify-between"><div class="flex items-center space-x-3"><div class="w-4 h-4 bg-orange-500 rounded-full"></div><div><p class="text-sm font-medium text-gray-600">Zone d&#39;attention</p><p class="text-xs text-gray-500">${ssrInterpolate(exceedanceThresholds.value.orange)}% - ${ssrInterpolate(exceedanceThresholds.value.red)}%</p></div></div><div class="text-right"><p class="text-2xl font-bold text-orange-600">${ssrInterpolate(exceedanceStats.value.orange)}</p><p class="text-xs text-gray-500">enseignants</p></div></div></div><div class="${ssrRenderClass([{ "ring-2 ring-red-500": exceedanceFilter.value === "red" }, "bg-red-50 border border-red-200 rounded-lg p-4 cursor-pointer hover:bg-red-100 transition-colors"])}"><div class="flex items-center justify-between"><div class="flex items-center space-x-3"><div class="w-4 h-4 bg-red-500 rounded-full"></div><div><p class="text-sm font-medium text-gray-600">Zone critique</p><p class="text-xs text-gray-500">${ssrInterpolate(exceedanceThresholds.value.red)}% - 100%</p></div></div><div class="text-right"><p class="text-2xl font-bold text-red-600">${ssrInterpolate(exceedanceStats.value.red)}</p><p class="text-xs text-gray-500">enseignants</p></div></div></div><div class="${ssrRenderClass([{ "ring-2 ring-gray-500": exceedanceFilter.value === "black" }, "bg-gray-50 border border-gray-200 rounded-lg p-4 cursor-pointer hover:bg-gray-100 transition-colors"])}"><div class="flex items-center justify-between"><div class="flex items-center space-x-3"><div class="w-4 h-4 bg-gray-800 rounded-full"></div><div><p class="text-sm font-medium text-gray-600">D\xE9passements</p><p class="text-xs text-gray-500">&gt; 100%</p></div></div><div class="text-right"><p class="text-2xl font-bold text-gray-800">${ssrInterpolate(exceedanceStats.value.black)}</p><p class="text-xs text-gray-500">enseignants</p></div></div></div></div></div><div class="space-y-4"><!--[-->`);
        ssrRenderList(filteredTeachers.value, (teacher) => {
          _push(`<div class="border border-gray-200 rounded-lg p-4"><div class="flex items-center justify-between mb-4"><h3 class="font-semibold text-gray-900">${ssrInterpolate(teacher.first_name)} ${ssrInterpolate(teacher.last_name)}</h3><div class="flex space-x-1"><!--[-->`);
          ssrRenderList(getExceedanceIndicators(teacher), (indicator) => {
            _push(`<div class="${ssrRenderClass([indicator.color, "w-3 h-3 rounded-full"])}"${ssrRenderAttr("title", indicator.tooltip)}></div>`);
          });
          _push(`<!--]--></div></div><div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4"><div class="bg-gray-50 rounded-lg p-3"><p class="text-sm text-gray-500">Heures ${ssrInterpolate(selectedPeriod.value === "monthly" ? "ce mois" : "cette ann\xE9e")}</p><p class="text-lg font-semibold">${ssrInterpolate(teacher.period_hours || 0)}h</p></div><div class="bg-gray-50 rounded-lg p-3"><p class="text-sm text-gray-500">Montant ${ssrInterpolate(selectedPeriod.value === "monthly" ? "ce mois" : "cette ann\xE9e")}</p><p class="text-lg font-semibold">${ssrInterpolate(teacher.period_amount || 0)}\u20AC</p></div><div class="bg-gray-50 rounded-lg p-3"><p class="text-sm text-gray-500">Kilom\xE9trage ${ssrInterpolate(selectedPeriod.value === "monthly" ? "ce mois" : "cette ann\xE9e")}</p><p class="text-lg font-semibold">${ssrInterpolate(teacher.period_mileage || 0)}km</p></div></div><div class="border-t pt-4"><h4 class="font-medium text-gray-900 mb-3">Historique des Paiements</h4><div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200"><thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Heures</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kilom\xE9trage</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th></tr></thead><tbody class="bg-white divide-y divide-gray-200"><!--[-->`);
          ssrRenderList(teacher.payments, (payment) => {
            _push(`<tr><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${ssrInterpolate(formatDate(payment.date))}</td><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${ssrInterpolate(payment.amount)}\u20AC</td><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${ssrInterpolate(payment.hours)}h</td><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${ssrInterpolate(payment.mileage || 0)}km</td><td class="px-6 py-4 whitespace-nowrap"><span class="${ssrRenderClass([getPaymentStatusClass(payment.status), "px-2 py-1 rounded-full text-xs font-medium"])}">${ssrInterpolate(payment.status)}</span></td></tr>`);
          });
          _push(`<!--]--></tbody></table></div></div></div>`);
        });
        _push(`<!--]--></div></div></div>`);
      } else if (activeTab.value === "settings") {
        _push(`<div class="space-y-6"><div class="bg-white rounded-lg shadow border border-gray-200 p-6"><h2 class="text-xl font-bold text-gray-800 mb-6">Param\xE8tres des Zones de D\xE9passement</h2><form class="space-y-6"><div class="bg-gray-50 rounded-lg p-6"><h3 class="text-lg font-semibold text-gray-800 mb-4">Seuils de D\xE9passement</h3><p class="text-sm text-gray-600 mb-6"> Configurez les pourcentages qui d\xE9finissent les diff\xE9rentes zones de d\xE9passement pour les contrats. </p><div class="grid grid-cols-1 md:grid-cols-2 gap-6"><div><label class="block text-sm font-medium text-gray-700 mb-2"> Seuil Zone d&#39;Attention (Orange) </label><div class="relative"><input${ssrRenderAttr("value", exceedanceThresholds.value.orange)} type="number" min="0" max="100" step="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500" placeholder="80"><div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none"><span class="text-gray-500 sm:text-sm">%</span></div></div><p class="text-xs text-gray-500 mt-1"> Enseignants entre ${ssrInterpolate(exceedanceThresholds.value.orange)}% et ${ssrInterpolate(exceedanceThresholds.value.red)}% des plafonds </p></div><div><label class="block text-sm font-medium text-gray-700 mb-2"> Seuil Zone Critique (Rouge) </label><div class="relative"><input${ssrRenderAttr("value", exceedanceThresholds.value.red)} type="number" min="0" max="100" step="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500" placeholder="95"><div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none"><span class="text-gray-500 sm:text-sm">%</span></div></div><p class="text-xs text-gray-500 mt-1"> Enseignants entre ${ssrInterpolate(exceedanceThresholds.value.red)}% et 100% des plafonds </p></div></div></div><div class="bg-blue-50 rounded-lg p-6"><h3 class="text-lg font-semibold text-gray-800 mb-4">Aper\xE7u des Zones</h3><div class="grid grid-cols-1 md:grid-cols-4 gap-4"><div class="bg-green-50 border border-green-200 rounded-lg p-4"><div class="flex items-center space-x-2 mb-2"><div class="w-3 h-3 bg-green-500 rounded-full"></div><span class="font-medium text-green-800">Zone Verte</span></div><p class="text-sm text-green-700"> &lt; ${ssrInterpolate(exceedanceThresholds.value.orange)}% </p><p class="text-xs text-green-600 mt-1"> Dans les limites </p></div><div class="bg-orange-50 border border-orange-200 rounded-lg p-4"><div class="flex items-center space-x-2 mb-2"><div class="w-3 h-3 bg-orange-500 rounded-full"></div><span class="font-medium text-orange-800">Zone Orange</span></div><p class="text-sm text-orange-700">${ssrInterpolate(exceedanceThresholds.value.orange)}% - ${ssrInterpolate(exceedanceThresholds.value.red)}% </p><p class="text-xs text-orange-600 mt-1"> Zone d&#39;attention </p></div><div class="bg-red-50 border border-red-200 rounded-lg p-4"><div class="flex items-center space-x-2 mb-2"><div class="w-3 h-3 bg-red-500 rounded-full"></div><span class="font-medium text-red-800">Zone Rouge</span></div><p class="text-sm text-red-700">${ssrInterpolate(exceedanceThresholds.value.red)}% - 100% </p><p class="text-xs text-red-600 mt-1"> Zone critique </p></div><div class="bg-gray-50 border border-gray-200 rounded-lg p-4"><div class="flex items-center space-x-2 mb-2"><div class="w-3 h-3 bg-gray-800 rounded-full"></div><span class="font-medium text-gray-800">Zone Noire</span></div><p class="text-sm text-gray-700"> &gt; 100% </p><p class="text-xs text-gray-600 mt-1"> D\xE9passements </p></div></div></div><div class="flex justify-end"><button type="submit"${ssrIncludeBooleanAttr(isSaving.value) ? " disabled" : ""} class="px-6 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 disabled:opacity-50">`);
        if (isSaving.value) {
          _push(`<span>Sauvegarde...</span>`);
        } else {
          _push(`<span>Enregistrer les param\xE8tres</span>`);
        }
        _push(`</button></div></form></div></div>`);
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/admin/contracts.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=contracts-C2ybpdFk.mjs.map
