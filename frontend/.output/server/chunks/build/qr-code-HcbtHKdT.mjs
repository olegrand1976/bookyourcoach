import { ref, mergeProps, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrInterpolate, ssrIncludeBooleanAttr, ssrRenderList } from 'vue/server-renderer';

const _sfc_main = {
  __name: "qr-code",
  __ssrInlineRender: true,
  setup(__props) {
    const user = ref(null);
    const qrData = ref(null);
    const userClubs = ref([]);
    const loading = ref(false);
    const formatDate = (dateString) => {
      const date = new Date(dateString);
      return date.toLocaleDateString("fr-FR", {
        day: "numeric",
        month: "long",
        year: "numeric"
      });
    };
    return (_ctx, _push, _parent, _attrs) => {
      var _a2;
      var _a, _b;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-50" }, _attrs))}><div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8"><div class="mb-8"><h1 class="text-3xl font-bold text-gray-900">Mon QR Code</h1><p class="mt-2 text-gray-600"> Pr\xE9sentez ce QR code aux clubs pour vous ajouter rapidement </p></div><div class="bg-white rounded-xl shadow-lg overflow-hidden"><div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50"><div class="flex items-center justify-between"><div class="flex items-center space-x-3"><div class="bg-blue-100 p-2 rounded-lg"><svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg></div><div><h3 class="text-lg font-semibold text-gray-900">Code QR Personnel</h3><p class="text-sm text-gray-600">${ssrInterpolate((_a = user.value) == null ? void 0 : _a.name)} - ${ssrInterpolate((_b = user.value) == null ? void 0 : _b.email)}</p></div></div><button${ssrIncludeBooleanAttr(loading.value) ? " disabled" : ""} class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors text-sm font-medium">${ssrInterpolate(loading.value ? "G\xE9n\xE9ration..." : "R\xE9g\xE9n\xE9rer")}</button></div></div><div class="p-8">`);
      if (qrData.value) {
        _push(`<div class="text-center"><div class="inline-block p-4 bg-white border-2 border-gray-200 rounded-lg shadow-sm"><div class="mx-auto">${(_a2 = qrData.value.qr_svg) != null ? _a2 : ""}</div></div><div class="mt-6 space-y-2"><p class="text-sm text-gray-600">Code QR:</p><p class="font-mono text-sm bg-gray-100 px-3 py-2 rounded-lg inline-block">${ssrInterpolate(qrData.value.qr_code)}</p></div><div class="mt-4 text-xs text-gray-500"> G\xE9n\xE9r\xE9 le ${ssrInterpolate(formatDate(qrData.value.generated_at))}</div></div>`);
      } else {
        _push(`<div class="text-center py-12"><svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg><p class="text-gray-500">Chargement du QR code...</p></div>`);
      }
      _push(`</div></div><div class="mt-8 bg-blue-50 rounded-xl p-6"><div class="flex items-start space-x-3"><div class="bg-blue-100 p-2 rounded-lg"><svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></div><div><h4 class="text-lg font-semibold text-gray-900 mb-2">Comment utiliser votre QR code</h4><div class="space-y-2 text-sm text-gray-700"><p>1. <strong>Pr\xE9sentez votre QR code</strong> aux clubs qui souhaitent vous ajouter</p><p>2. <strong>Ils peuvent le scanner</strong> avec leur application ou saisir le code manuellement</p><p>3. <strong>Vous serez ajout\xE9</strong> automatiquement \xE0 leur liste d&#39;enseignants</p><p>4. <strong>Vous pourrez enseigner</strong> dans plusieurs clubs simultan\xE9ment</p></div></div></div></div>`);
      if (userClubs.value.length > 0) {
        _push(`<div class="mt-8 bg-white rounded-xl shadow-lg overflow-hidden"><div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-emerald-50 to-teal-50"><h3 class="text-lg font-semibold text-gray-900">Clubs o\xF9 vous enseignez</h3></div><div class="p-6"><div class="grid grid-cols-1 md:grid-cols-2 gap-4"><!--[-->`);
        ssrRenderList(userClubs.value, (club) => {
          _push(`<div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors"><div class="flex items-center justify-between"><div><h4 class="font-medium text-gray-900">${ssrInterpolate(club.name)}</h4><p class="text-sm text-gray-600">${ssrInterpolate(club.email)}</p><p class="text-xs text-emerald-600">Membre depuis ${ssrInterpolate(formatDate(club.pivot.joined_at))}</p></div><span class="px-2 py-1 text-xs font-medium bg-emerald-100 text-emerald-800 rounded-full"> Actif </span></div></div>`);
        });
        _push(`<!--]--></div></div></div>`);
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/teacher/qr-code.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=qr-code-HcbtHKdT.mjs.map
