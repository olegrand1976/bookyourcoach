import "vue";
import { b as useNuxtApp } from "../server.mjs";
function useRequestEvent(nuxtApp) {
  var _a;
  nuxtApp || (nuxtApp = useNuxtApp());
  return (_a = nuxtApp.ssrContext) == null ? void 0 : _a.event;
}
function useRequestFetch() {
  var _a;
  return ((_a = useRequestEvent()) == null ? void 0 : _a.$fetch) || globalThis.$fetch;
}
export {
  useRequestEvent as a,
  useRequestFetch as u
};
//# sourceMappingURL=ssr-B4FXEZKR.js.map
