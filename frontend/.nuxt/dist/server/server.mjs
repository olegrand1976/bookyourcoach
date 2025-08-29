import { shallowReactive, reactive, effectScope, getCurrentScope, hasInjectionContext, getCurrentInstance, inject, toRef, shallowRef, isReadonly, isRef, isShallow, isReactive, toRaw, ref, markRaw, nextTick, onScopeDispose, watch, toRefs, computed, defineComponent, createElementBlock, provide, cloneVNode, h, unref, Fragment, createVNode, Text, defineAsyncComponent, Suspense, mergeProps, withCtx, useSSRContext, onErrorCaptured, onServerPrefetch, resolveDynamicComponent, createApp } from "vue";
import { $fetch } from "ofetch";
import { baseURL } from "#internal/nuxt/paths";
import { createHooks } from "/workspace/frontend/node_modules/hookable/dist/index.mjs";
import { getContext, executeAsync } from "/workspace/frontend/node_modules/unctx/dist/index.mjs";
import { sanitizeStatusCode, createError as createError$1, getRequestHeaders, getRequestHeader, setCookie, getCookie, deleteCookie } from "/workspace/frontend/node_modules/h3/dist/index.mjs";
import { START_LOCATION, createMemoryHistory, createRouter as createRouter$1, useRoute as useRoute$1, RouterView } from "vue-router";
import { toRouteMatcher, createRouter } from "/workspace/frontend/node_modules/radix3/dist/index.mjs";
import { defu } from "/workspace/frontend/node_modules/defu/dist/defu.mjs";
import { klona } from "/workspace/frontend/node_modules/klona/dist/index.mjs";
import { parse as parse$1 } from "/workspace/frontend/node_modules/nuxt/node_modules/cookie-es/dist/index.mjs";
import destr from "/workspace/frontend/node_modules/destr/dist/index.mjs";
import { isEqual as isEqual$1 } from "/workspace/frontend/node_modules/ohash/dist/index.mjs";
import { setupDevtoolsPlugin } from "@vue/devtools-api";
import { ssrRenderComponent, ssrRenderSuspense, ssrRenderVNode } from "vue/server-renderer";
import { useHead as useHead$1, headSymbol } from "/workspace/frontend/node_modules/@unhead/vue/dist/index.mjs";
if (!globalThis.$fetch) {
  globalThis.$fetch = $fetch.create({
    baseURL: baseURL()
  });
}
if (!("global" in globalThis)) {
  globalThis.global = globalThis;
}
const appLayoutTransition = false;
const nuxtLinkDefaults = { "componentName": "NuxtLink" };
const asyncDataDefaults = { "value": null, "errorValue": null, "deep": true };
const fetchDefaults = {};
const appId = "nuxt-app";
function getNuxtAppCtx(id = appId) {
  return getContext(id, {
    asyncContext: false
  });
}
const NuxtPluginIndicator = "__nuxt_plugin";
function createNuxtApp(options) {
  var _a;
  let hydratingCount = 0;
  const nuxtApp = {
    _id: options.id || appId || "nuxt-app",
    _scope: effectScope(),
    provide: void 0,
    globalName: "nuxt",
    versions: {
      get nuxt() {
        return "3.17.7";
      },
      get vue() {
        return nuxtApp.vueApp.version;
      }
    },
    payload: shallowReactive({
      ...((_a = options.ssrContext) == null ? void 0 : _a.payload) || {},
      data: shallowReactive({}),
      state: reactive({}),
      once: /* @__PURE__ */ new Set(),
      _errors: shallowReactive({})
    }),
    static: {
      data: {}
    },
    runWithContext(fn) {
      if (nuxtApp._scope.active && !getCurrentScope()) {
        return nuxtApp._scope.run(() => callWithNuxt(nuxtApp, fn));
      }
      return callWithNuxt(nuxtApp, fn);
    },
    isHydrating: false,
    deferHydration() {
      if (!nuxtApp.isHydrating) {
        return () => {
        };
      }
      hydratingCount++;
      let called = false;
      return () => {
        if (called) {
          return;
        }
        called = true;
        hydratingCount--;
        if (hydratingCount === 0) {
          nuxtApp.isHydrating = false;
          return nuxtApp.callHook("app:suspense:resolve");
        }
      };
    },
    _asyncDataPromises: {},
    _asyncData: shallowReactive({}),
    _payloadRevivers: {},
    ...options
  };
  {
    nuxtApp.payload.serverRendered = true;
  }
  if (nuxtApp.ssrContext) {
    nuxtApp.payload.path = nuxtApp.ssrContext.url;
    nuxtApp.ssrContext.nuxt = nuxtApp;
    nuxtApp.ssrContext.payload = nuxtApp.payload;
    nuxtApp.ssrContext.config = {
      public: nuxtApp.ssrContext.runtimeConfig.public,
      app: nuxtApp.ssrContext.runtimeConfig.app
    };
  }
  nuxtApp.hooks = createHooks();
  nuxtApp.hook = nuxtApp.hooks.hook;
  {
    const contextCaller = async function(hooks, args) {
      for (const hook of hooks) {
        await nuxtApp.runWithContext(() => hook(...args));
      }
    };
    nuxtApp.hooks.callHook = (name, ...args) => nuxtApp.hooks.callHookWith(contextCaller, name, ...args);
  }
  nuxtApp.callHook = nuxtApp.hooks.callHook;
  nuxtApp.provide = (name, value) => {
    const $name = "$" + name;
    defineGetter$1(nuxtApp, $name, value);
    defineGetter$1(nuxtApp.vueApp.config.globalProperties, $name, value);
  };
  defineGetter$1(nuxtApp.vueApp, "$nuxt", nuxtApp);
  defineGetter$1(nuxtApp.vueApp.config.globalProperties, "$nuxt", nuxtApp);
  const runtimeConfig = options.ssrContext.runtimeConfig;
  nuxtApp.provide("config", runtimeConfig);
  return nuxtApp;
}
function registerPluginHooks(nuxtApp, plugin2) {
  if (plugin2.hooks) {
    nuxtApp.hooks.addHooks(plugin2.hooks);
  }
}
async function applyPlugin(nuxtApp, plugin2) {
  if (typeof plugin2 === "function") {
    const { provide: provide2 } = await nuxtApp.runWithContext(() => plugin2(nuxtApp)) || {};
    if (provide2 && typeof provide2 === "object") {
      for (const key in provide2) {
        nuxtApp.provide(key, provide2[key]);
      }
    }
  }
}
async function applyPlugins(nuxtApp, plugins2) {
  var _a, _b, _c, _d;
  const resolvedPlugins = /* @__PURE__ */ new Set();
  const unresolvedPlugins = [];
  const parallels = [];
  const errors = [];
  let promiseDepth = 0;
  async function executePlugin(plugin2) {
    var _a2;
    const unresolvedPluginsForThisPlugin = ((_a2 = plugin2.dependsOn) == null ? void 0 : _a2.filter((name) => plugins2.some((p) => p._name === name) && !resolvedPlugins.has(name))) ?? [];
    if (unresolvedPluginsForThisPlugin.length > 0) {
      unresolvedPlugins.push([new Set(unresolvedPluginsForThisPlugin), plugin2]);
    } else {
      const promise = applyPlugin(nuxtApp, plugin2).then(async () => {
        if (plugin2._name) {
          resolvedPlugins.add(plugin2._name);
          await Promise.all(unresolvedPlugins.map(async ([dependsOn, unexecutedPlugin]) => {
            if (dependsOn.has(plugin2._name)) {
              dependsOn.delete(plugin2._name);
              if (dependsOn.size === 0) {
                promiseDepth++;
                await executePlugin(unexecutedPlugin);
              }
            }
          }));
        }
      });
      if (plugin2.parallel) {
        parallels.push(promise.catch((e) => errors.push(e)));
      } else {
        await promise;
      }
    }
  }
  for (const plugin2 of plugins2) {
    if (((_a = nuxtApp.ssrContext) == null ? void 0 : _a.islandContext) && ((_b = plugin2.env) == null ? void 0 : _b.islands) === false) {
      continue;
    }
    registerPluginHooks(nuxtApp, plugin2);
  }
  for (const plugin2 of plugins2) {
    if (((_c = nuxtApp.ssrContext) == null ? void 0 : _c.islandContext) && ((_d = plugin2.env) == null ? void 0 : _d.islands) === false) {
      continue;
    }
    await executePlugin(plugin2);
  }
  await Promise.all(parallels);
  if (promiseDepth) {
    for (let i = 0; i < promiseDepth; i++) {
      await Promise.all(parallels);
    }
  }
  if (errors.length) {
    throw errors[0];
  }
}
// @__NO_SIDE_EFFECTS__
function defineNuxtPlugin(plugin2) {
  if (typeof plugin2 === "function") {
    return plugin2;
  }
  const _name = plugin2._name || plugin2.name;
  delete plugin2.name;
  return Object.assign(plugin2.setup || (() => {
  }), plugin2, { [NuxtPluginIndicator]: true, _name });
}
function callWithNuxt(nuxt, setup, args) {
  const fn = () => setup();
  const nuxtAppCtx = getNuxtAppCtx(nuxt._id);
  {
    return nuxt.vueApp.runWithContext(() => nuxtAppCtx.callAsync(nuxt, fn));
  }
}
function tryUseNuxtApp(id) {
  var _a;
  let nuxtAppInstance;
  if (hasInjectionContext()) {
    nuxtAppInstance = (_a = getCurrentInstance()) == null ? void 0 : _a.appContext.app.$nuxt;
  }
  nuxtAppInstance || (nuxtAppInstance = getNuxtAppCtx(id).tryUse());
  return nuxtAppInstance || null;
}
function useNuxtApp(id) {
  const nuxtAppInstance = tryUseNuxtApp(id);
  if (!nuxtAppInstance) {
    {
      throw new Error("[nuxt] instance unavailable");
    }
  }
  return nuxtAppInstance;
}
// @__NO_SIDE_EFFECTS__
function useRuntimeConfig(_event) {
  return useNuxtApp().$config;
}
function defineGetter$1(obj, key, val) {
  Object.defineProperty(obj, key, { get: () => val });
}
const HASH_RE = /#/g;
const AMPERSAND_RE = /&/g;
const SLASH_RE = /\//g;
const EQUAL_RE = /=/g;
const PLUS_RE = /\+/g;
const ENC_CARET_RE = /%5e/gi;
const ENC_BACKTICK_RE = /%60/gi;
const ENC_PIPE_RE = /%7c/gi;
const ENC_SPACE_RE = /%20/gi;
function encode(text) {
  return encodeURI("" + text).replace(ENC_PIPE_RE, "|");
}
function encodeQueryValue(input) {
  return encode(typeof input === "string" ? input : JSON.stringify(input)).replace(PLUS_RE, "%2B").replace(ENC_SPACE_RE, "+").replace(HASH_RE, "%23").replace(AMPERSAND_RE, "%26").replace(ENC_BACKTICK_RE, "`").replace(ENC_CARET_RE, "^").replace(SLASH_RE, "%2F");
}
function encodeQueryKey(text) {
  return encodeQueryValue(text).replace(EQUAL_RE, "%3D");
}
function decode(text = "") {
  try {
    return decodeURIComponent("" + text);
  } catch {
    return "" + text;
  }
}
function decodeQueryKey(text) {
  return decode(text.replace(PLUS_RE, " "));
}
function decodeQueryValue(text) {
  return decode(text.replace(PLUS_RE, " "));
}
function parseQuery(parametersString = "") {
  const object = /* @__PURE__ */ Object.create(null);
  if (parametersString[0] === "?") {
    parametersString = parametersString.slice(1);
  }
  for (const parameter of parametersString.split("&")) {
    const s = parameter.match(/([^=]+)=?(.*)/) || [];
    if (s.length < 2) {
      continue;
    }
    const key = decodeQueryKey(s[1]);
    if (key === "__proto__" || key === "constructor") {
      continue;
    }
    const value = decodeQueryValue(s[2] || "");
    if (object[key] === void 0) {
      object[key] = value;
    } else if (Array.isArray(object[key])) {
      object[key].push(value);
    } else {
      object[key] = [object[key], value];
    }
  }
  return object;
}
function encodeQueryItem(key, value) {
  if (typeof value === "number" || typeof value === "boolean") {
    value = String(value);
  }
  if (!value) {
    return encodeQueryKey(key);
  }
  if (Array.isArray(value)) {
    return value.map(
      (_value) => `${encodeQueryKey(key)}=${encodeQueryValue(_value)}`
    ).join("&");
  }
  return `${encodeQueryKey(key)}=${encodeQueryValue(value)}`;
}
function stringifyQuery(query) {
  return Object.keys(query).filter((k) => query[k] !== void 0).map((k) => encodeQueryItem(k, query[k])).filter(Boolean).join("&");
}
const PROTOCOL_STRICT_REGEX = /^[\s\w\0+.-]{2,}:([/\\]{1,2})/;
const PROTOCOL_REGEX = /^[\s\w\0+.-]{2,}:([/\\]{2})?/;
const PROTOCOL_RELATIVE_REGEX = /^([/\\]\s*){2,}[^/\\]/;
const PROTOCOL_SCRIPT_RE = /^[\s\0]*(blob|data|javascript|vbscript):$/i;
const TRAILING_SLASH_RE = /\/$|\/\?|\/#/;
const JOIN_LEADING_SLASH_RE = /^\.?\//;
function hasProtocol(inputString, opts = {}) {
  if (typeof opts === "boolean") {
    opts = { acceptRelative: opts };
  }
  if (opts.strict) {
    return PROTOCOL_STRICT_REGEX.test(inputString);
  }
  return PROTOCOL_REGEX.test(inputString) || (opts.acceptRelative ? PROTOCOL_RELATIVE_REGEX.test(inputString) : false);
}
function isScriptProtocol(protocol) {
  return !!protocol && PROTOCOL_SCRIPT_RE.test(protocol);
}
function hasTrailingSlash(input = "", respectQueryAndFragment) {
  if (!respectQueryAndFragment) {
    return input.endsWith("/");
  }
  return TRAILING_SLASH_RE.test(input);
}
function withoutTrailingSlash(input = "", respectQueryAndFragment) {
  if (!respectQueryAndFragment) {
    return (hasTrailingSlash(input) ? input.slice(0, -1) : input) || "/";
  }
  if (!hasTrailingSlash(input, true)) {
    return input || "/";
  }
  let path = input;
  let fragment = "";
  const fragmentIndex = input.indexOf("#");
  if (fragmentIndex !== -1) {
    path = input.slice(0, fragmentIndex);
    fragment = input.slice(fragmentIndex);
  }
  const [s0, ...s] = path.split("?");
  const cleanPath = s0.endsWith("/") ? s0.slice(0, -1) : s0;
  return (cleanPath || "/") + (s.length > 0 ? `?${s.join("?")}` : "") + fragment;
}
function withTrailingSlash(input = "", respectQueryAndFragment) {
  if (!respectQueryAndFragment) {
    return input.endsWith("/") ? input : input + "/";
  }
  if (hasTrailingSlash(input, true)) {
    return input || "/";
  }
  let path = input;
  let fragment = "";
  const fragmentIndex = input.indexOf("#");
  if (fragmentIndex !== -1) {
    path = input.slice(0, fragmentIndex);
    fragment = input.slice(fragmentIndex);
    if (!path) {
      return fragment;
    }
  }
  const [s0, ...s] = path.split("?");
  return s0 + "/" + (s.length > 0 ? `?${s.join("?")}` : "") + fragment;
}
function hasLeadingSlash(input = "") {
  return input.startsWith("/");
}
function withLeadingSlash(input = "") {
  return hasLeadingSlash(input) ? input : "/" + input;
}
function withQuery(input, query) {
  const parsed = parseURL(input);
  const mergedQuery = { ...parseQuery(parsed.search), ...query };
  parsed.search = stringifyQuery(mergedQuery);
  return stringifyParsedURL(parsed);
}
function isNonEmptyURL(url) {
  return url && url !== "/";
}
function joinURL(base, ...input) {
  let url = base || "";
  for (const segment of input.filter((url2) => isNonEmptyURL(url2))) {
    if (url) {
      const _segment = segment.replace(JOIN_LEADING_SLASH_RE, "");
      url = withTrailingSlash(url) + _segment;
    } else {
      url = segment;
    }
  }
  return url;
}
function isEqual(a, b, options = {}) {
  if (!options.trailingSlash) {
    a = withTrailingSlash(a);
    b = withTrailingSlash(b);
  }
  if (!options.leadingSlash) {
    a = withLeadingSlash(a);
    b = withLeadingSlash(b);
  }
  if (!options.encoding) {
    a = decode(a);
    b = decode(b);
  }
  return a === b;
}
const protocolRelative = Symbol.for("ufo:protocolRelative");
function parseURL(input = "", defaultProto) {
  const _specialProtoMatch = input.match(
    /^[\s\0]*(blob:|data:|javascript:|vbscript:)(.*)/i
  );
  if (_specialProtoMatch) {
    const [, _proto, _pathname = ""] = _specialProtoMatch;
    return {
      protocol: _proto.toLowerCase(),
      pathname: _pathname,
      href: _proto + _pathname,
      auth: "",
      host: "",
      search: "",
      hash: ""
    };
  }
  if (!hasProtocol(input, { acceptRelative: true })) {
    return defaultProto ? parseURL(defaultProto + input) : parsePath(input);
  }
  const [, protocol = "", auth, hostAndPath = ""] = input.replace(/\\/g, "/").match(/^[\s\0]*([\w+.-]{2,}:)?\/\/([^/@]+@)?(.*)/) || [];
  let [, host = "", path = ""] = hostAndPath.match(/([^#/?]*)(.*)?/) || [];
  if (protocol === "file:") {
    path = path.replace(/\/(?=[A-Za-z]:)/, "");
  }
  const { pathname, search, hash } = parsePath(path);
  return {
    protocol: protocol.toLowerCase(),
    auth: auth ? auth.slice(0, Math.max(0, auth.length - 1)) : "",
    host,
    pathname,
    search,
    hash,
    [protocolRelative]: !protocol
  };
}
function parsePath(input = "") {
  const [pathname = "", search = "", hash = ""] = (input.match(/([^#?]*)(\?[^#]*)?(#.*)?/) || []).splice(1);
  return {
    pathname,
    search,
    hash
  };
}
function stringifyParsedURL(parsed) {
  const pathname = parsed.pathname || "";
  const search = parsed.search ? (parsed.search.startsWith("?") ? "" : "?") + parsed.search : "";
  const hash = parsed.hash || "";
  const auth = parsed.auth ? parsed.auth + "@" : "";
  const host = parsed.host || "";
  const proto = parsed.protocol || parsed[protocolRelative] ? (parsed.protocol || "") + "//" : "";
  return proto + auth + host + pathname + search + hash;
}
const LayoutMetaSymbol = Symbol("layout-meta");
const PageRouteSymbol = Symbol("route");
const useRouter = () => {
  var _a;
  return (_a = useNuxtApp()) == null ? void 0 : _a.$router;
};
const useRoute = () => {
  if (hasInjectionContext()) {
    return inject(PageRouteSymbol, useNuxtApp()._route);
  }
  return useNuxtApp()._route;
};
// @__NO_SIDE_EFFECTS__
function defineNuxtRouteMiddleware(middleware) {
  return middleware;
}
const addRouteMiddleware = (name, middleware, options = {}) => {
  const nuxtApp = useNuxtApp();
  const global2 = options.global || typeof name !== "string";
  const mw = middleware;
  if (!mw) {
    console.warn("[nuxt] No route middleware passed to `addRouteMiddleware`.", name);
    return;
  }
  if (global2) {
    nuxtApp._middleware.global.push(mw);
  } else {
    nuxtApp._middleware.named[name] = mw;
  }
};
const isProcessingMiddleware = () => {
  try {
    if (useNuxtApp()._processingMiddleware) {
      return true;
    }
  } catch {
    return false;
  }
  return false;
};
const URL_QUOTE_RE = /"/g;
const navigateTo = (to, options) => {
  to || (to = "/");
  const toPath = typeof to === "string" ? to : "path" in to ? resolveRouteObject(to) : useRouter().resolve(to).href;
  const isExternalHost = hasProtocol(toPath, { acceptRelative: true });
  const isExternal = (options == null ? void 0 : options.external) || isExternalHost;
  if (isExternal) {
    if (!(options == null ? void 0 : options.external)) {
      throw new Error("Navigating to an external URL is not allowed by default. Use `navigateTo(url, { external: true })`.");
    }
    const { protocol } = new URL(toPath, "http://localhost");
    if (protocol && isScriptProtocol(protocol)) {
      throw new Error(`Cannot navigate to a URL with '${protocol}' protocol.`);
    }
  }
  const inMiddleware = isProcessingMiddleware();
  const router = useRouter();
  const nuxtApp = useNuxtApp();
  {
    if (nuxtApp.ssrContext) {
      const fullPath = typeof to === "string" || isExternal ? toPath : router.resolve(to).fullPath || "/";
      const location2 = isExternal ? toPath : joinURL((/* @__PURE__ */ useRuntimeConfig()).app.baseURL, fullPath);
      const redirect = async function(response) {
        await nuxtApp.callHook("app:redirected");
        const encodedLoc = location2.replace(URL_QUOTE_RE, "%22");
        const encodedHeader = encodeURL(location2, isExternalHost);
        nuxtApp.ssrContext._renderResponse = {
          statusCode: sanitizeStatusCode((options == null ? void 0 : options.redirectCode) || 302, 302),
          body: `<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0; url=${encodedLoc}"></head></html>`,
          headers: { location: encodedHeader }
        };
        return response;
      };
      if (!isExternal && inMiddleware) {
        router.afterEach((final) => final.fullPath === fullPath ? redirect(false) : void 0);
        return to;
      }
      return redirect(!inMiddleware ? void 0 : (
        /* abort route navigation */
        false
      ));
    }
  }
  if (isExternal) {
    nuxtApp._scope.stop();
    if (options == null ? void 0 : options.replace) {
      (void 0).replace(toPath);
    } else {
      (void 0).href = toPath;
    }
    if (inMiddleware) {
      if (!nuxtApp.isHydrating) {
        return false;
      }
      return new Promise(() => {
      });
    }
    return Promise.resolve();
  }
  return (options == null ? void 0 : options.replace) ? router.replace(to) : router.push(to);
};
function resolveRouteObject(to) {
  return withQuery(to.path || "", to.query || {}) + (to.hash || "");
}
function encodeURL(location2, isExternalHost = false) {
  const url = new URL(location2, "http://localhost");
  if (!isExternalHost) {
    return url.pathname + url.search + url.hash;
  }
  if (location2.startsWith("//")) {
    return url.toString().replace(url.protocol, "");
  }
  return url.toString();
}
const NUXT_ERROR_SIGNATURE = "__nuxt_error";
const useError = () => toRef(useNuxtApp().payload, "error");
const showError = (error) => {
  const nuxtError = createError(error);
  try {
    const nuxtApp = useNuxtApp();
    const error2 = useError();
    if (false) ;
    error2.value || (error2.value = nuxtError);
  } catch {
    throw nuxtError;
  }
  return nuxtError;
};
const isNuxtError = (error) => !!error && typeof error === "object" && NUXT_ERROR_SIGNATURE in error;
const createError = (error) => {
  const nuxtError = createError$1(error);
  Object.defineProperty(nuxtError, NUXT_ERROR_SIGNATURE, {
    value: true,
    configurable: false,
    writable: false
  });
  return nuxtError;
};
const unhead_k2P3m_ZDyjlr2mMYnoDPwavjsDN8hBlk9cFai0bbopU = /* @__PURE__ */ defineNuxtPlugin({
  name: "nuxt:head",
  enforce: "pre",
  setup(nuxtApp) {
    const head = nuxtApp.ssrContext.head;
    nuxtApp.vueApp.use(head);
  }
});
function toArray(value) {
  return Array.isArray(value) ? value : [value];
}
async function getRouteRules(arg) {
  const path = typeof arg === "string" ? arg : arg.path;
  {
    useNuxtApp().ssrContext._preloadManifest = true;
    const _routeRulesMatcher = toRouteMatcher(
      createRouter({ routes: (/* @__PURE__ */ useRuntimeConfig()).nitro.routeRules })
    );
    return defu({}, ..._routeRulesMatcher.matchAll(path).reverse());
  }
}
const __nuxt_page_meta$2 = {
  layout: false
};
const __nuxt_page_meta$1 = {
  layout: false
};
const __nuxt_page_meta = {
  layout: "admin"
};
const _routes = [
  {
    name: "index___fr",
    path: "/",
    component: () => import("./_nuxt/index-XQ0VDCoh.js")
  },
  {
    name: "index___en",
    path: "/en",
    component: () => import("./_nuxt/index-XQ0VDCoh.js")
  },
  {
    name: "index___nl",
    path: "/nl",
    component: () => import("./_nuxt/index-XQ0VDCoh.js")
  },
  {
    name: "index___de",
    path: "/de",
    component: () => import("./_nuxt/index-XQ0VDCoh.js")
  },
  {
    name: "index___it",
    path: "/it",
    component: () => import("./_nuxt/index-XQ0VDCoh.js")
  },
  {
    name: "index___es",
    path: "/es",
    component: () => import("./_nuxt/index-XQ0VDCoh.js")
  },
  {
    name: "index___pt",
    path: "/pt",
    component: () => import("./_nuxt/index-XQ0VDCoh.js")
  },
  {
    name: "index___hu",
    path: "/hu",
    component: () => import("./_nuxt/index-XQ0VDCoh.js")
  },
  {
    name: "index___pl",
    path: "/pl",
    component: () => import("./_nuxt/index-XQ0VDCoh.js")
  },
  {
    name: "index___zh",
    path: "/zh",
    component: () => import("./_nuxt/index-XQ0VDCoh.js")
  },
  {
    name: "index___ja",
    path: "/ja",
    component: () => import("./_nuxt/index-XQ0VDCoh.js")
  },
  {
    name: "index___sv",
    path: "/sv",
    component: () => import("./_nuxt/index-XQ0VDCoh.js")
  },
  {
    name: "index___no",
    path: "/no",
    component: () => import("./_nuxt/index-XQ0VDCoh.js")
  },
  {
    name: "index___fi",
    path: "/fi",
    component: () => import("./_nuxt/index-XQ0VDCoh.js")
  },
  {
    name: "index___da",
    path: "/da",
    component: () => import("./_nuxt/index-XQ0VDCoh.js")
  },
  {
    name: "login___fr",
    path: "/login",
    meta: __nuxt_page_meta$2 || {},
    component: () => import("./_nuxt/login-B8E7W4SR.js")
  },
  {
    name: "login___en",
    path: "/en/login",
    meta: __nuxt_page_meta$2 || {},
    component: () => import("./_nuxt/login-B8E7W4SR.js")
  },
  {
    name: "login___nl",
    path: "/nl/login",
    meta: __nuxt_page_meta$2 || {},
    component: () => import("./_nuxt/login-B8E7W4SR.js")
  },
  {
    name: "login___de",
    path: "/de/login",
    meta: __nuxt_page_meta$2 || {},
    component: () => import("./_nuxt/login-B8E7W4SR.js")
  },
  {
    name: "login___it",
    path: "/it/login",
    meta: __nuxt_page_meta$2 || {},
    component: () => import("./_nuxt/login-B8E7W4SR.js")
  },
  {
    name: "login___es",
    path: "/es/login",
    meta: __nuxt_page_meta$2 || {},
    component: () => import("./_nuxt/login-B8E7W4SR.js")
  },
  {
    name: "login___pt",
    path: "/pt/login",
    meta: __nuxt_page_meta$2 || {},
    component: () => import("./_nuxt/login-B8E7W4SR.js")
  },
  {
    name: "login___hu",
    path: "/hu/login",
    meta: __nuxt_page_meta$2 || {},
    component: () => import("./_nuxt/login-B8E7W4SR.js")
  },
  {
    name: "login___pl",
    path: "/pl/login",
    meta: __nuxt_page_meta$2 || {},
    component: () => import("./_nuxt/login-B8E7W4SR.js")
  },
  {
    name: "login___zh",
    path: "/zh/login",
    meta: __nuxt_page_meta$2 || {},
    component: () => import("./_nuxt/login-B8E7W4SR.js")
  },
  {
    name: "login___ja",
    path: "/ja/login",
    meta: __nuxt_page_meta$2 || {},
    component: () => import("./_nuxt/login-B8E7W4SR.js")
  },
  {
    name: "login___sv",
    path: "/sv/login",
    meta: __nuxt_page_meta$2 || {},
    component: () => import("./_nuxt/login-B8E7W4SR.js")
  },
  {
    name: "login___no",
    path: "/no/login",
    meta: __nuxt_page_meta$2 || {},
    component: () => import("./_nuxt/login-B8E7W4SR.js")
  },
  {
    name: "login___fi",
    path: "/fi/login",
    meta: __nuxt_page_meta$2 || {},
    component: () => import("./_nuxt/login-B8E7W4SR.js")
  },
  {
    name: "login___da",
    path: "/da/login",
    meta: __nuxt_page_meta$2 || {},
    component: () => import("./_nuxt/login-B8E7W4SR.js")
  },
  {
    name: "profile___fr",
    path: "/profile",
    meta: { "middleware": "auth" },
    component: () => import("./_nuxt/profile-3C5rpJTJ.js")
  },
  {
    name: "profile___en",
    path: "/en/profile",
    meta: { "middleware": "auth" },
    component: () => import("./_nuxt/profile-3C5rpJTJ.js")
  },
  {
    name: "profile___nl",
    path: "/nl/profile",
    meta: { "middleware": "auth" },
    component: () => import("./_nuxt/profile-3C5rpJTJ.js")
  },
  {
    name: "profile___de",
    path: "/de/profile",
    meta: { "middleware": "auth" },
    component: () => import("./_nuxt/profile-3C5rpJTJ.js")
  },
  {
    name: "profile___it",
    path: "/it/profile",
    meta: { "middleware": "auth" },
    component: () => import("./_nuxt/profile-3C5rpJTJ.js")
  },
  {
    name: "profile___es",
    path: "/es/profile",
    meta: { "middleware": "auth" },
    component: () => import("./_nuxt/profile-3C5rpJTJ.js")
  },
  {
    name: "profile___pt",
    path: "/pt/profile",
    meta: { "middleware": "auth" },
    component: () => import("./_nuxt/profile-3C5rpJTJ.js")
  },
  {
    name: "profile___hu",
    path: "/hu/profile",
    meta: { "middleware": "auth" },
    component: () => import("./_nuxt/profile-3C5rpJTJ.js")
  },
  {
    name: "profile___pl",
    path: "/pl/profile",
    meta: { "middleware": "auth" },
    component: () => import("./_nuxt/profile-3C5rpJTJ.js")
  },
  {
    name: "profile___zh",
    path: "/zh/profile",
    meta: { "middleware": "auth" },
    component: () => import("./_nuxt/profile-3C5rpJTJ.js")
  },
  {
    name: "profile___ja",
    path: "/ja/profile",
    meta: { "middleware": "auth" },
    component: () => import("./_nuxt/profile-3C5rpJTJ.js")
  },
  {
    name: "profile___sv",
    path: "/sv/profile",
    meta: { "middleware": "auth" },
    component: () => import("./_nuxt/profile-3C5rpJTJ.js")
  },
  {
    name: "profile___no",
    path: "/no/profile",
    meta: { "middleware": "auth" },
    component: () => import("./_nuxt/profile-3C5rpJTJ.js")
  },
  {
    name: "profile___fi",
    path: "/fi/profile",
    meta: { "middleware": "auth" },
    component: () => import("./_nuxt/profile-3C5rpJTJ.js")
  },
  {
    name: "profile___da",
    path: "/da/profile",
    meta: { "middleware": "auth" },
    component: () => import("./_nuxt/profile-3C5rpJTJ.js")
  },
  {
    name: "register___fr",
    path: "/register",
    meta: __nuxt_page_meta$1 || {},
    component: () => import("./_nuxt/register-DIfKK4d3.js")
  },
  {
    name: "register___en",
    path: "/en/register",
    meta: __nuxt_page_meta$1 || {},
    component: () => import("./_nuxt/register-DIfKK4d3.js")
  },
  {
    name: "register___nl",
    path: "/nl/register",
    meta: __nuxt_page_meta$1 || {},
    component: () => import("./_nuxt/register-DIfKK4d3.js")
  },
  {
    name: "register___de",
    path: "/de/register",
    meta: __nuxt_page_meta$1 || {},
    component: () => import("./_nuxt/register-DIfKK4d3.js")
  },
  {
    name: "register___it",
    path: "/it/register",
    meta: __nuxt_page_meta$1 || {},
    component: () => import("./_nuxt/register-DIfKK4d3.js")
  },
  {
    name: "register___es",
    path: "/es/register",
    meta: __nuxt_page_meta$1 || {},
    component: () => import("./_nuxt/register-DIfKK4d3.js")
  },
  {
    name: "register___pt",
    path: "/pt/register",
    meta: __nuxt_page_meta$1 || {},
    component: () => import("./_nuxt/register-DIfKK4d3.js")
  },
  {
    name: "register___hu",
    path: "/hu/register",
    meta: __nuxt_page_meta$1 || {},
    component: () => import("./_nuxt/register-DIfKK4d3.js")
  },
  {
    name: "register___pl",
    path: "/pl/register",
    meta: __nuxt_page_meta$1 || {},
    component: () => import("./_nuxt/register-DIfKK4d3.js")
  },
  {
    name: "register___zh",
    path: "/zh/register",
    meta: __nuxt_page_meta$1 || {},
    component: () => import("./_nuxt/register-DIfKK4d3.js")
  },
  {
    name: "register___ja",
    path: "/ja/register",
    meta: __nuxt_page_meta$1 || {},
    component: () => import("./_nuxt/register-DIfKK4d3.js")
  },
  {
    name: "register___sv",
    path: "/sv/register",
    meta: __nuxt_page_meta$1 || {},
    component: () => import("./_nuxt/register-DIfKK4d3.js")
  },
  {
    name: "register___no",
    path: "/no/register",
    meta: __nuxt_page_meta$1 || {},
    component: () => import("./_nuxt/register-DIfKK4d3.js")
  },
  {
    name: "register___fi",
    path: "/fi/register",
    meta: __nuxt_page_meta$1 || {},
    component: () => import("./_nuxt/register-DIfKK4d3.js")
  },
  {
    name: "register___da",
    path: "/da/register",
    meta: __nuxt_page_meta$1 || {},
    component: () => import("./_nuxt/register-DIfKK4d3.js")
  },
  {
    name: "teachers___fr",
    path: "/teachers",
    component: () => import("./_nuxt/teachers-C7QjHpYN.js"),
    children: [
      {
        name: "teachers-id___fr",
        path: ":id()",
        component: () => import("./_nuxt/_id_-Dc2w6XFS.js")
      }
    ]
  },
  {
    name: "teachers___en",
    path: "/en/teachers",
    component: () => import("./_nuxt/teachers-C7QjHpYN.js"),
    children: [
      {
        name: "teachers-id___en",
        path: ":id()",
        component: () => import("./_nuxt/_id_-Dc2w6XFS.js")
      }
    ]
  },
  {
    name: "teachers___nl",
    path: "/nl/teachers",
    component: () => import("./_nuxt/teachers-C7QjHpYN.js"),
    children: [
      {
        name: "teachers-id___nl",
        path: ":id()",
        component: () => import("./_nuxt/_id_-Dc2w6XFS.js")
      }
    ]
  },
  {
    name: "teachers___de",
    path: "/de/teachers",
    component: () => import("./_nuxt/teachers-C7QjHpYN.js"),
    children: [
      {
        name: "teachers-id___de",
        path: ":id()",
        component: () => import("./_nuxt/_id_-Dc2w6XFS.js")
      }
    ]
  },
  {
    name: "teachers___it",
    path: "/it/teachers",
    component: () => import("./_nuxt/teachers-C7QjHpYN.js"),
    children: [
      {
        name: "teachers-id___it",
        path: ":id()",
        component: () => import("./_nuxt/_id_-Dc2w6XFS.js")
      }
    ]
  },
  {
    name: "teachers___es",
    path: "/es/teachers",
    component: () => import("./_nuxt/teachers-C7QjHpYN.js"),
    children: [
      {
        name: "teachers-id___es",
        path: ":id()",
        component: () => import("./_nuxt/_id_-Dc2w6XFS.js")
      }
    ]
  },
  {
    name: "teachers___pt",
    path: "/pt/teachers",
    component: () => import("./_nuxt/teachers-C7QjHpYN.js"),
    children: [
      {
        name: "teachers-id___pt",
        path: ":id()",
        component: () => import("./_nuxt/_id_-Dc2w6XFS.js")
      }
    ]
  },
  {
    name: "teachers___hu",
    path: "/hu/teachers",
    component: () => import("./_nuxt/teachers-C7QjHpYN.js"),
    children: [
      {
        name: "teachers-id___hu",
        path: ":id()",
        component: () => import("./_nuxt/_id_-Dc2w6XFS.js")
      }
    ]
  },
  {
    name: "teachers___pl",
    path: "/pl/teachers",
    component: () => import("./_nuxt/teachers-C7QjHpYN.js"),
    children: [
      {
        name: "teachers-id___pl",
        path: ":id()",
        component: () => import("./_nuxt/_id_-Dc2w6XFS.js")
      }
    ]
  },
  {
    name: "teachers___zh",
    path: "/zh/teachers",
    component: () => import("./_nuxt/teachers-C7QjHpYN.js"),
    children: [
      {
        name: "teachers-id___zh",
        path: ":id()",
        component: () => import("./_nuxt/_id_-Dc2w6XFS.js")
      }
    ]
  },
  {
    name: "teachers___ja",
    path: "/ja/teachers",
    component: () => import("./_nuxt/teachers-C7QjHpYN.js"),
    children: [
      {
        name: "teachers-id___ja",
        path: ":id()",
        component: () => import("./_nuxt/_id_-Dc2w6XFS.js")
      }
    ]
  },
  {
    name: "teachers___sv",
    path: "/sv/teachers",
    component: () => import("./_nuxt/teachers-C7QjHpYN.js"),
    children: [
      {
        name: "teachers-id___sv",
        path: ":id()",
        component: () => import("./_nuxt/_id_-Dc2w6XFS.js")
      }
    ]
  },
  {
    name: "teachers___no",
    path: "/no/teachers",
    component: () => import("./_nuxt/teachers-C7QjHpYN.js"),
    children: [
      {
        name: "teachers-id___no",
        path: ":id()",
        component: () => import("./_nuxt/_id_-Dc2w6XFS.js")
      }
    ]
  },
  {
    name: "teachers___fi",
    path: "/fi/teachers",
    component: () => import("./_nuxt/teachers-C7QjHpYN.js"),
    children: [
      {
        name: "teachers-id___fi",
        path: ":id()",
        component: () => import("./_nuxt/_id_-Dc2w6XFS.js")
      }
    ]
  },
  {
    name: "teachers___da",
    path: "/da/teachers",
    component: () => import("./_nuxt/teachers-C7QjHpYN.js"),
    children: [
      {
        name: "teachers-id___da",
        path: ":id()",
        component: () => import("./_nuxt/_id_-Dc2w6XFS.js")
      }
    ]
  },
  {
    name: "test-api___fr",
    path: "/test-api",
    component: () => import("./_nuxt/test-api-Z5UKyaq6.js")
  },
  {
    name: "test-api___en",
    path: "/en/test-api",
    component: () => import("./_nuxt/test-api-Z5UKyaq6.js")
  },
  {
    name: "test-api___nl",
    path: "/nl/test-api",
    component: () => import("./_nuxt/test-api-Z5UKyaq6.js")
  },
  {
    name: "test-api___de",
    path: "/de/test-api",
    component: () => import("./_nuxt/test-api-Z5UKyaq6.js")
  },
  {
    name: "test-api___it",
    path: "/it/test-api",
    component: () => import("./_nuxt/test-api-Z5UKyaq6.js")
  },
  {
    name: "test-api___es",
    path: "/es/test-api",
    component: () => import("./_nuxt/test-api-Z5UKyaq6.js")
  },
  {
    name: "test-api___pt",
    path: "/pt/test-api",
    component: () => import("./_nuxt/test-api-Z5UKyaq6.js")
  },
  {
    name: "test-api___hu",
    path: "/hu/test-api",
    component: () => import("./_nuxt/test-api-Z5UKyaq6.js")
  },
  {
    name: "test-api___pl",
    path: "/pl/test-api",
    component: () => import("./_nuxt/test-api-Z5UKyaq6.js")
  },
  {
    name: "test-api___zh",
    path: "/zh/test-api",
    component: () => import("./_nuxt/test-api-Z5UKyaq6.js")
  },
  {
    name: "test-api___ja",
    path: "/ja/test-api",
    component: () => import("./_nuxt/test-api-Z5UKyaq6.js")
  },
  {
    name: "test-api___sv",
    path: "/sv/test-api",
    component: () => import("./_nuxt/test-api-Z5UKyaq6.js")
  },
  {
    name: "test-api___no",
    path: "/no/test-api",
    component: () => import("./_nuxt/test-api-Z5UKyaq6.js")
  },
  {
    name: "test-api___fi",
    path: "/fi/test-api",
    component: () => import("./_nuxt/test-api-Z5UKyaq6.js")
  },
  {
    name: "test-api___da",
    path: "/da/test-api",
    component: () => import("./_nuxt/test-api-Z5UKyaq6.js")
  },
  {
    name: "dashboard___fr",
    path: "/dashboard",
    meta: { "middleware": "auth" },
    component: () => import("./_nuxt/dashboard-Dtc8Rq2B.js")
  },
  {
    name: "dashboard___en",
    path: "/en/dashboard",
    meta: { "middleware": "auth" },
    component: () => import("./_nuxt/dashboard-Dtc8Rq2B.js")
  },
  {
    name: "dashboard___nl",
    path: "/nl/dashboard",
    meta: { "middleware": "auth" },
    component: () => import("./_nuxt/dashboard-Dtc8Rq2B.js")
  },
  {
    name: "dashboard___de",
    path: "/de/dashboard",
    meta: { "middleware": "auth" },
    component: () => import("./_nuxt/dashboard-Dtc8Rq2B.js")
  },
  {
    name: "dashboard___it",
    path: "/it/dashboard",
    meta: { "middleware": "auth" },
    component: () => import("./_nuxt/dashboard-Dtc8Rq2B.js")
  },
  {
    name: "dashboard___es",
    path: "/es/dashboard",
    meta: { "middleware": "auth" },
    component: () => import("./_nuxt/dashboard-Dtc8Rq2B.js")
  },
  {
    name: "dashboard___pt",
    path: "/pt/dashboard",
    meta: { "middleware": "auth" },
    component: () => import("./_nuxt/dashboard-Dtc8Rq2B.js")
  },
  {
    name: "dashboard___hu",
    path: "/hu/dashboard",
    meta: { "middleware": "auth" },
    component: () => import("./_nuxt/dashboard-Dtc8Rq2B.js")
  },
  {
    name: "dashboard___pl",
    path: "/pl/dashboard",
    meta: { "middleware": "auth" },
    component: () => import("./_nuxt/dashboard-Dtc8Rq2B.js")
  },
  {
    name: "dashboard___zh",
    path: "/zh/dashboard",
    meta: { "middleware": "auth" },
    component: () => import("./_nuxt/dashboard-Dtc8Rq2B.js")
  },
  {
    name: "dashboard___ja",
    path: "/ja/dashboard",
    meta: { "middleware": "auth" },
    component: () => import("./_nuxt/dashboard-Dtc8Rq2B.js")
  },
  {
    name: "dashboard___sv",
    path: "/sv/dashboard",
    meta: { "middleware": "auth" },
    component: () => import("./_nuxt/dashboard-Dtc8Rq2B.js")
  },
  {
    name: "dashboard___no",
    path: "/no/dashboard",
    meta: { "middleware": "auth" },
    component: () => import("./_nuxt/dashboard-Dtc8Rq2B.js")
  },
  {
    name: "dashboard___fi",
    path: "/fi/dashboard",
    meta: { "middleware": "auth" },
    component: () => import("./_nuxt/dashboard-Dtc8Rq2B.js")
  },
  {
    name: "dashboard___da",
    path: "/da/dashboard",
    meta: { "middleware": "auth" },
    component: () => import("./_nuxt/dashboard-Dtc8Rq2B.js")
  },
  {
    name: "test-auth___fr",
    path: "/test-auth",
    component: () => import("./_nuxt/test-auth-CDCWNLTq.js")
  },
  {
    name: "test-auth___en",
    path: "/en/test-auth",
    component: () => import("./_nuxt/test-auth-CDCWNLTq.js")
  },
  {
    name: "test-auth___nl",
    path: "/nl/test-auth",
    component: () => import("./_nuxt/test-auth-CDCWNLTq.js")
  },
  {
    name: "test-auth___de",
    path: "/de/test-auth",
    component: () => import("./_nuxt/test-auth-CDCWNLTq.js")
  },
  {
    name: "test-auth___it",
    path: "/it/test-auth",
    component: () => import("./_nuxt/test-auth-CDCWNLTq.js")
  },
  {
    name: "test-auth___es",
    path: "/es/test-auth",
    component: () => import("./_nuxt/test-auth-CDCWNLTq.js")
  },
  {
    name: "test-auth___pt",
    path: "/pt/test-auth",
    component: () => import("./_nuxt/test-auth-CDCWNLTq.js")
  },
  {
    name: "test-auth___hu",
    path: "/hu/test-auth",
    component: () => import("./_nuxt/test-auth-CDCWNLTq.js")
  },
  {
    name: "test-auth___pl",
    path: "/pl/test-auth",
    component: () => import("./_nuxt/test-auth-CDCWNLTq.js")
  },
  {
    name: "test-auth___zh",
    path: "/zh/test-auth",
    component: () => import("./_nuxt/test-auth-CDCWNLTq.js")
  },
  {
    name: "test-auth___ja",
    path: "/ja/test-auth",
    component: () => import("./_nuxt/test-auth-CDCWNLTq.js")
  },
  {
    name: "test-auth___sv",
    path: "/sv/test-auth",
    component: () => import("./_nuxt/test-auth-CDCWNLTq.js")
  },
  {
    name: "test-auth___no",
    path: "/no/test-auth",
    component: () => import("./_nuxt/test-auth-CDCWNLTq.js")
  },
  {
    name: "test-auth___fi",
    path: "/fi/test-auth",
    component: () => import("./_nuxt/test-auth-CDCWNLTq.js")
  },
  {
    name: "test-auth___da",
    path: "/da/test-auth",
    component: () => import("./_nuxt/test-auth-CDCWNLTq.js")
  },
  {
    name: "debug-auth___fr",
    path: "/debug-auth",
    component: () => import("./_nuxt/debug-auth-CokvVzUg.js")
  },
  {
    name: "debug-auth___en",
    path: "/en/debug-auth",
    component: () => import("./_nuxt/debug-auth-CokvVzUg.js")
  },
  {
    name: "debug-auth___nl",
    path: "/nl/debug-auth",
    component: () => import("./_nuxt/debug-auth-CokvVzUg.js")
  },
  {
    name: "debug-auth___de",
    path: "/de/debug-auth",
    component: () => import("./_nuxt/debug-auth-CokvVzUg.js")
  },
  {
    name: "debug-auth___it",
    path: "/it/debug-auth",
    component: () => import("./_nuxt/debug-auth-CokvVzUg.js")
  },
  {
    name: "debug-auth___es",
    path: "/es/debug-auth",
    component: () => import("./_nuxt/debug-auth-CokvVzUg.js")
  },
  {
    name: "debug-auth___pt",
    path: "/pt/debug-auth",
    component: () => import("./_nuxt/debug-auth-CokvVzUg.js")
  },
  {
    name: "debug-auth___hu",
    path: "/hu/debug-auth",
    component: () => import("./_nuxt/debug-auth-CokvVzUg.js")
  },
  {
    name: "debug-auth___pl",
    path: "/pl/debug-auth",
    component: () => import("./_nuxt/debug-auth-CokvVzUg.js")
  },
  {
    name: "debug-auth___zh",
    path: "/zh/debug-auth",
    component: () => import("./_nuxt/debug-auth-CokvVzUg.js")
  },
  {
    name: "debug-auth___ja",
    path: "/ja/debug-auth",
    component: () => import("./_nuxt/debug-auth-CokvVzUg.js")
  },
  {
    name: "debug-auth___sv",
    path: "/sv/debug-auth",
    component: () => import("./_nuxt/debug-auth-CokvVzUg.js")
  },
  {
    name: "debug-auth___no",
    path: "/no/debug-auth",
    component: () => import("./_nuxt/debug-auth-CokvVzUg.js")
  },
  {
    name: "debug-auth___fi",
    path: "/fi/debug-auth",
    component: () => import("./_nuxt/debug-auth-CokvVzUg.js")
  },
  {
    name: "debug-auth___da",
    path: "/da/debug-auth",
    component: () => import("./_nuxt/debug-auth-CokvVzUg.js")
  },
  {
    name: "admin___fr",
    path: "/admin",
    meta: { ...__nuxt_page_meta || {}, ...{ "middleware": ["auth", "admin"] } },
    component: () => import("./_nuxt/index-2C0cKAAO.js")
  },
  {
    name: "admin___en",
    path: "/en/admin",
    meta: { ...__nuxt_page_meta || {}, ...{ "middleware": ["auth", "admin"] } },
    component: () => import("./_nuxt/index-2C0cKAAO.js")
  },
  {
    name: "admin___nl",
    path: "/nl/admin",
    meta: { ...__nuxt_page_meta || {}, ...{ "middleware": ["auth", "admin"] } },
    component: () => import("./_nuxt/index-2C0cKAAO.js")
  },
  {
    name: "admin___de",
    path: "/de/admin",
    meta: { ...__nuxt_page_meta || {}, ...{ "middleware": ["auth", "admin"] } },
    component: () => import("./_nuxt/index-2C0cKAAO.js")
  },
  {
    name: "admin___it",
    path: "/it/admin",
    meta: { ...__nuxt_page_meta || {}, ...{ "middleware": ["auth", "admin"] } },
    component: () => import("./_nuxt/index-2C0cKAAO.js")
  },
  {
    name: "admin___es",
    path: "/es/admin",
    meta: { ...__nuxt_page_meta || {}, ...{ "middleware": ["auth", "admin"] } },
    component: () => import("./_nuxt/index-2C0cKAAO.js")
  },
  {
    name: "admin___pt",
    path: "/pt/admin",
    meta: { ...__nuxt_page_meta || {}, ...{ "middleware": ["auth", "admin"] } },
    component: () => import("./_nuxt/index-2C0cKAAO.js")
  },
  {
    name: "admin___hu",
    path: "/hu/admin",
    meta: { ...__nuxt_page_meta || {}, ...{ "middleware": ["auth", "admin"] } },
    component: () => import("./_nuxt/index-2C0cKAAO.js")
  },
  {
    name: "admin___pl",
    path: "/pl/admin",
    meta: { ...__nuxt_page_meta || {}, ...{ "middleware": ["auth", "admin"] } },
    component: () => import("./_nuxt/index-2C0cKAAO.js")
  },
  {
    name: "admin___zh",
    path: "/zh/admin",
    meta: { ...__nuxt_page_meta || {}, ...{ "middleware": ["auth", "admin"] } },
    component: () => import("./_nuxt/index-2C0cKAAO.js")
  },
  {
    name: "admin___ja",
    path: "/ja/admin",
    meta: { ...__nuxt_page_meta || {}, ...{ "middleware": ["auth", "admin"] } },
    component: () => import("./_nuxt/index-2C0cKAAO.js")
  },
  {
    name: "admin___sv",
    path: "/sv/admin",
    meta: { ...__nuxt_page_meta || {}, ...{ "middleware": ["auth", "admin"] } },
    component: () => import("./_nuxt/index-2C0cKAAO.js")
  },
  {
    name: "admin___no",
    path: "/no/admin",
    meta: { ...__nuxt_page_meta || {}, ...{ "middleware": ["auth", "admin"] } },
    component: () => import("./_nuxt/index-2C0cKAAO.js")
  },
  {
    name: "admin___fi",
    path: "/fi/admin",
    meta: { ...__nuxt_page_meta || {}, ...{ "middleware": ["auth", "admin"] } },
    component: () => import("./_nuxt/index-2C0cKAAO.js")
  },
  {
    name: "admin___da",
    path: "/da/admin",
    meta: { ...__nuxt_page_meta || {}, ...{ "middleware": ["auth", "admin"] } },
    component: () => import("./_nuxt/index-2C0cKAAO.js")
  },
  {
    name: "admin-users___fr",
    path: "/admin/users",
    meta: { "middleware": "auth-admin" },
    component: () => import("./_nuxt/users-DhxM_Xp0.js")
  },
  {
    name: "admin-users___en",
    path: "/en/admin/users",
    meta: { "middleware": "auth-admin" },
    component: () => import("./_nuxt/users-DhxM_Xp0.js")
  },
  {
    name: "admin-users___nl",
    path: "/nl/admin/users",
    meta: { "middleware": "auth-admin" },
    component: () => import("./_nuxt/users-DhxM_Xp0.js")
  },
  {
    name: "admin-users___de",
    path: "/de/admin/users",
    meta: { "middleware": "auth-admin" },
    component: () => import("./_nuxt/users-DhxM_Xp0.js")
  },
  {
    name: "admin-users___it",
    path: "/it/admin/users",
    meta: { "middleware": "auth-admin" },
    component: () => import("./_nuxt/users-DhxM_Xp0.js")
  },
  {
    name: "admin-users___es",
    path: "/es/admin/users",
    meta: { "middleware": "auth-admin" },
    component: () => import("./_nuxt/users-DhxM_Xp0.js")
  },
  {
    name: "admin-users___pt",
    path: "/pt/admin/users",
    meta: { "middleware": "auth-admin" },
    component: () => import("./_nuxt/users-DhxM_Xp0.js")
  },
  {
    name: "admin-users___hu",
    path: "/hu/admin/users",
    meta: { "middleware": "auth-admin" },
    component: () => import("./_nuxt/users-DhxM_Xp0.js")
  },
  {
    name: "admin-users___pl",
    path: "/pl/admin/users",
    meta: { "middleware": "auth-admin" },
    component: () => import("./_nuxt/users-DhxM_Xp0.js")
  },
  {
    name: "admin-users___zh",
    path: "/zh/admin/users",
    meta: { "middleware": "auth-admin" },
    component: () => import("./_nuxt/users-DhxM_Xp0.js")
  },
  {
    name: "admin-users___ja",
    path: "/ja/admin/users",
    meta: { "middleware": "auth-admin" },
    component: () => import("./_nuxt/users-DhxM_Xp0.js")
  },
  {
    name: "admin-users___sv",
    path: "/sv/admin/users",
    meta: { "middleware": "auth-admin" },
    component: () => import("./_nuxt/users-DhxM_Xp0.js")
  },
  {
    name: "admin-users___no",
    path: "/no/admin/users",
    meta: { "middleware": "auth-admin" },
    component: () => import("./_nuxt/users-DhxM_Xp0.js")
  },
  {
    name: "admin-users___fi",
    path: "/fi/admin/users",
    meta: { "middleware": "auth-admin" },
    component: () => import("./_nuxt/users-DhxM_Xp0.js")
  },
  {
    name: "admin-users___da",
    path: "/da/admin/users",
    meta: { "middleware": "auth-admin" },
    component: () => import("./_nuxt/users-DhxM_Xp0.js")
  },
  {
    name: "admin-settings___fr",
    path: "/admin/settings",
    meta: { "middleware": ["auth", "admin"] },
    component: () => import("./_nuxt/settings-BUP-Ai1j.js")
  },
  {
    name: "admin-settings___en",
    path: "/en/admin/settings",
    meta: { "middleware": ["auth", "admin"] },
    component: () => import("./_nuxt/settings-BUP-Ai1j.js")
  },
  {
    name: "admin-settings___nl",
    path: "/nl/admin/settings",
    meta: { "middleware": ["auth", "admin"] },
    component: () => import("./_nuxt/settings-BUP-Ai1j.js")
  },
  {
    name: "admin-settings___de",
    path: "/de/admin/settings",
    meta: { "middleware": ["auth", "admin"] },
    component: () => import("./_nuxt/settings-BUP-Ai1j.js")
  },
  {
    name: "admin-settings___it",
    path: "/it/admin/settings",
    meta: { "middleware": ["auth", "admin"] },
    component: () => import("./_nuxt/settings-BUP-Ai1j.js")
  },
  {
    name: "admin-settings___es",
    path: "/es/admin/settings",
    meta: { "middleware": ["auth", "admin"] },
    component: () => import("./_nuxt/settings-BUP-Ai1j.js")
  },
  {
    name: "admin-settings___pt",
    path: "/pt/admin/settings",
    meta: { "middleware": ["auth", "admin"] },
    component: () => import("./_nuxt/settings-BUP-Ai1j.js")
  },
  {
    name: "admin-settings___hu",
    path: "/hu/admin/settings",
    meta: { "middleware": ["auth", "admin"] },
    component: () => import("./_nuxt/settings-BUP-Ai1j.js")
  },
  {
    name: "admin-settings___pl",
    path: "/pl/admin/settings",
    meta: { "middleware": ["auth", "admin"] },
    component: () => import("./_nuxt/settings-BUP-Ai1j.js")
  },
  {
    name: "admin-settings___zh",
    path: "/zh/admin/settings",
    meta: { "middleware": ["auth", "admin"] },
    component: () => import("./_nuxt/settings-BUP-Ai1j.js")
  },
  {
    name: "admin-settings___ja",
    path: "/ja/admin/settings",
    meta: { "middleware": ["auth", "admin"] },
    component: () => import("./_nuxt/settings-BUP-Ai1j.js")
  },
  {
    name: "admin-settings___sv",
    path: "/sv/admin/settings",
    meta: { "middleware": ["auth", "admin"] },
    component: () => import("./_nuxt/settings-BUP-Ai1j.js")
  },
  {
    name: "admin-settings___no",
    path: "/no/admin/settings",
    meta: { "middleware": ["auth", "admin"] },
    component: () => import("./_nuxt/settings-BUP-Ai1j.js")
  },
  {
    name: "admin-settings___fi",
    path: "/fi/admin/settings",
    meta: { "middleware": ["auth", "admin"] },
    component: () => import("./_nuxt/settings-BUP-Ai1j.js")
  },
  {
    name: "admin-settings___da",
    path: "/da/admin/settings",
    meta: { "middleware": ["auth", "admin"] },
    component: () => import("./_nuxt/settings-BUP-Ai1j.js")
  },
  {
    name: "test-api-direct___fr",
    path: "/test-api-direct",
    component: () => import("./_nuxt/test-api-direct-ClnpXwWo.js")
  },
  {
    name: "test-api-direct___en",
    path: "/en/test-api-direct",
    component: () => import("./_nuxt/test-api-direct-ClnpXwWo.js")
  },
  {
    name: "test-api-direct___nl",
    path: "/nl/test-api-direct",
    component: () => import("./_nuxt/test-api-direct-ClnpXwWo.js")
  },
  {
    name: "test-api-direct___de",
    path: "/de/test-api-direct",
    component: () => import("./_nuxt/test-api-direct-ClnpXwWo.js")
  },
  {
    name: "test-api-direct___it",
    path: "/it/test-api-direct",
    component: () => import("./_nuxt/test-api-direct-ClnpXwWo.js")
  },
  {
    name: "test-api-direct___es",
    path: "/es/test-api-direct",
    component: () => import("./_nuxt/test-api-direct-ClnpXwWo.js")
  },
  {
    name: "test-api-direct___pt",
    path: "/pt/test-api-direct",
    component: () => import("./_nuxt/test-api-direct-ClnpXwWo.js")
  },
  {
    name: "test-api-direct___hu",
    path: "/hu/test-api-direct",
    component: () => import("./_nuxt/test-api-direct-ClnpXwWo.js")
  },
  {
    name: "test-api-direct___pl",
    path: "/pl/test-api-direct",
    component: () => import("./_nuxt/test-api-direct-ClnpXwWo.js")
  },
  {
    name: "test-api-direct___zh",
    path: "/zh/test-api-direct",
    component: () => import("./_nuxt/test-api-direct-ClnpXwWo.js")
  },
  {
    name: "test-api-direct___ja",
    path: "/ja/test-api-direct",
    component: () => import("./_nuxt/test-api-direct-ClnpXwWo.js")
  },
  {
    name: "test-api-direct___sv",
    path: "/sv/test-api-direct",
    component: () => import("./_nuxt/test-api-direct-ClnpXwWo.js")
  },
  {
    name: "test-api-direct___no",
    path: "/no/test-api-direct",
    component: () => import("./_nuxt/test-api-direct-ClnpXwWo.js")
  },
  {
    name: "test-api-direct___fi",
    path: "/fi/test-api-direct",
    component: () => import("./_nuxt/test-api-direct-ClnpXwWo.js")
  },
  {
    name: "test-api-direct___da",
    path: "/da/test-api-direct",
    component: () => import("./_nuxt/test-api-direct-ClnpXwWo.js")
  },
  {
    name: "teacher-dashboard___fr",
    path: "/teacher/dashboard",
    meta: { "middleware": ["auth", "teacher"] },
    component: () => import("./_nuxt/dashboard-B-XyUsej.js")
  },
  {
    name: "teacher-dashboard___en",
    path: "/en/teacher/dashboard",
    meta: { "middleware": ["auth", "teacher"] },
    component: () => import("./_nuxt/dashboard-B-XyUsej.js")
  },
  {
    name: "teacher-dashboard___nl",
    path: "/nl/teacher/dashboard",
    meta: { "middleware": ["auth", "teacher"] },
    component: () => import("./_nuxt/dashboard-B-XyUsej.js")
  },
  {
    name: "teacher-dashboard___de",
    path: "/de/teacher/dashboard",
    meta: { "middleware": ["auth", "teacher"] },
    component: () => import("./_nuxt/dashboard-B-XyUsej.js")
  },
  {
    name: "teacher-dashboard___it",
    path: "/it/teacher/dashboard",
    meta: { "middleware": ["auth", "teacher"] },
    component: () => import("./_nuxt/dashboard-B-XyUsej.js")
  },
  {
    name: "teacher-dashboard___es",
    path: "/es/teacher/dashboard",
    meta: { "middleware": ["auth", "teacher"] },
    component: () => import("./_nuxt/dashboard-B-XyUsej.js")
  },
  {
    name: "teacher-dashboard___pt",
    path: "/pt/teacher/dashboard",
    meta: { "middleware": ["auth", "teacher"] },
    component: () => import("./_nuxt/dashboard-B-XyUsej.js")
  },
  {
    name: "teacher-dashboard___hu",
    path: "/hu/teacher/dashboard",
    meta: { "middleware": ["auth", "teacher"] },
    component: () => import("./_nuxt/dashboard-B-XyUsej.js")
  },
  {
    name: "teacher-dashboard___pl",
    path: "/pl/teacher/dashboard",
    meta: { "middleware": ["auth", "teacher"] },
    component: () => import("./_nuxt/dashboard-B-XyUsej.js")
  },
  {
    name: "teacher-dashboard___zh",
    path: "/zh/teacher/dashboard",
    meta: { "middleware": ["auth", "teacher"] },
    component: () => import("./_nuxt/dashboard-B-XyUsej.js")
  },
  {
    name: "teacher-dashboard___ja",
    path: "/ja/teacher/dashboard",
    meta: { "middleware": ["auth", "teacher"] },
    component: () => import("./_nuxt/dashboard-B-XyUsej.js")
  },
  {
    name: "teacher-dashboard___sv",
    path: "/sv/teacher/dashboard",
    meta: { "middleware": ["auth", "teacher"] },
    component: () => import("./_nuxt/dashboard-B-XyUsej.js")
  },
  {
    name: "teacher-dashboard___no",
    path: "/no/teacher/dashboard",
    meta: { "middleware": ["auth", "teacher"] },
    component: () => import("./_nuxt/dashboard-B-XyUsej.js")
  },
  {
    name: "teacher-dashboard___fi",
    path: "/fi/teacher/dashboard",
    meta: { "middleware": ["auth", "teacher"] },
    component: () => import("./_nuxt/dashboard-B-XyUsej.js")
  },
  {
    name: "teacher-dashboard___da",
    path: "/da/teacher/dashboard",
    meta: { "middleware": ["auth", "teacher"] },
    component: () => import("./_nuxt/dashboard-B-XyUsej.js")
  }
];
const _wrapInTransition = (props, children) => {
  return { default: () => {
    var _a;
    return (_a = children.default) == null ? void 0 : _a.call(children);
  } };
};
const ROUTE_KEY_PARENTHESES_RE = /(:\w+)\([^)]+\)/g;
const ROUTE_KEY_SYMBOLS_RE = /(:\w+)[?+*]/g;
const ROUTE_KEY_NORMAL_RE = /:\w+/g;
function generateRouteKey(route) {
  const source = (route == null ? void 0 : route.meta.key) ?? route.path.replace(ROUTE_KEY_PARENTHESES_RE, "$1").replace(ROUTE_KEY_SYMBOLS_RE, "$1").replace(ROUTE_KEY_NORMAL_RE, (r) => {
    var _a;
    return ((_a = route.params[r.slice(1)]) == null ? void 0 : _a.toString()) || "";
  });
  return typeof source === "function" ? source(route) : source;
}
function isChangingPage(to, from) {
  if (to === from || from === START_LOCATION) {
    return false;
  }
  if (generateRouteKey(to) !== generateRouteKey(from)) {
    return true;
  }
  const areComponentsSame = to.matched.every(
    (comp, index) => {
      var _a, _b;
      return comp.components && comp.components.default === ((_b = (_a = from.matched[index]) == null ? void 0 : _a.components) == null ? void 0 : _b.default);
    }
  );
  if (areComponentsSame) {
    return false;
  }
  return true;
}
const routerOptions0 = {
  scrollBehavior(to, from, savedPosition) {
    var _a;
    const nuxtApp = useNuxtApp();
    const behavior = ((_a = useRouter().options) == null ? void 0 : _a.scrollBehaviorType) ?? "auto";
    if (to.path === from.path) {
      if (from.hash && !to.hash) {
        return { left: 0, top: 0 };
      }
      if (to.hash) {
        return { el: to.hash, top: _getHashElementScrollMarginTop(to.hash), behavior };
      }
      return false;
    }
    const routeAllowsScrollToTop = typeof to.meta.scrollToTop === "function" ? to.meta.scrollToTop(to, from) : to.meta.scrollToTop;
    if (routeAllowsScrollToTop === false) {
      return false;
    }
    const hookToWait = nuxtApp._runningTransition ? "page:transition:finish" : "page:loading:end";
    return new Promise((resolve2) => {
      if (from === START_LOCATION) {
        resolve2(_calculatePosition(to, from, savedPosition, behavior));
        return;
      }
      nuxtApp.hooks.hookOnce(hookToWait, () => {
        requestAnimationFrame(() => resolve2(_calculatePosition(to, from, savedPosition, behavior)));
      });
    });
  }
};
function _getHashElementScrollMarginTop(selector) {
  try {
    const elem = (void 0).querySelector(selector);
    if (elem) {
      return (Number.parseFloat(getComputedStyle(elem).scrollMarginTop) || 0) + (Number.parseFloat(getComputedStyle((void 0).documentElement).scrollPaddingTop) || 0);
    }
  } catch {
  }
  return 0;
}
function _calculatePosition(to, from, savedPosition, defaultBehavior) {
  if (savedPosition) {
    return savedPosition;
  }
  const isPageNavigation = isChangingPage(to, from);
  if (to.hash) {
    return {
      el: to.hash,
      top: _getHashElementScrollMarginTop(to.hash),
      behavior: isPageNavigation ? defaultBehavior : "instant"
    };
  }
  return {
    left: 0,
    top: 0,
    behavior: isPageNavigation ? defaultBehavior : "instant"
  };
}
const configRouterOptions = {
  hashMode: false,
  scrollBehaviorType: "auto"
};
const routerOptions = {
  ...configRouterOptions,
  ...routerOptions0
};
const validate = /* @__PURE__ */ defineNuxtRouteMiddleware(async (to, from) => {
  var _a;
  let __temp, __restore;
  if (!((_a = to.meta) == null ? void 0 : _a.validate)) {
    return;
  }
  const result = ([__temp, __restore] = executeAsync(() => Promise.resolve(to.meta.validate(to))), __temp = await __temp, __restore(), __temp);
  if (result === true) {
    return;
  }
  const error = createError({
    fatal: false,
    statusCode: result && result.statusCode || 404,
    statusMessage: result && result.statusMessage || `Page Not Found: ${to.fullPath}`,
    data: {
      path: to.fullPath
    }
  });
  return error;
});
const manifest_45route_45rule = /* @__PURE__ */ defineNuxtRouteMiddleware(async (to) => {
  {
    return;
  }
});
const globalMiddleware = [
  validate,
  manifest_45route_45rule
];
const namedMiddleware = {
  admin: () => import("./_nuxt/admin-Bj3p_rOq.js"),
  "auth-admin": () => import("./_nuxt/auth-admin-9kxYnPKa.js"),
  auth: () => import("./_nuxt/auth-CpWNZrNB.js"),
  "role-control": () => import("./_nuxt/role-control-DpT6FdCQ.js")
};
const plugin$1 = /* @__PURE__ */ defineNuxtPlugin({
  name: "nuxt:router",
  enforce: "pre",
  async setup(nuxtApp) {
    var _a, _b, _c;
    let __temp, __restore;
    let routerBase = (/* @__PURE__ */ useRuntimeConfig()).app.baseURL;
    const history = ((_a = routerOptions.history) == null ? void 0 : _a.call(routerOptions, routerBase)) ?? createMemoryHistory(routerBase);
    const routes = routerOptions.routes ? ([__temp, __restore] = executeAsync(() => routerOptions.routes(_routes)), __temp = await __temp, __restore(), __temp) ?? _routes : _routes;
    let startPosition;
    const router = createRouter$1({
      ...routerOptions,
      scrollBehavior: (to, from, savedPosition) => {
        if (from === START_LOCATION) {
          startPosition = savedPosition;
          return;
        }
        if (routerOptions.scrollBehavior) {
          router.options.scrollBehavior = routerOptions.scrollBehavior;
          if ("scrollRestoration" in (void 0).history) {
            const unsub = router.beforeEach(() => {
              unsub();
              (void 0).history.scrollRestoration = "manual";
            });
          }
          return routerOptions.scrollBehavior(to, START_LOCATION, startPosition || savedPosition);
        }
      },
      history,
      routes
    });
    nuxtApp.vueApp.use(router);
    const previousRoute = shallowRef(router.currentRoute.value);
    router.afterEach((_to, from) => {
      previousRoute.value = from;
    });
    Object.defineProperty(nuxtApp.vueApp.config.globalProperties, "previousRoute", {
      get: () => previousRoute.value
    });
    const initialURL = nuxtApp.ssrContext.url;
    const _route = shallowRef(router.currentRoute.value);
    const syncCurrentRoute = () => {
      _route.value = router.currentRoute.value;
    };
    nuxtApp.hook("page:finish", syncCurrentRoute);
    router.afterEach((to, from) => {
      var _a2, _b2, _c2, _d;
      if (((_b2 = (_a2 = to.matched[0]) == null ? void 0 : _a2.components) == null ? void 0 : _b2.default) === ((_d = (_c2 = from.matched[0]) == null ? void 0 : _c2.components) == null ? void 0 : _d.default)) {
        syncCurrentRoute();
      }
    });
    const route = {};
    for (const key in _route.value) {
      Object.defineProperty(route, key, {
        get: () => _route.value[key],
        enumerable: true
      });
    }
    nuxtApp._route = shallowReactive(route);
    nuxtApp._middleware || (nuxtApp._middleware = {
      global: [],
      named: {}
    });
    useError();
    if (!((_b = nuxtApp.ssrContext) == null ? void 0 : _b.islandContext)) {
      router.afterEach(async (to, _from, failure) => {
        delete nuxtApp._processingMiddleware;
        if (failure) {
          await nuxtApp.callHook("page:loading:end");
        }
        if ((failure == null ? void 0 : failure.type) === 4) {
          return;
        }
        if (to.redirectedFrom && to.fullPath !== initialURL) {
          await nuxtApp.runWithContext(() => navigateTo(to.fullPath || "/"));
        }
      });
    }
    try {
      if (true) {
        ;
        [__temp, __restore] = executeAsync(() => router.push(initialURL)), await __temp, __restore();
        ;
      }
      ;
      [__temp, __restore] = executeAsync(() => router.isReady()), await __temp, __restore();
      ;
    } catch (error2) {
      [__temp, __restore] = executeAsync(() => nuxtApp.runWithContext(() => showError(error2))), await __temp, __restore();
    }
    const resolvedInitialRoute = router.currentRoute.value;
    syncCurrentRoute();
    if ((_c = nuxtApp.ssrContext) == null ? void 0 : _c.islandContext) {
      return { provide: { router } };
    }
    const initialLayout = nuxtApp.payload.state._layout;
    router.beforeEach(async (to, from) => {
      var _a2, _b2;
      await nuxtApp.callHook("page:loading:start");
      to.meta = reactive(to.meta);
      if (nuxtApp.isHydrating && initialLayout && !isReadonly(to.meta.layout)) {
        to.meta.layout = initialLayout;
      }
      nuxtApp._processingMiddleware = true;
      if (!((_a2 = nuxtApp.ssrContext) == null ? void 0 : _a2.islandContext)) {
        const middlewareEntries = /* @__PURE__ */ new Set([...globalMiddleware, ...nuxtApp._middleware.global]);
        for (const component of to.matched) {
          const componentMiddleware = component.meta.middleware;
          if (!componentMiddleware) {
            continue;
          }
          for (const entry2 of toArray(componentMiddleware)) {
            middlewareEntries.add(entry2);
          }
        }
        {
          const routeRules = await nuxtApp.runWithContext(() => getRouteRules({ path: to.path }));
          if (routeRules.appMiddleware) {
            for (const key in routeRules.appMiddleware) {
              if (routeRules.appMiddleware[key]) {
                middlewareEntries.add(key);
              } else {
                middlewareEntries.delete(key);
              }
            }
          }
        }
        for (const entry2 of middlewareEntries) {
          const middleware = typeof entry2 === "string" ? nuxtApp._middleware.named[entry2] || await ((_b2 = namedMiddleware[entry2]) == null ? void 0 : _b2.call(namedMiddleware).then((r) => r.default || r)) : entry2;
          if (!middleware) {
            throw new Error(`Unknown route middleware: '${entry2}'.`);
          }
          try {
            const result = await nuxtApp.runWithContext(() => middleware(to, from));
            if (true) {
              if (result === false || result instanceof Error) {
                const error2 = result || createError({
                  statusCode: 404,
                  statusMessage: `Page Not Found: ${initialURL}`
                });
                await nuxtApp.runWithContext(() => showError(error2));
                return false;
              }
            }
            if (result === true) {
              continue;
            }
            if (result === false) {
              return result;
            }
            if (result) {
              if (isNuxtError(result) && result.fatal) {
                await nuxtApp.runWithContext(() => showError(result));
              }
              return result;
            }
          } catch (err) {
            const error2 = createError(err);
            if (error2.fatal) {
              await nuxtApp.runWithContext(() => showError(error2));
            }
            return error2;
          }
        }
      }
    });
    router.onError(async () => {
      delete nuxtApp._processingMiddleware;
      await nuxtApp.callHook("page:loading:end");
    });
    router.afterEach(async (to, _from) => {
      if (to.matched.length === 0) {
        await nuxtApp.runWithContext(() => showError(createError({
          statusCode: 404,
          fatal: false,
          statusMessage: `Page not found: ${to.fullPath}`,
          data: {
            path: to.fullPath
          }
        })));
      }
    });
    nuxtApp.hooks.hookOnce("app:created", async () => {
      try {
        if ("name" in resolvedInitialRoute) {
          resolvedInitialRoute.name = void 0;
        }
        await router.replace({
          ...resolvedInitialRoute,
          force: true
        });
        router.options.scrollBehavior = routerOptions.scrollBehavior;
      } catch (error2) {
        await nuxtApp.runWithContext(() => showError(error2));
      }
    });
    return { provide: { router } };
  }
});
function injectHead(nuxtApp) {
  var _a;
  const nuxt = nuxtApp || tryUseNuxtApp();
  return ((_a = nuxt == null ? void 0 : nuxt.ssrContext) == null ? void 0 : _a.head) || (nuxt == null ? void 0 : nuxt.runWithContext(() => {
    if (hasInjectionContext()) {
      return inject(headSymbol);
    }
  }));
}
function useHead(input, options = {}) {
  const head = injectHead(options.nuxt);
  if (head) {
    return useHead$1(input, { head, ...options });
  }
}
function definePayloadReducer(name, reduce) {
  {
    useNuxtApp().ssrContext._payloadReducers[name] = reduce;
  }
}
const reducers = [
  ["NuxtError", (data) => isNuxtError(data) && data.toJSON()],
  ["EmptyShallowRef", (data) => isRef(data) && isShallow(data) && !data.value && (typeof data.value === "bigint" ? "0n" : JSON.stringify(data.value) || "_")],
  ["EmptyRef", (data) => isRef(data) && !data.value && (typeof data.value === "bigint" ? "0n" : JSON.stringify(data.value) || "_")],
  ["ShallowRef", (data) => isRef(data) && isShallow(data) && data.value],
  ["ShallowReactive", (data) => isReactive(data) && isShallow(data) && toRaw(data)],
  ["Ref", (data) => isRef(data) && data.value],
  ["Reactive", (data) => isReactive(data) && toRaw(data)]
];
const revive_payload_server_MVtmlZaQpj6ApFmshWfUWl5PehCebzaBf2NuRMiIbms = /* @__PURE__ */ defineNuxtPlugin({
  name: "nuxt:revive-payload:server",
  setup() {
    for (const [reducer, fn] of reducers) {
      definePayloadReducer(reducer, fn);
    }
  }
});
function set(target, key, val) {
  if (Array.isArray(target)) {
    target.length = Math.max(target.length, key);
    target.splice(key, 1, val);
    return val;
  }
  target[key] = val;
  return val;
}
function del(target, key) {
  if (Array.isArray(target)) {
    target.splice(key, 1);
    return;
  }
  delete target[key];
}
/*!
 * pinia v2.3.1
 * (c) 2025 Eduardo San Martin Morote
 * @license MIT
 */
let activePinia;
const setActivePinia = (pinia) => activePinia = pinia;
const piniaSymbol = process.env.NODE_ENV !== "production" ? Symbol("pinia") : (
  /* istanbul ignore next */
  Symbol()
);
function isPlainObject$1(o) {
  return o && typeof o === "object" && Object.prototype.toString.call(o) === "[object Object]" && typeof o.toJSON !== "function";
}
var MutationType;
(function(MutationType2) {
  MutationType2["direct"] = "direct";
  MutationType2["patchObject"] = "patch object";
  MutationType2["patchFunction"] = "patch function";
})(MutationType || (MutationType = {}));
const IS_CLIENT = false;
function createPinia() {
  const scope = effectScope(true);
  const state = scope.run(() => ref({}));
  let _p = [];
  let toBeInstalled = [];
  const pinia = markRaw({
    install(app) {
      setActivePinia(pinia);
      {
        pinia._a = app;
        app.provide(piniaSymbol, pinia);
        app.config.globalProperties.$pinia = pinia;
        if ((process.env.NODE_ENV !== "production" || false) && !(process.env.NODE_ENV === "test") && IS_CLIENT) ;
        toBeInstalled.forEach((plugin2) => _p.push(plugin2));
        toBeInstalled = [];
      }
    },
    use(plugin2) {
      if (!this._a && true) {
        toBeInstalled.push(plugin2);
      } else {
        _p.push(plugin2);
      }
      return this;
    },
    _p,
    // it's actually undefined here
    // @ts-expect-error
    _a: null,
    _e: scope,
    _s: /* @__PURE__ */ new Map(),
    state
  });
  if ((process.env.NODE_ENV !== "production" || false) && !(process.env.NODE_ENV === "test") && IS_CLIENT) ;
  return pinia;
}
function patchObject(newState, oldState) {
  for (const key in oldState) {
    const subPatch = oldState[key];
    if (!(key in newState)) {
      continue;
    }
    const targetValue = newState[key];
    if (isPlainObject$1(targetValue) && isPlainObject$1(subPatch) && !isRef(subPatch) && !isReactive(subPatch)) {
      newState[key] = patchObject(targetValue, subPatch);
    } else {
      {
        newState[key] = subPatch;
      }
    }
  }
  return newState;
}
const noop = () => {
};
function addSubscription(subscriptions, callback, detached, onCleanup = noop) {
  subscriptions.push(callback);
  const removeSubscription = () => {
    const idx = subscriptions.indexOf(callback);
    if (idx > -1) {
      subscriptions.splice(idx, 1);
      onCleanup();
    }
  };
  if (!detached && getCurrentScope()) {
    onScopeDispose(removeSubscription);
  }
  return removeSubscription;
}
function triggerSubscriptions(subscriptions, ...args) {
  subscriptions.slice().forEach((callback) => {
    callback(...args);
  });
}
const fallbackRunWithContext = (fn) => fn();
const ACTION_MARKER = Symbol();
const ACTION_NAME = Symbol();
function mergeReactiveObjects(target, patchToApply) {
  if (target instanceof Map && patchToApply instanceof Map) {
    patchToApply.forEach((value, key) => target.set(key, value));
  } else if (target instanceof Set && patchToApply instanceof Set) {
    patchToApply.forEach(target.add, target);
  }
  for (const key in patchToApply) {
    if (!patchToApply.hasOwnProperty(key))
      continue;
    const subPatch = patchToApply[key];
    const targetValue = target[key];
    if (isPlainObject$1(targetValue) && isPlainObject$1(subPatch) && target.hasOwnProperty(key) && !isRef(subPatch) && !isReactive(subPatch)) {
      target[key] = mergeReactiveObjects(targetValue, subPatch);
    } else {
      target[key] = subPatch;
    }
  }
  return target;
}
const skipHydrateSymbol = process.env.NODE_ENV !== "production" ? Symbol("pinia:skipHydration") : (
  /* istanbul ignore next */
  Symbol()
);
function shouldHydrate(obj) {
  return !isPlainObject$1(obj) || !obj.hasOwnProperty(skipHydrateSymbol);
}
const { assign: assign$1 } = Object;
function isComputed(o) {
  return !!(isRef(o) && o.effect);
}
function createOptionsStore(id, options, pinia, hot) {
  const { state, actions, getters } = options;
  const initialState = pinia.state.value[id];
  let store;
  function setup() {
    if (!initialState && (!(process.env.NODE_ENV !== "production") || !hot)) {
      {
        pinia.state.value[id] = state ? state() : {};
      }
    }
    const localState = process.env.NODE_ENV !== "production" && hot ? (
      // use ref() to unwrap refs inside state TODO: check if this is still necessary
      toRefs(ref(state ? state() : {}).value)
    ) : toRefs(pinia.state.value[id]);
    return assign$1(localState, actions, Object.keys(getters || {}).reduce((computedGetters, name) => {
      if (process.env.NODE_ENV !== "production" && name in localState) {
        console.warn(`[🍍]: A getter cannot have the same name as another state property. Rename one of them. Found with "${name}" in store "${id}".`);
      }
      computedGetters[name] = markRaw(computed(() => {
        setActivePinia(pinia);
        const store2 = pinia._s.get(id);
        return getters[name].call(store2, store2);
      }));
      return computedGetters;
    }, {}));
  }
  store = createSetupStore(id, setup, options, pinia, hot, true);
  return store;
}
function createSetupStore($id, setup, options = {}, pinia, hot, isOptionsStore) {
  let scope;
  const optionsForPlugin = assign$1({ actions: {} }, options);
  if (process.env.NODE_ENV !== "production" && !pinia._e.active) {
    throw new Error("Pinia destroyed");
  }
  const $subscribeOptions = { deep: true };
  if (process.env.NODE_ENV !== "production" && true) {
    $subscribeOptions.onTrigger = (event) => {
      if (isListening) {
        debuggerEvents = event;
      } else if (isListening == false && !store._hotUpdating) {
        if (Array.isArray(debuggerEvents)) {
          debuggerEvents.push(event);
        } else {
          console.error("🍍 debuggerEvents should be an array. This is most likely an internal Pinia bug.");
        }
      }
    };
  }
  let isListening;
  let isSyncListening;
  let subscriptions = [];
  let actionSubscriptions = [];
  let debuggerEvents;
  const initialState = pinia.state.value[$id];
  if (!isOptionsStore && !initialState && (!(process.env.NODE_ENV !== "production") || !hot)) {
    {
      pinia.state.value[$id] = {};
    }
  }
  const hotState = ref({});
  let activeListener;
  function $patch(partialStateOrMutator) {
    let subscriptionMutation;
    isListening = isSyncListening = false;
    if (process.env.NODE_ENV !== "production") {
      debuggerEvents = [];
    }
    if (typeof partialStateOrMutator === "function") {
      partialStateOrMutator(pinia.state.value[$id]);
      subscriptionMutation = {
        type: MutationType.patchFunction,
        storeId: $id,
        events: debuggerEvents
      };
    } else {
      mergeReactiveObjects(pinia.state.value[$id], partialStateOrMutator);
      subscriptionMutation = {
        type: MutationType.patchObject,
        payload: partialStateOrMutator,
        storeId: $id,
        events: debuggerEvents
      };
    }
    const myListenerId = activeListener = Symbol();
    nextTick().then(() => {
      if (activeListener === myListenerId) {
        isListening = true;
      }
    });
    isSyncListening = true;
    triggerSubscriptions(subscriptions, subscriptionMutation, pinia.state.value[$id]);
  }
  const $reset = isOptionsStore ? function $reset2() {
    const { state } = options;
    const newState = state ? state() : {};
    this.$patch(($state) => {
      assign$1($state, newState);
    });
  } : (
    /* istanbul ignore next */
    process.env.NODE_ENV !== "production" ? () => {
      throw new Error(`🍍: Store "${$id}" is built using the setup syntax and does not implement $reset().`);
    } : noop
  );
  function $dispose() {
    scope.stop();
    subscriptions = [];
    actionSubscriptions = [];
    pinia._s.delete($id);
  }
  const action = (fn, name = "") => {
    if (ACTION_MARKER in fn) {
      fn[ACTION_NAME] = name;
      return fn;
    }
    const wrappedAction = function() {
      setActivePinia(pinia);
      const args = Array.from(arguments);
      const afterCallbackList = [];
      const onErrorCallbackList = [];
      function after(callback) {
        afterCallbackList.push(callback);
      }
      function onError(callback) {
        onErrorCallbackList.push(callback);
      }
      triggerSubscriptions(actionSubscriptions, {
        args,
        name: wrappedAction[ACTION_NAME],
        store,
        after,
        onError
      });
      let ret;
      try {
        ret = fn.apply(this && this.$id === $id ? this : store, args);
      } catch (error) {
        triggerSubscriptions(onErrorCallbackList, error);
        throw error;
      }
      if (ret instanceof Promise) {
        return ret.then((value) => {
          triggerSubscriptions(afterCallbackList, value);
          return value;
        }).catch((error) => {
          triggerSubscriptions(onErrorCallbackList, error);
          return Promise.reject(error);
        });
      }
      triggerSubscriptions(afterCallbackList, ret);
      return ret;
    };
    wrappedAction[ACTION_MARKER] = true;
    wrappedAction[ACTION_NAME] = name;
    return wrappedAction;
  };
  const _hmrPayload = /* @__PURE__ */ markRaw({
    actions: {},
    getters: {},
    state: [],
    hotState
  });
  const partialStore = {
    _p: pinia,
    // _s: scope,
    $id,
    $onAction: addSubscription.bind(null, actionSubscriptions),
    $patch,
    $reset,
    $subscribe(callback, options2 = {}) {
      const removeSubscription = addSubscription(subscriptions, callback, options2.detached, () => stopWatcher());
      const stopWatcher = scope.run(() => watch(() => pinia.state.value[$id], (state) => {
        if (options2.flush === "sync" ? isSyncListening : isListening) {
          callback({
            storeId: $id,
            type: MutationType.direct,
            events: debuggerEvents
          }, state);
        }
      }, assign$1({}, $subscribeOptions, options2)));
      return removeSubscription;
    },
    $dispose
  };
  const store = reactive(process.env.NODE_ENV !== "production" || (process.env.NODE_ENV !== "production" || false) && !(process.env.NODE_ENV === "test") && IS_CLIENT ? assign$1(
    {
      _hmrPayload,
      _customProperties: markRaw(/* @__PURE__ */ new Set())
      // devtools custom properties
    },
    partialStore
    // must be added later
    // setupStore
  ) : partialStore);
  pinia._s.set($id, store);
  const runWithContext = pinia._a && pinia._a.runWithContext || fallbackRunWithContext;
  const setupStore = runWithContext(() => pinia._e.run(() => (scope = effectScope()).run(() => setup({ action }))));
  for (const key in setupStore) {
    const prop = setupStore[key];
    if (isRef(prop) && !isComputed(prop) || isReactive(prop)) {
      if (process.env.NODE_ENV !== "production" && hot) {
        set(hotState.value, key, toRef(setupStore, key));
      } else if (!isOptionsStore) {
        if (initialState && shouldHydrate(prop)) {
          if (isRef(prop)) {
            prop.value = initialState[key];
          } else {
            mergeReactiveObjects(prop, initialState[key]);
          }
        }
        {
          pinia.state.value[$id][key] = prop;
        }
      }
      if (process.env.NODE_ENV !== "production") {
        _hmrPayload.state.push(key);
      }
    } else if (typeof prop === "function") {
      const actionValue = process.env.NODE_ENV !== "production" && hot ? prop : action(prop, key);
      {
        setupStore[key] = actionValue;
      }
      if (process.env.NODE_ENV !== "production") {
        _hmrPayload.actions[key] = prop;
      }
      optionsForPlugin.actions[key] = prop;
    } else if (process.env.NODE_ENV !== "production") {
      if (isComputed(prop)) {
        _hmrPayload.getters[key] = isOptionsStore ? (
          // @ts-expect-error
          options.getters[key]
        ) : prop;
      }
    }
  }
  {
    assign$1(store, setupStore);
    assign$1(toRaw(store), setupStore);
  }
  Object.defineProperty(store, "$state", {
    get: () => process.env.NODE_ENV !== "production" && hot ? hotState.value : pinia.state.value[$id],
    set: (state) => {
      if (process.env.NODE_ENV !== "production" && hot) {
        throw new Error("cannot set hotState");
      }
      $patch(($state) => {
        assign$1($state, state);
      });
    }
  });
  if (process.env.NODE_ENV !== "production") {
    store._hotUpdate = markRaw((newStore) => {
      store._hotUpdating = true;
      newStore._hmrPayload.state.forEach((stateKey) => {
        if (stateKey in store.$state) {
          const newStateTarget = newStore.$state[stateKey];
          const oldStateSource = store.$state[stateKey];
          if (typeof newStateTarget === "object" && isPlainObject$1(newStateTarget) && isPlainObject$1(oldStateSource)) {
            patchObject(newStateTarget, oldStateSource);
          } else {
            newStore.$state[stateKey] = oldStateSource;
          }
        }
        set(store, stateKey, toRef(newStore.$state, stateKey));
      });
      Object.keys(store.$state).forEach((stateKey) => {
        if (!(stateKey in newStore.$state)) {
          del(store, stateKey);
        }
      });
      isListening = false;
      isSyncListening = false;
      pinia.state.value[$id] = toRef(newStore._hmrPayload, "hotState");
      isSyncListening = true;
      nextTick().then(() => {
        isListening = true;
      });
      for (const actionName in newStore._hmrPayload.actions) {
        const actionFn = newStore[actionName];
        set(store, actionName, action(actionFn, actionName));
      }
      for (const getterName in newStore._hmrPayload.getters) {
        const getter = newStore._hmrPayload.getters[getterName];
        const getterValue = isOptionsStore ? (
          // special handling of options api
          computed(() => {
            setActivePinia(pinia);
            return getter.call(store, store);
          })
        ) : getter;
        set(store, getterName, getterValue);
      }
      Object.keys(store._hmrPayload.getters).forEach((key) => {
        if (!(key in newStore._hmrPayload.getters)) {
          del(store, key);
        }
      });
      Object.keys(store._hmrPayload.actions).forEach((key) => {
        if (!(key in newStore._hmrPayload.actions)) {
          del(store, key);
        }
      });
      store._hmrPayload = newStore._hmrPayload;
      store._getters = newStore._getters;
      store._hotUpdating = false;
    });
  }
  if ((process.env.NODE_ENV !== "production" || false) && !(process.env.NODE_ENV === "test") && IS_CLIENT) ;
  pinia._p.forEach((extender) => {
    if ((process.env.NODE_ENV !== "production" || false) && !(process.env.NODE_ENV === "test") && IS_CLIENT) ;
    else {
      assign$1(store, scope.run(() => extender({
        store,
        app: pinia._a,
        pinia,
        options: optionsForPlugin
      })));
    }
  });
  if (process.env.NODE_ENV !== "production" && store.$state && typeof store.$state === "object" && typeof store.$state.constructor === "function" && !store.$state.constructor.toString().includes("[native code]")) {
    console.warn(`[🍍]: The "state" must be a plain object. It cannot be
	state: () => new MyClass()
Found in store "${store.$id}".`);
  }
  if (initialState && isOptionsStore && options.hydrate) {
    options.hydrate(store.$state, initialState);
  }
  isListening = true;
  isSyncListening = true;
  return store;
}
/*! #__NO_SIDE_EFFECTS__ */
// @__NO_SIDE_EFFECTS__
function defineStore(idOrOptions, setup, setupOptions) {
  let id;
  let options;
  const isSetupStore = typeof setup === "function";
  {
    id = idOrOptions;
    options = isSetupStore ? setupOptions : setup;
  }
  function useStore(pinia, hot) {
    const hasContext = hasInjectionContext();
    pinia = // in test mode, ignore the argument provided as we can always retrieve a
    // pinia instance with getActivePinia()
    (process.env.NODE_ENV === "test" && activePinia && activePinia._testing ? null : pinia) || (hasContext ? inject(piniaSymbol, null) : null);
    if (pinia)
      setActivePinia(pinia);
    if (process.env.NODE_ENV !== "production" && !activePinia) {
      throw new Error(`[🍍]: "getActivePinia()" was called but there was no active Pinia. Are you trying to use a store before calling "app.use(pinia)"?
See https://pinia.vuejs.org/core-concepts/outside-component-usage.html for help.
This will fail in production.`);
    }
    pinia = activePinia;
    if (!pinia._s.has(id)) {
      if (isSetupStore) {
        createSetupStore(id, setup, options, pinia);
      } else {
        createOptionsStore(id, options, pinia);
      }
      if (process.env.NODE_ENV !== "production") {
        useStore._pinia = pinia;
      }
    }
    const store = pinia._s.get(id);
    if (process.env.NODE_ENV !== "production" && hot) {
      const hotId = "__hot:" + id;
      const newStore = isSetupStore ? createSetupStore(hotId, setup, options, pinia, true) : createOptionsStore(hotId, assign$1({}, options), pinia, true);
      hot._hotUpdate(newStore);
      delete pinia.state.value[hotId];
      pinia._s.delete(hotId);
    }
    if (process.env.NODE_ENV !== "production" && IS_CLIENT) ;
    return store;
  }
  useStore.$id = id;
  return useStore;
}
defineComponent({
  name: "ServerPlaceholder",
  render() {
    return createElementBlock("div");
  }
});
const clientOnlySymbol = Symbol.for("nuxt:client-only");
defineComponent({
  name: "ClientOnly",
  inheritAttrs: false,
  props: ["fallback", "placeholder", "placeholderTag", "fallbackTag"],
  setup(props, { slots, attrs }) {
    const mounted = shallowRef(false);
    const vm = getCurrentInstance();
    if (vm) {
      vm._nuxtClientOnly = true;
    }
    provide(clientOnlySymbol, true);
    return () => {
      var _a;
      if (mounted.value) {
        const vnodes = (_a = slots.default) == null ? void 0 : _a.call(slots);
        if (vnodes && vnodes.length === 1) {
          return [cloneVNode(vnodes[0], attrs)];
        }
        return vnodes;
      }
      const slot = slots.fallback || slots.placeholder;
      if (slot) {
        return h(slot);
      }
      const fallbackStr = props.fallback || props.placeholder || "";
      const fallbackTag = props.fallbackTag || props.placeholderTag || "span";
      return createElementBlock(fallbackTag, attrs, fallbackStr);
    };
  }
});
const useStateKeyPrefix = "$s";
function useState(...args) {
  const autoKey = typeof args[args.length - 1] === "string" ? args.pop() : void 0;
  if (typeof args[0] !== "string") {
    args.unshift(autoKey);
  }
  const [_key, init] = args;
  if (!_key || typeof _key !== "string") {
    throw new TypeError("[nuxt] [useState] key must be a string: " + _key);
  }
  if (init !== void 0 && typeof init !== "function") {
    throw new Error("[nuxt] [useState] init must be a function: " + init);
  }
  const key = useStateKeyPrefix + _key;
  const nuxtApp = useNuxtApp();
  const state = toRef(nuxtApp.payload.state, key);
  if (state.value === void 0 && init) {
    const initialValue = init();
    if (isRef(initialValue)) {
      nuxtApp.payload.state[key] = initialValue;
      return initialValue;
    }
    state.value = initialValue;
  }
  return state;
}
function useRequestEvent(nuxtApp) {
  var _a;
  nuxtApp || (nuxtApp = useNuxtApp());
  return (_a = nuxtApp.ssrContext) == null ? void 0 : _a.event;
}
function useRequestHeaders(include) {
  const event = useRequestEvent();
  const _headers = event ? getRequestHeaders(event) : {};
  if (!include || !event) {
    return _headers;
  }
  const headers = /* @__PURE__ */ Object.create(null);
  for (const _key of include) {
    const key = _key.toLowerCase();
    const header = _headers[key];
    if (header) {
      headers[key] = header;
    }
  }
  return headers;
}
function useRequestFetch() {
  var _a;
  return ((_a = useRequestEvent()) == null ? void 0 : _a.$fetch) || globalThis.$fetch;
}
const CookieDefaults = {
  path: "/",
  watch: true,
  decode: (val) => destr(decodeURIComponent(val)),
  encode: (val) => encodeURIComponent(typeof val === "string" ? val : JSON.stringify(val))
};
function useCookie(name, _opts) {
  var _a;
  const opts = { ...CookieDefaults, ..._opts };
  opts.filter ?? (opts.filter = (key) => key === name);
  const cookies = readRawCookies(opts) || {};
  let delay;
  if (opts.maxAge !== void 0) {
    delay = opts.maxAge * 1e3;
  } else if (opts.expires) {
    delay = opts.expires.getTime() - Date.now();
  }
  const hasExpired = delay !== void 0 && delay <= 0;
  const cookieValue = klona(hasExpired ? void 0 : cookies[name] ?? ((_a = opts.default) == null ? void 0 : _a.call(opts)));
  const cookie = ref(cookieValue);
  {
    const nuxtApp = useNuxtApp();
    const writeFinalCookieValue = () => {
      if (opts.readonly || isEqual$1(cookie.value, cookies[name])) {
        return;
      }
      nuxtApp._cookies || (nuxtApp._cookies = {});
      if (name in nuxtApp._cookies) {
        if (isEqual$1(cookie.value, nuxtApp._cookies[name])) {
          return;
        }
      }
      nuxtApp._cookies[name] = cookie.value;
      writeServerCookie(useRequestEvent(nuxtApp), name, cookie.value, opts);
    };
    const unhook = nuxtApp.hooks.hookOnce("app:rendered", writeFinalCookieValue);
    nuxtApp.hooks.hookOnce("app:error", () => {
      unhook();
      return writeFinalCookieValue();
    });
  }
  return cookie;
}
function readRawCookies(opts = {}) {
  {
    return parse$1(getRequestHeader(useRequestEvent(), "cookie") || "", opts);
  }
}
function writeServerCookie(event, name, value, opts = {}) {
  if (event) {
    if (value !== null && value !== void 0) {
      return setCookie(event, name, value, opts);
    }
    if (getCookie(event, name) !== void 0) {
      return deleteCookie(event, name, opts);
    }
  }
}
const plugin = /* @__PURE__ */ defineNuxtPlugin({
  name: "pinia",
  setup(nuxtApp) {
    const pinia = createPinia();
    nuxtApp.vueApp.use(pinia);
    setActivePinia(pinia);
    {
      nuxtApp.payload.pinia = pinia.state.value;
    }
    return {
      provide: {
        pinia
      }
    };
  }
});
const components_plugin_z4hgvsiddfKkfXTP6M8M4zG5Cb7sGnDhcryKVM45Di4 = /* @__PURE__ */ defineNuxtPlugin({
  name: "nuxt:global-components"
});
/*!
  * shared v9.14.5
  * (c) 2025 kazuya kawaguchi
  * Released under the MIT License.
  */
function warn(msg, err) {
  if (typeof console !== "undefined") {
    console.warn(`[intlify] ` + msg);
    if (err) {
      console.warn(err.stack);
    }
  }
}
const hasWarned = {};
function warnOnce(msg) {
  if (!hasWarned[msg]) {
    hasWarned[msg] = true;
    warn(msg);
  }
}
const inBrowser = false;
if (process.env.NODE_ENV !== "production") ;
const RE_ARGS = /\{([0-9a-zA-Z]+)\}/g;
function format$1(message, ...args) {
  if (args.length === 1 && isObject(args[0])) {
    args = args[0];
  }
  if (!args || !args.hasOwnProperty) {
    args = {};
  }
  return message.replace(RE_ARGS, (match, identifier) => {
    return args.hasOwnProperty(identifier) ? args[identifier] : "";
  });
}
const makeSymbol = (name, shareable = false) => !shareable ? Symbol(name) : Symbol.for(name);
const generateFormatCacheKey = (locale, key, source) => friendlyJSONstringify({ l: locale, k: key, s: source });
const friendlyJSONstringify = (json) => JSON.stringify(json).replace(/\u2028/g, "\\u2028").replace(/\u2029/g, "\\u2029").replace(/\u0027/g, "\\u0027");
const isNumber = (val) => typeof val === "number" && isFinite(val);
const isDate = (val) => toTypeString(val) === "[object Date]";
const isRegExp = (val) => toTypeString(val) === "[object RegExp]";
const isEmptyObject = (val) => isPlainObject(val) && Object.keys(val).length === 0;
const assign = Object.assign;
const _create = Object.create;
const create = (obj = null) => _create(obj);
let _globalThis;
const getGlobalThis = () => {
  return _globalThis || (_globalThis = typeof globalThis !== "undefined" ? globalThis : typeof self !== "undefined" ? self : typeof global !== "undefined" ? global : create());
};
function escapeHtml(rawText) {
  return rawText.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&apos;").replace(/\//g, "&#x2F;").replace(/=/g, "&#x3D;");
}
function escapeAttributeValue(value) {
  return value.replace(/&(?![a-zA-Z0-9#]{2,6};)/g, "&amp;").replace(/"/g, "&quot;").replace(/'/g, "&apos;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
}
function sanitizeTranslatedHtml(html) {
  html = html.replace(/(\w+)\s*=\s*"([^"]*)"/g, (_, attrName, attrValue) => `${attrName}="${escapeAttributeValue(attrValue)}"`);
  html = html.replace(/(\w+)\s*=\s*'([^']*)'/g, (_, attrName, attrValue) => `${attrName}='${escapeAttributeValue(attrValue)}'`);
  const eventHandlerPattern = /\s*on\w+\s*=\s*["']?[^"'>]+["']?/gi;
  if (eventHandlerPattern.test(html)) {
    if (process.env.NODE_ENV !== "production") {
      warn("Potentially dangerous event handlers detected in translation. Consider removing onclick, onerror, etc. from your translation messages.");
    }
    html = html.replace(/(\s+)(on)(\w+\s*=)/gi, "$1&#111;n$3");
  }
  const javascriptUrlPattern = [
    // In href, src, action, formaction attributes
    /(\s+(?:href|src|action|formaction)\s*=\s*["']?)\s*javascript:/gi,
    // In style attributes within url()
    /(style\s*=\s*["'][^"']*url\s*\(\s*)javascript:/gi
  ];
  javascriptUrlPattern.forEach((pattern) => {
    html = html.replace(pattern, "$1javascript&#58;");
  });
  return html;
}
const hasOwnProperty = Object.prototype.hasOwnProperty;
function hasOwn(obj, key) {
  return hasOwnProperty.call(obj, key);
}
const isArray = Array.isArray;
const isFunction = (val) => typeof val === "function";
const isString = (val) => typeof val === "string";
const isBoolean = (val) => typeof val === "boolean";
const isSymbol = (val) => typeof val === "symbol";
const isObject = (val) => val !== null && typeof val === "object";
const isPromise = (val) => {
  return isObject(val) && isFunction(val.then) && isFunction(val.catch);
};
const objectToString = Object.prototype.toString;
const toTypeString = (value) => objectToString.call(value);
const isPlainObject = (val) => {
  if (!isObject(val))
    return false;
  const proto = Object.getPrototypeOf(val);
  return proto === null || proto.constructor === Object;
};
const toDisplayString = (val) => {
  return val == null ? "" : isArray(val) || isPlainObject(val) && val.toString === objectToString ? JSON.stringify(val, null, 2) : String(val);
};
function join(items, separator = "") {
  return items.reduce((str, item, index) => index === 0 ? str + item : str + separator + item, "");
}
const RANGE = 2;
function generateCodeFrame(source, start = 0, end = source.length) {
  const lines = source.split(/\r?\n/);
  let count = 0;
  const res = [];
  for (let i = 0; i < lines.length; i++) {
    count += lines[i].length + 1;
    if (count >= start) {
      for (let j = i - RANGE; j <= i + RANGE || end > count; j++) {
        if (j < 0 || j >= lines.length)
          continue;
        const line = j + 1;
        res.push(`${line}${" ".repeat(3 - String(line).length)}|  ${lines[j]}`);
        const lineLength = lines[j].length;
        if (j === i) {
          const pad = start - (count - lineLength) + 1;
          const length = Math.max(1, end > count ? lineLength - pad : end - start);
          res.push(`   |  ` + " ".repeat(pad) + "^".repeat(length));
        } else if (j > i) {
          if (end > count) {
            const length = Math.max(Math.min(end - count, lineLength), 1);
            res.push(`   |  ` + "^".repeat(length));
          }
          count += lineLength + 1;
        }
      }
      break;
    }
  }
  return res.join("\n");
}
function incrementer(code2) {
  let current = code2;
  return () => ++current;
}
function createEmitter() {
  const events = /* @__PURE__ */ new Map();
  const emitter = {
    events,
    on(event, handler) {
      const handlers = events.get(event);
      const added = handlers && handlers.push(handler);
      if (!added) {
        events.set(event, [handler]);
      }
    },
    off(event, handler) {
      const handlers = events.get(event);
      if (handlers) {
        handlers.splice(handlers.indexOf(handler) >>> 0, 1);
      }
    },
    emit(event, payload) {
      (events.get(event) || []).slice().map((handler) => handler(payload));
      (events.get("*") || []).slice().map((handler) => handler(event, payload));
    }
  };
  return emitter;
}
const isNotObjectOrIsArray = (val) => !isObject(val) || isArray(val);
function deepCopy(src, des) {
  if (isNotObjectOrIsArray(src) || isNotObjectOrIsArray(des)) {
    throw new Error("Invalid value");
  }
  const stack = [{ src, des }];
  while (stack.length) {
    const { src: src2, des: des2 } = stack.pop();
    Object.keys(src2).forEach((key) => {
      if (key === "__proto__") {
        return;
      }
      if (isObject(src2[key]) && !isObject(des2[key])) {
        des2[key] = Array.isArray(src2[key]) ? [] : create();
      }
      if (isNotObjectOrIsArray(des2[key]) || isNotObjectOrIsArray(src2[key])) {
        des2[key] = src2[key];
      } else {
        stack.push({ src: src2[key], des: des2[key] });
      }
    });
  }
}
function isHTTPS(req, trustProxy = true) {
  const _xForwardedProto = trustProxy && req.headers ? req.headers["x-forwarded-proto"] : void 0;
  const protoCheck = typeof _xForwardedProto === "string" ? _xForwardedProto.includes("https") : void 0;
  if (protoCheck) {
    return true;
  }
  const _encrypted = req.connection ? req.connection.encrypted : void 0;
  const encryptedCheck = _encrypted !== void 0 ? _encrypted === true : void 0;
  if (encryptedCheck) {
    return true;
  }
  if (protoCheck === void 0 && encryptedCheck === void 0) {
    return void 0;
  }
  return false;
}
const resource$e = {
  "nav": {
    "dashboard": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Tableau de bord" } },
    "teacherSpace": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Espace Enseignant" } },
    "profile": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Mon Profil" } },
    "admin": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Administration" } },
    "login": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Se connecter" } },
    "register": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "S'inscrire" } },
    "logout": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Se déconnecter" } }
  },
  "auth": {
    "login": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Connexion" } },
    "email": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Adresse e-mail" } },
    "password": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Mot de passe" } },
    "rememberMe": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Se souvenir de moi" } },
    "forgotPassword": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Mot de passe oublié ?" } },
    "noAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Pas encore de compte ?" } },
    "createAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Créer un compte" } },
    "name": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Nom complet" } },
    "confirmPassword": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Confirmer le mot de passe" } },
    "alreadyAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Déjà un compte ?" } },
    "loginHere": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Se connecter ici" } }
  },
  "loginPage": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Connexion à votre compte" } },
    "or": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Ou" } },
    "createAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "créez un nouveau compte" } },
    "loggingIn": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Connexion en cours..." } }
  },
  "registerPage": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Créer un compte" } },
    "or": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Ou" } },
    "login": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "connectez-vous à votre compte existant" } },
    "terms": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "J'accepte les" } },
    "termsLink": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "conditions d'utilisation" } },
    "creatingAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Création du compte..." } }
  },
  "dashboard": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3, "v": "Bienvenue, " }, { "t": 4, "k": "name" }] } },
    "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Voici votre tableau de bord personnel" } },
    "upcomingLessons": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Prochains cours" } },
    "completedLessons": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cours terminés" } },
    "totalHours": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Heures totales" } },
    "quickActions": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Actions rapides" } },
    "viewAll": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Voir tous" } },
    "noLessons": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Aucun cours planifié" } },
    "bookLesson": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Réserver un cours" } },
    "browseTeachers": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Parcourir les enseignants" } },
    "with": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "avec" } },
    "confirmed": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Confirmé" } }
  },
  "teachers": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Nos Instructeurs Équestres" } },
    "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Découvrez notre équipe d'instructeurs passionnés et expérimentés" } },
    "searchPlaceholder": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Rechercher un instructeur..." } },
    "allDisciplines": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Toutes disciplines" } },
    "allLevels": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Tous niveaux" } },
    "dressage": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Dressage" } },
    "jumping": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Saut d'obstacles" } },
    "cross": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cross" } },
    "ponyGames": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Pony Games" } },
    "western": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Western" } },
    "beginner": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Débutant" } },
    "intermediate": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Intermédiaire" } },
    "advanced": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Avancé" } },
    "competition": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Compétition" } },
    "experience": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "ans d'expérience" } },
    "viewProfile": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Voir le profil" } },
    "noTeachers": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Aucun instructeur trouvé pour ces critères" } }
  },
  "common": {
    "welcome": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Bienvenue" } },
    "loading": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Chargement..." } },
    "save": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Enregistrer" } },
    "cancel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Annuler" } },
    "delete": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Supprimer" } },
    "edit": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Modifier" } },
    "back": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Retour" } },
    "next": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Suivant" } },
    "previous": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Précédent" } },
    "search": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Rechercher" } },
    "filter": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Filtrer" } },
    "sort": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Trier" } },
    "actions": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Actions" } }
  },
  "footer": {
    "description": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "La plateforme de référence pour réserver vos cours d'équitation avec des instructeurs certifiés." } },
    "quickLinks": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Liens rapides" } },
    "support": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Support" } },
    "legal": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Légal" } },
    "contact": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Contact" } },
    "about": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "À propos" } },
    "terms": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Conditions d'utilisation" } },
    "privacy": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Politique de confidentialité" } }
  },
  "home": {
    "hero": {
      "tagline": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Trouvez votre instructeur équestre idéal et réservez vos cours d'équitation" } },
      "ctaStart": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Commencer l'aventure" } },
      "ctaDiscover": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Découvrir les coaches" } }
    },
    "features": {
      "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3, "v": "Pourquoi choisir " }, { "t": 4, "k": "platform" }, { "t": 3, "v": " ?" }] } },
      "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "La plateforme de référence pour l'équitation moderne" } },
      "items": {
        "certified": {
          "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Instructeurs Certifiés" } },
          "desc": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Tous nos instructeurs sont diplômés d'État et possèdent une expérience reconnue en équitation" } }
        },
        "flexible": {
          "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Réservation Flexible" } },
          "desc": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Réservez vos cours selon vos disponibilités, en carrière, en manège ou en extérieur" } }
        },
        "safety": {
          "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Sécurité Garantie" } },
          "desc": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Équipements certifiés, chevaux bien dressés et encadrement professionnel pour votre sécurité" } }
        }
      }
    },
    "stats": {
      "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Notre Communauté Équestre" } },
      "coachesLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Instructeurs Certifiés" } },
      "studentsLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cavaliers Satisfaits" } },
      "lessonsLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cours Dispensés" } },
      "locationsLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Centres Équestres" } }
    },
    "cta": {
      "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Prêt à Galoper vers l'Excellence ?" } },
      "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Rejoignez notre communauté de passionnés d'équitation et découvrez le plaisir d'apprendre avec les meilleurs instructeurs" } },
      "ctaSignup": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "S'inscrire Gratuitement" } },
      "ctaAbout": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "En Savoir Plus" } }
    }
  }
};
const resource$d = {
  "nav": {
    "dashboard": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Dashboard" } },
    "teacherSpace": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Teacher Space" } },
    "profile": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "My Profile" } },
    "admin": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Administration" } },
    "login": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Sign In" } },
    "register": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Sign Up" } },
    "logout": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Sign Out" } }
  },
  "auth": {
    "login": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Login" } },
    "email": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Email address" } },
    "password": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Password" } },
    "rememberMe": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Remember me" } },
    "forgotPassword": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Forgot password?" } },
    "noAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Don't have an account?" } },
    "createAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Create account" } },
    "name": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Full name" } },
    "confirmPassword": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Confirm password" } },
    "alreadyAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Already have an account?" } },
    "loginHere": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Sign in here" } }
  },
  "loginPage": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Sign in to your account" } },
    "or": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Or" } },
    "createAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "create a new account" } },
    "loggingIn": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Signing in..." } }
  },
  "registerPage": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Create an account" } },
    "or": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Or" } },
    "login": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "sign in to your existing account" } },
    "terms": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "I accept the" } },
    "termsLink": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "terms of use" } },
    "creatingAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Creating account..." } }
  },
  "dashboard": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3, "v": "Welcome, " }, { "t": 4, "k": "name" }] } },
    "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Here is your personal dashboard" } },
    "upcomingLessons": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Upcoming lessons" } },
    "completedLessons": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Completed lessons" } },
    "totalHours": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Total hours" } },
    "quickActions": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Quick actions" } },
    "viewAll": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "View all" } },
    "noLessons": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "No lessons scheduled" } },
    "bookLesson": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Book a lesson" } },
    "browseTeachers": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Browse teachers" } },
    "with": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "with" } },
    "confirmed": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Confirmed" } }
  },
  "teachers": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Our Equestrian Instructors" } },
    "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Discover our team of passionate and experienced instructors" } },
    "searchPlaceholder": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Search for an instructor..." } },
    "allDisciplines": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "All disciplines" } },
    "allLevels": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "All levels" } },
    "dressage": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Dressage" } },
    "jumping": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Show jumping" } },
    "cross": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cross country" } },
    "ponyGames": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Pony Games" } },
    "western": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Western" } },
    "beginner": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Beginner" } },
    "intermediate": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Intermediate" } },
    "advanced": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Advanced" } },
    "competition": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Competition" } },
    "experience": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "years of experience" } },
    "viewProfile": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "View profile" } },
    "noTeachers": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "No instructors found for these criteria" } }
  },
  "common": {
    "welcome": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Welcome" } },
    "loading": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Loading..." } },
    "save": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Save" } },
    "cancel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cancel" } },
    "delete": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Delete" } },
    "edit": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Edit" } },
    "back": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Back" } },
    "next": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Next" } },
    "previous": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Previous" } },
    "search": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Search" } },
    "filter": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Filter" } },
    "sort": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Sort" } },
    "actions": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Actions" } }
  },
  "footer": {
    "description": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "The reference platform for booking your riding lessons with certified instructors." } },
    "quickLinks": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Quick links" } },
    "support": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Support" } },
    "legal": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Legal" } },
    "contact": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Contact" } },
    "about": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "About" } },
    "terms": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Terms of use" } },
    "privacy": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Privacy policy" } }
  },
  "home": {
    "hero": {
      "tagline": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Find your ideal equestrian instructor and book your riding lessons" } },
      "ctaStart": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Start your journey" } },
      "ctaDiscover": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Discover coaches" } }
    },
    "features": {
      "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3, "v": "Why choose " }, { "t": 4, "k": "platform" }, { "t": 3, "v": "?" }] } },
      "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "The reference platform for modern horse riding" } },
      "items": {
        "certified": {
          "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Certified Instructors" } },
          "desc": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "All our instructors are certified and have proven experience in horse riding" } }
        },
        "flexible": {
          "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Flexible Booking" } },
          "desc": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Book lessons according to your availability, in arena, indoor school or outdoors" } }
        },
        "safety": {
          "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Safety Guaranteed" } },
          "desc": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Certified equipment, well-trained horses and professional supervision for your safety" } }
        }
      }
    },
    "stats": {
      "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Our Equestrian Community" } },
      "coachesLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Certified Instructors" } },
      "studentsLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Satisfied Riders" } },
      "lessonsLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Lessons Taught" } },
      "locationsLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Equestrian Centers" } }
    },
    "cta": {
      "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Ready to Gallop toward Excellence?" } },
      "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Join our community of equestrian enthusiasts and enjoy learning with the best instructors" } },
      "ctaSignup": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Sign up for free" } },
      "ctaAbout": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Learn more" } }
    }
  }
};
const resource$c = {
  "nav": {
    "dashboard": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Dashboard" } },
    "teacherSpace": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Leraar Ruimte" } },
    "profile": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Mijn Profiel" } },
    "admin": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Administratie" } },
    "login": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Inloggen" } },
    "register": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Registreren" } },
    "logout": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Uitloggen" } }
  },
  "auth": {
    "login": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Aanmelden" } },
    "email": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "E-mailadres" } },
    "password": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Wachtwoord" } },
    "rememberMe": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Onthoud mij" } },
    "forgotPassword": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Wachtwoord vergeten?" } },
    "noAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Nog geen account?" } },
    "createAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Account aanmaken" } },
    "name": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Volledige naam" } },
    "confirmPassword": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Bevestig wachtwoord" } },
    "alreadyAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Al een account?" } },
    "loginHere": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Log hier in" } }
  },
  "loginPage": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Inloggen op uw account" } },
    "or": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Of" } },
    "createAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "maak een nieuw account aan" } },
    "loggingIn": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Inloggen..." } }
  },
  "registerPage": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Een account aanmaken" } },
    "or": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Of" } },
    "login": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "log in op uw bestaande account" } },
    "terms": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Ik accepteer de" } },
    "termsLink": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "gebruiksvoorwaarden" } },
    "creatingAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Account aanmaken..." } }
  },
  "dashboard": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3, "v": "Welkom, " }, { "t": 4, "k": "name" }] } },
    "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Hier is uw persoonlijke dashboard" } },
    "upcomingLessons": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Komende lessen" } },
    "completedLessons": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Voltooide lessen" } },
    "totalHours": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Totaal uren" } },
    "quickActions": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Snelle acties" } },
    "viewAll": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Bekijk alles" } },
    "noLessons": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Geen lessen gepland" } },
    "bookLesson": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Boek een les" } },
    "browseTeachers": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Bekijk instructeurs" } },
    "with": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "met" } },
    "confirmed": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Bevestigd" } }
  },
  "teachers": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Onze Ruiterinstructeurs" } },
    "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Ontdek ons team van gepassioneerde en ervaren instructeurs" } },
    "searchPlaceholder": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Zoek een instructeur..." } },
    "allDisciplines": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Alle disciplines" } },
    "allLevels": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Alle niveaus" } },
    "dressage": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Dressuur" } },
    "jumping": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Springen" } },
    "cross": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cross country" } },
    "ponyGames": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Pony Games" } },
    "western": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Western" } },
    "beginner": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Beginner" } },
    "intermediate": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Gemiddeld" } },
    "advanced": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Gevorderd" } },
    "competition": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Competitie" } },
    "experience": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "jaar ervaring" } },
    "viewProfile": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Bekijk profiel" } },
    "noTeachers": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Geen instructeurs gevonden voor deze criteria" } }
  },
  "common": {
    "welcome": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Welkom" } },
    "loading": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Laden..." } },
    "save": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Opslaan" } },
    "cancel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Annuleren" } },
    "delete": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Verwijderen" } },
    "edit": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Bewerken" } },
    "back": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Terug" } },
    "next": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Volgende" } },
    "previous": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Vorige" } },
    "search": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Zoeken" } },
    "filter": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Filteren" } },
    "sort": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Sorteren" } },
    "actions": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Acties" } }
  },
  "footer": {
    "description": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Het referentieplatform voor het boeken van je rijlessen met gecertificeerde instructeurs." } },
    "quickLinks": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Snelle links" } },
    "support": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Ondersteuning" } },
    "legal": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Juridisch" } },
    "contact": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Contact" } },
    "about": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Over ons" } },
    "terms": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Gebruiksvoorwaarden" } },
    "privacy": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Privacybeleid" } }
  },
  "home": {
    "hero": {
      "tagline": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Vind je ideale rijinstructeur en boek je rijlessen" } },
      "ctaStart": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Begin je avontuur" } },
      "ctaDiscover": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Ontdek coaches" } }
    },
    "features": {
      "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3, "v": "Waarom kiezen voor " }, { "t": 4, "k": "platform" }, { "t": 3, "v": "?" }] } },
      "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Het referentieplatform voor moderne paardensport" } },
      "items": {
        "certified": {
          "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Gecertificeerde Instructeurs" } },
          "desc": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Al onze instructeurs zijn gecertificeerd en hebben bewezen ervaring in de paardensport" } }
        },
        "flexible": {
          "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Flexibel Boeken" } },
          "desc": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Boek lessen volgens je beschikbaarheid, in de buitenbak, binnenbak of buiten" } }
        },
        "safety": {
          "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Gegarandeerde Veiligheid" } },
          "desc": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Gecertificeerde uitrusting, goed getrainde paarden en professionele begeleiding voor je veiligheid" } }
        }
      }
    },
    "stats": {
      "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Onze Ruitergemeenschap" } },
      "coachesLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Gecertificeerde Instructeurs" } },
      "studentsLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Tevreden ruiters" } },
      "lessonsLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Gegeven lessen" } },
      "locationsLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Ruitersportcentra" } }
    },
    "cta": {
      "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Klaar om naar excellentie te galopperen?" } },
      "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Word lid van onze gemeenschap van paardenliefhebbers en leer met de beste instructeurs" } },
      "ctaSignup": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Gratis inschrijven" } },
      "ctaAbout": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Meer informatie" } }
    }
  }
};
const resource$b = {
  "nav": {
    "dashboard": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Dashboard" } },
    "teacherSpace": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Lehrer Bereich" } },
    "profile": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Mein Profil" } },
    "admin": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Administration" } },
    "login": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Anmelden" } },
    "register": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Registrieren" } },
    "logout": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Abmelden" } }
  },
  "auth": {
    "login": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Anmeldung" } },
    "email": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "E-Mail-Adresse" } },
    "password": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Passwort" } },
    "rememberMe": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Angemeldet bleiben" } },
    "forgotPassword": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Passwort vergessen?" } },
    "noAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Noch kein Konto?" } },
    "createAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Konto erstellen" } },
    "name": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Vollständiger Name" } },
    "confirmPassword": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Passwort bestätigen" } },
    "alreadyAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Bereits ein Konto?" } },
    "loginHere": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Hier anmelden" } }
  },
  "loginPage": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Bei Ihrem Konto anmelden" } },
    "or": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Oder" } },
    "createAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "ein neues Konto erstellen" } },
    "loggingIn": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Anmelden..." } }
  },
  "registerPage": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Ein Konto erstellen" } },
    "or": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Oder" } },
    "login": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "bei Ihrem bestehenden Konto anmelden" } },
    "terms": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Ich akzeptiere die" } },
    "termsLink": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Nutzungsbedingungen" } },
    "creatingAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Konto wird erstellt..." } }
  },
  "dashboard": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3, "v": "Willkommen, " }, { "t": 4, "k": "name" }] } },
    "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Hier ist Ihr persönliches Dashboard" } },
    "upcomingLessons": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Kommende Lektionen" } },
    "completedLessons": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Abgeschlossene Lektionen" } },
    "totalHours": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Gesamtstunden" } },
    "quickActions": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Schnelle Aktionen" } },
    "viewAll": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Alle anzeigen" } },
    "noLessons": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Keine Lektionen geplant" } },
    "bookLesson": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Eine Lektion buchen" } },
    "browseTeachers": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Lehrer durchsuchen" } },
    "with": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "mit" } },
    "confirmed": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Bestätigt" } }
  },
  "teachers": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Unsere Reitinstruktoren" } },
    "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Entdecken Sie unser Team aus leidenschaftlichen und erfahrenen Instruktoren" } },
    "searchPlaceholder": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Einen Instruktor suchen..." } },
    "allDisciplines": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Alle Disziplinen" } },
    "allLevels": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Alle Stufen" } },
    "dressage": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Dressur" } },
    "jumping": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Springen" } },
    "cross": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Geländereiten" } },
    "ponyGames": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Pony Games" } },
    "western": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Western" } },
    "beginner": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Anfänger" } },
    "intermediate": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Mittelstufe" } },
    "advanced": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Fortgeschritten" } },
    "competition": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Wettkampf" } },
    "experience": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Jahre Erfahrung" } },
    "viewProfile": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Profil ansehen" } },
    "noTeachers": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Keine Instruktoren für diese Kriterien gefunden" } }
  },
  "common": {
    "welcome": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Willkommen" } },
    "loading": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Laden..." } },
    "save": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Speichern" } },
    "cancel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Abbrechen" } },
    "delete": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Löschen" } },
    "edit": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Bearbeiten" } },
    "back": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Zurück" } },
    "next": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Weiter" } },
    "previous": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Vorherige" } },
    "search": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Suchen" } },
    "filter": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Filtern" } },
    "sort": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Sortieren" } },
    "actions": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Aktionen" } }
  },
  "footer": {
    "description": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Die Referenzplattform für die Buchung Ihrer Reitstunden mit zertifizierten Ausbildern." } },
    "quickLinks": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Schnelle Links" } },
    "support": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Support" } },
    "legal": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Rechtliches" } },
    "contact": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Kontakt" } },
    "about": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Über uns" } },
    "terms": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Nutzungsbedingungen" } },
    "privacy": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Datenschutzrichtlinie" } }
  },
  "home": {
    "hero": {
      "tagline": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Finden Sie Ihren idealen Reitlehrer und buchen Sie Ihre Reitstunden" } },
      "ctaStart": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Jetzt starten" } },
      "ctaDiscover": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Coaches entdecken" } }
    },
    "features": {
      "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3, "v": "Warum " }, { "t": 4, "k": "platform" }, { "t": 3, "v": " wählen?" }] } },
      "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Die Referenzplattform für modernen Reitsport" } },
      "items": {
        "certified": {
          "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Zertifizierte Ausbilder" } },
          "desc": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Alle unsere Ausbilder sind zertifiziert und besitzen nachgewiesene Erfahrung im Reitsport" } }
        },
        "flexible": {
          "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Flexible Buchung" } },
          "desc": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Buchen Sie Unterricht nach Ihrer Verfügbarkeit, in der Halle, auf dem Platz oder im Freien" } }
        },
        "safety": {
          "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Garantierte Sicherheit" } },
          "desc": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Zertifizierte Ausrüstung, gut ausgebildete Pferde und professionelle Betreuung für Ihre Sicherheit" } }
        }
      }
    },
    "stats": {
      "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Unsere Reitgemeinschaft" } },
      "coachesLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Zertifizierte Ausbilder" } },
      "studentsLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Zufriedene Reiter" } },
      "lessonsLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Erteilte Stunden" } },
      "locationsLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Reitzentren" } }
    },
    "cta": {
      "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Bereit zum Galopp Richtung Exzellenz?" } },
      "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Treten Sie unserer Gemeinschaft von Reitsportbegeisterten bei und lernen Sie mit den besten Ausbildern" } },
      "ctaSignup": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Kostenlos registrieren" } },
      "ctaAbout": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Mehr erfahren" } }
    }
  }
};
const resource$a = {
  "nav": {
    "dashboard": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Dashboard" } },
    "teacherSpace": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Area Insegnante" } },
    "profile": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Il Mio Profilo" } },
    "admin": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Amministrazione" } },
    "login": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Accedi" } },
    "register": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Registrati" } },
    "logout": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Esci" } }
  },
  "auth": {
    "login": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Accesso" } },
    "email": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Indirizzo email" } },
    "password": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Password" } },
    "rememberMe": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Ricordami" } },
    "forgotPassword": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Password dimenticata?" } },
    "noAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Non hai un account?" } },
    "createAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Crea account" } },
    "name": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Nome completo" } },
    "confirmPassword": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Conferma password" } },
    "alreadyAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Hai già un account?" } },
    "loginHere": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Accedi qui" } }
  },
  "common": {
    "welcome": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Benvenuto" } },
    "loading": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Caricamento..." } },
    "save": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Salva" } },
    "cancel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Annulla" } },
    "delete": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Elimina" } },
    "edit": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Modifica" } },
    "back": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Indietro" } },
    "next": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Successivo" } },
    "previous": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Precedente" } },
    "search": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cerca" } },
    "filter": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Filtra" } },
    "sort": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Ordina" } },
    "actions": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Azioni" } }
  },
  "footer": {
    "description": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "La piattaforma di riferimento per prenotare le tue lezioni di equitazione con istruttori certificati." } },
    "quickLinks": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Link rapidi" } },
    "support": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Supporto" } },
    "legal": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Legale" } },
    "contact": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Contatto" } },
    "about": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Chi siamo" } },
    "terms": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Termini di utilizzo" } },
    "privacy": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Politica sulla privacy" } }
  },
  "loginPage": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Accedi al tuo account" } },
    "or": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Oppure" } },
    "createAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "crea un nuovo account" } },
    "loggingIn": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Accesso in corso..." } }
  },
  "registerPage": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Crea un account" } },
    "or": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Oppure" } },
    "login": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "accedi al tuo account esistente" } },
    "terms": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Accetto i" } },
    "termsLink": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "termini di utilizzo" } },
    "creatingAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Creazione dell'account..." } }
  },
  "dashboard": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3, "v": "Benvenuto, " }, { "t": 4, "k": "name" }] } },
    "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Ecco il tuo pannello personale" } },
    "upcomingLessons": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Prossime lezioni" } },
    "completedLessons": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Lezioni completate" } },
    "totalHours": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Ore totali" } },
    "quickActions": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Azioni rapide" } },
    "viewAll": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Vedi tutto" } },
    "noLessons": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Nessuna lezione programmata" } },
    "bookLesson": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Prenota una lezione" } },
    "browseTeachers": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Sfoglia gli insegnanti" } },
    "with": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "con" } },
    "confirmed": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Confermato" } }
  },
  "teachers": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "I Nostri Istruttori di Equitazione" } },
    "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Scopri il nostro team di istruttori appassionati ed esperti" } },
    "searchPlaceholder": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cerca un istruttore..." } },
    "allDisciplines": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Tutte le discipline" } },
    "allLevels": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Tutti i livelli" } },
    "dressage": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Dressage" } },
    "jumping": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Salto ostacoli" } },
    "cross": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cross country" } },
    "ponyGames": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Pony Games" } },
    "western": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Western" } },
    "beginner": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Principiante" } },
    "intermediate": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Intermedio" } },
    "advanced": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Avanzato" } },
    "competition": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Competizione" } },
    "experience": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "anni di esperienza" } },
    "viewProfile": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Vedi profilo" } },
    "noTeachers": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Nessun istruttore trovato per questi criteri" } }
  },
  "home": {
    "hero": {
      "tagline": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Trova il tuo istruttore di equitazione ideale e prenota le tue lezioni" } },
      "ctaStart": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Inizia il tuo percorso" } },
      "ctaDiscover": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Scopri i coach" } }
    },
    "features": {
      "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3, "v": "Perché scegliere " }, { "t": 4, "k": "platform" }, { "t": 3, "v": "?" }] } },
      "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "La piattaforma di riferimento per l'equitazione moderna" } },
      "items": {
        "certified": {
          "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Istruttori Certificati" } },
          "desc": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Tutti i nostri istruttori sono certificati e hanno comprovata esperienza nell'equitazione" } }
        },
        "flexible": {
          "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Prenotazione Flessibile" } },
          "desc": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Prenota le lezioni secondo la tua disponibilità, in campo, in maneggio o all'aperto" } }
        },
        "safety": {
          "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Sicurezza Garantita" } },
          "desc": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Attrezzature certificate, cavalli ben addestrati e supervisione professionale per la tua sicurezza" } }
        }
      }
    },
    "stats": {
      "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "La Nostra Comunità Equestre" } },
      "coachesLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Istruttori Certificati" } },
      "studentsLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cavalieri Soddisfatti" } },
      "lessonsLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Lezioni Erogate" } },
      "locationsLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Centri Ippici" } }
    },
    "cta": {
      "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Pronto a Galoppare verso l'Eccellenza?" } },
      "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Unisciti alla nostra comunità di appassionati di equitazione e scopri il piacere di imparare con i migliori istruttori" } },
      "ctaSignup": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Iscriviti Gratis" } },
      "ctaAbout": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Scopri di più" } }
    }
  }
};
const resource$9 = {
  "nav": {
    "dashboard": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Panel" } },
    "teacherSpace": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Área Profesor" } },
    "profile": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Mi Perfil" } },
    "admin": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Administración" } },
    "login": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Iniciar sesión" } },
    "register": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Registrarse" } },
    "logout": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cerrar sesión" } }
  },
  "auth": {
    "login": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Iniciar sesión" } },
    "email": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Dirección de correo" } },
    "password": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Contraseña" } },
    "rememberMe": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Recordarme" } },
    "forgotPassword": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "¿Olvidaste tu contraseña?" } },
    "noAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "¿No tienes cuenta?" } },
    "createAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Crear cuenta" } },
    "name": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Nombre completo" } },
    "confirmPassword": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Confirmar contraseña" } },
    "alreadyAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "¿Ya tienes cuenta?" } },
    "loginHere": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Inicia sesión aquí" } }
  },
  "common": {
    "welcome": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Bienvenido" } },
    "loading": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cargando..." } },
    "save": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Guardar" } },
    "cancel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cancelar" } },
    "delete": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Eliminar" } },
    "edit": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Editar" } },
    "back": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Atrás" } },
    "next": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Siguiente" } },
    "previous": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Anterior" } },
    "search": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Buscar" } },
    "filter": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Filtrar" } },
    "sort": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Ordenar" } },
    "actions": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Acciones" } }
  },
  "footer": {
    "description": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "La plataforma de referencia para reservar tus clases de equitación con instructores certificados." } },
    "quickLinks": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Enlaces rápidos" } },
    "support": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Soporte" } },
    "legal": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Legal" } },
    "contact": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Contacto" } },
    "about": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Acerca de" } },
    "terms": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Términos de uso" } },
    "privacy": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Política de privacidad" } }
  },
  "loginPage": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Inicia sesión en tu cuenta" } },
    "or": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "O" } },
    "createAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "crea una nueva cuenta" } },
    "loggingIn": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Iniciando sesión..." } }
  },
  "registerPage": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Crea una cuenta" } },
    "or": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "O" } },
    "login": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "inicia sesión en tu cuenta existente" } },
    "terms": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Acepto los" } },
    "termsLink": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "términos de uso" } },
    "creatingAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Creando cuenta..." } }
  },
  "dashboard": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3, "v": "Bienvenido, " }, { "t": 4, "k": "name" }] } },
    "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Aquí está tu panel personal" } },
    "upcomingLessons": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Próximas clases" } },
    "completedLessons": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Clases completadas" } },
    "totalHours": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Horas totales" } },
    "quickActions": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Acciones rápidas" } },
    "viewAll": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Ver todo" } },
    "noLessons": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "No hay clases programadas" } },
    "bookLesson": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Reservar una clase" } },
    "browseTeachers": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Ver instructores" } },
    "with": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "con" } },
    "confirmed": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Confirmado" } }
  },
  "teachers": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Nuestros Instructores Ecuestres" } },
    "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Descubre nuestro equipo de instructores apasionados y con experiencia" } },
    "searchPlaceholder": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Buscar un instructor..." } },
    "allDisciplines": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Todas las disciplinas" } },
    "allLevels": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Todos los niveles" } },
    "dressage": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Doma" } },
    "jumping": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Saltos" } },
    "cross": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Campo a través" } },
    "ponyGames": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Pony Games" } },
    "western": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Western" } },
    "beginner": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Principiante" } },
    "intermediate": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Intermedio" } },
    "advanced": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Avanzado" } },
    "competition": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Competición" } },
    "experience": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "años de experiencia" } },
    "viewProfile": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Ver perfil" } },
    "noTeachers": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "No se encontraron instructores para estos criterios" } }
  },
  "home": {
    "hero": {
      "tagline": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Encuentra a tu instructor ecuestre ideal y reserva tus clases de equitación" } },
      "ctaStart": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Comienza tu aventura" } },
      "ctaDiscover": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Descubrir coaches" } }
    },
    "features": {
      "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3, "v": "¿Por qué elegir " }, { "t": 4, "k": "platform" }, { "t": 3, "v": "?" }] } },
      "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "La plataforma de referencia para la equitación moderna" } },
      "items": {
        "certified": {
          "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Instructores Certificados" } },
          "desc": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Todos nuestros instructores están certificados y cuentan con experiencia demostrada en equitación" } }
        },
        "flexible": {
          "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Reserva Flexible" } },
          "desc": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Reserva tus clases según tu disponibilidad, en pista, picadero o al aire libre" } }
        },
        "safety": {
          "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Seguridad Garantizada" } },
          "desc": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Equipamiento certificado, caballos bien entrenados y supervisión profesional para tu seguridad" } }
        }
      }
    },
    "stats": {
      "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Nuestra Comunidad Ecuestre" } },
      "coachesLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Instructores Certificados" } },
      "studentsLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Jinetes Satisfechos" } },
      "lessonsLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Clases Impartidas" } },
      "locationsLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Centros Ecuestres" } }
    },
    "cta": {
      "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "¿Listo para galopar hacia la excelencia?" } },
      "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Únete a nuestra comunidad de apasionados de la equitación y disfruta aprendiendo con los mejores instructores" } },
      "ctaSignup": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Regístrate gratis" } },
      "ctaAbout": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Saber más" } }
    }
  }
};
const resource$8 = {
  "nav": {
    "dashboard": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Painel" } },
    "teacherSpace": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Área Professor" } },
    "profile": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Meu Perfil" } },
    "admin": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Administração" } },
    "login": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Entrar" } },
    "register": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Registar" } },
    "logout": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Sair" } }
  },
  "auth": {
    "login": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Login" } },
    "email": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Endereço de e-mail" } },
    "password": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Senha" } },
    "rememberMe": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Lembrar de mim" } },
    "forgotPassword": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Esqueceu a senha?" } },
    "noAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Não tem conta?" } },
    "createAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Criar conta" } },
    "name": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Nome completo" } },
    "confirmPassword": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Confirmar senha" } },
    "alreadyAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Já tem conta?" } },
    "loginHere": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Entre aqui" } }
  },
  "common": {
    "welcome": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Bem-vindo" } },
    "loading": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Carregando..." } },
    "save": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Salvar" } },
    "cancel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cancelar" } },
    "delete": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Excluir" } },
    "edit": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Editar" } },
    "back": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Voltar" } },
    "next": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Próximo" } },
    "previous": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Anterior" } },
    "search": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Pesquisar" } },
    "filter": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Filtrar" } },
    "sort": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Ordenar" } },
    "actions": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Ações" } }
  },
  "footer": {
    "description": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "A plataforma de referência para reservar suas aulas de equitação com instrutores certificados." } },
    "quickLinks": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Links rápidos" } },
    "support": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Suporte" } },
    "legal": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Legal" } },
    "contact": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Contato" } },
    "about": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Sobre" } },
    "terms": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Termos de uso" } },
    "privacy": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Política de privacidade" } }
  },
  "loginPage": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Inicie sessão na sua conta" } },
    "or": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Ou" } },
    "createAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "crie uma nova conta" } },
    "loggingIn": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "A iniciar sessão..." } }
  },
  "registerPage": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Criar uma conta" } },
    "or": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Ou" } },
    "login": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "inicie sessão na sua conta existente" } },
    "terms": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Aceito os" } },
    "termsLink": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "termos de uso" } },
    "creatingAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "A criar conta..." } }
  },
  "dashboard": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3, "v": "Bem-vindo, " }, { "t": 4, "k": "name" }] } },
    "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Aqui está o seu painel pessoal" } },
    "upcomingLessons": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Próximas aulas" } },
    "completedLessons": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Aulas concluídas" } },
    "totalHours": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Total de horas" } },
    "quickActions": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Ações rápidas" } },
    "viewAll": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Ver tudo" } },
    "noLessons": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Nenhuma aula agendada" } },
    "bookLesson": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Reservar uma aula" } },
    "browseTeachers": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Ver instrutores" } },
    "with": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "com" } },
    "confirmed": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Confirmado" } }
  },
  "teachers": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Nossos Instrutores de Equitação" } },
    "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Descubra a nossa equipa de instrutores apaixonados e experientes" } },
    "searchPlaceholder": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Procurar um instrutor..." } },
    "allDisciplines": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Todas as disciplinas" } },
    "allLevels": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Todos os níveis" } },
    "dressage": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Dressage" } },
    "jumping": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Saltos" } },
    "cross": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cross country" } },
    "ponyGames": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Pony Games" } },
    "western": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Western" } },
    "beginner": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Principiante" } },
    "intermediate": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Intermédio" } },
    "advanced": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Avançado" } },
    "competition": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Competição" } },
    "experience": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "anos de experiência" } },
    "viewProfile": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Ver perfil" } },
    "noTeachers": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Nenhum instrutor encontrado para estes critérios" } }
  },
  "home": {
    "hero": {
      "tagline": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Encontre o seu instrutor equestre ideal e reserve as suas aulas de equitação" } },
      "ctaStart": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Comece a sua aventura" } },
      "ctaDiscover": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Descobrir coaches" } }
    },
    "features": {
      "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3, "v": "Porquê escolher " }, { "t": 4, "k": "platform" }, { "t": 3, "v": "?" }] } },
      "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "A plataforma de referência para a equitação moderna" } },
      "items": {
        "certified": {
          "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Instrutores Certificados" } },
          "desc": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Todos os nossos instrutores são certificados e têm experiência comprovada em equitação" } }
        },
        "flexible": {
          "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Reserva Flexível" } },
          "desc": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Reserve aulas conforme a sua disponibilidade, em pista, picadeiro ou ao ar livre" } }
        },
        "safety": {
          "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Segurança Garantida" } },
          "desc": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Equipamento certificado, cavalos bem treinados e supervisão profissional para a sua segurança" } }
        }
      }
    },
    "stats": {
      "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "A Nossa Comunidade Equestre" } },
      "coachesLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Instrutores Certificados" } },
      "studentsLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cavaleiros Satisfeitos" } },
      "lessonsLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Aulas Ministradas" } },
      "locationsLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Centros Equestres" } }
    },
    "cta": {
      "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Pronto para galopar rumo à excelência?" } },
      "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Junte-se à nossa comunidade de apaixonados pela equitação e aprenda com os melhores instrutores" } },
      "ctaSignup": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Registar gratuitamente" } },
      "ctaAbout": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Saber mais" } }
    }
  }
};
const resource$7 = {
  "nav": {
    "dashboard": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Irányítópult" } },
    "teacherSpace": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Tanár Terület" } },
    "profile": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Profilom" } },
    "admin": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Adminisztráció" } },
    "login": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Bejelentkezés" } },
    "register": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Regisztráció" } },
    "logout": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Kijelentkezés" } }
  },
  "auth": {
    "login": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Bejelentkezés" } },
    "email": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "E-mail cím" } },
    "password": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Jelszó" } },
    "rememberMe": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Emlékezz rám" } },
    "forgotPassword": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Elfelejtett jelszó?" } },
    "noAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Nincs még fiókja?" } },
    "createAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Fiók létrehozása" } },
    "name": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Teljes név" } },
    "confirmPassword": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Jelszó megerősítése" } },
    "alreadyAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Már van fiókja?" } },
    "loginHere": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Jelentkezzen be itt" } }
  },
  "common": {
    "welcome": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Üdvözöljük" } },
    "loading": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Betöltés..." } },
    "save": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Mentés" } },
    "cancel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Mégse" } },
    "delete": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Törlés" } },
    "edit": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Szerkesztés" } },
    "back": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Vissza" } },
    "next": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Következő" } },
    "previous": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Előző" } },
    "search": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Keresés" } },
    "filter": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Szűrés" } },
    "sort": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Rendezés" } },
    "actions": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Műveletek" } }
  },
  "footer": {
    "description": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "A referencia platform lovaglási óráinak foglalásához tanúsított oktatókkal." } },
    "quickLinks": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Gyors linkek" } },
    "support": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Támogatás" } },
    "legal": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Jogi" } },
    "contact": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Kapcsolat" } },
    "about": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Rólunk" } },
    "terms": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Használati feltételek" } },
    "privacy": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Adatvédelmi irányelvek" } }
  },
  "loginPage": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Connexion à votre compte" } },
    "or": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Ou" } },
    "createAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "créez un nouveau compte" } },
    "loggingIn": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Connexion en cours..." } }
  },
  "registerPage": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Créer un compte" } },
    "or": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Ou" } },
    "login": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "connectez-vous à votre compte existant" } },
    "terms": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "J'accepte les" } },
    "termsLink": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "conditions d'utilisation" } },
    "creatingAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Création du compte..." } }
  },
  "dashboard": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3, "v": "Bienvenue, " }, { "t": 4, "k": "name" }] } },
    "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Voici votre tableau de bord personnel" } },
    "upcomingLessons": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Prochains cours" } },
    "completedLessons": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cours terminés" } },
    "totalHours": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Heures totales" } },
    "quickActions": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Actions rapides" } },
    "viewAll": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Voir tous" } },
    "noLessons": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Aucun cours planifié" } },
    "bookLesson": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Réserver un cours" } },
    "browseTeachers": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Parcourir les enseignants" } },
    "with": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "avec" } },
    "confirmed": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Confirmé" } }
  },
  "teachers": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Nos Instructeurs Équestres" } },
    "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Découvrez notre équipe d'instructeurs passionnés et expérimentés" } },
    "searchPlaceholder": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Rechercher un instructeur..." } },
    "allDisciplines": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Toutes disciplines" } },
    "allLevels": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Tous niveaux" } },
    "dressage": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Dressage" } },
    "jumping": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Saut d'obstacles" } },
    "cross": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cross" } },
    "ponyGames": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Pony Games" } },
    "western": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Western" } },
    "beginner": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Débutant" } },
    "intermediate": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Intermédiaire" } },
    "advanced": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Avancé" } },
    "competition": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Compétition" } },
    "experience": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "ans d'expérience" } },
    "viewProfile": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Voir le profil" } },
    "noTeachers": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Aucun instructeur trouvé pour ces critères" } }
  },
  "home": {
    "hero": {
      "tagline": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Trouvez votre instructeur équestre idéal et réservez vos cours d'équitation" } },
      "ctaStart": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Commencer l'aventure" } },
      "ctaDiscover": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Découvrir les coaches" } }
    },
    "features": {
      "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3, "v": "Pourquoi choisir " }, { "t": 4, "k": "platform" }, { "t": 3, "v": " ?" }] } },
      "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "La plateforme de référence pour l'équitation moderne" } },
      "items": {
        "certified": {
          "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Instructeurs Certifiés" } },
          "desc": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Tous nos instructeurs sont diplômés d'État et possèdent une expérience reconnue en équitation" } }
        },
        "flexible": {
          "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Réservation Flexible" } },
          "desc": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Réservez vos cours selon vos disponibilités, en carrière, en manège ou en extérieur" } }
        },
        "safety": {
          "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Sécurité Garantie" } },
          "desc": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Équipements certifiés, chevaux bien dressés et encadrement professionnel pour votre sécurité" } }
        }
      }
    },
    "stats": {
      "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Notre Communauté Équestre" } },
      "coachesLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Instructeurs Certifiés" } },
      "studentsLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cavaliers Satisfaits" } },
      "lessonsLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cours Dispensés" } },
      "locationsLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Centres Équestres" } }
    },
    "cta": {
      "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Prêt à Galoper vers l'Excellence ?" } },
      "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Rejoignez notre communauté de passionnés d'équitation et découvrez le plaisir d'apprendre avec les meilleurs instructeurs" } },
      "ctaSignup": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "S'inscrire Gratuitement" } },
      "ctaAbout": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "En Savoir Plus" } }
    }
  }
};
const resource$6 = {
  "nav": {
    "dashboard": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Panel" } },
    "teacherSpace": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Obszar Nauczyciela" } },
    "profile": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Mój Profil" } },
    "admin": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Administracja" } },
    "login": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Zaloguj się" } },
    "register": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Zarejestruj się" } },
    "logout": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Wyloguj się" } }
  },
  "auth": {
    "login": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Logowanie" } },
    "email": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Adres e-mail" } },
    "password": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Hasło" } },
    "rememberMe": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Zapamiętaj mnie" } },
    "forgotPassword": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Zapomniałeś hasła?" } },
    "noAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Nie masz konta?" } },
    "createAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Utwórz konto" } },
    "name": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Pełne imię" } },
    "confirmPassword": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Potwierdź hasło" } },
    "alreadyAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Masz już konto?" } },
    "loginHere": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Zaloguj się tutaj" } }
  },
  "common": {
    "welcome": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Witamy" } },
    "loading": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Ładowanie..." } },
    "save": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Zapisz" } },
    "cancel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Anuluj" } },
    "delete": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Usuń" } },
    "edit": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Edytuj" } },
    "back": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Wstecz" } },
    "next": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Następny" } },
    "previous": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Poprzedni" } },
    "search": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Szukaj" } },
    "filter": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Filtruj" } },
    "sort": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Sortuj" } },
    "actions": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Akcje" } }
  },
  "footer": {
    "description": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Platforma referencyjna do rezerwacji lekcji jazdy konnej z certyfikowanymi instruktorami." } },
    "quickLinks": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Szybkie linki" } },
    "support": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Wsparcie" } },
    "legal": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Prawne" } },
    "contact": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Kontakt" } },
    "about": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "O nas" } },
    "terms": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Warunki użytkowania" } },
    "privacy": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Polityka prywatności" } }
  },
  "loginPage": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Connexion à votre compte" } },
    "or": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Ou" } },
    "createAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "créez un nouveau compte" } },
    "loggingIn": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Connexion en cours..." } }
  },
  "registerPage": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Créer un compte" } },
    "or": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Ou" } },
    "login": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "connectez-vous à votre compte existant" } },
    "terms": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "J'accepte les" } },
    "termsLink": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "conditions d'utilisation" } },
    "creatingAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Création du compte..." } }
  },
  "dashboard": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3, "v": "Bienvenue, " }, { "t": 4, "k": "name" }] } },
    "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Voici votre tableau de bord personnel" } },
    "upcomingLessons": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Prochains cours" } },
    "completedLessons": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cours terminés" } },
    "totalHours": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Heures totales" } },
    "quickActions": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Actions rapides" } },
    "viewAll": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Voir tous" } },
    "noLessons": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Aucun cours planifié" } },
    "bookLesson": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Réserver un cours" } },
    "browseTeachers": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Parcourir les enseignants" } },
    "with": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "avec" } },
    "confirmed": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Confirmé" } }
  },
  "teachers": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Nos Instructeurs Équestres" } },
    "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Découvrez notre équipe d'instructeurs passionnés et expérimentés" } },
    "searchPlaceholder": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Rechercher un instructeur..." } },
    "allDisciplines": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Toutes disciplines" } },
    "allLevels": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Tous niveaux" } },
    "dressage": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Dressage" } },
    "jumping": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Saut d'obstacles" } },
    "cross": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cross" } },
    "ponyGames": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Pony Games" } },
    "western": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Western" } },
    "beginner": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Débutant" } },
    "intermediate": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Intermédiaire" } },
    "advanced": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Avancé" } },
    "competition": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Compétition" } },
    "experience": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "ans d'expérience" } },
    "viewProfile": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Voir le profil" } },
    "noTeachers": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Aucun instructeur trouvé pour ces critères" } }
  },
  "home": {
    "hero": {
      "tagline": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Trouvez votre instructeur équestre idéal et réservez vos cours d'équitation" } },
      "ctaStart": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Commencer l'aventure" } },
      "ctaDiscover": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Découvrir les coaches" } }
    },
    "features": {
      "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3, "v": "Pourquoi choisir " }, { "t": 4, "k": "platform" }, { "t": 3, "v": " ?" }] } },
      "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "La plateforme de référence pour l'équitation moderne" } },
      "items": {
        "certified": {
          "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Instructeurs Certifiés" } },
          "desc": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Tous nos instructeurs sont diplômés d'État et possèdent une expérience reconnue en équitation" } }
        },
        "flexible": {
          "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Réservation Flexible" } },
          "desc": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Réservez vos cours selon vos disponibilités, en carrière, en manège ou en extérieur" } }
        },
        "safety": {
          "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Sécurité Garantie" } },
          "desc": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Équipements certifiés, chevaux bien dressés et encadrement professionnel pour votre sécurité" } }
        }
      }
    },
    "stats": {
      "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Notre Communauté Équestre" } },
      "coachesLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Instructeurs Certifiés" } },
      "studentsLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cavaliers Satisfaits" } },
      "lessonsLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cours Dispensés" } },
      "locationsLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Centres Équestres" } }
    },
    "cta": {
      "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Prêt à Galoper vers l'Excellence ?" } },
      "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Rejoignez notre communauté de passionnés d'équitation et découvrez le plaisir d'apprendre avec les meilleurs instructeurs" } },
      "ctaSignup": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "S'inscrire Gratuitement" } },
      "ctaAbout": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "En Savoir Plus" } }
    }
  }
};
const resource$5 = {
  "nav": {
    "dashboard": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "仪表板" } },
    "teacherSpace": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "教师空间" } },
    "profile": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "我的资料" } },
    "admin": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "管理" } },
    "login": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "登录" } },
    "register": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "注册" } },
    "logout": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "退出" } }
  },
  "auth": {
    "login": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "登录" } },
    "email": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "邮箱地址" } },
    "password": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "密码" } },
    "rememberMe": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "记住我" } },
    "forgotPassword": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "忘记密码？" } },
    "noAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "还没有账户？" } },
    "createAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "创建账户" } },
    "name": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "全名" } },
    "confirmPassword": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "确认密码" } },
    "alreadyAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "已有账户？" } },
    "loginHere": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "在此登录" } }
  },
  "common": {
    "welcome": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "欢迎" } },
    "loading": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "加载中..." } },
    "save": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "保存" } },
    "cancel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "取消" } },
    "delete": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "删除" } },
    "edit": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "编辑" } },
    "back": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "返回" } },
    "next": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "下一个" } },
    "previous": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "上一个" } },
    "search": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "搜索" } },
    "filter": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "筛选" } },
    "sort": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "排序" } },
    "actions": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "操作" } }
  },
  "footer": {
    "description": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "与认证教练预订马术课程的参考平台。" } },
    "quickLinks": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "快速链接" } },
    "support": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "支持" } },
    "legal": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "法律" } },
    "contact": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "联系" } },
    "about": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "关于" } },
    "terms": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "使用条款" } },
    "privacy": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "隐私政策" } }
  },
  "loginPage": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Connexion à votre compte" } },
    "or": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Ou" } },
    "createAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "créez un nouveau compte" } },
    "loggingIn": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Connexion en cours..." } }
  },
  "registerPage": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Créer un compte" } },
    "or": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Ou" } },
    "login": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "connectez-vous à votre compte existant" } },
    "terms": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "J'accepte les" } },
    "termsLink": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "conditions d'utilisation" } },
    "creatingAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Création du compte..." } }
  },
  "dashboard": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3, "v": "Bienvenue, " }, { "t": 4, "k": "name" }] } },
    "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Voici votre tableau de bord personnel" } },
    "upcomingLessons": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Prochains cours" } },
    "completedLessons": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cours terminés" } },
    "totalHours": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Heures totales" } },
    "quickActions": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Actions rapides" } },
    "viewAll": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Voir tous" } },
    "noLessons": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Aucun cours planifié" } },
    "bookLesson": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Réserver un cours" } },
    "browseTeachers": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Parcourir les enseignants" } },
    "with": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "avec" } },
    "confirmed": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Confirmé" } }
  },
  "teachers": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Nos Instructeurs Équestres" } },
    "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Découvrez notre équipe d'instructeurs passionnés et expérimentés" } },
    "searchPlaceholder": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Rechercher un instructeur..." } },
    "allDisciplines": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Toutes disciplines" } },
    "allLevels": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Tous niveaux" } },
    "dressage": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Dressage" } },
    "jumping": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Saut d'obstacles" } },
    "cross": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cross" } },
    "ponyGames": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Pony Games" } },
    "western": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Western" } },
    "beginner": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Débutant" } },
    "intermediate": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Intermédiaire" } },
    "advanced": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Avancé" } },
    "competition": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Compétition" } },
    "experience": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "ans d'expérience" } },
    "viewProfile": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Voir le profil" } },
    "noTeachers": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Aucun instructeur trouvé pour ces critères" } }
  },
  "home": {
    "hero": {
      "tagline": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Trouvez votre instructeur équestre idéal et réservez vos cours d'équitation" } },
      "ctaStart": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Commencer l'aventure" } },
      "ctaDiscover": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Découvrir les coaches" } }
    },
    "features": {
      "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3, "v": "Pourquoi choisir " }, { "t": 4, "k": "platform" }, { "t": 3, "v": " ?" }] } },
      "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "La plateforme de référence pour l'équitation moderne" } },
      "items": {
        "certified": {
          "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Instructeurs Certifiés" } },
          "desc": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Tous nos instructeurs sont diplômés d'État et possèdent une expérience reconnue en équitation" } }
        },
        "flexible": {
          "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Réservation Flexible" } },
          "desc": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Réservez vos cours selon vos disponibilités, en carrière, en manège ou en extérieur" } }
        },
        "safety": {
          "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Sécurité Garantie" } },
          "desc": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Équipements certifiés, chevaux bien dressés et encadrement professionnel pour votre sécurité" } }
        }
      }
    },
    "stats": {
      "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Notre Communauté Équestre" } },
      "coachesLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Instructeurs Certifiés" } },
      "studentsLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cavaliers Satisfaits" } },
      "lessonsLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cours Dispensés" } },
      "locationsLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Centres Équestres" } }
    },
    "cta": {
      "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Prêt à Galoper vers l'Excellence ?" } },
      "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Rejoignez notre communauté de passionnés d'équitation et découvrez le plaisir d'apprendre avec les meilleurs instructeurs" } },
      "ctaSignup": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "S'inscrire Gratuitement" } },
      "ctaAbout": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "En Savoir Plus" } }
    }
  }
};
const resource$4 = {
  "nav": {
    "dashboard": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "ダッシュボード" } },
    "teacherSpace": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "講師エリア" } },
    "profile": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "マイプロフィール" } },
    "admin": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "管理" } },
    "login": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "ログイン" } },
    "register": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "登録" } },
    "logout": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "ログアウト" } }
  },
  "auth": {
    "login": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "ログイン" } },
    "email": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "メールアドレス" } },
    "password": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "パスワード" } },
    "rememberMe": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "ログイン状態を保持" } },
    "forgotPassword": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "パスワードを忘れた？" } },
    "noAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "アカウントをお持ちでない？" } },
    "createAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "アカウント作成" } },
    "name": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "氏名" } },
    "confirmPassword": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "パスワード確認" } },
    "alreadyAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "既にアカウントをお持ちですか？" } },
    "loginHere": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "こちらでログイン" } }
  },
  "common": {
    "welcome": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "ようこそ" } },
    "loading": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "読み込み中..." } },
    "save": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "保存" } },
    "cancel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "キャンセル" } },
    "delete": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "削除" } },
    "edit": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "編集" } },
    "back": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "戻る" } },
    "next": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "次へ" } },
    "previous": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "前へ" } },
    "search": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "検索" } },
    "filter": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "フィルター" } },
    "sort": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "並び替え" } },
    "actions": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "アクション" } }
  },
  "footer": {
    "description": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "認定インストラクターと乗馬レッスンを予約するための参考プラットフォーム。" } },
    "quickLinks": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "クイックリンク" } },
    "support": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "サポート" } },
    "legal": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "法的" } },
    "contact": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "お問い合わせ" } },
    "about": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "私たちについて" } },
    "terms": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "利用規約" } },
    "privacy": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "プライバシーポリシー" } }
  },
  "loginPage": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Connexion à votre compte" } },
    "or": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Ou" } },
    "createAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "créez un nouveau compte" } },
    "loggingIn": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Connexion en cours..." } }
  },
  "registerPage": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Créer un compte" } },
    "or": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Ou" } },
    "login": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "connectez-vous à votre compte existant" } },
    "terms": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "J'accepte les" } },
    "termsLink": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "conditions d'utilisation" } },
    "creatingAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Création du compte..." } }
  },
  "dashboard": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3, "v": "Bienvenue, " }, { "t": 4, "k": "name" }] } },
    "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Voici votre tableau de bord personnel" } },
    "upcomingLessons": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Prochains cours" } },
    "completedLessons": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cours terminés" } },
    "totalHours": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Heures totales" } },
    "quickActions": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Actions rapides" } },
    "viewAll": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Voir tous" } },
    "noLessons": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Aucun cours planifié" } },
    "bookLesson": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Réserver un cours" } },
    "browseTeachers": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Parcourir les enseignants" } },
    "with": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "avec" } },
    "confirmed": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Confirmé" } }
  },
  "teachers": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Nos Instructeurs Équestres" } },
    "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Découvrez notre équipe d'instructeurs passionnés et expérimentés" } },
    "searchPlaceholder": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Rechercher un instructeur..." } },
    "allDisciplines": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Toutes disciplines" } },
    "allLevels": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Tous niveaux" } },
    "dressage": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Dressage" } },
    "jumping": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Saut d'obstacles" } },
    "cross": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cross" } },
    "ponyGames": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Pony Games" } },
    "western": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Western" } },
    "beginner": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Débutant" } },
    "intermediate": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Intermédiaire" } },
    "advanced": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Avancé" } },
    "competition": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Compétition" } },
    "experience": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "ans d'expérience" } },
    "viewProfile": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Voir le profil" } },
    "noTeachers": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Aucun instructeur trouvé pour ces critères" } }
  },
  "home": {
    "hero": {
      "tagline": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Trouvez votre instructeur équestre idéal et réservez vos cours d'équitation" } },
      "ctaStart": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Commencer l'aventure" } },
      "ctaDiscover": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Découvrir les coaches" } }
    },
    "features": {
      "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3, "v": "Pourquoi choisir " }, { "t": 4, "k": "platform" }, { "t": 3, "v": " ?" }] } },
      "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "La plateforme de référence pour l'équitation moderne" } },
      "items": {
        "certified": {
          "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Instructeurs Certifiés" } },
          "desc": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Tous nos instructeurs sont diplômés d'État et possèdent une expérience reconnue en équitation" } }
        },
        "flexible": {
          "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Réservation Flexible" } },
          "desc": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Réservez vos cours selon vos disponibilités, en carrière, en manège ou en extérieur" } }
        },
        "safety": {
          "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Sécurité Garantie" } },
          "desc": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Équipements certifiés, chevaux bien dressés et encadrement professionnel pour votre sécurité" } }
        }
      }
    },
    "stats": {
      "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Notre Communauté Équestre" } },
      "coachesLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Instructeurs Certifiés" } },
      "studentsLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cavaliers Satisfaits" } },
      "lessonsLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cours Dispensés" } },
      "locationsLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Centres Équestres" } }
    },
    "cta": {
      "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Prêt à Galoper vers l'Excellence ?" } },
      "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Rejoignez notre communauté de passionnés d'équitation et découvrez le plaisir d'apprendre avec les meilleurs instructeurs" } },
      "ctaSignup": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "S'inscrire Gratuitement" } },
      "ctaAbout": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "En Savoir Plus" } }
    }
  }
};
const resource$3 = {
  "nav": {
    "dashboard": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Instrumentpanel" } },
    "teacherSpace": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Lärarutrymme" } },
    "profile": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Min Profil" } },
    "admin": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Administration" } },
    "login": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Logga in" } },
    "register": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Registrera" } },
    "logout": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Logga ut" } }
  },
  "auth": {
    "login": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Inloggning" } },
    "email": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "E-postadress" } },
    "password": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Lösenord" } },
    "rememberMe": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Kom ihåg mig" } },
    "forgotPassword": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Glömt lösenord?" } },
    "noAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Har inget konto?" } },
    "createAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Skapa konto" } },
    "name": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Fullständigt namn" } },
    "confirmPassword": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Bekräfta lösenord" } },
    "alreadyAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Har redan konto?" } },
    "loginHere": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Logga in här" } }
  },
  "common": {
    "welcome": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Välkommen" } },
    "loading": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Laddar..." } },
    "save": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Spara" } },
    "cancel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Avbryt" } },
    "delete": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Radera" } },
    "edit": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Redigera" } },
    "back": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Tillbaka" } },
    "next": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Nästa" } },
    "previous": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Föregående" } },
    "search": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Sök" } },
    "filter": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Filtrera" } },
    "sort": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Sortera" } },
    "actions": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Åtgärder" } }
  },
  "footer": {
    "description": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Referensplattformen för att boka dina ridlektioner med certifierade instruktörer." } },
    "quickLinks": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Snabblänkar" } },
    "support": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Support" } },
    "legal": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Juridiskt" } },
    "contact": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Kontakt" } },
    "about": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Om oss" } },
    "terms": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Användarvillkor" } },
    "privacy": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Integritetspolicy" } }
  },
  "loginPage": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Connexion à votre compte" } },
    "or": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Ou" } },
    "createAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "créez un nouveau compte" } },
    "loggingIn": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Connexion en cours..." } }
  },
  "registerPage": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Créer un compte" } },
    "or": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Ou" } },
    "login": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "connectez-vous à votre compte existant" } },
    "terms": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "J'accepte les" } },
    "termsLink": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "conditions d'utilisation" } },
    "creatingAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Création du compte..." } }
  },
  "dashboard": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3, "v": "Bienvenue, " }, { "t": 4, "k": "name" }] } },
    "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Voici votre tableau de bord personnel" } },
    "upcomingLessons": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Prochains cours" } },
    "completedLessons": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cours terminés" } },
    "totalHours": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Heures totales" } },
    "quickActions": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Actions rapides" } },
    "viewAll": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Voir tous" } },
    "noLessons": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Aucun cours planifié" } },
    "bookLesson": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Réserver un cours" } },
    "browseTeachers": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Parcourir les enseignants" } },
    "with": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "avec" } },
    "confirmed": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Confirmé" } }
  },
  "teachers": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Nos Instructeurs Équestres" } },
    "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Découvrez notre équipe d'instructeurs passionnés et expérimentés" } },
    "searchPlaceholder": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Rechercher un instructeur..." } },
    "allDisciplines": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Toutes disciplines" } },
    "allLevels": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Tous niveaux" } },
    "dressage": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Dressage" } },
    "jumping": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Saut d'obstacles" } },
    "cross": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cross" } },
    "ponyGames": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Pony Games" } },
    "western": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Western" } },
    "beginner": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Débutant" } },
    "intermediate": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Intermédiaire" } },
    "advanced": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Avancé" } },
    "competition": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Compétition" } },
    "experience": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "ans d'expérience" } },
    "viewProfile": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Voir le profil" } },
    "noTeachers": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Aucun instructeur trouvé pour ces critères" } }
  },
  "home": {
    "hero": {
      "tagline": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Trouvez votre instructeur équestre idéal et réservez vos cours d'équitation" } },
      "ctaStart": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Commencer l'aventure" } },
      "ctaDiscover": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Découvrir les coaches" } }
    },
    "features": {
      "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3, "v": "Pourquoi choisir " }, { "t": 4, "k": "platform" }, { "t": 3, "v": " ?" }] } },
      "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "La plateforme de référence pour l'équitation moderne" } },
      "items": {
        "certified": {
          "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Instructeurs Certifiés" } },
          "desc": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Tous nos instructeurs sont diplômés d'État et possèdent une expérience reconnue en équitation" } }
        },
        "flexible": {
          "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Réservation Flexible" } },
          "desc": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Réservez vos cours selon vos disponibilités, en carrière, en manège ou en extérieur" } }
        },
        "safety": {
          "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Sécurité Garantie" } },
          "desc": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Équipements certifiés, chevaux bien dressés et encadrement professionnel pour votre sécurité" } }
        }
      }
    },
    "stats": {
      "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Notre Communauté Équestre" } },
      "coachesLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Instructeurs Certifiés" } },
      "studentsLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cavaliers Satisfaits" } },
      "lessonsLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cours Dispensés" } },
      "locationsLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Centres Équestres" } }
    },
    "cta": {
      "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Prêt à Galoper vers l'Excellence ?" } },
      "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Rejoignez notre communauté de passionnés d'équitation et découvrez le plaisir d'apprendre avec les meilleurs instructeurs" } },
      "ctaSignup": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "S'inscrire Gratuitement" } },
      "ctaAbout": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "En Savoir Plus" } }
    }
  }
};
const resource$2 = {
  "nav": {
    "dashboard": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Dashbord" } },
    "teacherSpace": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Lærer Område" } },
    "profile": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Min Profil" } },
    "admin": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Administrasjon" } },
    "login": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Logg inn" } },
    "register": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Registrer" } },
    "logout": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Logg ut" } }
  },
  "auth": {
    "login": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Innlogging" } },
    "email": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "E-postadresse" } },
    "password": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Passord" } },
    "rememberMe": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Husk meg" } },
    "forgotPassword": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Glemt passord?" } },
    "noAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Har ikke konto?" } },
    "createAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Opprett konto" } },
    "name": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Fullt navn" } },
    "confirmPassword": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Bekreft passord" } },
    "alreadyAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Har allerede konto?" } },
    "loginHere": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Logg inn her" } }
  },
  "common": {
    "welcome": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Velkommen" } },
    "loading": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Laster..." } },
    "save": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Lagre" } },
    "cancel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Avbryt" } },
    "delete": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Slett" } },
    "edit": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Rediger" } },
    "back": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Tilbake" } },
    "next": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Neste" } },
    "previous": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Forrige" } },
    "search": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Søk" } },
    "filter": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Filtrer" } },
    "sort": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Sorter" } },
    "actions": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Handlinger" } }
  },
  "footer": {
    "description": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Referanseplattformen for å bestille ridelesingene dine med sertifiserte instruktører." } },
    "quickLinks": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Hurtigkoblinger" } },
    "support": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Støtte" } },
    "legal": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Juridisk" } },
    "contact": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Kontakt" } },
    "about": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Om oss" } },
    "terms": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Bruksvilkår" } },
    "privacy": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Personvernpolicy" } }
  },
  "loginPage": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Connexion à votre compte" } },
    "or": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Ou" } },
    "createAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "créez un nouveau compte" } },
    "loggingIn": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Connexion en cours..." } }
  },
  "registerPage": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Créer un compte" } },
    "or": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Ou" } },
    "login": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "connectez-vous à votre compte existant" } },
    "terms": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "J'accepte les" } },
    "termsLink": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "conditions d'utilisation" } },
    "creatingAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Création du compte..." } }
  },
  "dashboard": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3, "v": "Bienvenue, " }, { "t": 4, "k": "name" }] } },
    "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Voici votre tableau de bord personnel" } },
    "upcomingLessons": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Prochains cours" } },
    "completedLessons": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cours terminés" } },
    "totalHours": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Heures totales" } },
    "quickActions": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Actions rapides" } },
    "viewAll": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Voir tous" } },
    "noLessons": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Aucun cours planifié" } },
    "bookLesson": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Réserver un cours" } },
    "browseTeachers": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Parcourir les enseignants" } },
    "with": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "avec" } },
    "confirmed": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Confirmé" } }
  },
  "teachers": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Nos Instructeurs Équestres" } },
    "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Découvrez notre équipe d'instructeurs passionnés et expérimentés" } },
    "searchPlaceholder": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Rechercher un instructeur..." } },
    "allDisciplines": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Toutes disciplines" } },
    "allLevels": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Tous niveaux" } },
    "dressage": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Dressage" } },
    "jumping": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Saut d'obstacles" } },
    "cross": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cross" } },
    "ponyGames": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Pony Games" } },
    "western": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Western" } },
    "beginner": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Débutant" } },
    "intermediate": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Intermédiaire" } },
    "advanced": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Avancé" } },
    "competition": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Compétition" } },
    "experience": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "ans d'expérience" } },
    "viewProfile": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Voir le profil" } },
    "noTeachers": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Aucun instructeur trouvé pour ces critères" } }
  },
  "home": {
    "hero": {
      "tagline": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Trouvez votre instructeur équestre idéal et réservez vos cours d'équitation" } },
      "ctaStart": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Commencer l'aventure" } },
      "ctaDiscover": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Découvrir les coaches" } }
    },
    "features": {
      "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3, "v": "Pourquoi choisir " }, { "t": 4, "k": "platform" }, { "t": 3, "v": " ?" }] } },
      "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "La plateforme de référence pour l'équitation moderne" } },
      "items": {
        "certified": {
          "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Instructeurs Certifiés" } },
          "desc": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Tous nos instructeurs sont diplômés d'État et possèdent une expérience reconnue en équitation" } }
        },
        "flexible": {
          "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Réservation Flexible" } },
          "desc": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Réservez vos cours selon vos disponibilités, en carrière, en manège ou en extérieur" } }
        },
        "safety": {
          "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Sécurité Garantie" } },
          "desc": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Équipements certifiés, chevaux bien dressés et encadrement professionnel pour votre sécurité" } }
        }
      }
    },
    "stats": {
      "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Notre Communauté Équestre" } },
      "coachesLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Instructeurs Certifiés" } },
      "studentsLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cavaliers Satisfaits" } },
      "lessonsLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cours Dispensés" } },
      "locationsLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Centres Équestres" } }
    },
    "cta": {
      "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Prêt à Galoper vers l'Excellence ?" } },
      "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Rejoignez notre communauté de passionnés d'équitation et découvrez le plaisir d'apprendre avec les meilleurs instructeurs" } },
      "ctaSignup": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "S'inscrire Gratuitement" } },
      "ctaAbout": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "En Savoir Plus" } }
    }
  }
};
const resource$1 = {
  "nav": {
    "dashboard": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Kojelauta" } },
    "teacherSpace": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Opettajan Alue" } },
    "profile": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Profiilini" } },
    "admin": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Hallinto" } },
    "login": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Kirjaudu sisään" } },
    "register": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Rekisteröidy" } },
    "logout": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Kirjaudu ulos" } }
  },
  "auth": {
    "login": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Sisäänkirjautuminen" } },
    "email": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Sähköpostiosoite" } },
    "password": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Salasana" } },
    "rememberMe": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Muista minut" } },
    "forgotPassword": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Unohditko salasanan?" } },
    "noAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Ei tiliä?" } },
    "createAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Luo tili" } },
    "name": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Koko nimi" } },
    "confirmPassword": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Vahvista salasana" } },
    "alreadyAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Onko sinulla jo tili?" } },
    "loginHere": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Kirjaudu sisään tästä" } }
  },
  "common": {
    "welcome": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Tervetuloa" } },
    "loading": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Ladataan..." } },
    "save": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Tallenna" } },
    "cancel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Peruuta" } },
    "delete": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Poista" } },
    "edit": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Muokkaa" } },
    "back": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Takaisin" } },
    "next": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Seuraava" } },
    "previous": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Edellinen" } },
    "search": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Haku" } },
    "filter": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Suodata" } },
    "sort": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Lajittele" } },
    "actions": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Toiminnot" } }
  },
  "footer": {
    "description": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Viitealusta ratsastustuntien varaamiseen sertifioitujen ohjaajien kanssa." } },
    "quickLinks": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Pikalinkit" } },
    "support": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Tuki" } },
    "legal": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Oikeudellinen" } },
    "contact": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Ota yhteyttä" } },
    "about": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Tietoa meistä" } },
    "terms": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Käyttöehdot" } },
    "privacy": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Tietosuojakäytäntö" } }
  },
  "loginPage": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Connexion à votre compte" } },
    "or": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Ou" } },
    "createAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "créez un nouveau compte" } },
    "loggingIn": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Connexion en cours..." } }
  },
  "registerPage": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Créer un compte" } },
    "or": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Ou" } },
    "login": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "connectez-vous à votre compte existant" } },
    "terms": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "J'accepte les" } },
    "termsLink": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "conditions d'utilisation" } },
    "creatingAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Création du compte..." } }
  },
  "dashboard": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3, "v": "Bienvenue, " }, { "t": 4, "k": "name" }] } },
    "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Voici votre tableau de bord personnel" } },
    "upcomingLessons": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Prochains cours" } },
    "completedLessons": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cours terminés" } },
    "totalHours": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Heures totales" } },
    "quickActions": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Actions rapides" } },
    "viewAll": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Voir tous" } },
    "noLessons": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Aucun cours planifié" } },
    "bookLesson": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Réserver un cours" } },
    "browseTeachers": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Parcourir les enseignants" } },
    "with": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "avec" } },
    "confirmed": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Confirmé" } }
  },
  "teachers": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Nos Instructeurs Équestres" } },
    "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Découvrez notre équipe d'instructeurs passionnés et expérimentés" } },
    "searchPlaceholder": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Rechercher un instructeur..." } },
    "allDisciplines": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Toutes disciplines" } },
    "allLevels": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Tous niveaux" } },
    "dressage": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Dressage" } },
    "jumping": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Saut d'obstacles" } },
    "cross": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cross" } },
    "ponyGames": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Pony Games" } },
    "western": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Western" } },
    "beginner": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Débutant" } },
    "intermediate": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Intermédiaire" } },
    "advanced": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Avancé" } },
    "competition": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Compétition" } },
    "experience": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "ans d'expérience" } },
    "viewProfile": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Voir le profil" } },
    "noTeachers": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Aucun instructeur trouvé pour ces critères" } }
  },
  "home": {
    "hero": {
      "tagline": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Trouvez votre instructeur équestre idéal et réservez vos cours d'équitation" } },
      "ctaStart": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Commencer l'aventure" } },
      "ctaDiscover": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Découvrir les coaches" } }
    },
    "features": {
      "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3, "v": "Pourquoi choisir " }, { "t": 4, "k": "platform" }, { "t": 3, "v": " ?" }] } },
      "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "La plateforme de référence pour l'équitation moderne" } },
      "items": {
        "certified": {
          "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Instructeurs Certifiés" } },
          "desc": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Tous nos instructeurs sont diplômés d'État et possèdent une expérience reconnue en équitation" } }
        },
        "flexible": {
          "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Réservation Flexible" } },
          "desc": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Réservez vos cours selon vos disponibilités, en carrière, en manège ou en extérieur" } }
        },
        "safety": {
          "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Sécurité Garantie" } },
          "desc": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Équipements certifiés, chevaux bien dressés et encadrement professionnel pour votre sécurité" } }
        }
      }
    },
    "stats": {
      "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Notre Communauté Équestre" } },
      "coachesLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Instructeurs Certifiés" } },
      "studentsLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cavaliers Satisfaits" } },
      "lessonsLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cours Dispensés" } },
      "locationsLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Centres Équestres" } }
    },
    "cta": {
      "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Prêt à Galoper vers l'Excellence ?" } },
      "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Rejoignez notre communauté de passionnés d'équitation et découvrez le plaisir d'apprendre avec les meilleurs instructeurs" } },
      "ctaSignup": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "S'inscrire Gratuitement" } },
      "ctaAbout": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "En Savoir Plus" } }
    }
  }
};
const resource = {
  "nav": {
    "dashboard": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Dashboard" } },
    "teacherSpace": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Lærer Område" } },
    "profile": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Min Profil" } },
    "admin": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Administration" } },
    "login": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Log ind" } },
    "register": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Tilmeld" } },
    "logout": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Log ud" } }
  },
  "auth": {
    "login": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Log ind" } },
    "email": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "E-mailadresse" } },
    "password": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Adgangskode" } },
    "rememberMe": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Husk mig" } },
    "forgotPassword": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Glemt adgangskode?" } },
    "noAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Har ikke en konto?" } },
    "createAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Opret konto" } },
    "name": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Fulde navn" } },
    "confirmPassword": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Bekræft adgangskode" } },
    "alreadyAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Har allerede en konto?" } },
    "loginHere": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Log ind her" } }
  },
  "common": {
    "welcome": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Velkommen" } },
    "loading": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Indlæser..." } },
    "save": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Gem" } },
    "cancel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Annuller" } },
    "delete": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Slet" } },
    "edit": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Rediger" } },
    "back": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Tilbage" } },
    "next": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Næste" } },
    "previous": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Forrige" } },
    "search": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Søg" } },
    "filter": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Filtrer" } },
    "sort": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Sorter" } },
    "actions": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Handlinger" } }
  },
  "footer": {
    "description": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Referenceplatformen til at booke dine ridetimer med certificerede instruktører." } },
    "quickLinks": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Hurtige links" } },
    "support": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Support" } },
    "legal": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Juridisk" } },
    "contact": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Kontakt" } },
    "about": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Om os" } },
    "terms": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Brugsvilkår" } },
    "privacy": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Privatlivspolitik" } }
  },
  "loginPage": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Connexion à votre compte" } },
    "or": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Ou" } },
    "createAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "créez un nouveau compte" } },
    "loggingIn": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Connexion en cours..." } }
  },
  "registerPage": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Créer un compte" } },
    "or": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Ou" } },
    "login": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "connectez-vous à votre compte existant" } },
    "terms": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "J'accepte les" } },
    "termsLink": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "conditions d'utilisation" } },
    "creatingAccount": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Création du compte..." } }
  },
  "dashboard": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3, "v": "Bienvenue, " }, { "t": 4, "k": "name" }] } },
    "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Voici votre tableau de bord personnel" } },
    "upcomingLessons": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Prochains cours" } },
    "completedLessons": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cours terminés" } },
    "totalHours": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Heures totales" } },
    "quickActions": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Actions rapides" } },
    "viewAll": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Voir tous" } },
    "noLessons": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Aucun cours planifié" } },
    "bookLesson": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Réserver un cours" } },
    "browseTeachers": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Parcourir les enseignants" } },
    "with": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "avec" } },
    "confirmed": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Confirmé" } }
  },
  "teachers": {
    "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Nos Instructeurs Équestres" } },
    "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Découvrez notre équipe d'instructeurs passionnés et expérimentés" } },
    "searchPlaceholder": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Rechercher un instructeur..." } },
    "allDisciplines": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Toutes disciplines" } },
    "allLevels": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Tous niveaux" } },
    "dressage": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Dressage" } },
    "jumping": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Saut d'obstacles" } },
    "cross": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cross" } },
    "ponyGames": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Pony Games" } },
    "western": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Western" } },
    "beginner": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Débutant" } },
    "intermediate": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Intermédiaire" } },
    "advanced": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Avancé" } },
    "competition": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Compétition" } },
    "experience": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "ans d'expérience" } },
    "viewProfile": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Voir le profil" } },
    "noTeachers": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Aucun instructeur trouvé pour ces critères" } }
  },
  "home": {
    "hero": {
      "tagline": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Trouvez votre instructeur équestre idéal et réservez vos cours d'équitation" } },
      "ctaStart": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Commencer l'aventure" } },
      "ctaDiscover": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Découvrir les coaches" } }
    },
    "features": {
      "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3, "v": "Pourquoi choisir " }, { "t": 4, "k": "platform" }, { "t": 3, "v": " ?" }] } },
      "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "La plateforme de référence pour l'équitation moderne" } },
      "items": {
        "certified": {
          "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Instructeurs Certifiés" } },
          "desc": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Tous nos instructeurs sont diplômés d'État et possèdent une expérience reconnue en équitation" } }
        },
        "flexible": {
          "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Réservation Flexible" } },
          "desc": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Réservez vos cours selon vos disponibilités, en carrière, en manège ou en extérieur" } }
        },
        "safety": {
          "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Sécurité Garantie" } },
          "desc": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Équipements certifiés, chevaux bien dressés et encadrement professionnel pour votre sécurité" } }
        }
      }
    },
    "stats": {
      "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Notre Communauté Équestre" } },
      "coachesLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Instructeurs Certifiés" } },
      "studentsLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cavaliers Satisfaits" } },
      "lessonsLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Cours Dispensés" } },
      "locationsLabel": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Centres Équestres" } }
    },
    "cta": {
      "title": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Prêt à Galoper vers l'Excellence ?" } },
      "subtitle": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "Rejoignez notre communauté de passionnés d'équitation et découvrez le plaisir d'apprendre avec les meilleurs instructeurs" } },
      "ctaSignup": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "S'inscrire Gratuitement" } },
      "ctaAbout": { "t": 0, "b": { "t": 2, "i": [{ "t": 3 }], "s": "En Savoir Plus" } }
    }
  }
};
const localeCodes = [
  "fr",
  "en",
  "nl",
  "de",
  "it",
  "es",
  "pt",
  "hu",
  "pl",
  "zh",
  "ja",
  "sv",
  "no",
  "fi",
  "da"
];
const localeLoaders = {
  "fr": [{ key: "../locales/fr.json", load: () => Promise.resolve(resource$e), cache: true }],
  "en": [{ key: "../locales/en.json", load: () => Promise.resolve(resource$d), cache: true }],
  "nl": [{ key: "../locales/nl.json", load: () => Promise.resolve(resource$c), cache: true }],
  "de": [{ key: "../locales/de.json", load: () => Promise.resolve(resource$b), cache: true }],
  "it": [{ key: "../locales/it.json", load: () => Promise.resolve(resource$a), cache: true }],
  "es": [{ key: "../locales/es.json", load: () => Promise.resolve(resource$9), cache: true }],
  "pt": [{ key: "../locales/pt.json", load: () => Promise.resolve(resource$8), cache: true }],
  "hu": [{ key: "../locales/hu.json", load: () => Promise.resolve(resource$7), cache: true }],
  "pl": [{ key: "../locales/pl.json", load: () => Promise.resolve(resource$6), cache: true }],
  "zh": [{ key: "../locales/zh.json", load: () => Promise.resolve(resource$5), cache: true }],
  "ja": [{ key: "../locales/ja.json", load: () => Promise.resolve(resource$4), cache: true }],
  "sv": [{ key: "../locales/sv.json", load: () => Promise.resolve(resource$3), cache: true }],
  "no": [{ key: "../locales/no.json", load: () => Promise.resolve(resource$2), cache: true }],
  "fi": [{ key: "../locales/fi.json", load: () => Promise.resolve(resource$1), cache: true }],
  "da": [{ key: "../locales/da.json", load: () => Promise.resolve(resource), cache: true }]
};
const vueI18nConfigs = [];
const normalizedLocales = [
  {
    "code": "fr",
    "name": "Français",
    "files": [
      {
        "path": "/workspace/frontend/locales/fr.json"
      }
    ]
  },
  {
    "code": "en",
    "name": "English",
    "files": [
      {
        "path": "/workspace/frontend/locales/en.json"
      }
    ]
  },
  {
    "code": "nl",
    "name": "Nederlands",
    "files": [
      {
        "path": "/workspace/frontend/locales/nl.json"
      }
    ]
  },
  {
    "code": "de",
    "name": "Deutsch",
    "files": [
      {
        "path": "/workspace/frontend/locales/de.json"
      }
    ]
  },
  {
    "code": "it",
    "name": "Italiano",
    "files": [
      {
        "path": "/workspace/frontend/locales/it.json"
      }
    ]
  },
  {
    "code": "es",
    "name": "Español",
    "files": [
      {
        "path": "/workspace/frontend/locales/es.json"
      }
    ]
  },
  {
    "code": "pt",
    "name": "Português",
    "files": [
      {
        "path": "/workspace/frontend/locales/pt.json"
      }
    ]
  },
  {
    "code": "hu",
    "name": "Magyar",
    "files": [
      {
        "path": "/workspace/frontend/locales/hu.json"
      }
    ]
  },
  {
    "code": "pl",
    "name": "Polski",
    "files": [
      {
        "path": "/workspace/frontend/locales/pl.json"
      }
    ]
  },
  {
    "code": "zh",
    "name": "中文",
    "files": [
      {
        "path": "/workspace/frontend/locales/zh.json"
      }
    ]
  },
  {
    "code": "ja",
    "name": "日本語",
    "files": [
      {
        "path": "/workspace/frontend/locales/ja.json"
      }
    ]
  },
  {
    "code": "sv",
    "name": "Svenska",
    "files": [
      {
        "path": "/workspace/frontend/locales/sv.json"
      }
    ]
  },
  {
    "code": "no",
    "name": "Norsk",
    "files": [
      {
        "path": "/workspace/frontend/locales/no.json"
      }
    ]
  },
  {
    "code": "fi",
    "name": "Suomi",
    "files": [
      {
        "path": "/workspace/frontend/locales/fi.json"
      }
    ]
  },
  {
    "code": "da",
    "name": "Dansk",
    "files": [
      {
        "path": "/workspace/frontend/locales/da.json"
      }
    ]
  }
];
const NUXT_I18N_MODULE_ID = "@nuxtjs/i18n";
const parallelPlugin = false;
const isSSG = false;
const DEFAULT_DYNAMIC_PARAMS_KEY = "nuxtI18n";
const DEFAULT_COOKIE_KEY = "i18n_redirected";
const SWITCH_LOCALE_PATH_LINK_IDENTIFIER = "nuxt-i18n-slp";
function getNormalizedLocales(locales) {
  locales = locales || [];
  const normalized = [];
  for (const locale of locales) {
    if (isString(locale)) {
      normalized.push({ code: locale });
    } else {
      normalized.push(locale);
    }
  }
  return normalized;
}
function isI18nInstance(i18n) {
  return i18n != null && "global" in i18n && "mode" in i18n;
}
function isComposer(target) {
  return target != null && !("__composer" in target) && "locale" in target && isRef(target.locale);
}
function isVueI18n(target) {
  return target != null && "__composer" in target;
}
function getI18nTarget(i18n) {
  return isI18nInstance(i18n) ? i18n.global : i18n;
}
function getComposer$3(i18n) {
  const target = getI18nTarget(i18n);
  if (isComposer(target)) return target;
  if (isVueI18n(target)) return target.__composer;
  return target;
}
function getLocale$1(i18n) {
  return unref(getI18nTarget(i18n).locale);
}
function getLocales(i18n) {
  return unref(getI18nTarget(i18n).locales);
}
function getLocaleCodes(i18n) {
  return unref(getI18nTarget(i18n).localeCodes);
}
function setLocale(i18n, locale) {
  const target = getI18nTarget(i18n);
  if (isRef(target.locale)) {
    target.locale.value = locale;
  } else {
    target.locale = locale;
  }
}
function getRouteName(routeName) {
  if (isString(routeName)) return routeName;
  if (isSymbol(routeName)) return routeName.toString();
  return "(null)";
}
function getLocaleRouteName(routeName, locale, {
  defaultLocale,
  strategy,
  routesNameSeparator,
  defaultLocaleRouteNameSuffix,
  differentDomains
}) {
  const localizedRoutes = strategy !== "no_prefix" || differentDomains;
  let name = getRouteName(routeName) + (localizedRoutes ? routesNameSeparator + locale : "");
  if (locale === defaultLocale && strategy === "prefix_and_default") {
    name += routesNameSeparator + defaultLocaleRouteNameSuffix;
  }
  return name;
}
function resolveBaseUrl(baseUrl, context) {
  if (isFunction(baseUrl)) {
    return baseUrl(context);
  }
  return baseUrl;
}
function matchBrowserLocale(locales, browserLocales) {
  const matchedLocales = [];
  for (const [index, browserCode] of browserLocales.entries()) {
    const matchedLocale = locales.find((l) => l.language.toLowerCase() === browserCode.toLowerCase());
    if (matchedLocale) {
      matchedLocales.push({ code: matchedLocale.code, score: 1 - index / browserLocales.length });
      break;
    }
  }
  for (const [index, browserCode] of browserLocales.entries()) {
    const languageCode = browserCode.split("-")[0].toLowerCase();
    const matchedLocale = locales.find((l) => l.language.split("-")[0].toLowerCase() === languageCode);
    if (matchedLocale) {
      matchedLocales.push({ code: matchedLocale.code, score: 0.999 - index / browserLocales.length });
      break;
    }
  }
  return matchedLocales;
}
const DefaultBrowserLocaleMatcher = matchBrowserLocale;
function compareBrowserLocale(a, b) {
  if (a.score === b.score) {
    return b.code.length - a.code.length;
  }
  return b.score - a.score;
}
const DefaultBrowerLocaleComparer = compareBrowserLocale;
function findBrowserLocale(locales, browserLocales, { matcher = DefaultBrowserLocaleMatcher, comparer = DefaultBrowerLocaleComparer } = {}) {
  const normalizedLocales2 = [];
  for (const l of locales) {
    const { code: code2 } = l;
    const language = l.language || code2;
    normalizedLocales2.push({ code: code2, language });
  }
  const matchedLocales = matcher(normalizedLocales2, browserLocales);
  if (matchedLocales.length > 1) {
    matchedLocales.sort(comparer);
  }
  return matchedLocales.length ? matchedLocales[0].code : "";
}
function getLocalesRegex(localeCodes2) {
  return new RegExp(`^/(${localeCodes2.join("|")})(?:/|$)`, "i");
}
const cacheMessages = /* @__PURE__ */ new Map();
async function loadVueI18nOptions(vueI18nConfigs2, nuxt) {
  const vueI18nOptions = { messages: {} };
  for (const configFile of vueI18nConfigs2) {
    const { default: resolver } = await configFile();
    const resolved = isFunction(resolver) ? await nuxt.runWithContext(async () => await resolver()) : resolver;
    deepCopy(resolved, vueI18nOptions);
  }
  return vueI18nOptions;
}
function makeFallbackLocaleCodes(fallback, locales) {
  let fallbackLocales = [];
  if (isArray(fallback)) {
    fallbackLocales = fallback;
  } else if (isObject(fallback)) {
    const targets = [...locales, "default"];
    for (const locale of targets) {
      if (fallback[locale]) {
        fallbackLocales = [...fallbackLocales, ...fallback[locale].filter(Boolean)];
      }
    }
  } else if (isString(fallback) && locales.every((locale) => locale !== fallback)) {
    fallbackLocales.push(fallback);
  }
  return fallbackLocales;
}
async function loadInitialMessages(messages, localeLoaders2, options) {
  const { defaultLocale, initialLocale, localeCodes: localeCodes2, fallbackLocale, lazy } = options;
  if (lazy && fallbackLocale) {
    const fallbackLocales = makeFallbackLocaleCodes(fallbackLocale, [defaultLocale, initialLocale]);
    await Promise.all(fallbackLocales.map((locale) => loadAndSetLocaleMessages(locale, localeLoaders2, messages)));
  }
  const locales = lazy ? [...(/* @__PURE__ */ new Set()).add(defaultLocale).add(initialLocale)] : localeCodes2;
  await Promise.all(locales.map((locale) => loadAndSetLocaleMessages(locale, localeLoaders2, messages)));
  return messages;
}
async function loadMessage(locale, { key, load }) {
  let message = null;
  try {
    const getter = await load().then((r) => r.default || r);
    if (isFunction(getter)) {
      message = await getter(locale);
    } else {
      message = getter;
      if (message != null && cacheMessages) {
        cacheMessages.set(key, message);
      }
    }
  } catch (e) {
    console.error("Failed locale loading: " + e.message);
  }
  return message;
}
async function loadLocale(locale, localeLoaders2, setter) {
  const loaders = localeLoaders2[locale];
  if (loaders == null) {
    console.warn("Could not find messages for locale code: " + locale);
    return;
  }
  const targetMessage = {};
  for (const loader of loaders) {
    let message = null;
    if (cacheMessages && cacheMessages.has(loader.key) && loader.cache) {
      message = cacheMessages.get(loader.key);
    } else {
      message = await loadMessage(locale, loader);
    }
    if (message != null) {
      deepCopy(message, targetMessage);
    }
  }
  setter(locale, targetMessage);
}
async function loadAndSetLocaleMessages(locale, localeLoaders2, messages) {
  const setter = (locale2, message) => {
    const base = messages[locale2] || {};
    deepCopy(message, base);
    messages[locale2] = base;
  };
  await loadLocale(locale, localeLoaders2, setter);
}
function split(str, index) {
  const result = [str.slice(0, index), str.slice(index)];
  return result;
}
function routeToObject(route) {
  const { fullPath, query, hash, name, path, params, meta, redirectedFrom, matched } = route;
  return {
    fullPath,
    params,
    query,
    hash,
    name,
    path,
    meta,
    matched,
    redirectedFrom
  };
}
function resolve({ router }, route, strategy, locale) {
  var _a, _b;
  if (strategy !== "prefix") {
    return router.resolve(route);
  }
  const [rootSlash, restPath] = split(route.path, 1);
  const targetPath = `${rootSlash}${locale}${restPath === "" ? restPath : `/${restPath}`}`;
  const _route = (_b = (_a = router.options) == null ? void 0 : _a.routes) == null ? void 0 : _b.find((r) => r.path === targetPath);
  if (_route == null) {
    return route;
  }
  const _resolvableRoute = assign({}, route, _route);
  _resolvableRoute.path = targetPath;
  return router.resolve(_resolvableRoute);
}
const RESOLVED_PREFIXED = /* @__PURE__ */ new Set(["prefix_and_default", "prefix_except_default"]);
function prefixable(options) {
  const { currentLocale, defaultLocale, strategy } = options;
  const isDefaultLocale = currentLocale === defaultLocale;
  return !(isDefaultLocale && RESOLVED_PREFIXED.has(strategy)) && // no prefix for any language
  !(strategy === "no_prefix");
}
const DefaultPrefixable = prefixable;
function getRouteBaseName(common, givenRoute) {
  const { routesNameSeparator } = common.runtimeConfig.public.i18n;
  const route = unref(givenRoute);
  if (route == null || !route.name) {
    return;
  }
  const name = getRouteName(route.name);
  return name.split(routesNameSeparator)[0];
}
function localePath(common, route, locale) {
  var _a;
  if (typeof route === "string" && hasProtocol(route, { acceptRelative: true })) {
    return route;
  }
  const localizedRoute = resolveRoute(common, route, locale);
  return localizedRoute == null ? "" : ((_a = localizedRoute.redirectedFrom) == null ? void 0 : _a.fullPath) || localizedRoute.fullPath;
}
function localeRoute(common, route, locale) {
  const resolved = resolveRoute(common, route, locale);
  return resolved ?? void 0;
}
function localeLocation(common, route, locale) {
  const resolved = resolveRoute(common, route, locale);
  return resolved ?? void 0;
}
function resolveRoute(common, route, locale) {
  const { router, i18n } = common;
  const _locale = locale || getLocale$1(i18n);
  const { defaultLocale, strategy, trailingSlash } = common.runtimeConfig.public.i18n;
  const prefixable2 = extendPrefixable(common.runtimeConfig);
  let _route;
  if (isString(route)) {
    if (route[0] === "/") {
      const { pathname: path, search, hash } = parsePath(route);
      const query = parseQuery(search);
      _route = { path, query, hash };
    } else {
      _route = { name: route };
    }
  } else {
    _route = route;
  }
  let localizedRoute = assign({}, _route);
  const isRouteLocationPathRaw = (val) => "path" in val && !!val.path && !("name" in val);
  if (isRouteLocationPathRaw(localizedRoute)) {
    const resolvedRoute = resolve(common, localizedRoute, strategy, _locale);
    const resolvedRouteName = getRouteBaseName(common, resolvedRoute);
    if (isString(resolvedRouteName)) {
      localizedRoute = {
        name: getLocaleRouteName(resolvedRouteName, _locale, common.runtimeConfig.public.i18n),
        // @ts-ignore
        // eslint-disable-next-line @typescript-eslint/no-unsafe-assignment -- FIXME
        params: resolvedRoute.params,
        query: resolvedRoute.query,
        hash: resolvedRoute.hash
      };
      localizedRoute.state = resolvedRoute.state;
    } else {
      if (prefixable2({ currentLocale: _locale, defaultLocale, strategy })) {
        localizedRoute.path = `/${_locale}${localizedRoute.path}`;
      }
      localizedRoute.path = trailingSlash ? withTrailingSlash(localizedRoute.path, true) : withoutTrailingSlash(localizedRoute.path, true);
    }
  } else {
    if (!localizedRoute.name && !("path" in localizedRoute)) {
      localizedRoute.name = getRouteBaseName(common, router.currentRoute.value);
    }
    localizedRoute.name = getLocaleRouteName(localizedRoute.name, _locale, common.runtimeConfig.public.i18n);
  }
  try {
    const resolvedRoute = router.resolve(localizedRoute);
    if (resolvedRoute.name) {
      return resolvedRoute;
    }
    return router.resolve(route);
  } catch (e) {
    if (typeof e === "object" && "type" in e && e.type === 1) {
      return null;
    }
  }
}
const DefaultSwitchLocalePathIntercepter = (path) => path;
function getLocalizableMetaFromDynamicParams(common, route) {
  var _a;
  if (common.runtimeConfig.public.i18n.experimental.switchLocalePathLinkSSR) {
    return unref(common.metaState.value);
  }
  const meta = route.meta || {};
  return ((_a = unref(meta)) == null ? void 0 : _a[DEFAULT_DYNAMIC_PARAMS_KEY]) || {};
}
function switchLocalePath(common, locale, _route) {
  const route = _route ?? common.router.currentRoute.value;
  const name = getRouteBaseName(common, route);
  if (!name) {
    return "";
  }
  const switchLocalePathIntercepter = extendSwitchLocalePathIntercepter(common.runtimeConfig);
  const routeCopy = routeToObject(route);
  const resolvedParams = getLocalizableMetaFromDynamicParams(common, route)[locale];
  const baseRoute = { ...routeCopy, name, params: { ...routeCopy.params, ...resolvedParams } };
  const path = localePath(common, baseRoute, locale);
  return switchLocalePathIntercepter(path, locale);
}
function localeHead(common, {
  addDirAttribute = false,
  addSeoAttributes: seoAttributes = true,
  identifierAttribute: idAttribute = "hid"
}) {
  const { defaultDirection } = (/* @__PURE__ */ useRuntimeConfig()).public.i18n;
  const i18n = getComposer$3(common.i18n);
  const metaObject = {
    htmlAttrs: {},
    link: [],
    meta: []
  };
  if (unref(i18n.locales) == null || unref(i18n.baseUrl) == null) {
    return metaObject;
  }
  const locale = getLocale$1(common.i18n);
  const locales = getLocales(common.i18n);
  const currentLocale = getNormalizedLocales(locales).find((l) => l.code === locale) || {};
  const currentLanguage = currentLocale.language;
  const currentDir = currentLocale.dir || defaultDirection;
  if (addDirAttribute) {
    metaObject.htmlAttrs.dir = currentDir;
  }
  if (seoAttributes && locale && unref(i18n.locales)) {
    if (currentLanguage) {
      metaObject.htmlAttrs.lang = currentLanguage;
    }
    metaObject.link.push(
      ...getHreflangLinks(common, unref(locales), idAttribute),
      ...getCanonicalLink(common, idAttribute, seoAttributes)
    );
    metaObject.meta.push(
      ...getOgUrl(common, idAttribute, seoAttributes),
      ...getCurrentOgLocale(currentLocale, currentLanguage, idAttribute),
      ...getAlternateOgLocales(unref(locales), currentLanguage, idAttribute)
    );
  }
  return metaObject;
}
function getBaseUrl() {
  const nuxtApp = useNuxtApp();
  const i18n = getComposer$3(nuxtApp.$i18n);
  return joinURL(unref(i18n.baseUrl), nuxtApp.$config.app.baseURL);
}
function getHreflangLinks(common, locales, idAttribute) {
  const baseUrl = getBaseUrl();
  const { defaultLocale, strategy } = (/* @__PURE__ */ useRuntimeConfig()).public.i18n;
  const links = [];
  if (strategy === "no_prefix") return links;
  const localeMap = /* @__PURE__ */ new Map();
  for (const locale of locales) {
    const localeLanguage = locale.language;
    if (!localeLanguage) {
      console.warn("Locale `language` ISO code is required to generate alternate link");
      continue;
    }
    const [language, region] = localeLanguage.split("-");
    if (language && region && (locale.isCatchallLocale || !localeMap.has(language))) {
      localeMap.set(language, locale);
    }
    localeMap.set(localeLanguage, locale);
  }
  for (const [language, mapLocale] of localeMap.entries()) {
    const localePath2 = switchLocalePath(common, mapLocale.code);
    if (localePath2) {
      links.push({
        [idAttribute]: `i18n-alt-${language}`,
        rel: "alternate",
        href: toAbsoluteUrl(localePath2, baseUrl),
        hreflang: language
      });
    }
  }
  if (defaultLocale) {
    const localePath2 = switchLocalePath(common, defaultLocale);
    if (localePath2) {
      links.push({
        [idAttribute]: "i18n-xd",
        rel: "alternate",
        href: toAbsoluteUrl(localePath2, baseUrl),
        hreflang: "x-default"
      });
    }
  }
  return links;
}
function getCanonicalUrl(common, baseUrl, seoAttributes) {
  const route = common.router.currentRoute.value;
  const currentRoute = localeRoute(common, {
    ...route,
    path: void 0,
    name: getRouteBaseName(common, route)
  });
  if (!currentRoute) return "";
  let href = toAbsoluteUrl(currentRoute.path, baseUrl);
  const canonicalQueries = isObject(seoAttributes) && seoAttributes.canonicalQueries || [];
  const currentRouteQueryParams = currentRoute.query;
  const params = new URLSearchParams();
  for (const queryParamName of canonicalQueries) {
    if (queryParamName in currentRouteQueryParams) {
      const queryParamValue = currentRouteQueryParams[queryParamName];
      if (isArray(queryParamValue)) {
        queryParamValue.forEach((v) => params.append(queryParamName, v || ""));
      } else {
        params.append(queryParamName, queryParamValue || "");
      }
    }
  }
  const queryString = params.toString();
  if (queryString) {
    href = `${href}?${queryString}`;
  }
  return href;
}
function getCanonicalLink(common, idAttribute, seoAttributes) {
  const baseUrl = getBaseUrl();
  const href = getCanonicalUrl(common, baseUrl, seoAttributes);
  if (!href) return [];
  return [{ [idAttribute]: "i18n-can", rel: "canonical", href }];
}
function getOgUrl(common, idAttribute, seoAttributes) {
  const baseUrl = getBaseUrl();
  const href = getCanonicalUrl(common, baseUrl, seoAttributes);
  if (!href) return [];
  return [{ [idAttribute]: "i18n-og-url", property: "og:url", content: href }];
}
function getCurrentOgLocale(currentLocale, currentLanguage, idAttribute) {
  if (!currentLocale || !currentLanguage) return [];
  return [{ [idAttribute]: "i18n-og", property: "og:locale", content: hypenToUnderscore(currentLanguage) }];
}
function getAlternateOgLocales(locales, currentLanguage, idAttribute) {
  const alternateLocales = locales.filter((locale) => locale.language && locale.language !== currentLanguage);
  return alternateLocales.map((locale) => ({
    [idAttribute]: `i18n-og-alt-${locale.language}`,
    property: "og:locale:alternate",
    content: hypenToUnderscore(locale.language)
  }));
}
function hypenToUnderscore(str) {
  return (str || "").replace(/-/g, "_");
}
function toAbsoluteUrl(urlOrPath, baseUrl) {
  if (urlOrPath.match(/^https?:\/\//)) return urlOrPath;
  return joinURL(baseUrl, urlOrPath);
}
function createLocaleFromRouteGetter() {
  const { routesNameSeparator, defaultLocaleRouteNameSuffix } = (/* @__PURE__ */ useRuntimeConfig()).public.i18n;
  const localesPattern = `(${localeCodes.join("|")})`;
  const defaultSuffixPattern = `(?:${routesNameSeparator}${defaultLocaleRouteNameSuffix})?`;
  const regexpName = new RegExp(`${routesNameSeparator}${localesPattern}${defaultSuffixPattern}$`, "i");
  const regexpPath = getLocalesRegex(localeCodes);
  const getLocaleFromRoute = (route) => {
    if (isObject(route)) {
      if (route.name) {
        const name = isString(route.name) ? route.name : route.name.toString();
        const matches = name.match(regexpName);
        if (matches && matches.length > 1) {
          return matches[1];
        }
      } else if (route.path) {
        const matches = route.path.match(regexpPath);
        if (matches && matches.length > 1) {
          return matches[1];
        }
      }
    } else if (isString(route)) {
      const matches = route.match(regexpPath);
      if (matches && matches.length > 1) {
        return matches[1];
      }
    }
    return "";
  };
  return getLocaleFromRoute;
}
function setCookieLocale(i18n, locale) {
  return callVueI18nInterfaces(i18n, "setLocaleCookie", locale);
}
function mergeLocaleMessage(i18n, locale, messages) {
  return callVueI18nInterfaces(i18n, "mergeLocaleMessage", locale, messages);
}
async function onBeforeLanguageSwitch(i18n, oldLocale, newLocale, initial, context) {
  return callVueI18nInterfaces(i18n, "onBeforeLanguageSwitch", oldLocale, newLocale, initial, context);
}
function onLanguageSwitched(i18n, oldLocale, newLocale) {
  return callVueI18nInterfaces(i18n, "onLanguageSwitched", oldLocale, newLocale);
}
function initCommonComposableOptions(i18n) {
  return {
    i18n: i18n ?? useNuxtApp().$i18n,
    router: useRouter(),
    runtimeConfig: /* @__PURE__ */ useRuntimeConfig(),
    metaState: useState("nuxt-i18n-meta", () => ({}))
  };
}
async function loadAndSetLocale(newLocale, i18n, runtimeI18n, initial = false) {
  const { differentDomains, skipSettingLocaleOnNavigate, lazy } = runtimeI18n;
  const opts = runtimeDetectBrowserLanguage(runtimeI18n);
  const nuxtApp = useNuxtApp();
  const oldLocale = getLocale$1(i18n);
  const localeCodes2 = getLocaleCodes(i18n);
  function syncCookie(locale = oldLocale) {
    if (opts === false || !opts.useCookie) return;
    if (skipSettingLocaleOnNavigate) return;
    setCookieLocale(i18n, locale);
  }
  if (!newLocale) {
    syncCookie();
    return false;
  }
  if (!initial && differentDomains) {
    syncCookie();
    return false;
  }
  if (oldLocale === newLocale) {
    syncCookie();
    return false;
  }
  const localeOverride = await onBeforeLanguageSwitch(i18n, oldLocale, newLocale, initial, nuxtApp);
  if (localeOverride && localeCodes2.includes(localeOverride)) {
    if (oldLocale === localeOverride) {
      syncCookie();
      return false;
    }
    newLocale = localeOverride;
  }
  if (lazy) {
    const i18nFallbackLocales = getVueI18nPropertyValue(i18n, "fallbackLocale");
    const setter = (locale, message) => mergeLocaleMessage(i18n, locale, message);
    if (i18nFallbackLocales) {
      const fallbackLocales = makeFallbackLocaleCodes(i18nFallbackLocales, [newLocale]);
      await Promise.all(fallbackLocales.map((locale) => loadLocale(locale, localeLoaders, setter)));
    }
    await loadLocale(newLocale, localeLoaders, setter);
  }
  if (skipSettingLocaleOnNavigate) {
    return false;
  }
  syncCookie(newLocale);
  setLocale(i18n, newLocale);
  await onLanguageSwitched(i18n, oldLocale, newLocale);
  return true;
}
function createLogger(label) {
  return {
    log: console.log.bind(console, `${label}:`)
    // change to this after implementing logger across runtime code
    // log: console.log.bind(console, `[i18n:${label}]`)
  };
}
function detectLocale(route, routeLocaleGetter, initialLocaleLoader, detectLocaleContext, runtimeI18n) {
  const { strategy, defaultLocale, differentDomains, multiDomainLocales } = runtimeI18n;
  const { localeCookie } = detectLocaleContext;
  const _detectBrowserLanguage = runtimeDetectBrowserLanguage(runtimeI18n);
  createLogger("detectLocale");
  const initialLocale = isFunction(initialLocaleLoader) ? initialLocaleLoader() : initialLocaleLoader;
  const detectedBrowser = detectBrowserLanguage(route, detectLocaleContext, initialLocale);
  if (detectedBrowser.reason === DetectFailure.SSG_IGNORE) {
    return initialLocale;
  }
  if (detectedBrowser.locale && detectedBrowser.from != null) {
    return detectedBrowser.locale;
  }
  let detected = "";
  if (differentDomains || multiDomainLocales) {
    detected || (detected = getLocaleDomain(normalizedLocales, strategy, route));
  } else if (strategy !== "no_prefix") {
    detected || (detected = routeLocaleGetter(route));
  }
  const cookieLocale = _detectBrowserLanguage && _detectBrowserLanguage.useCookie && localeCookie;
  detected || (detected = cookieLocale || initialLocale || defaultLocale || "");
  return detected;
}
function detectRedirect({
  route,
  targetLocale,
  routeLocaleGetter,
  calledWithRouting = false
}) {
  const nuxtApp = useNuxtApp();
  const common = initCommonComposableOptions();
  const { strategy, differentDomains } = common.runtimeConfig.public.i18n;
  let redirectPath = "";
  const { fullPath: toFullPath } = route.to;
  if (!differentDomains && (calledWithRouting || strategy !== "no_prefix") && routeLocaleGetter(route.to) !== targetLocale) {
    const routePath = nuxtApp.$switchLocalePath(targetLocale) || nuxtApp.$localePath(toFullPath, targetLocale);
    if (isString(routePath) && routePath && !isEqual(routePath, toFullPath) && !routePath.startsWith("//")) {
      redirectPath = !(route.from && route.from.fullPath === routePath) ? routePath : "";
    }
  }
  if ((differentDomains || isSSG) && routeLocaleGetter(route.to) !== targetLocale) {
    const routePath = switchLocalePath(common, targetLocale, route.to);
    if (isString(routePath) && routePath && !isEqual(routePath, toFullPath) && !routePath.startsWith("//")) {
      redirectPath = routePath;
    }
  }
  return redirectPath;
}
function isRootRedirectOptions(rootRedirect) {
  return isObject(rootRedirect) && "path" in rootRedirect && "statusCode" in rootRedirect;
}
const useRedirectState = () => useState(NUXT_I18N_MODULE_ID + ":redirect", () => "");
function _navigate(redirectPath, status) {
  return navigateTo(redirectPath, { redirectCode: status });
}
async function navigate(args, { status = 302, enableNavigate = false } = {}) {
  const { nuxtApp, i18n, locale, route } = args;
  const { rootRedirect, differentDomains, multiDomainLocales, skipSettingLocaleOnNavigate, configLocales, strategy } = nuxtApp.$config.public.i18n;
  let { redirectPath } = args;
  if (route.path === "/" && rootRedirect) {
    if (isString(rootRedirect)) {
      redirectPath = "/" + rootRedirect;
    } else if (isRootRedirectOptions(rootRedirect)) {
      redirectPath = "/" + rootRedirect.path;
      status = rootRedirect.statusCode;
    }
    redirectPath = nuxtApp.$localePath(redirectPath, locale);
    return _navigate(redirectPath, status);
  }
  if (multiDomainLocales && strategy === "prefix_except_default") {
    const host = getHost();
    const currentDomain = configLocales.find((locale2) => {
      var _a;
      if (typeof locale2 !== "string") {
        return (_a = locale2.defaultForDomains) == null ? void 0 : _a.find((domain) => domain === host);
      }
      return false;
    });
    const defaultLocaleForDomain = typeof currentDomain !== "string" ? currentDomain == null ? void 0 : currentDomain.code : void 0;
    if (route.path.startsWith(`/${defaultLocaleForDomain}`)) {
      return _navigate(route.path.replace(`/${defaultLocaleForDomain}`, ""), status);
    } else if (!route.path.startsWith(`/${locale}`) && locale !== defaultLocaleForDomain) {
      const getLocaleFromRoute = createLocaleFromRouteGetter();
      const oldLocale = getLocaleFromRoute(route.path);
      if (oldLocale !== "") {
        return _navigate(`/${locale + route.path.replace(`/${oldLocale}`, "")}`, status);
      } else {
        return _navigate(`/${locale + (route.path === "/" ? "" : route.path)}`, status);
      }
    } else if (redirectPath && route.path !== redirectPath) {
      return _navigate(redirectPath, status);
    }
    return;
  }
  if (!differentDomains) {
    if (redirectPath) {
      return _navigate(redirectPath, status);
    }
  } else {
    const state = useRedirectState();
    if (state.value && state.value !== redirectPath) {
      {
        state.value = redirectPath;
      }
    }
  }
}
function injectNuxtHelpers(nuxt, i18n) {
  defineGetter(nuxt, "$i18n", getI18nTarget(i18n));
  defineGetter(nuxt, "$getRouteBaseName", wrapComposable(getRouteBaseName));
  defineGetter(nuxt, "$localePath", wrapComposable(localePath));
  defineGetter(nuxt, "$localeRoute", wrapComposable(localeRoute));
  defineGetter(nuxt, "$switchLocalePath", wrapComposable(switchLocalePath));
  defineGetter(nuxt, "$localeHead", wrapComposable(localeHead));
}
function extendPrefixable(runtimeConfig = /* @__PURE__ */ useRuntimeConfig()) {
  return (opts) => {
    return DefaultPrefixable(opts) && !runtimeConfig.public.i18n.differentDomains;
  };
}
function extendSwitchLocalePathIntercepter(runtimeConfig = /* @__PURE__ */ useRuntimeConfig()) {
  return (path, locale) => {
    if (runtimeConfig.public.i18n.differentDomains) {
      const domain = getDomainFromLocale(locale);
      if (domain) {
        return joinURL(domain, path);
      } else {
        return path;
      }
    } else {
      return DefaultSwitchLocalePathIntercepter(path);
    }
  };
}
function extendBaseUrl() {
  return () => {
    const ctx = useNuxtApp();
    const { baseUrl, defaultLocale, differentDomains } = ctx.$config.public.i18n;
    if (isFunction(baseUrl)) {
      const baseUrlResult = baseUrl(ctx);
      return baseUrlResult;
    }
    const localeCode = isFunction(defaultLocale) ? defaultLocale() : defaultLocale;
    if (differentDomains && localeCode) {
      const domain = getDomainFromLocale(localeCode);
      if (domain) {
        return domain;
      }
    }
    if (baseUrl) {
      return baseUrl;
    }
    return baseUrl;
  };
}
function formatMessage(message) {
  return NUXT_I18N_MODULE_ID + " " + message;
}
function callVueI18nInterfaces(i18n, name, ...args) {
  const target = getI18nTarget(i18n);
  const [obj, method] = [target, target[name]];
  return Reflect.apply(method, obj, [...args]);
}
function getVueI18nPropertyValue(i18n, name) {
  const target = getI18nTarget(i18n);
  return unref(target[name]);
}
function defineGetter(obj, key, val) {
  Object.defineProperty(obj, key, { get: () => val });
}
function wrapComposable(fn, common = initCommonComposableOptions()) {
  return (...args) => fn(common, ...args);
}
function parseAcceptLanguage(input) {
  return input.split(",").map((tag) => tag.split(";")[0]);
}
function getBrowserLocale() {
  let ret;
  {
    const header = useRequestHeaders(["accept-language"]);
    const accept = header["accept-language"];
    if (accept) {
      ret = findBrowserLocale(normalizedLocales, parseAcceptLanguage(accept));
    }
  }
  return ret;
}
function getI18nCookie() {
  const detect = runtimeDetectBrowserLanguage();
  const cookieKey = detect && detect.cookieKey || DEFAULT_COOKIE_KEY;
  const date = /* @__PURE__ */ new Date();
  const cookieOptions = {
    expires: new Date(date.setDate(date.getDate() + 365)),
    path: "/",
    sameSite: detect && detect.cookieCrossOrigin ? "none" : "lax",
    secure: detect && detect.cookieCrossOrigin || detect && detect.cookieSecure
  };
  if (detect && detect.cookieDomain) {
    cookieOptions.domain = detect.cookieDomain;
  }
  return useCookie(cookieKey, cookieOptions);
}
function getLocaleCookie(cookieRef, detect, defaultLocale) {
  if (detect === false || !detect.useCookie) {
    return;
  }
  const localeCode = cookieRef.value ?? void 0;
  if (localeCode == null) {
    return;
  }
  if (localeCodes.includes(localeCode)) {
    return localeCode;
  }
  if (defaultLocale) {
    cookieRef.value = defaultLocale;
    return defaultLocale;
  }
  cookieRef.value = void 0;
  return;
}
function setLocaleCookie(cookieRef, locale, detect) {
  if (detect === false || !detect.useCookie) {
    return;
  }
  cookieRef.value = locale;
}
var DetectFailure = /* @__PURE__ */ ((DetectFailure2) => {
  DetectFailure2["NOT_FOUND"] = "not_found_match";
  DetectFailure2["FIRST_ACCESS"] = "first_access_only";
  DetectFailure2["NO_REDIRECT_ROOT"] = "not_redirect_on_root";
  DetectFailure2["NO_REDIRECT_NO_PREFIX"] = "not_redirect_on_no_prefix";
  DetectFailure2["SSG_IGNORE"] = "detect_ignore_on_ssg";
  return DetectFailure2;
})(DetectFailure || {});
const DefaultDetectBrowserLanguageFromResult = { locale: "" };
function detectBrowserLanguage(route, detectLocaleContext, locale = "") {
  createLogger("detectBrowserLanguage");
  const _detect = runtimeDetectBrowserLanguage();
  if (!_detect) {
    return DefaultDetectBrowserLanguageFromResult;
  }
  const { strategy } = (/* @__PURE__ */ useRuntimeConfig()).public.i18n;
  const { ssg, callType, firstAccess, localeCookie } = detectLocaleContext;
  if (!firstAccess) {
    return {
      locale: strategy === "no_prefix" ? locale : "",
      reason: "first_access_only"
      /* FIRST_ACCESS */
    };
  }
  const { redirectOn, alwaysRedirect, useCookie: useCookie2, fallbackLocale } = _detect;
  const path = isString(route) ? route : route.path;
  if (strategy !== "no_prefix") {
    if (redirectOn === "root" && path !== "/") {
      return {
        locale: "",
        reason: "not_redirect_on_root"
        /* NO_REDIRECT_ROOT */
      };
    }
    if (redirectOn === "no prefix" && !alwaysRedirect && path.match(getLocalesRegex(localeCodes))) {
      return {
        locale: "",
        reason: "not_redirect_on_no_prefix"
        /* NO_REDIRECT_NO_PREFIX */
      };
    }
  }
  let from;
  const cookieMatch = useCookie2 && localeCookie || void 0;
  if (useCookie2) {
    from = "cookie";
  }
  const browserMatch = getBrowserLocale();
  if (!cookieMatch) {
    from = "navigator_or_header";
  }
  const matchedLocale = cookieMatch || browserMatch;
  const resolved = matchedLocale || fallbackLocale || "";
  if (!matchedLocale && fallbackLocale) {
    from = "fallback";
  }
  return { locale: resolved, from };
}
function getHost() {
  let host;
  {
    const header = useRequestHeaders(["x-forwarded-host", "host"]);
    let detectedHost;
    if ("x-forwarded-host" in header) {
      detectedHost = header["x-forwarded-host"];
    } else if ("host" in header) {
      detectedHost = header["host"];
    }
    host = isArray(detectedHost) ? detectedHost[0] : detectedHost;
  }
  return host;
}
function getLocaleDomain(locales, strategy, route) {
  let host = getHost() || "";
  if (host) {
    let matchingLocale;
    const matchingLocales = locales.filter((locale) => {
      if (locale && locale.domain) {
        let domain = locale.domain;
        if (hasProtocol(locale.domain)) {
          domain = locale.domain.replace(/(http|https):\/\//, "");
        }
        return domain === host;
      } else if (Array.isArray(locale == null ? void 0 : locale.domains)) {
        return locale.domains.includes(host);
      }
      return false;
    });
    if (matchingLocales.length === 1) {
      matchingLocale = matchingLocales[0];
    } else if (matchingLocales.length > 1) {
      if (strategy === "no_prefix") {
        console.warn(
          formatMessage(
            "Multiple matching domains found! This is not supported for no_prefix strategy in combination with differentDomains!"
          )
        );
        matchingLocale = matchingLocales[0];
      } else {
        if (route) {
          const routePath = isObject(route) ? route.path : isString(route) ? route : "";
          if (routePath && routePath !== "") {
            const matches = routePath.match(getLocalesRegex(matchingLocales.map((l) => l.code)));
            if (matches && matches.length > 1) {
              matchingLocale = matchingLocales.find((l) => l.code === matches[1]);
            }
          }
        }
        if (!matchingLocale) {
          matchingLocale = matchingLocales.find(
            (l) => Array.isArray(l.defaultForDomains) ? l.defaultForDomains.includes(host) : l.domainDefault
          );
        }
      }
    }
    if (matchingLocale) {
      return matchingLocale.code;
    } else {
      host = "";
    }
  }
  return host;
}
function getDomainFromLocale(localeCode) {
  var _a, _b, _c, _d, _e, _f;
  const runtimeConfig = /* @__PURE__ */ useRuntimeConfig();
  const nuxtApp = useNuxtApp();
  const host = getHost();
  const config = runtimeConfig.public.i18n;
  const lang = normalizedLocales.find((locale) => locale.code === localeCode);
  const domain = ((_b = (_a = config == null ? void 0 : config.locales) == null ? void 0 : _a[localeCode]) == null ? void 0 : _b.domain) || (lang == null ? void 0 : lang.domain) || ((_e = (_d = (_c = config == null ? void 0 : config.locales) == null ? void 0 : _c[localeCode]) == null ? void 0 : _d.domains) == null ? void 0 : _e.find((v) => v === host)) || ((_f = lang == null ? void 0 : lang.domains) == null ? void 0 : _f.find((v) => v === host));
  if (domain) {
    if (hasProtocol(domain, { strict: true })) {
      return domain;
    }
    let protocol;
    {
      const {
        node: { req }
      } = useRequestEvent(nuxtApp);
      protocol = req && isHTTPS(req) ? "https:" : "http:";
    }
    return protocol + "//" + domain;
  }
  console.warn(formatMessage("Could not find domain name for locale " + localeCode));
}
const runtimeDetectBrowserLanguage = (opts = (/* @__PURE__ */ useRuntimeConfig()).public.i18n) => {
  if ((opts == null ? void 0 : opts.detectBrowserLanguage) === false) return false;
  return opts == null ? void 0 : opts.detectBrowserLanguage;
};
/*!
  * message-compiler v9.14.5
  * (c) 2025 kazuya kawaguchi
  * Released under the MIT License.
  */
function createPosition(line, column, offset) {
  return { line, column, offset };
}
function createLocation(start, end, source) {
  const loc = { start, end };
  return loc;
}
const CompileWarnCodes = {
  USE_MODULO_SYNTAX: 1,
  __EXTEND_POINT__: 2
};
const warnMessages$2 = {
  [CompileWarnCodes.USE_MODULO_SYNTAX]: `Use modulo before '{{0}}'.`
};
function createCompileWarn(code2, loc, ...args) {
  const msg = process.env.NODE_ENV !== "production" ? format$1(warnMessages$2[code2], ...args || []) : code2;
  const message = { message: String(msg), code: code2 };
  if (loc) {
    message.location = loc;
  }
  return message;
}
const CompileErrorCodes = {
  // tokenizer error codes
  EXPECTED_TOKEN: 1,
  INVALID_TOKEN_IN_PLACEHOLDER: 2,
  UNTERMINATED_SINGLE_QUOTE_IN_PLACEHOLDER: 3,
  UNKNOWN_ESCAPE_SEQUENCE: 4,
  INVALID_UNICODE_ESCAPE_SEQUENCE: 5,
  UNBALANCED_CLOSING_BRACE: 6,
  UNTERMINATED_CLOSING_BRACE: 7,
  EMPTY_PLACEHOLDER: 8,
  NOT_ALLOW_NEST_PLACEHOLDER: 9,
  INVALID_LINKED_FORMAT: 10,
  // parser error codes
  MUST_HAVE_MESSAGES_IN_PLURAL: 11,
  UNEXPECTED_EMPTY_LINKED_MODIFIER: 12,
  UNEXPECTED_EMPTY_LINKED_KEY: 13,
  UNEXPECTED_LEXICAL_ANALYSIS: 14,
  // generator error codes
  UNHANDLED_CODEGEN_NODE_TYPE: 15,
  // minifier error codes
  UNHANDLED_MINIFIER_NODE_TYPE: 16,
  // Special value for higher-order compilers to pick up the last code
  // to avoid collision of error codes. This should always be kept as the last
  // item.
  __EXTEND_POINT__: 17
};
const errorMessages$2 = {
  // tokenizer error messages
  [CompileErrorCodes.EXPECTED_TOKEN]: `Expected token: '{0}'`,
  [CompileErrorCodes.INVALID_TOKEN_IN_PLACEHOLDER]: `Invalid token in placeholder: '{0}'`,
  [CompileErrorCodes.UNTERMINATED_SINGLE_QUOTE_IN_PLACEHOLDER]: `Unterminated single quote in placeholder`,
  [CompileErrorCodes.UNKNOWN_ESCAPE_SEQUENCE]: `Unknown escape sequence: \\{0}`,
  [CompileErrorCodes.INVALID_UNICODE_ESCAPE_SEQUENCE]: `Invalid unicode escape sequence: {0}`,
  [CompileErrorCodes.UNBALANCED_CLOSING_BRACE]: `Unbalanced closing brace`,
  [CompileErrorCodes.UNTERMINATED_CLOSING_BRACE]: `Unterminated closing brace`,
  [CompileErrorCodes.EMPTY_PLACEHOLDER]: `Empty placeholder`,
  [CompileErrorCodes.NOT_ALLOW_NEST_PLACEHOLDER]: `Not allowed nest placeholder`,
  [CompileErrorCodes.INVALID_LINKED_FORMAT]: `Invalid linked format`,
  // parser error messages
  [CompileErrorCodes.MUST_HAVE_MESSAGES_IN_PLURAL]: `Plural must have messages`,
  [CompileErrorCodes.UNEXPECTED_EMPTY_LINKED_MODIFIER]: `Unexpected empty linked modifier`,
  [CompileErrorCodes.UNEXPECTED_EMPTY_LINKED_KEY]: `Unexpected empty linked key`,
  [CompileErrorCodes.UNEXPECTED_LEXICAL_ANALYSIS]: `Unexpected lexical analysis in token: '{0}'`,
  // generator error messages
  [CompileErrorCodes.UNHANDLED_CODEGEN_NODE_TYPE]: `unhandled codegen node type: '{0}'`,
  // minimizer error messages
  [CompileErrorCodes.UNHANDLED_MINIFIER_NODE_TYPE]: `unhandled mimifier node type: '{0}'`
};
function createCompileError(code2, loc, options = {}) {
  const { domain, messages, args } = options;
  const msg = process.env.NODE_ENV !== "production" ? format$1((messages || errorMessages$2)[code2] || "", ...args || []) : code2;
  const error = new SyntaxError(String(msg));
  error.code = code2;
  if (loc) {
    error.location = loc;
  }
  error.domain = domain;
  return error;
}
function defaultOnError(error) {
  throw error;
}
const RE_HTML_TAG = /<\/?[\w\s="/.':;#-\/]+>/;
const detectHtmlTag = (source) => RE_HTML_TAG.test(source);
const CHAR_SP = " ";
const CHAR_CR = "\r";
const CHAR_LF = "\n";
const CHAR_LS = String.fromCharCode(8232);
const CHAR_PS = String.fromCharCode(8233);
function createScanner(str) {
  const _buf = str;
  let _index = 0;
  let _line = 1;
  let _column = 1;
  let _peekOffset = 0;
  const isCRLF = (index2) => _buf[index2] === CHAR_CR && _buf[index2 + 1] === CHAR_LF;
  const isLF = (index2) => _buf[index2] === CHAR_LF;
  const isPS = (index2) => _buf[index2] === CHAR_PS;
  const isLS = (index2) => _buf[index2] === CHAR_LS;
  const isLineEnd = (index2) => isCRLF(index2) || isLF(index2) || isPS(index2) || isLS(index2);
  const index = () => _index;
  const line = () => _line;
  const column = () => _column;
  const peekOffset = () => _peekOffset;
  const charAt = (offset) => isCRLF(offset) || isPS(offset) || isLS(offset) ? CHAR_LF : _buf[offset];
  const currentChar = () => charAt(_index);
  const currentPeek = () => charAt(_index + _peekOffset);
  function next() {
    _peekOffset = 0;
    if (isLineEnd(_index)) {
      _line++;
      _column = 0;
    }
    if (isCRLF(_index)) {
      _index++;
    }
    _index++;
    _column++;
    return _buf[_index];
  }
  function peek() {
    if (isCRLF(_index + _peekOffset)) {
      _peekOffset++;
    }
    _peekOffset++;
    return _buf[_index + _peekOffset];
  }
  function reset() {
    _index = 0;
    _line = 1;
    _column = 1;
    _peekOffset = 0;
  }
  function resetPeek(offset = 0) {
    _peekOffset = offset;
  }
  function skipToPeek() {
    const target = _index + _peekOffset;
    while (target !== _index) {
      next();
    }
    _peekOffset = 0;
  }
  return {
    index,
    line,
    column,
    peekOffset,
    charAt,
    currentChar,
    currentPeek,
    next,
    peek,
    reset,
    resetPeek,
    skipToPeek
  };
}
const EOF = void 0;
const DOT = ".";
const LITERAL_DELIMITER = "'";
const ERROR_DOMAIN$3 = "tokenizer";
function createTokenizer(source, options = {}) {
  const location = options.location !== false;
  const _scnr = createScanner(source);
  const currentOffset = () => _scnr.index();
  const currentPosition = () => createPosition(_scnr.line(), _scnr.column(), _scnr.index());
  const _initLoc = currentPosition();
  const _initOffset = currentOffset();
  const _context = {
    currentType: 14,
    offset: _initOffset,
    startLoc: _initLoc,
    endLoc: _initLoc,
    lastType: 14,
    lastOffset: _initOffset,
    lastStartLoc: _initLoc,
    lastEndLoc: _initLoc,
    braceNest: 0,
    inLinked: false,
    text: ""
  };
  const context = () => _context;
  const { onError } = options;
  function emitError(code2, pos, offset, ...args) {
    const ctx = context();
    pos.column += offset;
    pos.offset += offset;
    if (onError) {
      const loc = location ? createLocation(ctx.startLoc, pos) : null;
      const err = createCompileError(code2, loc, {
        domain: ERROR_DOMAIN$3,
        args
      });
      onError(err);
    }
  }
  function getToken(context2, type, value) {
    context2.endLoc = currentPosition();
    context2.currentType = type;
    const token = { type };
    if (location) {
      token.loc = createLocation(context2.startLoc, context2.endLoc);
    }
    if (value != null) {
      token.value = value;
    }
    return token;
  }
  const getEndToken = (context2) => getToken(
    context2,
    14
    /* TokenTypes.EOF */
  );
  function eat(scnr, ch) {
    if (scnr.currentChar() === ch) {
      scnr.next();
      return ch;
    } else {
      emitError(CompileErrorCodes.EXPECTED_TOKEN, currentPosition(), 0, ch);
      return "";
    }
  }
  function peekSpaces(scnr) {
    let buf = "";
    while (scnr.currentPeek() === CHAR_SP || scnr.currentPeek() === CHAR_LF) {
      buf += scnr.currentPeek();
      scnr.peek();
    }
    return buf;
  }
  function skipSpaces(scnr) {
    const buf = peekSpaces(scnr);
    scnr.skipToPeek();
    return buf;
  }
  function isIdentifierStart(ch) {
    if (ch === EOF) {
      return false;
    }
    const cc = ch.charCodeAt(0);
    return cc >= 97 && cc <= 122 || // a-z
    cc >= 65 && cc <= 90 || // A-Z
    cc === 95;
  }
  function isNumberStart(ch) {
    if (ch === EOF) {
      return false;
    }
    const cc = ch.charCodeAt(0);
    return cc >= 48 && cc <= 57;
  }
  function isNamedIdentifierStart(scnr, context2) {
    const { currentType } = context2;
    if (currentType !== 2) {
      return false;
    }
    peekSpaces(scnr);
    const ret = isIdentifierStart(scnr.currentPeek());
    scnr.resetPeek();
    return ret;
  }
  function isListIdentifierStart(scnr, context2) {
    const { currentType } = context2;
    if (currentType !== 2) {
      return false;
    }
    peekSpaces(scnr);
    const ch = scnr.currentPeek() === "-" ? scnr.peek() : scnr.currentPeek();
    const ret = isNumberStart(ch);
    scnr.resetPeek();
    return ret;
  }
  function isLiteralStart(scnr, context2) {
    const { currentType } = context2;
    if (currentType !== 2) {
      return false;
    }
    peekSpaces(scnr);
    const ret = scnr.currentPeek() === LITERAL_DELIMITER;
    scnr.resetPeek();
    return ret;
  }
  function isLinkedDotStart(scnr, context2) {
    const { currentType } = context2;
    if (currentType !== 8) {
      return false;
    }
    peekSpaces(scnr);
    const ret = scnr.currentPeek() === ".";
    scnr.resetPeek();
    return ret;
  }
  function isLinkedModifierStart(scnr, context2) {
    const { currentType } = context2;
    if (currentType !== 9) {
      return false;
    }
    peekSpaces(scnr);
    const ret = isIdentifierStart(scnr.currentPeek());
    scnr.resetPeek();
    return ret;
  }
  function isLinkedDelimiterStart(scnr, context2) {
    const { currentType } = context2;
    if (!(currentType === 8 || currentType === 12)) {
      return false;
    }
    peekSpaces(scnr);
    const ret = scnr.currentPeek() === ":";
    scnr.resetPeek();
    return ret;
  }
  function isLinkedReferStart(scnr, context2) {
    const { currentType } = context2;
    if (currentType !== 10) {
      return false;
    }
    const fn = () => {
      const ch = scnr.currentPeek();
      if (ch === "{") {
        return isIdentifierStart(scnr.peek());
      } else if (ch === "@" || ch === "%" || ch === "|" || ch === ":" || ch === "." || ch === CHAR_SP || !ch) {
        return false;
      } else if (ch === CHAR_LF) {
        scnr.peek();
        return fn();
      } else {
        return isTextStart(scnr, false);
      }
    };
    const ret = fn();
    scnr.resetPeek();
    return ret;
  }
  function isPluralStart(scnr) {
    peekSpaces(scnr);
    const ret = scnr.currentPeek() === "|";
    scnr.resetPeek();
    return ret;
  }
  function detectModuloStart(scnr) {
    const spaces = peekSpaces(scnr);
    const ret = scnr.currentPeek() === "%" && scnr.peek() === "{";
    scnr.resetPeek();
    return {
      isModulo: ret,
      hasSpace: spaces.length > 0
    };
  }
  function isTextStart(scnr, reset = true) {
    const fn = (hasSpace = false, prev = "", detectModulo = false) => {
      const ch = scnr.currentPeek();
      if (ch === "{") {
        return prev === "%" ? false : hasSpace;
      } else if (ch === "@" || !ch) {
        return prev === "%" ? true : hasSpace;
      } else if (ch === "%") {
        scnr.peek();
        return fn(hasSpace, "%", true);
      } else if (ch === "|") {
        return prev === "%" || detectModulo ? true : !(prev === CHAR_SP || prev === CHAR_LF);
      } else if (ch === CHAR_SP) {
        scnr.peek();
        return fn(true, CHAR_SP, detectModulo);
      } else if (ch === CHAR_LF) {
        scnr.peek();
        return fn(true, CHAR_LF, detectModulo);
      } else {
        return true;
      }
    };
    const ret = fn();
    reset && scnr.resetPeek();
    return ret;
  }
  function takeChar(scnr, fn) {
    const ch = scnr.currentChar();
    if (ch === EOF) {
      return EOF;
    }
    if (fn(ch)) {
      scnr.next();
      return ch;
    }
    return null;
  }
  function isIdentifier(ch) {
    const cc = ch.charCodeAt(0);
    return cc >= 97 && cc <= 122 || // a-z
    cc >= 65 && cc <= 90 || // A-Z
    cc >= 48 && cc <= 57 || // 0-9
    cc === 95 || // _
    cc === 36;
  }
  function takeIdentifierChar(scnr) {
    return takeChar(scnr, isIdentifier);
  }
  function isNamedIdentifier(ch) {
    const cc = ch.charCodeAt(0);
    return cc >= 97 && cc <= 122 || // a-z
    cc >= 65 && cc <= 90 || // A-Z
    cc >= 48 && cc <= 57 || // 0-9
    cc === 95 || // _
    cc === 36 || // $
    cc === 45;
  }
  function takeNamedIdentifierChar(scnr) {
    return takeChar(scnr, isNamedIdentifier);
  }
  function isDigit(ch) {
    const cc = ch.charCodeAt(0);
    return cc >= 48 && cc <= 57;
  }
  function takeDigit(scnr) {
    return takeChar(scnr, isDigit);
  }
  function isHexDigit(ch) {
    const cc = ch.charCodeAt(0);
    return cc >= 48 && cc <= 57 || // 0-9
    cc >= 65 && cc <= 70 || // A-F
    cc >= 97 && cc <= 102;
  }
  function takeHexDigit(scnr) {
    return takeChar(scnr, isHexDigit);
  }
  function getDigits(scnr) {
    let ch = "";
    let num = "";
    while (ch = takeDigit(scnr)) {
      num += ch;
    }
    return num;
  }
  function readModulo(scnr) {
    skipSpaces(scnr);
    const ch = scnr.currentChar();
    if (ch !== "%") {
      emitError(CompileErrorCodes.EXPECTED_TOKEN, currentPosition(), 0, ch);
    }
    scnr.next();
    return "%";
  }
  function readText(scnr) {
    let buf = "";
    while (true) {
      const ch = scnr.currentChar();
      if (ch === "{" || ch === "}" || ch === "@" || ch === "|" || !ch) {
        break;
      } else if (ch === "%") {
        if (isTextStart(scnr)) {
          buf += ch;
          scnr.next();
        } else {
          break;
        }
      } else if (ch === CHAR_SP || ch === CHAR_LF) {
        if (isTextStart(scnr)) {
          buf += ch;
          scnr.next();
        } else if (isPluralStart(scnr)) {
          break;
        } else {
          buf += ch;
          scnr.next();
        }
      } else {
        buf += ch;
        scnr.next();
      }
    }
    return buf;
  }
  function readNamedIdentifier(scnr) {
    skipSpaces(scnr);
    let ch = "";
    let name = "";
    while (ch = takeNamedIdentifierChar(scnr)) {
      name += ch;
    }
    if (scnr.currentChar() === EOF) {
      emitError(CompileErrorCodes.UNTERMINATED_CLOSING_BRACE, currentPosition(), 0);
    }
    return name;
  }
  function readListIdentifier(scnr) {
    skipSpaces(scnr);
    let value = "";
    if (scnr.currentChar() === "-") {
      scnr.next();
      value += `-${getDigits(scnr)}`;
    } else {
      value += getDigits(scnr);
    }
    if (scnr.currentChar() === EOF) {
      emitError(CompileErrorCodes.UNTERMINATED_CLOSING_BRACE, currentPosition(), 0);
    }
    return value;
  }
  function isLiteral2(ch) {
    return ch !== LITERAL_DELIMITER && ch !== CHAR_LF;
  }
  function readLiteral(scnr) {
    skipSpaces(scnr);
    eat(scnr, `'`);
    let ch = "";
    let literal = "";
    while (ch = takeChar(scnr, isLiteral2)) {
      if (ch === "\\") {
        literal += readEscapeSequence(scnr);
      } else {
        literal += ch;
      }
    }
    const current = scnr.currentChar();
    if (current === CHAR_LF || current === EOF) {
      emitError(CompileErrorCodes.UNTERMINATED_SINGLE_QUOTE_IN_PLACEHOLDER, currentPosition(), 0);
      if (current === CHAR_LF) {
        scnr.next();
        eat(scnr, `'`);
      }
      return literal;
    }
    eat(scnr, `'`);
    return literal;
  }
  function readEscapeSequence(scnr) {
    const ch = scnr.currentChar();
    switch (ch) {
      case "\\":
      case `'`:
        scnr.next();
        return `\\${ch}`;
      case "u":
        return readUnicodeEscapeSequence(scnr, ch, 4);
      case "U":
        return readUnicodeEscapeSequence(scnr, ch, 6);
      default:
        emitError(CompileErrorCodes.UNKNOWN_ESCAPE_SEQUENCE, currentPosition(), 0, ch);
        return "";
    }
  }
  function readUnicodeEscapeSequence(scnr, unicode, digits) {
    eat(scnr, unicode);
    let sequence = "";
    for (let i = 0; i < digits; i++) {
      const ch = takeHexDigit(scnr);
      if (!ch) {
        emitError(CompileErrorCodes.INVALID_UNICODE_ESCAPE_SEQUENCE, currentPosition(), 0, `\\${unicode}${sequence}${scnr.currentChar()}`);
        break;
      }
      sequence += ch;
    }
    return `\\${unicode}${sequence}`;
  }
  function isInvalidIdentifier(ch) {
    return ch !== "{" && ch !== "}" && ch !== CHAR_SP && ch !== CHAR_LF;
  }
  function readInvalidIdentifier(scnr) {
    skipSpaces(scnr);
    let ch = "";
    let identifiers = "";
    while (ch = takeChar(scnr, isInvalidIdentifier)) {
      identifiers += ch;
    }
    return identifiers;
  }
  function readLinkedModifier(scnr) {
    let ch = "";
    let name = "";
    while (ch = takeIdentifierChar(scnr)) {
      name += ch;
    }
    return name;
  }
  function readLinkedRefer(scnr) {
    const fn = (buf) => {
      const ch = scnr.currentChar();
      if (ch === "{" || ch === "%" || ch === "@" || ch === "|" || ch === "(" || ch === ")" || !ch) {
        return buf;
      } else if (ch === CHAR_SP) {
        return buf;
      } else if (ch === CHAR_LF || ch === DOT) {
        buf += ch;
        scnr.next();
        return fn(buf);
      } else {
        buf += ch;
        scnr.next();
        return fn(buf);
      }
    };
    return fn("");
  }
  function readPlural(scnr) {
    skipSpaces(scnr);
    const plural = eat(
      scnr,
      "|"
      /* TokenChars.Pipe */
    );
    skipSpaces(scnr);
    return plural;
  }
  function readTokenInPlaceholder(scnr, context2) {
    let token = null;
    const ch = scnr.currentChar();
    switch (ch) {
      case "{":
        if (context2.braceNest >= 1) {
          emitError(CompileErrorCodes.NOT_ALLOW_NEST_PLACEHOLDER, currentPosition(), 0);
        }
        scnr.next();
        token = getToken(
          context2,
          2,
          "{"
          /* TokenChars.BraceLeft */
        );
        skipSpaces(scnr);
        context2.braceNest++;
        return token;
      case "}":
        if (context2.braceNest > 0 && context2.currentType === 2) {
          emitError(CompileErrorCodes.EMPTY_PLACEHOLDER, currentPosition(), 0);
        }
        scnr.next();
        token = getToken(
          context2,
          3,
          "}"
          /* TokenChars.BraceRight */
        );
        context2.braceNest--;
        context2.braceNest > 0 && skipSpaces(scnr);
        if (context2.inLinked && context2.braceNest === 0) {
          context2.inLinked = false;
        }
        return token;
      case "@":
        if (context2.braceNest > 0) {
          emitError(CompileErrorCodes.UNTERMINATED_CLOSING_BRACE, currentPosition(), 0);
        }
        token = readTokenInLinked(scnr, context2) || getEndToken(context2);
        context2.braceNest = 0;
        return token;
      default: {
        let validNamedIdentifier = true;
        let validListIdentifier = true;
        let validLiteral = true;
        if (isPluralStart(scnr)) {
          if (context2.braceNest > 0) {
            emitError(CompileErrorCodes.UNTERMINATED_CLOSING_BRACE, currentPosition(), 0);
          }
          token = getToken(context2, 1, readPlural(scnr));
          context2.braceNest = 0;
          context2.inLinked = false;
          return token;
        }
        if (context2.braceNest > 0 && (context2.currentType === 5 || context2.currentType === 6 || context2.currentType === 7)) {
          emitError(CompileErrorCodes.UNTERMINATED_CLOSING_BRACE, currentPosition(), 0);
          context2.braceNest = 0;
          return readToken(scnr, context2);
        }
        if (validNamedIdentifier = isNamedIdentifierStart(scnr, context2)) {
          token = getToken(context2, 5, readNamedIdentifier(scnr));
          skipSpaces(scnr);
          return token;
        }
        if (validListIdentifier = isListIdentifierStart(scnr, context2)) {
          token = getToken(context2, 6, readListIdentifier(scnr));
          skipSpaces(scnr);
          return token;
        }
        if (validLiteral = isLiteralStart(scnr, context2)) {
          token = getToken(context2, 7, readLiteral(scnr));
          skipSpaces(scnr);
          return token;
        }
        if (!validNamedIdentifier && !validListIdentifier && !validLiteral) {
          token = getToken(context2, 13, readInvalidIdentifier(scnr));
          emitError(CompileErrorCodes.INVALID_TOKEN_IN_PLACEHOLDER, currentPosition(), 0, token.value);
          skipSpaces(scnr);
          return token;
        }
        break;
      }
    }
    return token;
  }
  function readTokenInLinked(scnr, context2) {
    const { currentType } = context2;
    let token = null;
    const ch = scnr.currentChar();
    if ((currentType === 8 || currentType === 9 || currentType === 12 || currentType === 10) && (ch === CHAR_LF || ch === CHAR_SP)) {
      emitError(CompileErrorCodes.INVALID_LINKED_FORMAT, currentPosition(), 0);
    }
    switch (ch) {
      case "@":
        scnr.next();
        token = getToken(
          context2,
          8,
          "@"
          /* TokenChars.LinkedAlias */
        );
        context2.inLinked = true;
        return token;
      case ".":
        skipSpaces(scnr);
        scnr.next();
        return getToken(
          context2,
          9,
          "."
          /* TokenChars.LinkedDot */
        );
      case ":":
        skipSpaces(scnr);
        scnr.next();
        return getToken(
          context2,
          10,
          ":"
          /* TokenChars.LinkedDelimiter */
        );
      default:
        if (isPluralStart(scnr)) {
          token = getToken(context2, 1, readPlural(scnr));
          context2.braceNest = 0;
          context2.inLinked = false;
          return token;
        }
        if (isLinkedDotStart(scnr, context2) || isLinkedDelimiterStart(scnr, context2)) {
          skipSpaces(scnr);
          return readTokenInLinked(scnr, context2);
        }
        if (isLinkedModifierStart(scnr, context2)) {
          skipSpaces(scnr);
          return getToken(context2, 12, readLinkedModifier(scnr));
        }
        if (isLinkedReferStart(scnr, context2)) {
          skipSpaces(scnr);
          if (ch === "{") {
            return readTokenInPlaceholder(scnr, context2) || token;
          } else {
            return getToken(context2, 11, readLinkedRefer(scnr));
          }
        }
        if (currentType === 8) {
          emitError(CompileErrorCodes.INVALID_LINKED_FORMAT, currentPosition(), 0);
        }
        context2.braceNest = 0;
        context2.inLinked = false;
        return readToken(scnr, context2);
    }
  }
  function readToken(scnr, context2) {
    let token = {
      type: 14
      /* TokenTypes.EOF */
    };
    if (context2.braceNest > 0) {
      return readTokenInPlaceholder(scnr, context2) || getEndToken(context2);
    }
    if (context2.inLinked) {
      return readTokenInLinked(scnr, context2) || getEndToken(context2);
    }
    const ch = scnr.currentChar();
    switch (ch) {
      case "{":
        return readTokenInPlaceholder(scnr, context2) || getEndToken(context2);
      case "}":
        emitError(CompileErrorCodes.UNBALANCED_CLOSING_BRACE, currentPosition(), 0);
        scnr.next();
        return getToken(
          context2,
          3,
          "}"
          /* TokenChars.BraceRight */
        );
      case "@":
        return readTokenInLinked(scnr, context2) || getEndToken(context2);
      default: {
        if (isPluralStart(scnr)) {
          token = getToken(context2, 1, readPlural(scnr));
          context2.braceNest = 0;
          context2.inLinked = false;
          return token;
        }
        const { isModulo, hasSpace } = detectModuloStart(scnr);
        if (isModulo) {
          return hasSpace ? getToken(context2, 0, readText(scnr)) : getToken(context2, 4, readModulo(scnr));
        }
        if (isTextStart(scnr)) {
          return getToken(context2, 0, readText(scnr));
        }
        break;
      }
    }
    return token;
  }
  function nextToken() {
    const { currentType, offset, startLoc, endLoc } = _context;
    _context.lastType = currentType;
    _context.lastOffset = offset;
    _context.lastStartLoc = startLoc;
    _context.lastEndLoc = endLoc;
    _context.offset = currentOffset();
    _context.startLoc = currentPosition();
    if (_scnr.currentChar() === EOF) {
      return getToken(
        _context,
        14
        /* TokenTypes.EOF */
      );
    }
    return readToken(_scnr, _context);
  }
  return {
    nextToken,
    currentOffset,
    currentPosition,
    context
  };
}
const ERROR_DOMAIN$2 = "parser";
const KNOWN_ESCAPES = /(?:\\\\|\\'|\\u([0-9a-fA-F]{4})|\\U([0-9a-fA-F]{6}))/g;
function fromEscapeSequence(match, codePoint4, codePoint6) {
  switch (match) {
    case `\\\\`:
      return `\\`;
    // eslint-disable-next-line no-useless-escape
    case `\\'`:
      return `'`;
    default: {
      const codePoint = parseInt(codePoint4 || codePoint6, 16);
      if (codePoint <= 55295 || codePoint >= 57344) {
        return String.fromCodePoint(codePoint);
      }
      return "�";
    }
  }
}
function createParser(options = {}) {
  const location = options.location !== false;
  const { onError, onWarn } = options;
  function emitError(tokenzer, code2, start, offset, ...args) {
    const end = tokenzer.currentPosition();
    end.offset += offset;
    end.column += offset;
    if (onError) {
      const loc = location ? createLocation(start, end) : null;
      const err = createCompileError(code2, loc, {
        domain: ERROR_DOMAIN$2,
        args
      });
      onError(err);
    }
  }
  function emitWarn(tokenzer, code2, start, offset, ...args) {
    const end = tokenzer.currentPosition();
    end.offset += offset;
    end.column += offset;
    if (onWarn) {
      const loc = location ? createLocation(start, end) : null;
      onWarn(createCompileWarn(code2, loc, args));
    }
  }
  function startNode(type, offset, loc) {
    const node = { type };
    if (location) {
      node.start = offset;
      node.end = offset;
      node.loc = { start: loc, end: loc };
    }
    return node;
  }
  function endNode(node, offset, pos, type) {
    if (location) {
      node.end = offset;
      if (node.loc) {
        node.loc.end = pos;
      }
    }
  }
  function parseText(tokenizer, value) {
    const context = tokenizer.context();
    const node = startNode(3, context.offset, context.startLoc);
    node.value = value;
    endNode(node, tokenizer.currentOffset(), tokenizer.currentPosition());
    return node;
  }
  function parseList(tokenizer, index) {
    const context = tokenizer.context();
    const { lastOffset: offset, lastStartLoc: loc } = context;
    const node = startNode(5, offset, loc);
    node.index = parseInt(index, 10);
    tokenizer.nextToken();
    endNode(node, tokenizer.currentOffset(), tokenizer.currentPosition());
    return node;
  }
  function parseNamed(tokenizer, key, modulo) {
    const context = tokenizer.context();
    const { lastOffset: offset, lastStartLoc: loc } = context;
    const node = startNode(4, offset, loc);
    node.key = key;
    if (modulo === true) {
      node.modulo = true;
    }
    tokenizer.nextToken();
    endNode(node, tokenizer.currentOffset(), tokenizer.currentPosition());
    return node;
  }
  function parseLiteral(tokenizer, value) {
    const context = tokenizer.context();
    const { lastOffset: offset, lastStartLoc: loc } = context;
    const node = startNode(9, offset, loc);
    node.value = value.replace(KNOWN_ESCAPES, fromEscapeSequence);
    tokenizer.nextToken();
    endNode(node, tokenizer.currentOffset(), tokenizer.currentPosition());
    return node;
  }
  function parseLinkedModifier(tokenizer) {
    const token = tokenizer.nextToken();
    const context = tokenizer.context();
    const { lastOffset: offset, lastStartLoc: loc } = context;
    const node = startNode(8, offset, loc);
    if (token.type !== 12) {
      emitError(tokenizer, CompileErrorCodes.UNEXPECTED_EMPTY_LINKED_MODIFIER, context.lastStartLoc, 0);
      node.value = "";
      endNode(node, offset, loc);
      return {
        nextConsumeToken: token,
        node
      };
    }
    if (token.value == null) {
      emitError(tokenizer, CompileErrorCodes.UNEXPECTED_LEXICAL_ANALYSIS, context.lastStartLoc, 0, getTokenCaption(token));
    }
    node.value = token.value || "";
    endNode(node, tokenizer.currentOffset(), tokenizer.currentPosition());
    return {
      node
    };
  }
  function parseLinkedKey(tokenizer, value) {
    const context = tokenizer.context();
    const node = startNode(7, context.offset, context.startLoc);
    node.value = value;
    endNode(node, tokenizer.currentOffset(), tokenizer.currentPosition());
    return node;
  }
  function parseLinked(tokenizer) {
    const context = tokenizer.context();
    const linkedNode = startNode(6, context.offset, context.startLoc);
    let token = tokenizer.nextToken();
    if (token.type === 9) {
      const parsed = parseLinkedModifier(tokenizer);
      linkedNode.modifier = parsed.node;
      token = parsed.nextConsumeToken || tokenizer.nextToken();
    }
    if (token.type !== 10) {
      emitError(tokenizer, CompileErrorCodes.UNEXPECTED_LEXICAL_ANALYSIS, context.lastStartLoc, 0, getTokenCaption(token));
    }
    token = tokenizer.nextToken();
    if (token.type === 2) {
      token = tokenizer.nextToken();
    }
    switch (token.type) {
      case 11:
        if (token.value == null) {
          emitError(tokenizer, CompileErrorCodes.UNEXPECTED_LEXICAL_ANALYSIS, context.lastStartLoc, 0, getTokenCaption(token));
        }
        linkedNode.key = parseLinkedKey(tokenizer, token.value || "");
        break;
      case 5:
        if (token.value == null) {
          emitError(tokenizer, CompileErrorCodes.UNEXPECTED_LEXICAL_ANALYSIS, context.lastStartLoc, 0, getTokenCaption(token));
        }
        linkedNode.key = parseNamed(tokenizer, token.value || "");
        break;
      case 6:
        if (token.value == null) {
          emitError(tokenizer, CompileErrorCodes.UNEXPECTED_LEXICAL_ANALYSIS, context.lastStartLoc, 0, getTokenCaption(token));
        }
        linkedNode.key = parseList(tokenizer, token.value || "");
        break;
      case 7:
        if (token.value == null) {
          emitError(tokenizer, CompileErrorCodes.UNEXPECTED_LEXICAL_ANALYSIS, context.lastStartLoc, 0, getTokenCaption(token));
        }
        linkedNode.key = parseLiteral(tokenizer, token.value || "");
        break;
      default: {
        emitError(tokenizer, CompileErrorCodes.UNEXPECTED_EMPTY_LINKED_KEY, context.lastStartLoc, 0);
        const nextContext = tokenizer.context();
        const emptyLinkedKeyNode = startNode(7, nextContext.offset, nextContext.startLoc);
        emptyLinkedKeyNode.value = "";
        endNode(emptyLinkedKeyNode, nextContext.offset, nextContext.startLoc);
        linkedNode.key = emptyLinkedKeyNode;
        endNode(linkedNode, nextContext.offset, nextContext.startLoc);
        return {
          nextConsumeToken: token,
          node: linkedNode
        };
      }
    }
    endNode(linkedNode, tokenizer.currentOffset(), tokenizer.currentPosition());
    return {
      node: linkedNode
    };
  }
  function parseMessage(tokenizer) {
    const context = tokenizer.context();
    const startOffset = context.currentType === 1 ? tokenizer.currentOffset() : context.offset;
    const startLoc = context.currentType === 1 ? context.endLoc : context.startLoc;
    const node = startNode(2, startOffset, startLoc);
    node.items = [];
    let nextToken = null;
    let modulo = null;
    do {
      const token = nextToken || tokenizer.nextToken();
      nextToken = null;
      switch (token.type) {
        case 0:
          if (token.value == null) {
            emitError(tokenizer, CompileErrorCodes.UNEXPECTED_LEXICAL_ANALYSIS, context.lastStartLoc, 0, getTokenCaption(token));
          }
          node.items.push(parseText(tokenizer, token.value || ""));
          break;
        case 6:
          if (token.value == null) {
            emitError(tokenizer, CompileErrorCodes.UNEXPECTED_LEXICAL_ANALYSIS, context.lastStartLoc, 0, getTokenCaption(token));
          }
          node.items.push(parseList(tokenizer, token.value || ""));
          break;
        case 4:
          modulo = true;
          break;
        case 5:
          if (token.value == null) {
            emitError(tokenizer, CompileErrorCodes.UNEXPECTED_LEXICAL_ANALYSIS, context.lastStartLoc, 0, getTokenCaption(token));
          }
          node.items.push(parseNamed(tokenizer, token.value || "", !!modulo));
          if (modulo) {
            emitWarn(tokenizer, CompileWarnCodes.USE_MODULO_SYNTAX, context.lastStartLoc, 0, getTokenCaption(token));
            modulo = null;
          }
          break;
        case 7:
          if (token.value == null) {
            emitError(tokenizer, CompileErrorCodes.UNEXPECTED_LEXICAL_ANALYSIS, context.lastStartLoc, 0, getTokenCaption(token));
          }
          node.items.push(parseLiteral(tokenizer, token.value || ""));
          break;
        case 8: {
          const parsed = parseLinked(tokenizer);
          node.items.push(parsed.node);
          nextToken = parsed.nextConsumeToken || null;
          break;
        }
      }
    } while (context.currentType !== 14 && context.currentType !== 1);
    const endOffset = context.currentType === 1 ? context.lastOffset : tokenizer.currentOffset();
    const endLoc = context.currentType === 1 ? context.lastEndLoc : tokenizer.currentPosition();
    endNode(node, endOffset, endLoc);
    return node;
  }
  function parsePlural(tokenizer, offset, loc, msgNode) {
    const context = tokenizer.context();
    let hasEmptyMessage = msgNode.items.length === 0;
    const node = startNode(1, offset, loc);
    node.cases = [];
    node.cases.push(msgNode);
    do {
      const msg = parseMessage(tokenizer);
      if (!hasEmptyMessage) {
        hasEmptyMessage = msg.items.length === 0;
      }
      node.cases.push(msg);
    } while (context.currentType !== 14);
    if (hasEmptyMessage) {
      emitError(tokenizer, CompileErrorCodes.MUST_HAVE_MESSAGES_IN_PLURAL, loc, 0);
    }
    endNode(node, tokenizer.currentOffset(), tokenizer.currentPosition());
    return node;
  }
  function parseResource(tokenizer) {
    const context = tokenizer.context();
    const { offset, startLoc } = context;
    const msgNode = parseMessage(tokenizer);
    if (context.currentType === 14) {
      return msgNode;
    } else {
      return parsePlural(tokenizer, offset, startLoc, msgNode);
    }
  }
  function parse2(source) {
    const tokenizer = createTokenizer(source, assign({}, options));
    const context = tokenizer.context();
    const node = startNode(0, context.offset, context.startLoc);
    if (location && node.loc) {
      node.loc.source = source;
    }
    node.body = parseResource(tokenizer);
    if (options.onCacheKey) {
      node.cacheKey = options.onCacheKey(source);
    }
    if (context.currentType !== 14) {
      emitError(tokenizer, CompileErrorCodes.UNEXPECTED_LEXICAL_ANALYSIS, context.lastStartLoc, 0, source[context.offset] || "");
    }
    endNode(node, tokenizer.currentOffset(), tokenizer.currentPosition());
    return node;
  }
  return { parse: parse2 };
}
function getTokenCaption(token) {
  if (token.type === 14) {
    return "EOF";
  }
  const name = (token.value || "").replace(/\r?\n/gu, "\\n");
  return name.length > 10 ? name.slice(0, 9) + "…" : name;
}
function createTransformer(ast, options = {}) {
  const _context = {
    ast,
    helpers: /* @__PURE__ */ new Set()
  };
  const context = () => _context;
  const helper = (name) => {
    _context.helpers.add(name);
    return name;
  };
  return { context, helper };
}
function traverseNodes(nodes, transformer) {
  for (let i = 0; i < nodes.length; i++) {
    traverseNode(nodes[i], transformer);
  }
}
function traverseNode(node, transformer) {
  switch (node.type) {
    case 1:
      traverseNodes(node.cases, transformer);
      transformer.helper(
        "plural"
        /* HelperNameMap.PLURAL */
      );
      break;
    case 2:
      traverseNodes(node.items, transformer);
      break;
    case 6: {
      const linked = node;
      traverseNode(linked.key, transformer);
      transformer.helper(
        "linked"
        /* HelperNameMap.LINKED */
      );
      transformer.helper(
        "type"
        /* HelperNameMap.TYPE */
      );
      break;
    }
    case 5:
      transformer.helper(
        "interpolate"
        /* HelperNameMap.INTERPOLATE */
      );
      transformer.helper(
        "list"
        /* HelperNameMap.LIST */
      );
      break;
    case 4:
      transformer.helper(
        "interpolate"
        /* HelperNameMap.INTERPOLATE */
      );
      transformer.helper(
        "named"
        /* HelperNameMap.NAMED */
      );
      break;
  }
}
function transform(ast, options = {}) {
  const transformer = createTransformer(ast);
  transformer.helper(
    "normalize"
    /* HelperNameMap.NORMALIZE */
  );
  ast.body && traverseNode(ast.body, transformer);
  const context = transformer.context();
  ast.helpers = Array.from(context.helpers);
}
function optimize(ast) {
  const body = ast.body;
  if (body.type === 2) {
    optimizeMessageNode(body);
  } else {
    body.cases.forEach((c) => optimizeMessageNode(c));
  }
  return ast;
}
function optimizeMessageNode(message) {
  if (message.items.length === 1) {
    const item = message.items[0];
    if (item.type === 3 || item.type === 9) {
      message.static = item.value;
      delete item.value;
    }
  } else {
    const values = [];
    for (let i = 0; i < message.items.length; i++) {
      const item = message.items[i];
      if (!(item.type === 3 || item.type === 9)) {
        break;
      }
      if (item.value == null) {
        break;
      }
      values.push(item.value);
    }
    if (values.length === message.items.length) {
      message.static = join(values);
      for (let i = 0; i < message.items.length; i++) {
        const item = message.items[i];
        if (item.type === 3 || item.type === 9) {
          delete item.value;
        }
      }
    }
  }
}
const ERROR_DOMAIN$1 = "minifier";
function minify(node) {
  node.t = node.type;
  switch (node.type) {
    case 0: {
      const resource2 = node;
      minify(resource2.body);
      resource2.b = resource2.body;
      delete resource2.body;
      break;
    }
    case 1: {
      const plural = node;
      const cases = plural.cases;
      for (let i = 0; i < cases.length; i++) {
        minify(cases[i]);
      }
      plural.c = cases;
      delete plural.cases;
      break;
    }
    case 2: {
      const message = node;
      const items = message.items;
      for (let i = 0; i < items.length; i++) {
        minify(items[i]);
      }
      message.i = items;
      delete message.items;
      if (message.static) {
        message.s = message.static;
        delete message.static;
      }
      break;
    }
    case 3:
    case 9:
    case 8:
    case 7: {
      const valueNode = node;
      if (valueNode.value) {
        valueNode.v = valueNode.value;
        delete valueNode.value;
      }
      break;
    }
    case 6: {
      const linked = node;
      minify(linked.key);
      linked.k = linked.key;
      delete linked.key;
      if (linked.modifier) {
        minify(linked.modifier);
        linked.m = linked.modifier;
        delete linked.modifier;
      }
      break;
    }
    case 5: {
      const list = node;
      list.i = list.index;
      delete list.index;
      break;
    }
    case 4: {
      const named = node;
      named.k = named.key;
      delete named.key;
      break;
    }
    default:
      if (process.env.NODE_ENV !== "production") {
        throw createCompileError(CompileErrorCodes.UNHANDLED_MINIFIER_NODE_TYPE, null, {
          domain: ERROR_DOMAIN$1,
          args: [node.type]
        });
      }
  }
  delete node.type;
}
const ERROR_DOMAIN = "parser";
function createCodeGenerator(ast, options) {
  const { filename, breakLineCode, needIndent: _needIndent } = options;
  const location = options.location !== false;
  const _context = {
    filename,
    code: "",
    column: 1,
    line: 1,
    offset: 0,
    map: void 0,
    breakLineCode,
    needIndent: _needIndent,
    indentLevel: 0
  };
  if (location && ast.loc) {
    _context.source = ast.loc.source;
  }
  const context = () => _context;
  function push(code2, node) {
    _context.code += code2;
  }
  function _newline(n, withBreakLine = true) {
    const _breakLineCode = withBreakLine ? breakLineCode : "";
    push(_needIndent ? _breakLineCode + `  `.repeat(n) : _breakLineCode);
  }
  function indent(withNewLine = true) {
    const level = ++_context.indentLevel;
    withNewLine && _newline(level);
  }
  function deindent(withNewLine = true) {
    const level = --_context.indentLevel;
    withNewLine && _newline(level);
  }
  function newline() {
    _newline(_context.indentLevel);
  }
  const helper = (key) => `_${key}`;
  const needIndent = () => _context.needIndent;
  return {
    context,
    push,
    indent,
    deindent,
    newline,
    helper,
    needIndent
  };
}
function generateLinkedNode(generator, node) {
  const { helper } = generator;
  generator.push(`${helper(
    "linked"
    /* HelperNameMap.LINKED */
  )}(`);
  generateNode(generator, node.key);
  if (node.modifier) {
    generator.push(`, `);
    generateNode(generator, node.modifier);
    generator.push(`, _type`);
  } else {
    generator.push(`, undefined, _type`);
  }
  generator.push(`)`);
}
function generateMessageNode(generator, node) {
  const { helper, needIndent } = generator;
  generator.push(`${helper(
    "normalize"
    /* HelperNameMap.NORMALIZE */
  )}([`);
  generator.indent(needIndent());
  const length = node.items.length;
  for (let i = 0; i < length; i++) {
    generateNode(generator, node.items[i]);
    if (i === length - 1) {
      break;
    }
    generator.push(", ");
  }
  generator.deindent(needIndent());
  generator.push("])");
}
function generatePluralNode(generator, node) {
  const { helper, needIndent } = generator;
  if (node.cases.length > 1) {
    generator.push(`${helper(
      "plural"
      /* HelperNameMap.PLURAL */
    )}([`);
    generator.indent(needIndent());
    const length = node.cases.length;
    for (let i = 0; i < length; i++) {
      generateNode(generator, node.cases[i]);
      if (i === length - 1) {
        break;
      }
      generator.push(", ");
    }
    generator.deindent(needIndent());
    generator.push(`])`);
  }
}
function generateResource(generator, node) {
  if (node.body) {
    generateNode(generator, node.body);
  } else {
    generator.push("null");
  }
}
function generateNode(generator, node) {
  const { helper } = generator;
  switch (node.type) {
    case 0:
      generateResource(generator, node);
      break;
    case 1:
      generatePluralNode(generator, node);
      break;
    case 2:
      generateMessageNode(generator, node);
      break;
    case 6:
      generateLinkedNode(generator, node);
      break;
    case 8:
      generator.push(JSON.stringify(node.value), node);
      break;
    case 7:
      generator.push(JSON.stringify(node.value), node);
      break;
    case 5:
      generator.push(`${helper(
        "interpolate"
        /* HelperNameMap.INTERPOLATE */
      )}(${helper(
        "list"
        /* HelperNameMap.LIST */
      )}(${node.index}))`, node);
      break;
    case 4:
      generator.push(`${helper(
        "interpolate"
        /* HelperNameMap.INTERPOLATE */
      )}(${helper(
        "named"
        /* HelperNameMap.NAMED */
      )}(${JSON.stringify(node.key)}))`, node);
      break;
    case 9:
      generator.push(JSON.stringify(node.value), node);
      break;
    case 3:
      generator.push(JSON.stringify(node.value), node);
      break;
    default:
      if (process.env.NODE_ENV !== "production") {
        throw createCompileError(CompileErrorCodes.UNHANDLED_CODEGEN_NODE_TYPE, null, {
          domain: ERROR_DOMAIN,
          args: [node.type]
        });
      }
  }
}
const generate = (ast, options = {}) => {
  const mode = isString(options.mode) ? options.mode : "normal";
  const filename = isString(options.filename) ? options.filename : "message.intl";
  !!options.sourceMap;
  const breakLineCode = options.breakLineCode != null ? options.breakLineCode : mode === "arrow" ? ";" : "\n";
  const needIndent = options.needIndent ? options.needIndent : mode !== "arrow";
  const helpers = ast.helpers || [];
  const generator = createCodeGenerator(ast, {
    filename,
    breakLineCode,
    needIndent
  });
  generator.push(mode === "normal" ? `function __msg__ (ctx) {` : `(ctx) => {`);
  generator.indent(needIndent);
  if (helpers.length > 0) {
    generator.push(`const { ${join(helpers.map((s) => `${s}: _${s}`), ", ")} } = ctx`);
    generator.newline();
  }
  generator.push(`return `);
  generateNode(generator, ast);
  generator.deindent(needIndent);
  generator.push(`}`);
  delete ast.helpers;
  const { code: code2, map } = generator.context();
  return {
    ast,
    code: code2,
    map: map ? map.toJSON() : void 0
    // eslint-disable-line @typescript-eslint/no-explicit-any
  };
};
function baseCompile$1(source, options = {}) {
  const assignedOptions = assign({}, options);
  const jit = !!assignedOptions.jit;
  const enalbeMinify = !!assignedOptions.minify;
  const enambeOptimize = assignedOptions.optimize == null ? true : assignedOptions.optimize;
  const parser = createParser(assignedOptions);
  const ast = parser.parse(source);
  if (!jit) {
    transform(ast, assignedOptions);
    return generate(ast, assignedOptions);
  } else {
    enambeOptimize && optimize(ast);
    enalbeMinify && minify(ast);
    return { ast, code: "" };
  }
}
/*!
  * core-base v9.14.5
  * (c) 2025 kazuya kawaguchi
  * Released under the MIT License.
  */
function initFeatureFlags$1() {
  if (typeof __INTLIFY_PROD_DEVTOOLS__ !== "boolean") {
    getGlobalThis().__INTLIFY_PROD_DEVTOOLS__ = false;
  }
}
function isMessageAST(val) {
  return isObject(val) && resolveType(val) === 0 && (hasOwn(val, "b") || hasOwn(val, "body"));
}
const PROPS_BODY = ["b", "body"];
function resolveBody(node) {
  return resolveProps(node, PROPS_BODY);
}
const PROPS_CASES = ["c", "cases"];
function resolveCases(node) {
  return resolveProps(node, PROPS_CASES, []);
}
const PROPS_STATIC = ["s", "static"];
function resolveStatic(node) {
  return resolveProps(node, PROPS_STATIC);
}
const PROPS_ITEMS = ["i", "items"];
function resolveItems(node) {
  return resolveProps(node, PROPS_ITEMS, []);
}
const PROPS_TYPE = ["t", "type"];
function resolveType(node) {
  return resolveProps(node, PROPS_TYPE);
}
const PROPS_VALUE = ["v", "value"];
function resolveValue$1(node, type) {
  const resolved = resolveProps(node, PROPS_VALUE);
  if (resolved != null) {
    return resolved;
  } else {
    throw createUnhandleNodeError(type);
  }
}
const PROPS_MODIFIER = ["m", "modifier"];
function resolveLinkedModifier(node) {
  return resolveProps(node, PROPS_MODIFIER);
}
const PROPS_KEY = ["k", "key"];
function resolveLinkedKey(node) {
  const resolved = resolveProps(node, PROPS_KEY);
  if (resolved) {
    return resolved;
  } else {
    throw createUnhandleNodeError(
      6
      /* NodeTypes.Linked */
    );
  }
}
function resolveProps(node, props, defaultValue) {
  for (let i = 0; i < props.length; i++) {
    const prop = props[i];
    if (hasOwn(node, prop) && node[prop] != null) {
      return node[prop];
    }
  }
  return defaultValue;
}
const AST_NODE_PROPS_KEYS = [
  ...PROPS_BODY,
  ...PROPS_CASES,
  ...PROPS_STATIC,
  ...PROPS_ITEMS,
  ...PROPS_KEY,
  ...PROPS_MODIFIER,
  ...PROPS_VALUE,
  ...PROPS_TYPE
];
function createUnhandleNodeError(type) {
  return new Error(`unhandled node type: ${type}`);
}
const pathStateMachine = [];
pathStateMachine[
  0
  /* States.BEFORE_PATH */
] = {
  [
    "w"
    /* PathCharTypes.WORKSPACE */
  ]: [
    0
    /* States.BEFORE_PATH */
  ],
  [
    "i"
    /* PathCharTypes.IDENT */
  ]: [
    3,
    0
    /* Actions.APPEND */
  ],
  [
    "["
    /* PathCharTypes.LEFT_BRACKET */
  ]: [
    4
    /* States.IN_SUB_PATH */
  ],
  [
    "o"
    /* PathCharTypes.END_OF_FAIL */
  ]: [
    7
    /* States.AFTER_PATH */
  ]
};
pathStateMachine[
  1
  /* States.IN_PATH */
] = {
  [
    "w"
    /* PathCharTypes.WORKSPACE */
  ]: [
    1
    /* States.IN_PATH */
  ],
  [
    "."
    /* PathCharTypes.DOT */
  ]: [
    2
    /* States.BEFORE_IDENT */
  ],
  [
    "["
    /* PathCharTypes.LEFT_BRACKET */
  ]: [
    4
    /* States.IN_SUB_PATH */
  ],
  [
    "o"
    /* PathCharTypes.END_OF_FAIL */
  ]: [
    7
    /* States.AFTER_PATH */
  ]
};
pathStateMachine[
  2
  /* States.BEFORE_IDENT */
] = {
  [
    "w"
    /* PathCharTypes.WORKSPACE */
  ]: [
    2
    /* States.BEFORE_IDENT */
  ],
  [
    "i"
    /* PathCharTypes.IDENT */
  ]: [
    3,
    0
    /* Actions.APPEND */
  ],
  [
    "0"
    /* PathCharTypes.ZERO */
  ]: [
    3,
    0
    /* Actions.APPEND */
  ]
};
pathStateMachine[
  3
  /* States.IN_IDENT */
] = {
  [
    "i"
    /* PathCharTypes.IDENT */
  ]: [
    3,
    0
    /* Actions.APPEND */
  ],
  [
    "0"
    /* PathCharTypes.ZERO */
  ]: [
    3,
    0
    /* Actions.APPEND */
  ],
  [
    "w"
    /* PathCharTypes.WORKSPACE */
  ]: [
    1,
    1
    /* Actions.PUSH */
  ],
  [
    "."
    /* PathCharTypes.DOT */
  ]: [
    2,
    1
    /* Actions.PUSH */
  ],
  [
    "["
    /* PathCharTypes.LEFT_BRACKET */
  ]: [
    4,
    1
    /* Actions.PUSH */
  ],
  [
    "o"
    /* PathCharTypes.END_OF_FAIL */
  ]: [
    7,
    1
    /* Actions.PUSH */
  ]
};
pathStateMachine[
  4
  /* States.IN_SUB_PATH */
] = {
  [
    "'"
    /* PathCharTypes.SINGLE_QUOTE */
  ]: [
    5,
    0
    /* Actions.APPEND */
  ],
  [
    '"'
    /* PathCharTypes.DOUBLE_QUOTE */
  ]: [
    6,
    0
    /* Actions.APPEND */
  ],
  [
    "["
    /* PathCharTypes.LEFT_BRACKET */
  ]: [
    4,
    2
    /* Actions.INC_SUB_PATH_DEPTH */
  ],
  [
    "]"
    /* PathCharTypes.RIGHT_BRACKET */
  ]: [
    1,
    3
    /* Actions.PUSH_SUB_PATH */
  ],
  [
    "o"
    /* PathCharTypes.END_OF_FAIL */
  ]: 8,
  [
    "l"
    /* PathCharTypes.ELSE */
  ]: [
    4,
    0
    /* Actions.APPEND */
  ]
};
pathStateMachine[
  5
  /* States.IN_SINGLE_QUOTE */
] = {
  [
    "'"
    /* PathCharTypes.SINGLE_QUOTE */
  ]: [
    4,
    0
    /* Actions.APPEND */
  ],
  [
    "o"
    /* PathCharTypes.END_OF_FAIL */
  ]: 8,
  [
    "l"
    /* PathCharTypes.ELSE */
  ]: [
    5,
    0
    /* Actions.APPEND */
  ]
};
pathStateMachine[
  6
  /* States.IN_DOUBLE_QUOTE */
] = {
  [
    '"'
    /* PathCharTypes.DOUBLE_QUOTE */
  ]: [
    4,
    0
    /* Actions.APPEND */
  ],
  [
    "o"
    /* PathCharTypes.END_OF_FAIL */
  ]: 8,
  [
    "l"
    /* PathCharTypes.ELSE */
  ]: [
    6,
    0
    /* Actions.APPEND */
  ]
};
const literalValueRE = /^\s?(?:true|false|-?[\d.]+|'[^']*'|"[^"]*")\s?$/;
function isLiteral(exp) {
  return literalValueRE.test(exp);
}
function stripQuotes(str) {
  const a = str.charCodeAt(0);
  const b = str.charCodeAt(str.length - 1);
  return a === b && (a === 34 || a === 39) ? str.slice(1, -1) : str;
}
function getPathCharType(ch) {
  if (ch === void 0 || ch === null) {
    return "o";
  }
  const code2 = ch.charCodeAt(0);
  switch (code2) {
    case 91:
    // [
    case 93:
    // ]
    case 46:
    // .
    case 34:
    // "
    case 39:
      return ch;
    case 95:
    // _
    case 36:
    // $
    case 45:
      return "i";
    case 9:
    // Tab (HT)
    case 10:
    // Newline (LF)
    case 13:
    // Return (CR)
    case 160:
    // No-break space (NBSP)
    case 65279:
    // Byte Order Mark (BOM)
    case 8232:
    // Line Separator (LS)
    case 8233:
      return "w";
  }
  return "i";
}
function formatSubPath(path) {
  const trimmed = path.trim();
  if (path.charAt(0) === "0" && isNaN(parseInt(path))) {
    return false;
  }
  return isLiteral(trimmed) ? stripQuotes(trimmed) : "*" + trimmed;
}
function parse(path) {
  const keys = [];
  let index = -1;
  let mode = 0;
  let subPathDepth = 0;
  let c;
  let key;
  let newChar;
  let type;
  let transition;
  let action;
  let typeMap;
  const actions = [];
  actions[
    0
    /* Actions.APPEND */
  ] = () => {
    if (key === void 0) {
      key = newChar;
    } else {
      key += newChar;
    }
  };
  actions[
    1
    /* Actions.PUSH */
  ] = () => {
    if (key !== void 0) {
      keys.push(key);
      key = void 0;
    }
  };
  actions[
    2
    /* Actions.INC_SUB_PATH_DEPTH */
  ] = () => {
    actions[
      0
      /* Actions.APPEND */
    ]();
    subPathDepth++;
  };
  actions[
    3
    /* Actions.PUSH_SUB_PATH */
  ] = () => {
    if (subPathDepth > 0) {
      subPathDepth--;
      mode = 4;
      actions[
        0
        /* Actions.APPEND */
      ]();
    } else {
      subPathDepth = 0;
      if (key === void 0) {
        return false;
      }
      key = formatSubPath(key);
      if (key === false) {
        return false;
      } else {
        actions[
          1
          /* Actions.PUSH */
        ]();
      }
    }
  };
  function maybeUnescapeQuote() {
    const nextChar = path[index + 1];
    if (mode === 5 && nextChar === "'" || mode === 6 && nextChar === '"') {
      index++;
      newChar = "\\" + nextChar;
      actions[
        0
        /* Actions.APPEND */
      ]();
      return true;
    }
  }
  while (mode !== null) {
    index++;
    c = path[index];
    if (c === "\\" && maybeUnescapeQuote()) {
      continue;
    }
    type = getPathCharType(c);
    typeMap = pathStateMachine[mode];
    transition = typeMap[type] || typeMap[
      "l"
      /* PathCharTypes.ELSE */
    ] || 8;
    if (transition === 8) {
      return;
    }
    mode = transition[0];
    if (transition[1] !== void 0) {
      action = actions[transition[1]];
      if (action) {
        newChar = c;
        if (action() === false) {
          return;
        }
      }
    }
    if (mode === 7) {
      return keys;
    }
  }
}
const cache = /* @__PURE__ */ new Map();
function resolveWithKeyValue(obj, path) {
  return isObject(obj) ? obj[path] : null;
}
function resolveValue(obj, path) {
  if (!isObject(obj)) {
    return null;
  }
  let hit = cache.get(path);
  if (!hit) {
    hit = parse(path);
    if (hit) {
      cache.set(path, hit);
    }
  }
  if (!hit) {
    return null;
  }
  const len = hit.length;
  let last = obj;
  let i = 0;
  while (i < len) {
    const key = hit[i];
    if (AST_NODE_PROPS_KEYS.includes(key) && isMessageAST(last)) {
      return null;
    }
    const val = last[key];
    if (val === void 0) {
      return null;
    }
    if (isFunction(last)) {
      return null;
    }
    last = val;
    i++;
  }
  return last;
}
const DEFAULT_MODIFIER = (str) => str;
const DEFAULT_MESSAGE = (ctx) => "";
const DEFAULT_MESSAGE_DATA_TYPE = "text";
const DEFAULT_NORMALIZE = (values) => values.length === 0 ? "" : join(values);
const DEFAULT_INTERPOLATE = toDisplayString;
function pluralDefault(choice, choicesLength) {
  choice = Math.abs(choice);
  if (choicesLength === 2) {
    return choice ? choice > 1 ? 1 : 0 : 1;
  }
  return choice ? Math.min(choice, 2) : 0;
}
function getPluralIndex(options) {
  const index = isNumber(options.pluralIndex) ? options.pluralIndex : -1;
  return options.named && (isNumber(options.named.count) || isNumber(options.named.n)) ? isNumber(options.named.count) ? options.named.count : isNumber(options.named.n) ? options.named.n : index : index;
}
function normalizeNamed(pluralIndex, props) {
  if (!props.count) {
    props.count = pluralIndex;
  }
  if (!props.n) {
    props.n = pluralIndex;
  }
}
function createMessageContext(options = {}) {
  const locale = options.locale;
  const pluralIndex = getPluralIndex(options);
  const pluralRule = isObject(options.pluralRules) && isString(locale) && isFunction(options.pluralRules[locale]) ? options.pluralRules[locale] : pluralDefault;
  const orgPluralRule = isObject(options.pluralRules) && isString(locale) && isFunction(options.pluralRules[locale]) ? pluralDefault : void 0;
  const plural = (messages) => {
    return messages[pluralRule(pluralIndex, messages.length, orgPluralRule)];
  };
  const _list = options.list || [];
  const list = (index) => _list[index];
  const _named = options.named || create();
  isNumber(options.pluralIndex) && normalizeNamed(pluralIndex, _named);
  const named = (key) => _named[key];
  function message(key) {
    const msg = isFunction(options.messages) ? options.messages(key) : isObject(options.messages) ? options.messages[key] : false;
    return !msg ? options.parent ? options.parent.message(key) : DEFAULT_MESSAGE : msg;
  }
  const _modifier = (name) => options.modifiers ? options.modifiers[name] : DEFAULT_MODIFIER;
  const normalize = isPlainObject(options.processor) && isFunction(options.processor.normalize) ? options.processor.normalize : DEFAULT_NORMALIZE;
  const interpolate = isPlainObject(options.processor) && isFunction(options.processor.interpolate) ? options.processor.interpolate : DEFAULT_INTERPOLATE;
  const type = isPlainObject(options.processor) && isString(options.processor.type) ? options.processor.type : DEFAULT_MESSAGE_DATA_TYPE;
  const linked = (key, ...args) => {
    const [arg1, arg2] = args;
    let type2 = "text";
    let modifier = "";
    if (args.length === 1) {
      if (isObject(arg1)) {
        modifier = arg1.modifier || modifier;
        type2 = arg1.type || type2;
      } else if (isString(arg1)) {
        modifier = arg1 || modifier;
      }
    } else if (args.length === 2) {
      if (isString(arg1)) {
        modifier = arg1 || modifier;
      }
      if (isString(arg2)) {
        type2 = arg2 || type2;
      }
    }
    const ret = message(key)(ctx);
    const msg = (
      // The message in vnode resolved with linked are returned as an array by processor.nomalize
      type2 === "vnode" && isArray(ret) && modifier ? ret[0] : ret
    );
    return modifier ? _modifier(modifier)(msg, type2) : msg;
  };
  const ctx = {
    [
      "list"
      /* HelperNameMap.LIST */
    ]: list,
    [
      "named"
      /* HelperNameMap.NAMED */
    ]: named,
    [
      "plural"
      /* HelperNameMap.PLURAL */
    ]: plural,
    [
      "linked"
      /* HelperNameMap.LINKED */
    ]: linked,
    [
      "message"
      /* HelperNameMap.MESSAGE */
    ]: message,
    [
      "type"
      /* HelperNameMap.TYPE */
    ]: type,
    [
      "interpolate"
      /* HelperNameMap.INTERPOLATE */
    ]: interpolate,
    [
      "normalize"
      /* HelperNameMap.NORMALIZE */
    ]: normalize,
    [
      "values"
      /* HelperNameMap.VALUES */
    ]: assign(create(), _list, _named)
  };
  return ctx;
}
let devtools = null;
function setDevToolsHook(hook) {
  devtools = hook;
}
function initI18nDevTools(i18n, version, meta) {
  devtools && devtools.emit("i18n:init", {
    timestamp: Date.now(),
    i18n,
    version,
    meta
  });
}
const translateDevTools = /* @__PURE__ */ createDevToolsHook(
  "function:translate"
  /* IntlifyDevToolsHooks.FunctionTranslate */
);
function createDevToolsHook(hook) {
  return (payloads) => devtools && devtools.emit(hook, payloads);
}
const code$1$1 = CompileWarnCodes.__EXTEND_POINT__;
const inc$1$1 = incrementer(code$1$1);
const CoreWarnCodes = {
  NOT_FOUND_KEY: code$1$1,
  // 2
  FALLBACK_TO_TRANSLATE: inc$1$1(),
  // 3
  CANNOT_FORMAT_NUMBER: inc$1$1(),
  // 4
  FALLBACK_TO_NUMBER_FORMAT: inc$1$1(),
  // 5
  CANNOT_FORMAT_DATE: inc$1$1(),
  // 6
  FALLBACK_TO_DATE_FORMAT: inc$1$1(),
  // 7
  EXPERIMENTAL_CUSTOM_MESSAGE_COMPILER: inc$1$1(),
  // 8
  __EXTEND_POINT__: inc$1$1()
  // 9
};
const warnMessages$1 = {
  [CoreWarnCodes.NOT_FOUND_KEY]: `Not found '{key}' key in '{locale}' locale messages.`,
  [CoreWarnCodes.FALLBACK_TO_TRANSLATE]: `Fall back to translate '{key}' key with '{target}' locale.`,
  [CoreWarnCodes.CANNOT_FORMAT_NUMBER]: `Cannot format a number value due to not supported Intl.NumberFormat.`,
  [CoreWarnCodes.FALLBACK_TO_NUMBER_FORMAT]: `Fall back to number format '{key}' key with '{target}' locale.`,
  [CoreWarnCodes.CANNOT_FORMAT_DATE]: `Cannot format a date value due to not supported Intl.DateTimeFormat.`,
  [CoreWarnCodes.FALLBACK_TO_DATE_FORMAT]: `Fall back to datetime format '{key}' key with '{target}' locale.`,
  [CoreWarnCodes.EXPERIMENTAL_CUSTOM_MESSAGE_COMPILER]: `This project is using Custom Message Compiler, which is an experimental feature. It may receive breaking changes or be removed in the future.`
};
function getWarnMessage$1(code2, ...args) {
  return format$1(warnMessages$1[code2], ...args);
}
const code$2 = CompileErrorCodes.__EXTEND_POINT__;
const inc$2 = incrementer(code$2);
const CoreErrorCodes = {
  INVALID_ARGUMENT: code$2,
  // 17
  INVALID_DATE_ARGUMENT: inc$2(),
  // 18
  INVALID_ISO_DATE_ARGUMENT: inc$2(),
  // 19
  NOT_SUPPORT_NON_STRING_MESSAGE: inc$2(),
  // 20
  NOT_SUPPORT_LOCALE_PROMISE_VALUE: inc$2(),
  // 21
  NOT_SUPPORT_LOCALE_ASYNC_FUNCTION: inc$2(),
  // 22
  NOT_SUPPORT_LOCALE_TYPE: inc$2(),
  // 23
  __EXTEND_POINT__: inc$2()
  // 24
};
function createCoreError(code2) {
  return createCompileError(code2, null, process.env.NODE_ENV !== "production" ? { messages: errorMessages$1 } : void 0);
}
const errorMessages$1 = {
  [CoreErrorCodes.INVALID_ARGUMENT]: "Invalid arguments",
  [CoreErrorCodes.INVALID_DATE_ARGUMENT]: "The date provided is an invalid Date object.Make sure your Date represents a valid date.",
  [CoreErrorCodes.INVALID_ISO_DATE_ARGUMENT]: "The argument provided is not a valid ISO date string",
  [CoreErrorCodes.NOT_SUPPORT_NON_STRING_MESSAGE]: "Not support non-string message",
  [CoreErrorCodes.NOT_SUPPORT_LOCALE_PROMISE_VALUE]: "cannot support promise value",
  [CoreErrorCodes.NOT_SUPPORT_LOCALE_ASYNC_FUNCTION]: "cannot support async function",
  [CoreErrorCodes.NOT_SUPPORT_LOCALE_TYPE]: "cannot support locale type"
};
function getLocale(context, options) {
  return options.locale != null ? resolveLocale(options.locale) : resolveLocale(context.locale);
}
let _resolveLocale;
function resolveLocale(locale) {
  if (isString(locale)) {
    return locale;
  } else {
    if (isFunction(locale)) {
      if (locale.resolvedOnce && _resolveLocale != null) {
        return _resolveLocale;
      } else if (locale.constructor.name === "Function") {
        const resolve2 = locale();
        if (isPromise(resolve2)) {
          throw createCoreError(CoreErrorCodes.NOT_SUPPORT_LOCALE_PROMISE_VALUE);
        }
        return _resolveLocale = resolve2;
      } else {
        throw createCoreError(CoreErrorCodes.NOT_SUPPORT_LOCALE_ASYNC_FUNCTION);
      }
    } else {
      throw createCoreError(CoreErrorCodes.NOT_SUPPORT_LOCALE_TYPE);
    }
  }
}
function fallbackWithSimple(ctx, fallback, start) {
  return [.../* @__PURE__ */ new Set([
    start,
    ...isArray(fallback) ? fallback : isObject(fallback) ? Object.keys(fallback) : isString(fallback) ? [fallback] : [start]
  ])];
}
function fallbackWithLocaleChain(ctx, fallback, start) {
  const startLocale = isString(start) ? start : DEFAULT_LOCALE;
  const context = ctx;
  if (!context.__localeChainCache) {
    context.__localeChainCache = /* @__PURE__ */ new Map();
  }
  let chain = context.__localeChainCache.get(startLocale);
  if (!chain) {
    chain = [];
    let block = [start];
    while (isArray(block)) {
      block = appendBlockToChain(chain, block, fallback);
    }
    const defaults = isArray(fallback) || !isPlainObject(fallback) ? fallback : fallback["default"] ? fallback["default"] : null;
    block = isString(defaults) ? [defaults] : defaults;
    if (isArray(block)) {
      appendBlockToChain(chain, block, false);
    }
    context.__localeChainCache.set(startLocale, chain);
  }
  return chain;
}
function appendBlockToChain(chain, block, blocks) {
  let follow = true;
  for (let i = 0; i < block.length && isBoolean(follow); i++) {
    const locale = block[i];
    if (isString(locale)) {
      follow = appendLocaleToChain(chain, block[i], blocks);
    }
  }
  return follow;
}
function appendLocaleToChain(chain, locale, blocks) {
  let follow;
  const tokens = locale.split("-");
  do {
    const target = tokens.join("-");
    follow = appendItemToChain(chain, target, blocks);
    tokens.splice(-1, 1);
  } while (tokens.length && follow === true);
  return follow;
}
function appendItemToChain(chain, target, blocks) {
  let follow = false;
  if (!chain.includes(target)) {
    follow = true;
    if (target) {
      follow = target[target.length - 1] !== "!";
      const locale = target.replace(/!/g, "");
      chain.push(locale);
      if ((isArray(blocks) || isPlainObject(blocks)) && blocks[locale]) {
        follow = blocks[locale];
      }
    }
  }
  return follow;
}
const VERSION$1 = "9.14.5";
const NOT_REOSLVED = -1;
const DEFAULT_LOCALE = "en-US";
const MISSING_RESOLVE_VALUE = "";
const capitalize = (str) => `${str.charAt(0).toLocaleUpperCase()}${str.substr(1)}`;
function getDefaultLinkedModifiers() {
  return {
    upper: (val, type) => {
      return type === "text" && isString(val) ? val.toUpperCase() : type === "vnode" && isObject(val) && "__v_isVNode" in val ? val.children.toUpperCase() : val;
    },
    lower: (val, type) => {
      return type === "text" && isString(val) ? val.toLowerCase() : type === "vnode" && isObject(val) && "__v_isVNode" in val ? val.children.toLowerCase() : val;
    },
    capitalize: (val, type) => {
      return type === "text" && isString(val) ? capitalize(val) : type === "vnode" && isObject(val) && "__v_isVNode" in val ? capitalize(val.children) : val;
    }
  };
}
let _compiler;
function registerMessageCompiler(compiler) {
  _compiler = compiler;
}
let _resolver;
function registerMessageResolver(resolver) {
  _resolver = resolver;
}
let _fallbacker;
function registerLocaleFallbacker(fallbacker) {
  _fallbacker = fallbacker;
}
let _additionalMeta = null;
const setAdditionalMeta = /* @__NO_SIDE_EFFECTS__ */ (meta) => {
  _additionalMeta = meta;
};
const getAdditionalMeta = /* @__NO_SIDE_EFFECTS__ */ () => _additionalMeta;
let _fallbackContext = null;
const setFallbackContext = (context) => {
  _fallbackContext = context;
};
const getFallbackContext = () => _fallbackContext;
let _cid = 0;
function createCoreContext(options = {}) {
  const onWarn = isFunction(options.onWarn) ? options.onWarn : warn;
  const version = isString(options.version) ? options.version : VERSION$1;
  const locale = isString(options.locale) || isFunction(options.locale) ? options.locale : DEFAULT_LOCALE;
  const _locale = isFunction(locale) ? DEFAULT_LOCALE : locale;
  const fallbackLocale = isArray(options.fallbackLocale) || isPlainObject(options.fallbackLocale) || isString(options.fallbackLocale) || options.fallbackLocale === false ? options.fallbackLocale : _locale;
  const messages = isPlainObject(options.messages) ? options.messages : createResources(_locale);
  const datetimeFormats = isPlainObject(options.datetimeFormats) ? options.datetimeFormats : createResources(_locale);
  const numberFormats = isPlainObject(options.numberFormats) ? options.numberFormats : createResources(_locale);
  const modifiers = assign(create(), options.modifiers, getDefaultLinkedModifiers());
  const pluralRules = options.pluralRules || create();
  const missing = isFunction(options.missing) ? options.missing : null;
  const missingWarn = isBoolean(options.missingWarn) || isRegExp(options.missingWarn) ? options.missingWarn : true;
  const fallbackWarn = isBoolean(options.fallbackWarn) || isRegExp(options.fallbackWarn) ? options.fallbackWarn : true;
  const fallbackFormat = !!options.fallbackFormat;
  const unresolving = !!options.unresolving;
  const postTranslation = isFunction(options.postTranslation) ? options.postTranslation : null;
  const processor = isPlainObject(options.processor) ? options.processor : null;
  const warnHtmlMessage = isBoolean(options.warnHtmlMessage) ? options.warnHtmlMessage : true;
  const escapeParameter = !!options.escapeParameter;
  const messageCompiler = isFunction(options.messageCompiler) ? options.messageCompiler : _compiler;
  if (process.env.NODE_ENV !== "production" && true && true && isFunction(options.messageCompiler)) {
    warnOnce(getWarnMessage$1(CoreWarnCodes.EXPERIMENTAL_CUSTOM_MESSAGE_COMPILER));
  }
  const messageResolver = isFunction(options.messageResolver) ? options.messageResolver : _resolver || resolveWithKeyValue;
  const localeFallbacker = isFunction(options.localeFallbacker) ? options.localeFallbacker : _fallbacker || fallbackWithSimple;
  const fallbackContext = isObject(options.fallbackContext) ? options.fallbackContext : void 0;
  const internalOptions = options;
  const __datetimeFormatters = isObject(internalOptions.__datetimeFormatters) ? internalOptions.__datetimeFormatters : /* @__PURE__ */ new Map();
  const __numberFormatters = isObject(internalOptions.__numberFormatters) ? internalOptions.__numberFormatters : /* @__PURE__ */ new Map();
  const __meta = isObject(internalOptions.__meta) ? internalOptions.__meta : {};
  _cid++;
  const context = {
    version,
    cid: _cid,
    locale,
    fallbackLocale,
    messages,
    modifiers,
    pluralRules,
    missing,
    missingWarn,
    fallbackWarn,
    fallbackFormat,
    unresolving,
    postTranslation,
    processor,
    warnHtmlMessage,
    escapeParameter,
    messageCompiler,
    messageResolver,
    localeFallbacker,
    fallbackContext,
    onWarn,
    __meta
  };
  {
    context.datetimeFormats = datetimeFormats;
    context.numberFormats = numberFormats;
    context.__datetimeFormatters = __datetimeFormatters;
    context.__numberFormatters = __numberFormatters;
  }
  if (process.env.NODE_ENV !== "production") {
    context.__v_emitter = internalOptions.__v_emitter != null ? internalOptions.__v_emitter : void 0;
  }
  if (process.env.NODE_ENV !== "production" || __INTLIFY_PROD_DEVTOOLS__) {
    initI18nDevTools(context, version, __meta);
  }
  return context;
}
const createResources = (locale) => ({ [locale]: create() });
function isTranslateFallbackWarn(fallback, key) {
  return fallback instanceof RegExp ? fallback.test(key) : fallback;
}
function isTranslateMissingWarn(missing, key) {
  return missing instanceof RegExp ? missing.test(key) : missing;
}
function handleMissing(context, key, locale, missingWarn, type) {
  const { missing, onWarn } = context;
  if (process.env.NODE_ENV !== "production") {
    const emitter = context.__v_emitter;
    if (emitter) {
      emitter.emit("missing", {
        locale,
        key,
        type,
        groupId: `${type}:${key}`
      });
    }
  }
  if (missing !== null) {
    const ret = missing(context, locale, key, type);
    return isString(ret) ? ret : key;
  } else {
    if (process.env.NODE_ENV !== "production" && isTranslateMissingWarn(missingWarn, key)) {
      onWarn(getWarnMessage$1(CoreWarnCodes.NOT_FOUND_KEY, { key, locale }));
    }
    return key;
  }
}
function updateFallbackLocale(ctx, locale, fallback) {
  const context = ctx;
  context.__localeChainCache = /* @__PURE__ */ new Map();
  ctx.localeFallbacker(ctx, fallback, locale);
}
function isAlmostSameLocale(locale, compareLocale) {
  if (locale === compareLocale)
    return false;
  return locale.split("-")[0] === compareLocale.split("-")[0];
}
function isImplicitFallback(targetLocale, locales) {
  const index = locales.indexOf(targetLocale);
  if (index === -1) {
    return false;
  }
  for (let i = index + 1; i < locales.length; i++) {
    if (isAlmostSameLocale(targetLocale, locales[i])) {
      return true;
    }
  }
  return false;
}
function format(ast) {
  const msg = (ctx) => formatParts(ctx, ast);
  return msg;
}
function formatParts(ctx, ast) {
  const body = resolveBody(ast);
  if (body == null) {
    throw createUnhandleNodeError(
      0
      /* NodeTypes.Resource */
    );
  }
  const type = resolveType(body);
  if (type === 1) {
    const plural = body;
    const cases = resolveCases(plural);
    return ctx.plural(cases.reduce((messages, c) => [
      ...messages,
      formatMessageParts(ctx, c)
    ], []));
  } else {
    return formatMessageParts(ctx, body);
  }
}
function formatMessageParts(ctx, node) {
  const static_ = resolveStatic(node);
  if (static_ != null) {
    return ctx.type === "text" ? static_ : ctx.normalize([static_]);
  } else {
    const messages = resolveItems(node).reduce((acm, c) => [...acm, formatMessagePart(ctx, c)], []);
    return ctx.normalize(messages);
  }
}
function formatMessagePart(ctx, node) {
  const type = resolveType(node);
  switch (type) {
    case 3: {
      return resolveValue$1(node, type);
    }
    case 9: {
      return resolveValue$1(node, type);
    }
    case 4: {
      const named = node;
      if (hasOwn(named, "k") && named.k) {
        return ctx.interpolate(ctx.named(named.k));
      }
      if (hasOwn(named, "key") && named.key) {
        return ctx.interpolate(ctx.named(named.key));
      }
      throw createUnhandleNodeError(type);
    }
    case 5: {
      const list = node;
      if (hasOwn(list, "i") && isNumber(list.i)) {
        return ctx.interpolate(ctx.list(list.i));
      }
      if (hasOwn(list, "index") && isNumber(list.index)) {
        return ctx.interpolate(ctx.list(list.index));
      }
      throw createUnhandleNodeError(type);
    }
    case 6: {
      const linked = node;
      const modifier = resolveLinkedModifier(linked);
      const key = resolveLinkedKey(linked);
      return ctx.linked(formatMessagePart(ctx, key), modifier ? formatMessagePart(ctx, modifier) : void 0, ctx.type);
    }
    case 7: {
      return resolveValue$1(node, type);
    }
    case 8: {
      return resolveValue$1(node, type);
    }
    default:
      throw new Error(`unhandled node on format message part: ${type}`);
  }
}
const WARN_MESSAGE = `Detected HTML in '{source}' message. Recommend not using HTML messages to avoid XSS.`;
function checkHtmlMessage(source, warnHtmlMessage) {
  if (warnHtmlMessage && detectHtmlTag(source)) {
    warn(format$1(WARN_MESSAGE, { source }));
  }
}
const defaultOnCacheKey = (message) => message;
let compileCache = create();
function onCompileWarn(_warn) {
  if (_warn.code === CompileWarnCodes.USE_MODULO_SYNTAX) {
    warn(`The use of named interpolation with modulo syntax is deprecated. It will be removed in v10.
reference: https://vue-i18n.intlify.dev/guide/essentials/syntax#rails-i18n-format 
(message compiler warning message: ${_warn.message})`);
  }
}
function baseCompile(message, options = {}) {
  let detectError = false;
  const onError = options.onError || defaultOnError;
  options.onError = (err) => {
    detectError = true;
    onError(err);
  };
  return { ...baseCompile$1(message, options), detectError };
}
function compile(message, context) {
  if (process.env.NODE_ENV !== "production") {
    context.onWarn = onCompileWarn;
  }
  if (isString(message)) {
    const warnHtmlMessage = isBoolean(context.warnHtmlMessage) ? context.warnHtmlMessage : true;
    process.env.NODE_ENV !== "production" && checkHtmlMessage(message, warnHtmlMessage);
    const onCacheKey = context.onCacheKey || defaultOnCacheKey;
    const cacheKey = onCacheKey(message);
    const cached = compileCache[cacheKey];
    if (cached) {
      return cached;
    }
    const { ast, detectError } = baseCompile(message, {
      ...context,
      location: process.env.NODE_ENV !== "production",
      jit: true
    });
    const msg = format(ast);
    return !detectError ? compileCache[cacheKey] = msg : msg;
  } else {
    if (process.env.NODE_ENV !== "production" && !isMessageAST(message)) {
      warn(`the message that is resolve with key '${context.key}' is not supported for jit compilation`);
      return () => message;
    }
    const cacheKey = message.cacheKey;
    if (cacheKey) {
      const cached = compileCache[cacheKey];
      if (cached) {
        return cached;
      }
      return compileCache[cacheKey] = format(message);
    } else {
      return format(message);
    }
  }
}
const NOOP_MESSAGE_FUNCTION = () => "";
const isMessageFunction = (val) => isFunction(val);
function translate(context, ...args) {
  const { fallbackFormat, postTranslation, unresolving, messageCompiler, fallbackLocale, messages } = context;
  const [key, options] = parseTranslateArgs(...args);
  const missingWarn = isBoolean(options.missingWarn) ? options.missingWarn : context.missingWarn;
  const fallbackWarn = isBoolean(options.fallbackWarn) ? options.fallbackWarn : context.fallbackWarn;
  const escapeParameter = isBoolean(options.escapeParameter) ? options.escapeParameter : context.escapeParameter;
  const resolvedMessage = !!options.resolvedMessage;
  const defaultMsgOrKey = isString(options.default) || isBoolean(options.default) ? !isBoolean(options.default) ? options.default : !messageCompiler ? () => key : key : fallbackFormat ? !messageCompiler ? () => key : key : "";
  const enableDefaultMsg = fallbackFormat || defaultMsgOrKey !== "";
  const locale = getLocale(context, options);
  escapeParameter && escapeParams(options);
  let [formatScope, targetLocale, message] = !resolvedMessage ? resolveMessageFormat(context, key, locale, fallbackLocale, fallbackWarn, missingWarn) : [
    key,
    locale,
    messages[locale] || create()
  ];
  let format2 = formatScope;
  let cacheBaseKey = key;
  if (!resolvedMessage && !(isString(format2) || isMessageAST(format2) || isMessageFunction(format2))) {
    if (enableDefaultMsg) {
      format2 = defaultMsgOrKey;
      cacheBaseKey = format2;
    }
  }
  if (!resolvedMessage && (!(isString(format2) || isMessageAST(format2) || isMessageFunction(format2)) || !isString(targetLocale))) {
    return unresolving ? NOT_REOSLVED : key;
  }
  if (process.env.NODE_ENV !== "production" && isString(format2) && context.messageCompiler == null) {
    warn(`The message format compilation is not supported in this build. Because message compiler isn't included. You need to pre-compilation all message format. So translate function return '${key}'.`);
    return key;
  }
  let occurred = false;
  const onError = () => {
    occurred = true;
  };
  const msg = !isMessageFunction(format2) ? compileMessageFormat(context, key, targetLocale, format2, cacheBaseKey, onError) : format2;
  if (occurred) {
    return format2;
  }
  const ctxOptions = getMessageContextOptions(context, targetLocale, message, options);
  const msgContext = createMessageContext(ctxOptions);
  const messaged = evaluateMessage(context, msg, msgContext);
  let ret = postTranslation ? postTranslation(messaged, key) : messaged;
  if (escapeParameter && isString(ret)) {
    ret = sanitizeTranslatedHtml(ret);
  }
  if (process.env.NODE_ENV !== "production" || __INTLIFY_PROD_DEVTOOLS__) {
    const payloads = {
      timestamp: Date.now(),
      key: isString(key) ? key : isMessageFunction(format2) ? format2.key : "",
      locale: targetLocale || (isMessageFunction(format2) ? format2.locale : ""),
      format: isString(format2) ? format2 : isMessageFunction(format2) ? format2.source : "",
      message: ret
    };
    payloads.meta = assign({}, context.__meta, /* @__PURE__ */ getAdditionalMeta() || {});
    translateDevTools(payloads);
  }
  return ret;
}
function escapeParams(options) {
  if (isArray(options.list)) {
    options.list = options.list.map((item) => isString(item) ? escapeHtml(item) : item);
  } else if (isObject(options.named)) {
    Object.keys(options.named).forEach((key) => {
      if (isString(options.named[key])) {
        options.named[key] = escapeHtml(options.named[key]);
      }
    });
  }
}
function resolveMessageFormat(context, key, locale, fallbackLocale, fallbackWarn, missingWarn) {
  const { messages, onWarn, messageResolver: resolveValue2, localeFallbacker } = context;
  const locales = localeFallbacker(context, fallbackLocale, locale);
  let message = create();
  let targetLocale;
  let format2 = null;
  let from = locale;
  let to = null;
  const type = "translate";
  for (let i = 0; i < locales.length; i++) {
    targetLocale = to = locales[i];
    if (process.env.NODE_ENV !== "production" && locale !== targetLocale && !isAlmostSameLocale(locale, targetLocale) && isTranslateFallbackWarn(fallbackWarn, key)) {
      onWarn(getWarnMessage$1(CoreWarnCodes.FALLBACK_TO_TRANSLATE, {
        key,
        target: targetLocale
      }));
    }
    if (process.env.NODE_ENV !== "production" && locale !== targetLocale) {
      const emitter = context.__v_emitter;
      if (emitter) {
        emitter.emit("fallback", {
          type,
          key,
          from,
          to,
          groupId: `${type}:${key}`
        });
      }
    }
    message = messages[targetLocale] || create();
    if (process.env.NODE_ENV !== "production" && inBrowser) ;
    if ((format2 = resolveValue2(message, key)) === null) {
      format2 = message[key];
    }
    if (process.env.NODE_ENV !== "production" && inBrowser) ;
    if (isString(format2) || isMessageAST(format2) || isMessageFunction(format2)) {
      break;
    }
    if (!isImplicitFallback(targetLocale, locales)) {
      const missingRet = handleMissing(
        context,
        // eslint-disable-line @typescript-eslint/no-explicit-any
        key,
        targetLocale,
        missingWarn,
        type
      );
      if (missingRet !== key) {
        format2 = missingRet;
      }
    }
    from = to;
  }
  return [format2, targetLocale, message];
}
function compileMessageFormat(context, key, targetLocale, format2, cacheBaseKey, onError) {
  const { messageCompiler, warnHtmlMessage } = context;
  if (isMessageFunction(format2)) {
    const msg2 = format2;
    msg2.locale = msg2.locale || targetLocale;
    msg2.key = msg2.key || key;
    return msg2;
  }
  if (messageCompiler == null) {
    const msg2 = () => format2;
    msg2.locale = targetLocale;
    msg2.key = key;
    return msg2;
  }
  if (process.env.NODE_ENV !== "production" && inBrowser) ;
  const msg = messageCompiler(format2, getCompileContext(context, targetLocale, cacheBaseKey, format2, warnHtmlMessage, onError));
  if (process.env.NODE_ENV !== "production" && inBrowser) ;
  msg.locale = targetLocale;
  msg.key = key;
  msg.source = format2;
  return msg;
}
function evaluateMessage(context, msg, msgCtx) {
  if (process.env.NODE_ENV !== "production" && inBrowser) ;
  const messaged = msg(msgCtx);
  if (process.env.NODE_ENV !== "production" && inBrowser) ;
  return messaged;
}
function parseTranslateArgs(...args) {
  const [arg1, arg2, arg3] = args;
  const options = create();
  if (!isString(arg1) && !isNumber(arg1) && !isMessageFunction(arg1) && !isMessageAST(arg1)) {
    throw createCoreError(CoreErrorCodes.INVALID_ARGUMENT);
  }
  const key = isNumber(arg1) ? String(arg1) : isMessageFunction(arg1) ? arg1 : arg1;
  if (isNumber(arg2)) {
    options.plural = arg2;
  } else if (isString(arg2)) {
    options.default = arg2;
  } else if (isPlainObject(arg2) && !isEmptyObject(arg2)) {
    options.named = arg2;
  } else if (isArray(arg2)) {
    options.list = arg2;
  }
  if (isNumber(arg3)) {
    options.plural = arg3;
  } else if (isString(arg3)) {
    options.default = arg3;
  } else if (isPlainObject(arg3)) {
    assign(options, arg3);
  }
  return [key, options];
}
function getCompileContext(context, locale, key, source, warnHtmlMessage, onError) {
  return {
    locale,
    key,
    warnHtmlMessage,
    onError: (err) => {
      onError && onError(err);
      if (process.env.NODE_ENV !== "production") {
        const _source = getSourceForCodeFrame(source);
        const message = `Message compilation error: ${err.message}`;
        const codeFrame = err.location && _source && generateCodeFrame(_source, err.location.start.offset, err.location.end.offset);
        const emitter = context.__v_emitter;
        if (emitter && _source) {
          emitter.emit("compile-error", {
            message: _source,
            error: err.message,
            start: err.location && err.location.start.offset,
            end: err.location && err.location.end.offset,
            groupId: `${"translate"}:${key}`
          });
        }
        console.error(codeFrame ? `${message}
${codeFrame}` : message);
      } else {
        throw err;
      }
    },
    onCacheKey: (source2) => generateFormatCacheKey(locale, key, source2)
  };
}
function getSourceForCodeFrame(source) {
  if (isString(source)) {
    return source;
  } else {
    if (source.loc && source.loc.source) {
      return source.loc.source;
    }
  }
}
function getMessageContextOptions(context, locale, message, options) {
  const { modifiers, pluralRules, messageResolver: resolveValue2, fallbackLocale, fallbackWarn, missingWarn, fallbackContext } = context;
  const resolveMessage = (key) => {
    let val = resolveValue2(message, key);
    if (val == null && fallbackContext) {
      const [, , message2] = resolveMessageFormat(fallbackContext, key, locale, fallbackLocale, fallbackWarn, missingWarn);
      val = resolveValue2(message2, key);
    }
    if (isString(val) || isMessageAST(val)) {
      let occurred = false;
      const onError = () => {
        occurred = true;
      };
      const msg = compileMessageFormat(context, key, locale, val, key, onError);
      return !occurred ? msg : NOOP_MESSAGE_FUNCTION;
    } else if (isMessageFunction(val)) {
      return val;
    } else {
      return NOOP_MESSAGE_FUNCTION;
    }
  };
  const ctxOptions = {
    locale,
    modifiers,
    pluralRules,
    messages: resolveMessage
  };
  if (context.processor) {
    ctxOptions.processor = context.processor;
  }
  if (options.list) {
    ctxOptions.list = options.list;
  }
  if (options.named) {
    ctxOptions.named = options.named;
  }
  if (isNumber(options.plural)) {
    ctxOptions.pluralIndex = options.plural;
  }
  return ctxOptions;
}
const intlDefined = typeof Intl !== "undefined";
const Availabilities = {
  dateTimeFormat: intlDefined && typeof Intl.DateTimeFormat !== "undefined",
  numberFormat: intlDefined && typeof Intl.NumberFormat !== "undefined"
};
function datetime(context, ...args) {
  const { datetimeFormats, unresolving, fallbackLocale, onWarn, localeFallbacker } = context;
  const { __datetimeFormatters } = context;
  if (process.env.NODE_ENV !== "production" && !Availabilities.dateTimeFormat) {
    onWarn(getWarnMessage$1(CoreWarnCodes.CANNOT_FORMAT_DATE));
    return MISSING_RESOLVE_VALUE;
  }
  const [key, value, options, overrides] = parseDateTimeArgs(...args);
  const missingWarn = isBoolean(options.missingWarn) ? options.missingWarn : context.missingWarn;
  const fallbackWarn = isBoolean(options.fallbackWarn) ? options.fallbackWarn : context.fallbackWarn;
  const part = !!options.part;
  const locale = getLocale(context, options);
  const locales = localeFallbacker(
    context,
    // eslint-disable-line @typescript-eslint/no-explicit-any
    fallbackLocale,
    locale
  );
  if (!isString(key) || key === "") {
    return new Intl.DateTimeFormat(locale, overrides).format(value);
  }
  let datetimeFormat = {};
  let targetLocale;
  let format2 = null;
  let from = locale;
  let to = null;
  const type = "datetime format";
  for (let i = 0; i < locales.length; i++) {
    targetLocale = to = locales[i];
    if (process.env.NODE_ENV !== "production" && locale !== targetLocale && isTranslateFallbackWarn(fallbackWarn, key)) {
      onWarn(getWarnMessage$1(CoreWarnCodes.FALLBACK_TO_DATE_FORMAT, {
        key,
        target: targetLocale
      }));
    }
    if (process.env.NODE_ENV !== "production" && locale !== targetLocale) {
      const emitter = context.__v_emitter;
      if (emitter) {
        emitter.emit("fallback", {
          type,
          key,
          from,
          to,
          groupId: `${type}:${key}`
        });
      }
    }
    datetimeFormat = datetimeFormats[targetLocale] || {};
    format2 = datetimeFormat[key];
    if (isPlainObject(format2))
      break;
    handleMissing(context, key, targetLocale, missingWarn, type);
    from = to;
  }
  if (!isPlainObject(format2) || !isString(targetLocale)) {
    return unresolving ? NOT_REOSLVED : key;
  }
  let id = `${targetLocale}__${key}`;
  if (!isEmptyObject(overrides)) {
    id = `${id}__${JSON.stringify(overrides)}`;
  }
  let formatter = __datetimeFormatters.get(id);
  if (!formatter) {
    formatter = new Intl.DateTimeFormat(targetLocale, assign({}, format2, overrides));
    __datetimeFormatters.set(id, formatter);
  }
  return !part ? formatter.format(value) : formatter.formatToParts(value);
}
const DATETIME_FORMAT_OPTIONS_KEYS = [
  "localeMatcher",
  "weekday",
  "era",
  "year",
  "month",
  "day",
  "hour",
  "minute",
  "second",
  "timeZoneName",
  "formatMatcher",
  "hour12",
  "timeZone",
  "dateStyle",
  "timeStyle",
  "calendar",
  "dayPeriod",
  "numberingSystem",
  "hourCycle",
  "fractionalSecondDigits"
];
function parseDateTimeArgs(...args) {
  const [arg1, arg2, arg3, arg4] = args;
  const options = create();
  let overrides = create();
  let value;
  if (isString(arg1)) {
    const matches = arg1.match(/(\d{4}-\d{2}-\d{2})(T|\s)?(.*)/);
    if (!matches) {
      throw createCoreError(CoreErrorCodes.INVALID_ISO_DATE_ARGUMENT);
    }
    const dateTime = matches[3] ? matches[3].trim().startsWith("T") ? `${matches[1].trim()}${matches[3].trim()}` : `${matches[1].trim()}T${matches[3].trim()}` : matches[1].trim();
    value = new Date(dateTime);
    try {
      value.toISOString();
    } catch (e) {
      throw createCoreError(CoreErrorCodes.INVALID_ISO_DATE_ARGUMENT);
    }
  } else if (isDate(arg1)) {
    if (isNaN(arg1.getTime())) {
      throw createCoreError(CoreErrorCodes.INVALID_DATE_ARGUMENT);
    }
    value = arg1;
  } else if (isNumber(arg1)) {
    value = arg1;
  } else {
    throw createCoreError(CoreErrorCodes.INVALID_ARGUMENT);
  }
  if (isString(arg2)) {
    options.key = arg2;
  } else if (isPlainObject(arg2)) {
    Object.keys(arg2).forEach((key) => {
      if (DATETIME_FORMAT_OPTIONS_KEYS.includes(key)) {
        overrides[key] = arg2[key];
      } else {
        options[key] = arg2[key];
      }
    });
  }
  if (isString(arg3)) {
    options.locale = arg3;
  } else if (isPlainObject(arg3)) {
    overrides = arg3;
  }
  if (isPlainObject(arg4)) {
    overrides = arg4;
  }
  return [options.key || "", value, options, overrides];
}
function clearDateTimeFormat(ctx, locale, format2) {
  const context = ctx;
  for (const key in format2) {
    const id = `${locale}__${key}`;
    if (!context.__datetimeFormatters.has(id)) {
      continue;
    }
    context.__datetimeFormatters.delete(id);
  }
}
function number(context, ...args) {
  const { numberFormats, unresolving, fallbackLocale, onWarn, localeFallbacker } = context;
  const { __numberFormatters } = context;
  if (process.env.NODE_ENV !== "production" && !Availabilities.numberFormat) {
    onWarn(getWarnMessage$1(CoreWarnCodes.CANNOT_FORMAT_NUMBER));
    return MISSING_RESOLVE_VALUE;
  }
  const [key, value, options, overrides] = parseNumberArgs(...args);
  const missingWarn = isBoolean(options.missingWarn) ? options.missingWarn : context.missingWarn;
  const fallbackWarn = isBoolean(options.fallbackWarn) ? options.fallbackWarn : context.fallbackWarn;
  const part = !!options.part;
  const locale = getLocale(context, options);
  const locales = localeFallbacker(
    context,
    // eslint-disable-line @typescript-eslint/no-explicit-any
    fallbackLocale,
    locale
  );
  if (!isString(key) || key === "") {
    return new Intl.NumberFormat(locale, overrides).format(value);
  }
  let numberFormat = {};
  let targetLocale;
  let format2 = null;
  let from = locale;
  let to = null;
  const type = "number format";
  for (let i = 0; i < locales.length; i++) {
    targetLocale = to = locales[i];
    if (process.env.NODE_ENV !== "production" && locale !== targetLocale && isTranslateFallbackWarn(fallbackWarn, key)) {
      onWarn(getWarnMessage$1(CoreWarnCodes.FALLBACK_TO_NUMBER_FORMAT, {
        key,
        target: targetLocale
      }));
    }
    if (process.env.NODE_ENV !== "production" && locale !== targetLocale) {
      const emitter = context.__v_emitter;
      if (emitter) {
        emitter.emit("fallback", {
          type,
          key,
          from,
          to,
          groupId: `${type}:${key}`
        });
      }
    }
    numberFormat = numberFormats[targetLocale] || {};
    format2 = numberFormat[key];
    if (isPlainObject(format2))
      break;
    handleMissing(context, key, targetLocale, missingWarn, type);
    from = to;
  }
  if (!isPlainObject(format2) || !isString(targetLocale)) {
    return unresolving ? NOT_REOSLVED : key;
  }
  let id = `${targetLocale}__${key}`;
  if (!isEmptyObject(overrides)) {
    id = `${id}__${JSON.stringify(overrides)}`;
  }
  let formatter = __numberFormatters.get(id);
  if (!formatter) {
    formatter = new Intl.NumberFormat(targetLocale, assign({}, format2, overrides));
    __numberFormatters.set(id, formatter);
  }
  return !part ? formatter.format(value) : formatter.formatToParts(value);
}
const NUMBER_FORMAT_OPTIONS_KEYS = [
  "localeMatcher",
  "style",
  "currency",
  "currencyDisplay",
  "currencySign",
  "useGrouping",
  "minimumIntegerDigits",
  "minimumFractionDigits",
  "maximumFractionDigits",
  "minimumSignificantDigits",
  "maximumSignificantDigits",
  "compactDisplay",
  "notation",
  "signDisplay",
  "unit",
  "unitDisplay",
  "roundingMode",
  "roundingPriority",
  "roundingIncrement",
  "trailingZeroDisplay"
];
function parseNumberArgs(...args) {
  const [arg1, arg2, arg3, arg4] = args;
  const options = create();
  let overrides = create();
  if (!isNumber(arg1)) {
    throw createCoreError(CoreErrorCodes.INVALID_ARGUMENT);
  }
  const value = arg1;
  if (isString(arg2)) {
    options.key = arg2;
  } else if (isPlainObject(arg2)) {
    Object.keys(arg2).forEach((key) => {
      if (NUMBER_FORMAT_OPTIONS_KEYS.includes(key)) {
        overrides[key] = arg2[key];
      } else {
        options[key] = arg2[key];
      }
    });
  }
  if (isString(arg3)) {
    options.locale = arg3;
  } else if (isPlainObject(arg3)) {
    overrides = arg3;
  }
  if (isPlainObject(arg4)) {
    overrides = arg4;
  }
  return [options.key || "", value, options, overrides];
}
function clearNumberFormat(ctx, locale, format2) {
  const context = ctx;
  for (const key in format2) {
    const id = `${locale}__${key}`;
    if (!context.__numberFormatters.has(id)) {
      continue;
    }
    context.__numberFormatters.delete(id);
  }
}
{
  initFeatureFlags$1();
}
/*!
  * vue-i18n v9.14.5
  * (c) 2025 kazuya kawaguchi
  * Released under the MIT License.
  */
const VERSION = "9.14.5";
function initFeatureFlags() {
  if (typeof __INTLIFY_PROD_DEVTOOLS__ !== "boolean") {
    getGlobalThis().__INTLIFY_PROD_DEVTOOLS__ = false;
  }
}
const code$1 = CoreWarnCodes.__EXTEND_POINT__;
const inc$1 = incrementer(code$1);
const I18nWarnCodes = {
  FALLBACK_TO_ROOT: code$1,
  // 9
  NOT_SUPPORTED_PRESERVE: inc$1(),
  // 10
  NOT_SUPPORTED_FORMATTER: inc$1(),
  // 11
  NOT_SUPPORTED_PRESERVE_DIRECTIVE: inc$1(),
  // 12
  NOT_SUPPORTED_GET_CHOICE_INDEX: inc$1(),
  // 13
  COMPONENT_NAME_LEGACY_COMPATIBLE: inc$1(),
  // 14
  NOT_FOUND_PARENT_SCOPE: inc$1(),
  // 15
  IGNORE_OBJ_FLATTEN: inc$1(),
  // 16
  NOTICE_DROP_ALLOW_COMPOSITION: inc$1(),
  // 17
  NOTICE_DROP_TRANSLATE_EXIST_COMPATIBLE_FLAG: inc$1()
  // 18
};
const warnMessages = {
  [I18nWarnCodes.FALLBACK_TO_ROOT]: `Fall back to {type} '{key}' with root locale.`,
  [I18nWarnCodes.NOT_SUPPORTED_PRESERVE]: `Not supported 'preserve'.`,
  [I18nWarnCodes.NOT_SUPPORTED_FORMATTER]: `Not supported 'formatter'.`,
  [I18nWarnCodes.NOT_SUPPORTED_PRESERVE_DIRECTIVE]: `Not supported 'preserveDirectiveContent'.`,
  [I18nWarnCodes.NOT_SUPPORTED_GET_CHOICE_INDEX]: `Not supported 'getChoiceIndex'.`,
  [I18nWarnCodes.COMPONENT_NAME_LEGACY_COMPATIBLE]: `Component name legacy compatible: '{name}' -> 'i18n'`,
  [I18nWarnCodes.NOT_FOUND_PARENT_SCOPE]: `Not found parent scope. use the global scope.`,
  [I18nWarnCodes.IGNORE_OBJ_FLATTEN]: `Ignore object flatten: '{key}' key has an string value`,
  [I18nWarnCodes.NOTICE_DROP_ALLOW_COMPOSITION]: `'allowComposition' option will be dropped in the next major version. For more information, please see 👉 https://tinyurl.com/2p97mcze`,
  [I18nWarnCodes.NOTICE_DROP_TRANSLATE_EXIST_COMPATIBLE_FLAG]: `'translateExistCompatible' option will be dropped in the next major version.`
};
function getWarnMessage(code2, ...args) {
  return format$1(warnMessages[code2], ...args);
}
const code = CoreErrorCodes.__EXTEND_POINT__;
const inc = incrementer(code);
const I18nErrorCodes = {
  // composer module errors
  UNEXPECTED_RETURN_TYPE: code,
  // 24
  // legacy module errors
  INVALID_ARGUMENT: inc(),
  // 25
  // i18n module errors
  MUST_BE_CALL_SETUP_TOP: inc(),
  // 26
  NOT_INSTALLED: inc(),
  // 27
  NOT_AVAILABLE_IN_LEGACY_MODE: inc(),
  // 28
  // directive module errors
  REQUIRED_VALUE: inc(),
  // 29
  INVALID_VALUE: inc(),
  // 30
  // vue-devtools errors
  CANNOT_SETUP_VUE_DEVTOOLS_PLUGIN: inc(),
  // 31
  NOT_INSTALLED_WITH_PROVIDE: inc(),
  // 32
  // unexpected error
  UNEXPECTED_ERROR: inc(),
  // 33
  // not compatible legacy vue-i18n constructor
  NOT_COMPATIBLE_LEGACY_VUE_I18N: inc(),
  // 34
  // bridge support vue 2.x only
  BRIDGE_SUPPORT_VUE_2_ONLY: inc(),
  // 35
  // need to define `i18n` option in `allowComposition: true` and `useScope: 'local' at `useI18n``
  MUST_DEFINE_I18N_OPTION_IN_ALLOW_COMPOSITION: inc(),
  // 36
  // Not available Compostion API in Legacy API mode. Please make sure that the legacy API mode is working properly
  NOT_AVAILABLE_COMPOSITION_IN_LEGACY: inc(),
  // 37
  // for enhancement
  __EXTEND_POINT__: inc()
  // 38
};
function createI18nError(code2, ...args) {
  return createCompileError(code2, null, process.env.NODE_ENV !== "production" ? { messages: errorMessages, args } : void 0);
}
const errorMessages = {
  [I18nErrorCodes.UNEXPECTED_RETURN_TYPE]: "Unexpected return type in composer",
  [I18nErrorCodes.INVALID_ARGUMENT]: "Invalid argument",
  [I18nErrorCodes.MUST_BE_CALL_SETUP_TOP]: "Must be called at the top of a `setup` function",
  [I18nErrorCodes.NOT_INSTALLED]: "Need to install with `app.use` function",
  [I18nErrorCodes.UNEXPECTED_ERROR]: "Unexpected error",
  [I18nErrorCodes.NOT_AVAILABLE_IN_LEGACY_MODE]: "Not available in legacy mode",
  [I18nErrorCodes.REQUIRED_VALUE]: `Required in value: {0}`,
  [I18nErrorCodes.INVALID_VALUE]: `Invalid value`,
  [I18nErrorCodes.CANNOT_SETUP_VUE_DEVTOOLS_PLUGIN]: `Cannot setup vue-devtools plugin`,
  [I18nErrorCodes.NOT_INSTALLED_WITH_PROVIDE]: "Need to install with `provide` function",
  [I18nErrorCodes.NOT_COMPATIBLE_LEGACY_VUE_I18N]: "Not compatible legacy VueI18n.",
  [I18nErrorCodes.BRIDGE_SUPPORT_VUE_2_ONLY]: "vue-i18n-bridge support Vue 2.x only",
  [I18nErrorCodes.MUST_DEFINE_I18N_OPTION_IN_ALLOW_COMPOSITION]: "Must define ‘i18n’ option or custom block in Composition API with using local scope in Legacy API mode",
  [I18nErrorCodes.NOT_AVAILABLE_COMPOSITION_IN_LEGACY]: "Not available Compostion API in Legacy API mode. Please make sure that the legacy API mode is working properly"
};
const TranslateVNodeSymbol = /* @__PURE__ */ makeSymbol("__translateVNode");
const DatetimePartsSymbol = /* @__PURE__ */ makeSymbol("__datetimeParts");
const NumberPartsSymbol = /* @__PURE__ */ makeSymbol("__numberParts");
const EnableEmitter = /* @__PURE__ */ makeSymbol("__enableEmitter");
const DisableEmitter = /* @__PURE__ */ makeSymbol("__disableEmitter");
const SetPluralRulesSymbol = makeSymbol("__setPluralRules");
const InejctWithOptionSymbol = /* @__PURE__ */ makeSymbol("__injectWithOption");
const DisposeSymbol = /* @__PURE__ */ makeSymbol("__dispose");
function handleFlatJson(obj) {
  if (!isObject(obj)) {
    return obj;
  }
  if (isMessageAST(obj)) {
    return obj;
  }
  for (const key in obj) {
    if (!hasOwn(obj, key)) {
      continue;
    }
    if (!key.includes(".")) {
      if (isObject(obj[key])) {
        handleFlatJson(obj[key]);
      }
    } else {
      const subKeys = key.split(".");
      const lastIndex = subKeys.length - 1;
      let currentObj = obj;
      let hasStringValue = false;
      for (let i = 0; i < lastIndex; i++) {
        if (subKeys[i] === "__proto__") {
          throw new Error(`unsafe key: ${subKeys[i]}`);
        }
        if (!(subKeys[i] in currentObj)) {
          currentObj[subKeys[i]] = create();
        }
        if (!isObject(currentObj[subKeys[i]])) {
          process.env.NODE_ENV !== "production" && warn(getWarnMessage(I18nWarnCodes.IGNORE_OBJ_FLATTEN, {
            key: subKeys[i]
          }));
          hasStringValue = true;
          break;
        }
        currentObj = currentObj[subKeys[i]];
      }
      if (!hasStringValue) {
        if (!isMessageAST(currentObj)) {
          currentObj[subKeys[lastIndex]] = obj[key];
          delete obj[key];
        } else {
          if (!AST_NODE_PROPS_KEYS.includes(subKeys[lastIndex])) {
            delete obj[key];
          }
        }
      }
      if (!isMessageAST(currentObj)) {
        const target = currentObj[subKeys[lastIndex]];
        if (isObject(target)) {
          handleFlatJson(target);
        }
      }
    }
  }
  return obj;
}
function getLocaleMessages(locale, options) {
  const { messages, __i18n, messageResolver, flatJson } = options;
  const ret = isPlainObject(messages) ? messages : isArray(__i18n) ? create() : { [locale]: create() };
  if (isArray(__i18n)) {
    __i18n.forEach((custom) => {
      if ("locale" in custom && "resource" in custom) {
        const { locale: locale2, resource: resource2 } = custom;
        if (locale2) {
          ret[locale2] = ret[locale2] || create();
          deepCopy(resource2, ret[locale2]);
        } else {
          deepCopy(resource2, ret);
        }
      } else {
        isString(custom) && deepCopy(JSON.parse(custom), ret);
      }
    });
  }
  if (messageResolver == null && flatJson) {
    for (const key in ret) {
      if (hasOwn(ret, key)) {
        handleFlatJson(ret[key]);
      }
    }
  }
  return ret;
}
function getComponentOptions(instance) {
  return instance.type;
}
function adjustI18nResources(gl, options, componentOptions) {
  let messages = isObject(options.messages) ? options.messages : create();
  if ("__i18nGlobal" in componentOptions) {
    messages = getLocaleMessages(gl.locale.value, {
      messages,
      __i18n: componentOptions.__i18nGlobal
    });
  }
  const locales = Object.keys(messages);
  if (locales.length) {
    locales.forEach((locale) => {
      gl.mergeLocaleMessage(locale, messages[locale]);
    });
  }
  {
    if (isObject(options.datetimeFormats)) {
      const locales2 = Object.keys(options.datetimeFormats);
      if (locales2.length) {
        locales2.forEach((locale) => {
          gl.mergeDateTimeFormat(locale, options.datetimeFormats[locale]);
        });
      }
    }
    if (isObject(options.numberFormats)) {
      const locales2 = Object.keys(options.numberFormats);
      if (locales2.length) {
        locales2.forEach((locale) => {
          gl.mergeNumberFormat(locale, options.numberFormats[locale]);
        });
      }
    }
  }
}
function createTextNode(key) {
  return createVNode(Text, null, key, 0);
}
const DEVTOOLS_META = "__INTLIFY_META__";
const NOOP_RETURN_ARRAY = () => [];
const NOOP_RETURN_FALSE = () => false;
let composerID = 0;
function defineCoreMissingHandler(missing) {
  return (ctx, locale, key, type) => {
    return missing(locale, key, getCurrentInstance() || void 0, type);
  };
}
const getMetaInfo = /* @__NO_SIDE_EFFECTS__ */ () => {
  const instance = getCurrentInstance();
  let meta = null;
  return instance && (meta = getComponentOptions(instance)[DEVTOOLS_META]) ? { [DEVTOOLS_META]: meta } : null;
};
function createComposer(options = {}, VueI18nLegacy) {
  const { __root, __injectWithOption } = options;
  const _isGlobal = __root === void 0;
  const flatJson = options.flatJson;
  const _ref = shallowRef;
  const translateExistCompatible = !!options.translateExistCompatible;
  if (process.env.NODE_ENV !== "production") {
    if (translateExistCompatible && true) {
      warnOnce(getWarnMessage(I18nWarnCodes.NOTICE_DROP_TRANSLATE_EXIST_COMPATIBLE_FLAG));
    }
  }
  let _inheritLocale = isBoolean(options.inheritLocale) ? options.inheritLocale : true;
  const _locale = _ref(
    // prettier-ignore
    __root && _inheritLocale ? __root.locale.value : isString(options.locale) ? options.locale : DEFAULT_LOCALE
  );
  const _fallbackLocale = _ref(
    // prettier-ignore
    __root && _inheritLocale ? __root.fallbackLocale.value : isString(options.fallbackLocale) || isArray(options.fallbackLocale) || isPlainObject(options.fallbackLocale) || options.fallbackLocale === false ? options.fallbackLocale : _locale.value
  );
  const _messages = _ref(getLocaleMessages(_locale.value, options));
  const _datetimeFormats = _ref(isPlainObject(options.datetimeFormats) ? options.datetimeFormats : { [_locale.value]: {} });
  const _numberFormats = _ref(isPlainObject(options.numberFormats) ? options.numberFormats : { [_locale.value]: {} });
  let _missingWarn = __root ? __root.missingWarn : isBoolean(options.missingWarn) || isRegExp(options.missingWarn) ? options.missingWarn : true;
  let _fallbackWarn = __root ? __root.fallbackWarn : isBoolean(options.fallbackWarn) || isRegExp(options.fallbackWarn) ? options.fallbackWarn : true;
  let _fallbackRoot = __root ? __root.fallbackRoot : isBoolean(options.fallbackRoot) ? options.fallbackRoot : true;
  let _fallbackFormat = !!options.fallbackFormat;
  let _missing = isFunction(options.missing) ? options.missing : null;
  let _runtimeMissing = isFunction(options.missing) ? defineCoreMissingHandler(options.missing) : null;
  let _postTranslation = isFunction(options.postTranslation) ? options.postTranslation : null;
  let _warnHtmlMessage = __root ? __root.warnHtmlMessage : isBoolean(options.warnHtmlMessage) ? options.warnHtmlMessage : true;
  let _escapeParameter = !!options.escapeParameter;
  const _modifiers = __root ? __root.modifiers : isPlainObject(options.modifiers) ? options.modifiers : {};
  let _pluralRules = options.pluralRules || __root && __root.pluralRules;
  let _context;
  const getCoreContext = () => {
    _isGlobal && setFallbackContext(null);
    const ctxOptions = {
      version: VERSION,
      locale: _locale.value,
      fallbackLocale: _fallbackLocale.value,
      messages: _messages.value,
      modifiers: _modifiers,
      pluralRules: _pluralRules,
      missing: _runtimeMissing === null ? void 0 : _runtimeMissing,
      missingWarn: _missingWarn,
      fallbackWarn: _fallbackWarn,
      fallbackFormat: _fallbackFormat,
      unresolving: true,
      postTranslation: _postTranslation === null ? void 0 : _postTranslation,
      warnHtmlMessage: _warnHtmlMessage,
      escapeParameter: _escapeParameter,
      messageResolver: options.messageResolver,
      messageCompiler: options.messageCompiler,
      __meta: { framework: "vue" }
    };
    {
      ctxOptions.datetimeFormats = _datetimeFormats.value;
      ctxOptions.numberFormats = _numberFormats.value;
      ctxOptions.__datetimeFormatters = isPlainObject(_context) ? _context.__datetimeFormatters : void 0;
      ctxOptions.__numberFormatters = isPlainObject(_context) ? _context.__numberFormatters : void 0;
    }
    if (process.env.NODE_ENV !== "production") {
      ctxOptions.__v_emitter = isPlainObject(_context) ? _context.__v_emitter : void 0;
    }
    const ctx = createCoreContext(ctxOptions);
    _isGlobal && setFallbackContext(ctx);
    return ctx;
  };
  _context = getCoreContext();
  updateFallbackLocale(_context, _locale.value, _fallbackLocale.value);
  function trackReactivityValues() {
    return [
      _locale.value,
      _fallbackLocale.value,
      _messages.value,
      _datetimeFormats.value,
      _numberFormats.value
    ];
  }
  const locale = computed({
    get: () => _locale.value,
    set: (val) => {
      _locale.value = val;
      _context.locale = _locale.value;
    }
  });
  const fallbackLocale = computed({
    get: () => _fallbackLocale.value,
    set: (val) => {
      _fallbackLocale.value = val;
      _context.fallbackLocale = _fallbackLocale.value;
      updateFallbackLocale(_context, _locale.value, val);
    }
  });
  const messages = computed(() => _messages.value);
  const datetimeFormats = /* @__PURE__ */ computed(() => _datetimeFormats.value);
  const numberFormats = /* @__PURE__ */ computed(() => _numberFormats.value);
  function getPostTranslationHandler() {
    return isFunction(_postTranslation) ? _postTranslation : null;
  }
  function setPostTranslationHandler(handler) {
    _postTranslation = handler;
    _context.postTranslation = handler;
  }
  function getMissingHandler() {
    return _missing;
  }
  function setMissingHandler(handler) {
    if (handler !== null) {
      _runtimeMissing = defineCoreMissingHandler(handler);
    }
    _missing = handler;
    _context.missing = _runtimeMissing;
  }
  function isResolvedTranslateMessage(type, arg) {
    return type !== "translate" || !arg.resolvedMessage;
  }
  const wrapWithDeps = (fn, argumentParser, warnType, fallbackSuccess, fallbackFail, successCondition) => {
    trackReactivityValues();
    let ret;
    try {
      if (process.env.NODE_ENV !== "production" || __INTLIFY_PROD_DEVTOOLS__) {
        /* @__PURE__ */ setAdditionalMeta(/* @__PURE__ */ getMetaInfo());
      }
      if (!_isGlobal) {
        _context.fallbackContext = __root ? getFallbackContext() : void 0;
      }
      ret = fn(_context);
    } finally {
      if (process.env.NODE_ENV !== "production" || __INTLIFY_PROD_DEVTOOLS__) ;
      if (!_isGlobal) {
        _context.fallbackContext = void 0;
      }
    }
    if (warnType !== "translate exists" && // for not `te` (e.g `t`)
    isNumber(ret) && ret === NOT_REOSLVED || warnType === "translate exists" && !ret) {
      const [key, arg2] = argumentParser();
      if (process.env.NODE_ENV !== "production" && __root && isString(key) && isResolvedTranslateMessage(warnType, arg2)) {
        if (_fallbackRoot && (isTranslateFallbackWarn(_fallbackWarn, key) || isTranslateMissingWarn(_missingWarn, key))) {
          warn(getWarnMessage(I18nWarnCodes.FALLBACK_TO_ROOT, {
            key,
            type: warnType
          }));
        }
        if (process.env.NODE_ENV !== "production") {
          const { __v_emitter: emitter } = _context;
          if (emitter && _fallbackRoot) {
            emitter.emit("fallback", {
              type: warnType,
              key,
              to: "global",
              groupId: `${warnType}:${key}`
            });
          }
        }
      }
      return __root && _fallbackRoot ? fallbackSuccess(__root) : fallbackFail(key);
    } else if (successCondition(ret)) {
      return ret;
    } else {
      throw createI18nError(I18nErrorCodes.UNEXPECTED_RETURN_TYPE);
    }
  };
  function t(...args) {
    return wrapWithDeps((context) => Reflect.apply(translate, null, [context, ...args]), () => parseTranslateArgs(...args), "translate", (root) => Reflect.apply(root.t, root, [...args]), (key) => key, (val) => isString(val));
  }
  function rt(...args) {
    const [arg1, arg2, arg3] = args;
    if (arg3 && !isObject(arg3)) {
      throw createI18nError(I18nErrorCodes.INVALID_ARGUMENT);
    }
    return t(...[arg1, arg2, assign({ resolvedMessage: true }, arg3 || {})]);
  }
  function d(...args) {
    return wrapWithDeps((context) => Reflect.apply(datetime, null, [context, ...args]), () => parseDateTimeArgs(...args), "datetime format", (root) => Reflect.apply(root.d, root, [...args]), () => MISSING_RESOLVE_VALUE, (val) => isString(val));
  }
  function n(...args) {
    return wrapWithDeps((context) => Reflect.apply(number, null, [context, ...args]), () => parseNumberArgs(...args), "number format", (root) => Reflect.apply(root.n, root, [...args]), () => MISSING_RESOLVE_VALUE, (val) => isString(val));
  }
  function normalize(values) {
    return values.map((val) => isString(val) || isNumber(val) || isBoolean(val) ? createTextNode(String(val)) : val);
  }
  const interpolate = (val) => val;
  const processor = {
    normalize,
    interpolate,
    type: "vnode"
  };
  function translateVNode(...args) {
    return wrapWithDeps(
      (context) => {
        let ret;
        const _context2 = context;
        try {
          _context2.processor = processor;
          ret = Reflect.apply(translate, null, [_context2, ...args]);
        } finally {
          _context2.processor = null;
        }
        return ret;
      },
      () => parseTranslateArgs(...args),
      "translate",
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
      (root) => root[TranslateVNodeSymbol](...args),
      (key) => [createTextNode(key)],
      (val) => isArray(val)
    );
  }
  function numberParts(...args) {
    return wrapWithDeps(
      (context) => Reflect.apply(number, null, [context, ...args]),
      () => parseNumberArgs(...args),
      "number format",
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
      (root) => root[NumberPartsSymbol](...args),
      NOOP_RETURN_ARRAY,
      (val) => isString(val) || isArray(val)
    );
  }
  function datetimeParts(...args) {
    return wrapWithDeps(
      (context) => Reflect.apply(datetime, null, [context, ...args]),
      () => parseDateTimeArgs(...args),
      "datetime format",
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
      (root) => root[DatetimePartsSymbol](...args),
      NOOP_RETURN_ARRAY,
      (val) => isString(val) || isArray(val)
    );
  }
  function setPluralRules(rules) {
    _pluralRules = rules;
    _context.pluralRules = _pluralRules;
  }
  function te(key, locale2) {
    return wrapWithDeps(() => {
      if (!key) {
        return false;
      }
      const targetLocale = isString(locale2) ? locale2 : _locale.value;
      const message = getLocaleMessage(targetLocale);
      const resolved = _context.messageResolver(message, key);
      return !translateExistCompatible ? isMessageAST(resolved) || isMessageFunction(resolved) || isString(resolved) : resolved != null;
    }, () => [key], "translate exists", (root) => {
      return Reflect.apply(root.te, root, [key, locale2]);
    }, NOOP_RETURN_FALSE, (val) => isBoolean(val));
  }
  function resolveMessages(key) {
    let messages2 = null;
    const locales = fallbackWithLocaleChain(_context, _fallbackLocale.value, _locale.value);
    for (let i = 0; i < locales.length; i++) {
      const targetLocaleMessages = _messages.value[locales[i]] || {};
      const messageValue = _context.messageResolver(targetLocaleMessages, key);
      if (messageValue != null) {
        messages2 = messageValue;
        break;
      }
    }
    return messages2;
  }
  function tm(key) {
    const messages2 = resolveMessages(key);
    return messages2 != null ? messages2 : __root ? __root.tm(key) || {} : {};
  }
  function getLocaleMessage(locale2) {
    return _messages.value[locale2] || {};
  }
  function setLocaleMessage(locale2, message) {
    if (flatJson) {
      const _message = { [locale2]: message };
      for (const key in _message) {
        if (hasOwn(_message, key)) {
          handleFlatJson(_message[key]);
        }
      }
      message = _message[locale2];
    }
    _messages.value[locale2] = message;
    _context.messages = _messages.value;
  }
  function mergeLocaleMessage2(locale2, message) {
    _messages.value[locale2] = _messages.value[locale2] || {};
    const _message = { [locale2]: message };
    if (flatJson) {
      for (const key in _message) {
        if (hasOwn(_message, key)) {
          handleFlatJson(_message[key]);
        }
      }
    }
    message = _message[locale2];
    deepCopy(message, _messages.value[locale2]);
    _context.messages = _messages.value;
  }
  function getDateTimeFormat(locale2) {
    return _datetimeFormats.value[locale2] || {};
  }
  function setDateTimeFormat(locale2, format2) {
    _datetimeFormats.value[locale2] = format2;
    _context.datetimeFormats = _datetimeFormats.value;
    clearDateTimeFormat(_context, locale2, format2);
  }
  function mergeDateTimeFormat(locale2, format2) {
    _datetimeFormats.value[locale2] = assign(_datetimeFormats.value[locale2] || {}, format2);
    _context.datetimeFormats = _datetimeFormats.value;
    clearDateTimeFormat(_context, locale2, format2);
  }
  function getNumberFormat(locale2) {
    return _numberFormats.value[locale2] || {};
  }
  function setNumberFormat(locale2, format2) {
    _numberFormats.value[locale2] = format2;
    _context.numberFormats = _numberFormats.value;
    clearNumberFormat(_context, locale2, format2);
  }
  function mergeNumberFormat(locale2, format2) {
    _numberFormats.value[locale2] = assign(_numberFormats.value[locale2] || {}, format2);
    _context.numberFormats = _numberFormats.value;
    clearNumberFormat(_context, locale2, format2);
  }
  composerID++;
  const composer = {
    id: composerID,
    locale,
    fallbackLocale,
    get inheritLocale() {
      return _inheritLocale;
    },
    set inheritLocale(val) {
      _inheritLocale = val;
      if (val && __root) {
        _locale.value = __root.locale.value;
        _fallbackLocale.value = __root.fallbackLocale.value;
        updateFallbackLocale(_context, _locale.value, _fallbackLocale.value);
      }
    },
    get availableLocales() {
      return Object.keys(_messages.value).sort();
    },
    messages,
    get modifiers() {
      return _modifiers;
    },
    get pluralRules() {
      return _pluralRules || {};
    },
    get isGlobal() {
      return _isGlobal;
    },
    get missingWarn() {
      return _missingWarn;
    },
    set missingWarn(val) {
      _missingWarn = val;
      _context.missingWarn = _missingWarn;
    },
    get fallbackWarn() {
      return _fallbackWarn;
    },
    set fallbackWarn(val) {
      _fallbackWarn = val;
      _context.fallbackWarn = _fallbackWarn;
    },
    get fallbackRoot() {
      return _fallbackRoot;
    },
    set fallbackRoot(val) {
      _fallbackRoot = val;
    },
    get fallbackFormat() {
      return _fallbackFormat;
    },
    set fallbackFormat(val) {
      _fallbackFormat = val;
      _context.fallbackFormat = _fallbackFormat;
    },
    get warnHtmlMessage() {
      return _warnHtmlMessage;
    },
    set warnHtmlMessage(val) {
      _warnHtmlMessage = val;
      _context.warnHtmlMessage = val;
    },
    get escapeParameter() {
      return _escapeParameter;
    },
    set escapeParameter(val) {
      _escapeParameter = val;
      _context.escapeParameter = val;
    },
    t,
    getLocaleMessage,
    setLocaleMessage,
    mergeLocaleMessage: mergeLocaleMessage2,
    getPostTranslationHandler,
    setPostTranslationHandler,
    getMissingHandler,
    setMissingHandler,
    [SetPluralRulesSymbol]: setPluralRules
  };
  {
    composer.datetimeFormats = datetimeFormats;
    composer.numberFormats = numberFormats;
    composer.rt = rt;
    composer.te = te;
    composer.tm = tm;
    composer.d = d;
    composer.n = n;
    composer.getDateTimeFormat = getDateTimeFormat;
    composer.setDateTimeFormat = setDateTimeFormat;
    composer.mergeDateTimeFormat = mergeDateTimeFormat;
    composer.getNumberFormat = getNumberFormat;
    composer.setNumberFormat = setNumberFormat;
    composer.mergeNumberFormat = mergeNumberFormat;
    composer[InejctWithOptionSymbol] = __injectWithOption;
    composer[TranslateVNodeSymbol] = translateVNode;
    composer[DatetimePartsSymbol] = datetimeParts;
    composer[NumberPartsSymbol] = numberParts;
  }
  if (process.env.NODE_ENV !== "production") {
    composer[EnableEmitter] = (emitter) => {
      _context.__v_emitter = emitter;
    };
    composer[DisableEmitter] = () => {
      _context.__v_emitter = void 0;
    };
  }
  return composer;
}
const baseFormatProps = {
  tag: {
    type: [String, Object]
  },
  locale: {
    type: String
  },
  scope: {
    type: String,
    // NOTE: avoid https://github.com/microsoft/rushstack/issues/1050
    validator: (val) => val === "parent" || val === "global",
    default: "parent"
    /* ComponentI18nScope */
  },
  i18n: {
    type: Object
  }
};
function getInterpolateArg({ slots }, keys) {
  if (keys.length === 1 && keys[0] === "default") {
    const ret = slots.default ? slots.default() : [];
    return ret.reduce((slot, current) => {
      return [
        ...slot,
        // prettier-ignore
        ...current.type === Fragment ? current.children : [current]
      ];
    }, []);
  } else {
    return keys.reduce((arg, key) => {
      const slot = slots[key];
      if (slot) {
        arg[key] = slot();
      }
      return arg;
    }, create());
  }
}
function getFragmentableTag(tag) {
  return Fragment;
}
const TranslationImpl = /* @__PURE__ */ defineComponent({
  /* eslint-disable */
  name: "i18n-t",
  props: assign({
    keypath: {
      type: String,
      required: true
    },
    plural: {
      type: [Number, String],
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
      validator: (val) => isNumber(val) || !isNaN(val)
    }
  }, baseFormatProps),
  /* eslint-enable */
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  setup(props, context) {
    const { slots, attrs } = context;
    const i18n = props.i18n || useI18n({
      useScope: props.scope,
      __useComponent: true
    });
    return () => {
      const keys = Object.keys(slots).filter((key) => key !== "_");
      const options = create();
      if (props.locale) {
        options.locale = props.locale;
      }
      if (props.plural !== void 0) {
        options.plural = isString(props.plural) ? +props.plural : props.plural;
      }
      const arg = getInterpolateArg(context, keys);
      const children = i18n[TranslateVNodeSymbol](props.keypath, arg, options);
      const assignedAttrs = assign(create(), attrs);
      const tag = isString(props.tag) || isObject(props.tag) ? props.tag : getFragmentableTag();
      return h(tag, assignedAttrs, children);
    };
  }
});
const Translation = TranslationImpl;
function isVNode(target) {
  return isArray(target) && !isString(target[0]);
}
function renderFormatter(props, context, slotKeys, partFormatter) {
  const { slots, attrs } = context;
  return () => {
    const options = { part: true };
    let overrides = create();
    if (props.locale) {
      options.locale = props.locale;
    }
    if (isString(props.format)) {
      options.key = props.format;
    } else if (isObject(props.format)) {
      if (isString(props.format.key)) {
        options.key = props.format.key;
      }
      overrides = Object.keys(props.format).reduce((options2, prop) => {
        return slotKeys.includes(prop) ? assign(create(), options2, { [prop]: props.format[prop] }) : options2;
      }, create());
    }
    const parts = partFormatter(...[props.value, options, overrides]);
    let children = [options.key];
    if (isArray(parts)) {
      children = parts.map((part, index) => {
        const slot = slots[part.type];
        const node = slot ? slot({ [part.type]: part.value, index, parts }) : [part.value];
        if (isVNode(node)) {
          node[0].key = `${part.type}-${index}`;
        }
        return node;
      });
    } else if (isString(parts)) {
      children = [parts];
    }
    const assignedAttrs = assign(create(), attrs);
    const tag = isString(props.tag) || isObject(props.tag) ? props.tag : getFragmentableTag();
    return h(tag, assignedAttrs, children);
  };
}
const NumberFormatImpl = /* @__PURE__ */ defineComponent({
  /* eslint-disable */
  name: "i18n-n",
  props: assign({
    value: {
      type: Number,
      required: true
    },
    format: {
      type: [String, Object]
    }
  }, baseFormatProps),
  /* eslint-enable */
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  setup(props, context) {
    const i18n = props.i18n || useI18n({
      useScope: props.scope,
      __useComponent: true
    });
    return renderFormatter(props, context, NUMBER_FORMAT_OPTIONS_KEYS, (...args) => (
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
      i18n[NumberPartsSymbol](...args)
    ));
  }
});
const NumberFormat = NumberFormatImpl;
const DatetimeFormatImpl = /* @__PURE__ */ defineComponent({
  /* eslint-disable */
  name: "i18n-d",
  props: assign({
    value: {
      type: [Number, Date],
      required: true
    },
    format: {
      type: [String, Object]
    }
  }, baseFormatProps),
  /* eslint-enable */
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  setup(props, context) {
    const i18n = props.i18n || useI18n({
      useScope: props.scope,
      __useComponent: true
    });
    return renderFormatter(props, context, DATETIME_FORMAT_OPTIONS_KEYS, (...args) => (
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
      i18n[DatetimePartsSymbol](...args)
    ));
  }
});
const DatetimeFormat = DatetimeFormatImpl;
function getComposer$2(i18n, instance) {
  const i18nInternal = i18n;
  if (i18n.mode === "composition") {
    return i18nInternal.__getInstance(instance) || i18n.global;
  } else {
    const vueI18n = i18nInternal.__getInstance(instance);
    return vueI18n != null ? vueI18n.__composer : i18n.global.__composer;
  }
}
function vTDirective(i18n) {
  const _process = (binding) => {
    const { instance, modifiers, value } = binding;
    if (!instance || !instance.$) {
      throw createI18nError(I18nErrorCodes.UNEXPECTED_ERROR);
    }
    const composer = getComposer$2(i18n, instance.$);
    if (process.env.NODE_ENV !== "production" && modifiers.preserve) {
      warn(getWarnMessage(I18nWarnCodes.NOT_SUPPORTED_PRESERVE));
    }
    const parsedValue = parseValue(value);
    return [
      Reflect.apply(composer.t, composer, [...makeParams(parsedValue)]),
      composer
    ];
  };
  const register = (el, binding) => {
    const [textContent, composer] = _process(binding);
    el.__composer = composer;
    el.textContent = textContent;
  };
  const unregister = (el) => {
    if (el.__composer) {
      el.__composer = void 0;
      delete el.__composer;
    }
  };
  const update = (el, { value }) => {
    if (el.__composer) {
      const composer = el.__composer;
      const parsedValue = parseValue(value);
      el.textContent = Reflect.apply(composer.t, composer, [
        ...makeParams(parsedValue)
      ]);
    }
  };
  const getSSRProps = (binding) => {
    const [textContent] = _process(binding);
    return { textContent };
  };
  return {
    created: register,
    unmounted: unregister,
    beforeUpdate: update,
    getSSRProps
  };
}
function parseValue(value) {
  if (isString(value)) {
    return { path: value };
  } else if (isPlainObject(value)) {
    if (!("path" in value)) {
      throw createI18nError(I18nErrorCodes.REQUIRED_VALUE, "path");
    }
    return value;
  } else {
    throw createI18nError(I18nErrorCodes.INVALID_VALUE);
  }
}
function makeParams(value) {
  const { path, locale, args, choice, plural } = value;
  const options = {};
  const named = args || {};
  if (isString(locale)) {
    options.locale = locale;
  }
  if (isNumber(choice)) {
    options.plural = choice;
  }
  if (isNumber(plural)) {
    options.plural = plural;
  }
  return [path, named, options];
}
function apply(app, i18n, ...options) {
  const pluginOptions = isPlainObject(options[0]) ? options[0] : {};
  const useI18nComponentName = !!pluginOptions.useI18nComponentName;
  const globalInstall = isBoolean(pluginOptions.globalInstall) ? pluginOptions.globalInstall : true;
  if (process.env.NODE_ENV !== "production" && globalInstall && useI18nComponentName) {
    warn(getWarnMessage(I18nWarnCodes.COMPONENT_NAME_LEGACY_COMPATIBLE, {
      name: Translation.name
    }));
  }
  if (globalInstall) {
    [!useI18nComponentName ? Translation.name : "i18n", "I18nT"].forEach((name) => app.component(name, Translation));
    [NumberFormat.name, "I18nN"].forEach((name) => app.component(name, NumberFormat));
    [DatetimeFormat.name, "I18nD"].forEach((name) => app.component(name, DatetimeFormat));
  }
  {
    app.directive("t", vTDirective(i18n));
  }
}
const VueDevToolsLabels = {
  [
    "vue-devtools-plugin-vue-i18n"
    /* VueDevToolsIDs.PLUGIN */
  ]: "Vue I18n devtools",
  [
    "vue-i18n-resource-inspector"
    /* VueDevToolsIDs.CUSTOM_INSPECTOR */
  ]: "I18n Resources",
  [
    "vue-i18n-timeline"
    /* VueDevToolsIDs.TIMELINE */
  ]: "Vue I18n"
};
const VueDevToolsPlaceholders = {
  [
    "vue-i18n-resource-inspector"
    /* VueDevToolsIDs.CUSTOM_INSPECTOR */
  ]: "Search for scopes ..."
};
const VueDevToolsTimelineColors = {
  [
    "vue-i18n-timeline"
    /* VueDevToolsIDs.TIMELINE */
  ]: 16764185
};
const VUE_I18N_COMPONENT_TYPES = "vue-i18n: composer properties";
let devtoolsApi;
async function enableDevTools(app, i18n) {
  return new Promise((resolve2, reject) => {
    try {
      setupDevtoolsPlugin({
        id: "vue-devtools-plugin-vue-i18n",
        label: VueDevToolsLabels[
          "vue-devtools-plugin-vue-i18n"
          /* VueDevToolsIDs.PLUGIN */
        ],
        packageName: "vue-i18n",
        homepage: "https://vue-i18n.intlify.dev",
        logo: "https://vue-i18n.intlify.dev/vue-i18n-devtools-logo.png",
        componentStateTypes: [VUE_I18N_COMPONENT_TYPES],
        app
        // eslint-disable-line @typescript-eslint/no-explicit-any
      }, (api) => {
        devtoolsApi = api;
        api.on.visitComponentTree(({ componentInstance, treeNode }) => {
          updateComponentTreeTags(componentInstance, treeNode, i18n);
        });
        api.on.inspectComponent(({ componentInstance, instanceData }) => {
          if (componentInstance.vnode.el && componentInstance.vnode.el.__VUE_I18N__ && instanceData) {
            if (i18n.mode === "legacy") {
              if (componentInstance.vnode.el.__VUE_I18N__ !== i18n.global.__composer) {
                inspectComposer(instanceData, componentInstance.vnode.el.__VUE_I18N__);
              }
            } else {
              inspectComposer(instanceData, componentInstance.vnode.el.__VUE_I18N__);
            }
          }
        });
        api.addInspector({
          id: "vue-i18n-resource-inspector",
          label: VueDevToolsLabels[
            "vue-i18n-resource-inspector"
            /* VueDevToolsIDs.CUSTOM_INSPECTOR */
          ],
          icon: "language",
          treeFilterPlaceholder: VueDevToolsPlaceholders[
            "vue-i18n-resource-inspector"
            /* VueDevToolsIDs.CUSTOM_INSPECTOR */
          ]
        });
        api.on.getInspectorTree((payload) => {
          if (payload.app === app && payload.inspectorId === "vue-i18n-resource-inspector") {
            registerScope(payload, i18n);
          }
        });
        const roots = /* @__PURE__ */ new Map();
        api.on.getInspectorState(async (payload) => {
          if (payload.app === app && payload.inspectorId === "vue-i18n-resource-inspector") {
            api.unhighlightElement();
            inspectScope(payload, i18n);
            if (payload.nodeId === "global") {
              if (!roots.has(payload.app)) {
                const [root] = await api.getComponentInstances(payload.app);
                roots.set(payload.app, root);
              }
              api.highlightElement(roots.get(payload.app));
            } else {
              const instance = getComponentInstance(payload.nodeId, i18n);
              instance && api.highlightElement(instance);
            }
          }
        });
        api.on.editInspectorState((payload) => {
          if (payload.app === app && payload.inspectorId === "vue-i18n-resource-inspector") {
            editScope(payload, i18n);
          }
        });
        api.addTimelineLayer({
          id: "vue-i18n-timeline",
          label: VueDevToolsLabels[
            "vue-i18n-timeline"
            /* VueDevToolsIDs.TIMELINE */
          ],
          color: VueDevToolsTimelineColors[
            "vue-i18n-timeline"
            /* VueDevToolsIDs.TIMELINE */
          ]
        });
        resolve2(true);
      });
    } catch (e) {
      console.error(e);
      reject(false);
    }
  });
}
function getI18nScopeLable(instance) {
  return instance.type.name || instance.type.displayName || instance.type.__file || "Anonymous";
}
function updateComponentTreeTags(instance, treeNode, i18n) {
  const global2 = i18n.mode === "composition" ? i18n.global : i18n.global.__composer;
  if (instance && instance.vnode.el && instance.vnode.el.__VUE_I18N__) {
    if (instance.vnode.el.__VUE_I18N__ !== global2) {
      const tag = {
        label: `i18n (${getI18nScopeLable(instance)} Scope)`,
        textColor: 0,
        backgroundColor: 16764185
      };
      treeNode.tags.push(tag);
    }
  }
}
function inspectComposer(instanceData, composer) {
  const type = VUE_I18N_COMPONENT_TYPES;
  instanceData.state.push({
    type,
    key: "locale",
    editable: true,
    value: composer.locale.value
  });
  instanceData.state.push({
    type,
    key: "availableLocales",
    editable: false,
    value: composer.availableLocales
  });
  instanceData.state.push({
    type,
    key: "fallbackLocale",
    editable: true,
    value: composer.fallbackLocale.value
  });
  instanceData.state.push({
    type,
    key: "inheritLocale",
    editable: true,
    value: composer.inheritLocale
  });
  instanceData.state.push({
    type,
    key: "messages",
    editable: false,
    value: getLocaleMessageValue(composer.messages.value)
  });
  {
    instanceData.state.push({
      type,
      key: "datetimeFormats",
      editable: false,
      value: composer.datetimeFormats.value
    });
    instanceData.state.push({
      type,
      key: "numberFormats",
      editable: false,
      value: composer.numberFormats.value
    });
  }
}
function getLocaleMessageValue(messages) {
  const value = {};
  Object.keys(messages).forEach((key) => {
    const v = messages[key];
    if (isFunction(v) && "source" in v) {
      value[key] = getMessageFunctionDetails(v);
    } else if (isMessageAST(v) && v.loc && v.loc.source) {
      value[key] = v.loc.source;
    } else if (isObject(v)) {
      value[key] = getLocaleMessageValue(v);
    } else {
      value[key] = v;
    }
  });
  return value;
}
const ESC = {
  "<": "&lt;",
  ">": "&gt;",
  '"': "&quot;",
  "&": "&amp;"
};
function escape(s) {
  return s.replace(/[<>"&]/g, escapeChar);
}
function escapeChar(a) {
  return ESC[a] || a;
}
function getMessageFunctionDetails(func) {
  const argString = func.source ? `("${escape(func.source)}")` : `(?)`;
  return {
    _custom: {
      type: "function",
      display: `<span>ƒ</span> ${argString}`
    }
  };
}
function registerScope(payload, i18n) {
  payload.rootNodes.push({
    id: "global",
    label: "Global Scope"
  });
  const global2 = i18n.mode === "composition" ? i18n.global : i18n.global.__composer;
  for (const [keyInstance, instance] of i18n.__instances) {
    const composer = i18n.mode === "composition" ? instance : instance.__composer;
    if (global2 === composer) {
      continue;
    }
    payload.rootNodes.push({
      id: composer.id.toString(),
      label: `${getI18nScopeLable(keyInstance)} Scope`
    });
  }
}
function getComponentInstance(nodeId, i18n) {
  let instance = null;
  if (nodeId !== "global") {
    for (const [component, composer] of i18n.__instances.entries()) {
      if (composer.id.toString() === nodeId) {
        instance = component;
        break;
      }
    }
  }
  return instance;
}
function getComposer$1(nodeId, i18n) {
  if (nodeId === "global") {
    return i18n.mode === "composition" ? i18n.global : i18n.global.__composer;
  } else {
    const instance = Array.from(i18n.__instances.values()).find((item) => item.id.toString() === nodeId);
    if (instance) {
      return i18n.mode === "composition" ? instance : instance.__composer;
    } else {
      return null;
    }
  }
}
function inspectScope(payload, i18n) {
  const composer = getComposer$1(payload.nodeId, i18n);
  if (composer) {
    payload.state = makeScopeInspectState(composer);
  }
  return null;
}
function makeScopeInspectState(composer) {
  const state = {};
  const localeType = "Locale related info";
  const localeStates = [
    {
      type: localeType,
      key: "locale",
      editable: true,
      value: composer.locale.value
    },
    {
      type: localeType,
      key: "fallbackLocale",
      editable: true,
      value: composer.fallbackLocale.value
    },
    {
      type: localeType,
      key: "availableLocales",
      editable: false,
      value: composer.availableLocales
    },
    {
      type: localeType,
      key: "inheritLocale",
      editable: true,
      value: composer.inheritLocale
    }
  ];
  state[localeType] = localeStates;
  const localeMessagesType = "Locale messages info";
  const localeMessagesStates = [
    {
      type: localeMessagesType,
      key: "messages",
      editable: false,
      value: getLocaleMessageValue(composer.messages.value)
    }
  ];
  state[localeMessagesType] = localeMessagesStates;
  {
    const datetimeFormatsType = "Datetime formats info";
    const datetimeFormatsStates = [
      {
        type: datetimeFormatsType,
        key: "datetimeFormats",
        editable: false,
        value: composer.datetimeFormats.value
      }
    ];
    state[datetimeFormatsType] = datetimeFormatsStates;
    const numberFormatsType = "Datetime formats info";
    const numberFormatsStates = [
      {
        type: numberFormatsType,
        key: "numberFormats",
        editable: false,
        value: composer.numberFormats.value
      }
    ];
    state[numberFormatsType] = numberFormatsStates;
  }
  return state;
}
function addTimelineEvent(event, payload) {
  if (devtoolsApi) {
    let groupId;
    if (payload && "groupId" in payload) {
      groupId = payload.groupId;
      delete payload.groupId;
    }
    devtoolsApi.addTimelineEvent({
      layerId: "vue-i18n-timeline",
      event: {
        title: event,
        groupId,
        time: Date.now(),
        meta: {},
        data: payload || {},
        logType: event === "compile-error" ? "error" : event === "fallback" || event === "missing" ? "warning" : "default"
      }
    });
  }
}
function editScope(payload, i18n) {
  const composer = getComposer$1(payload.nodeId, i18n);
  if (composer) {
    const [field] = payload.path;
    if (field === "locale" && isString(payload.state.value)) {
      composer.locale.value = payload.state.value;
    } else if (field === "fallbackLocale" && (isString(payload.state.value) || isArray(payload.state.value) || isObject(payload.state.value))) {
      composer.fallbackLocale.value = payload.state.value;
    } else if (field === "inheritLocale" && isBoolean(payload.state.value)) {
      composer.inheritLocale = payload.state.value;
    }
  }
}
const I18nInjectionKey = /* @__PURE__ */ makeSymbol("global-vue-i18n");
function createI18n(options = {}, VueI18nLegacy) {
  const __globalInjection = isBoolean(options.globalInjection) ? options.globalInjection : true;
  const __allowComposition = true;
  const __instances = /* @__PURE__ */ new Map();
  const [globalScope, __global] = createGlobal(options);
  const symbol = /* @__PURE__ */ makeSymbol(process.env.NODE_ENV !== "production" ? "vue-i18n" : "");
  if (process.env.NODE_ENV !== "production") ;
  function __getInstance(component) {
    return __instances.get(component) || null;
  }
  function __setInstance(component, instance) {
    __instances.set(component, instance);
  }
  function __deleteInstance(component) {
    __instances.delete(component);
  }
  {
    const i18n = {
      // mode
      get mode() {
        return "composition";
      },
      // allowComposition
      get allowComposition() {
        return __allowComposition;
      },
      // install plugin
      async install(app, ...options2) {
        if ((process.env.NODE_ENV !== "production" || false) && true) {
          app.__VUE_I18N__ = i18n;
        }
        app.__VUE_I18N_SYMBOL__ = symbol;
        app.provide(app.__VUE_I18N_SYMBOL__, i18n);
        if (isPlainObject(options2[0])) {
          const opts = options2[0];
          i18n.__composerExtend = opts.__composerExtend;
          i18n.__vueI18nExtend = opts.__vueI18nExtend;
        }
        let globalReleaseHandler = null;
        if (__globalInjection) {
          globalReleaseHandler = injectGlobalFields(app, i18n.global);
        }
        {
          apply(app, i18n, ...options2);
        }
        const unmountApp = app.unmount;
        app.unmount = () => {
          globalReleaseHandler && globalReleaseHandler();
          i18n.dispose();
          unmountApp();
        };
        if ((process.env.NODE_ENV !== "production" || false) && true) {
          const ret = await enableDevTools(app, i18n);
          if (!ret) {
            throw createI18nError(I18nErrorCodes.CANNOT_SETUP_VUE_DEVTOOLS_PLUGIN);
          }
          const emitter = createEmitter();
          {
            const _composer = __global;
            _composer[EnableEmitter] && _composer[EnableEmitter](emitter);
          }
          emitter.on("*", addTimelineEvent);
        }
      },
      // global accessor
      get global() {
        return __global;
      },
      dispose() {
        globalScope.stop();
      },
      // @internal
      __instances,
      // @internal
      __getInstance,
      // @internal
      __setInstance,
      // @internal
      __deleteInstance
    };
    return i18n;
  }
}
function useI18n(options = {}) {
  const instance = getCurrentInstance();
  if (instance == null) {
    throw createI18nError(I18nErrorCodes.MUST_BE_CALL_SETUP_TOP);
  }
  if (!instance.isCE && instance.appContext.app != null && !instance.appContext.app.__VUE_I18N_SYMBOL__) {
    throw createI18nError(I18nErrorCodes.NOT_INSTALLED);
  }
  const i18n = getI18nInstance(instance);
  const gl = getGlobalComposer(i18n);
  const componentOptions = getComponentOptions(instance);
  const scope = getScope(options, componentOptions);
  if (scope === "global") {
    adjustI18nResources(gl, options, componentOptions);
    return gl;
  }
  if (scope === "parent") {
    let composer2 = getComposer(i18n, instance, options.__useComponent);
    if (composer2 == null) {
      if (process.env.NODE_ENV !== "production") {
        warn(getWarnMessage(I18nWarnCodes.NOT_FOUND_PARENT_SCOPE));
      }
      composer2 = gl;
    }
    return composer2;
  }
  const i18nInternal = i18n;
  let composer = i18nInternal.__getInstance(instance);
  if (composer == null) {
    const composerOptions = assign({}, options);
    if ("__i18n" in componentOptions) {
      composerOptions.__i18n = componentOptions.__i18n;
    }
    if (gl) {
      composerOptions.__root = gl;
    }
    composer = createComposer(composerOptions);
    if (i18nInternal.__composerExtend) {
      composer[DisposeSymbol] = i18nInternal.__composerExtend(composer);
    }
    i18nInternal.__setInstance(instance, composer);
  }
  return composer;
}
function createGlobal(options, legacyMode, VueI18nLegacy) {
  const scope = effectScope();
  {
    const obj = scope.run(() => createComposer(options));
    if (obj == null) {
      throw createI18nError(I18nErrorCodes.UNEXPECTED_ERROR);
    }
    return [scope, obj];
  }
}
function getI18nInstance(instance) {
  {
    const i18n = inject(!instance.isCE ? instance.appContext.app.__VUE_I18N_SYMBOL__ : I18nInjectionKey);
    if (!i18n) {
      throw createI18nError(!instance.isCE ? I18nErrorCodes.UNEXPECTED_ERROR : I18nErrorCodes.NOT_INSTALLED_WITH_PROVIDE);
    }
    return i18n;
  }
}
function getScope(options, componentOptions) {
  return isEmptyObject(options) ? "__i18n" in componentOptions ? "local" : "global" : !options.useScope ? "local" : options.useScope;
}
function getGlobalComposer(i18n) {
  return i18n.mode === "composition" ? i18n.global : i18n.global.__composer;
}
function getComposer(i18n, target, useComponent = false) {
  let composer = null;
  const root = target.root;
  let current = getParentComponentInstance(target, useComponent);
  while (current != null) {
    const i18nInternal = i18n;
    if (i18n.mode === "composition") {
      composer = i18nInternal.__getInstance(current);
    }
    if (composer != null) {
      break;
    }
    if (root === current) {
      break;
    }
    current = current.parent;
  }
  return composer;
}
function getParentComponentInstance(target, useComponent = false) {
  if (target == null) {
    return null;
  }
  {
    return !useComponent ? target.parent : target.vnode.ctx || target.parent;
  }
}
const globalExportProps = [
  "locale",
  "fallbackLocale",
  "availableLocales"
];
const globalExportMethods = ["t", "rt", "d", "n", "tm", "te"];
function injectGlobalFields(app, composer) {
  const i18n = /* @__PURE__ */ Object.create(null);
  globalExportProps.forEach((prop) => {
    const desc = Object.getOwnPropertyDescriptor(composer, prop);
    if (!desc) {
      throw createI18nError(I18nErrorCodes.UNEXPECTED_ERROR);
    }
    const wrap = isRef(desc.value) ? {
      get() {
        return desc.value.value;
      },
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
      set(val) {
        desc.value.value = val;
      }
    } : {
      get() {
        return desc.get && desc.get();
      }
    };
    Object.defineProperty(i18n, prop, wrap);
  });
  app.config.globalProperties.$i18n = i18n;
  globalExportMethods.forEach((method) => {
    const desc = Object.getOwnPropertyDescriptor(composer, method);
    if (!desc || !desc.value) {
      throw createI18nError(I18nErrorCodes.UNEXPECTED_ERROR);
    }
    Object.defineProperty(app.config.globalProperties, `$${method}`, desc);
  });
  const dispose = () => {
    delete app.config.globalProperties.$i18n;
    globalExportMethods.forEach((method) => {
      delete app.config.globalProperties[`$${method}`];
    });
  };
  return dispose;
}
{
  initFeatureFlags();
}
{
  registerMessageCompiler(compile);
}
registerMessageResolver(resolveValue);
registerLocaleFallbacker(fallbackWithLocaleChain);
if (process.env.NODE_ENV !== "production" || __INTLIFY_PROD_DEVTOOLS__) {
  const target = getGlobalThis();
  target.__INTLIFY__ = true;
  setDevToolsHook(target.__INTLIFY_DEVTOOLS_GLOBAL_HOOK__);
}
if (process.env.NODE_ENV !== "production") ;
function useSwitchLocalePath() {
  return wrapComposable(switchLocalePath);
}
const switch_locale_path_ssr_NflG9_QeVcJ1jVig0vCfxB_cZhpEMQ9U2ujRUiYbbVw = /* @__PURE__ */ defineNuxtPlugin({
  name: "i18n:plugin:switch-locale-path-ssr",
  dependsOn: ["i18n:plugin"],
  setup(nuxt) {
    if (nuxt.$config.public.i18n.experimental.switchLocalePathLinkSSR !== true) return;
    const switchLocalePath2 = useSwitchLocalePath();
    const switchLocalePathLinkWrapperExpr = new RegExp(
      [
        `<!--${SWITCH_LOCALE_PATH_LINK_IDENTIFIER}-\\[(\\w+)\\]-->`,
        `.+?`,
        `<!--/${SWITCH_LOCALE_PATH_LINK_IDENTIFIER}-->`
      ].join(""),
      "g"
    );
    nuxt.hook("app:rendered", (ctx) => {
      var _a;
      if (((_a = ctx.renderResult) == null ? void 0 : _a.html) == null) return;
      ctx.renderResult.html = ctx.renderResult.html.replaceAll(
        switchLocalePathLinkWrapperExpr,
        (match, p1) => match.replace(/href="([^"]+)"/, `href="${encodeURI(switchLocalePath2(p1 ?? ""))}"`)
      );
    });
  }
});
function extendI18n(i18n, {
  locales = [],
  localeCodes: localeCodes2 = [],
  baseUrl = "",
  hooks = {},
  context = {}
} = {}) {
  const scope = effectScope();
  const orgInstall = i18n.install;
  i18n.install = (vue, ...options) => {
    const pluginOptions = isPluginOptions(options[0]) ? assign({}, options[0]) : { inject: true };
    if (pluginOptions.inject == null) {
      pluginOptions.inject = true;
    }
    const orgComposerExtend = pluginOptions.__composerExtend;
    pluginOptions.__composerExtend = (localComposer) => {
      const globalComposer2 = getComposer$3(i18n);
      localComposer.locales = computed(() => globalComposer2.locales.value);
      localComposer.localeCodes = computed(() => globalComposer2.localeCodes.value);
      localComposer.baseUrl = computed(() => globalComposer2.baseUrl.value);
      let orgComposerDispose;
      if (isFunction(orgComposerExtend)) {
        orgComposerDispose = Reflect.apply(orgComposerExtend, pluginOptions, [localComposer]);
      }
      return () => {
        orgComposerDispose && orgComposerDispose();
      };
    };
    if (i18n.mode === "legacy") {
      const orgVueI18nExtend = pluginOptions.__vueI18nExtend;
      pluginOptions.__vueI18nExtend = (vueI18n) => {
        extendVueI18n(vueI18n, hooks.onExtendVueI18n);
        let orgVueI18nDispose;
        if (isFunction(orgVueI18nExtend)) {
          orgVueI18nDispose = Reflect.apply(orgVueI18nExtend, pluginOptions, [vueI18n]);
        }
        return () => {
          orgVueI18nDispose && orgVueI18nDispose();
        };
      };
    }
    options[0] = pluginOptions;
    Reflect.apply(orgInstall, i18n, [vue, ...options]);
    const globalComposer = getComposer$3(i18n);
    scope.run(() => {
      extendComposer(globalComposer, { locales, localeCodes: localeCodes2, baseUrl, hooks, context });
      if (i18n.mode === "legacy" && isVueI18n(i18n.global)) {
        extendVueI18n(i18n.global, hooks.onExtendVueI18n);
      }
    });
    const app = vue;
    const exported = i18n.mode === "composition" ? app.config.globalProperties.$i18n : null;
    if (exported) {
      extendExportedGlobal(exported, globalComposer, hooks.onExtendExportedGlobal);
    }
    if (pluginOptions.inject) {
      const common = initCommonComposableOptions(i18n);
      vue.mixin({
        methods: {
          getRouteBaseName: wrapComposable(getRouteBaseName, common),
          resolveRoute: wrapComposable(resolveRoute, common),
          localePath: wrapComposable(localePath, common),
          localeRoute: wrapComposable(localeRoute, common),
          localeLocation: wrapComposable(localeLocation, common),
          switchLocalePath: wrapComposable(switchLocalePath, common),
          localeHead: wrapComposable(localeHead, common)
        }
      });
    }
    if (app.unmount) {
      const unmountApp = app.unmount;
      app.unmount = () => {
        scope.stop();
        unmountApp();
      };
    }
  };
  return scope;
}
function extendComposer(composer, options) {
  const { locales, localeCodes: localeCodes2, baseUrl, context } = options;
  const _locales = ref(locales);
  const _localeCodes = ref(localeCodes2);
  const _baseUrl = ref("");
  composer.locales = computed(() => _locales.value);
  composer.localeCodes = computed(() => _localeCodes.value);
  composer.baseUrl = computed(() => _baseUrl.value);
  {
    _baseUrl.value = resolveBaseUrl(baseUrl, context);
  }
  if (options.hooks && options.hooks.onExtendComposer) {
    options.hooks.onExtendComposer(composer);
  }
}
function extendPropertyDescriptors(composer, exported, hook) {
  const properties = [
    {
      locales: {
        get() {
          return composer.locales.value;
        }
      },
      localeCodes: {
        get() {
          return composer.localeCodes.value;
        }
      },
      baseUrl: {
        get() {
          return composer.baseUrl.value;
        }
      }
    }
  ];
  hook && properties.push(hook(composer));
  for (const property of properties) {
    for (const [key, descriptor] of Object.entries(property)) {
      Object.defineProperty(exported, key, descriptor);
    }
  }
}
function extendExportedGlobal(exported, g, hook) {
  extendPropertyDescriptors(g, exported, hook);
}
function extendVueI18n(vueI18n, hook) {
  const c = getComposer$3(vueI18n);
  extendPropertyDescriptors(c, vueI18n, hook);
}
function isPluginOptions(options) {
  return isObject(options) && ("inject" in options || "__composerExtend" in options || "__vueI18nExtend" in options);
}
const i18n_EI7LsD1KYQADczz5hrChviGQCdVM8yUkvFEZLJpmnvM = /* @__PURE__ */ defineNuxtPlugin({
  name: "i18n:plugin",
  parallel: parallelPlugin,
  async setup(nuxt) {
    let __temp, __restore;
    const route = useRoute();
    const { vueApp: app } = nuxt;
    const nuxtContext = nuxt;
    const host = getHost();
    const { configLocales, defaultLocale, multiDomainLocales, strategy } = nuxtContext.$config.public.i18n;
    const hasDefaultForDomains = configLocales.some(
      (l) => typeof l !== "string" && Array.isArray(l.defaultForDomains)
    );
    let defaultLocaleDomain;
    if (defaultLocale) {
      defaultLocaleDomain = defaultLocale;
    } else if (hasDefaultForDomains) {
      const findDefaultLocale = configLocales.find(
        (l) => typeof l === "string" || !Array.isArray(l.defaultForDomains) ? false : l.defaultForDomains.includes(host ?? "")
      );
      defaultLocaleDomain = (findDefaultLocale == null ? void 0 : findDefaultLocale.code) ?? "";
    } else {
      defaultLocaleDomain = "";
    }
    if (multiDomainLocales && (strategy === "prefix_except_default" || strategy === "prefix_and_default")) {
      const router = useRouter();
      router.getRoutes().forEach((route2) => {
        var _a;
        if ((_a = route2.name) == null ? void 0 : _a.toString().includes("___default")) {
          const routeNameLocale = route2.name.toString().split("___")[1];
          if (routeNameLocale !== defaultLocaleDomain) {
            router.removeRoute(route2.name);
          } else {
            const newRouteName = route2.name.toString().replace("___default", "");
            route2.name = newRouteName;
          }
        }
      });
    }
    const runtimeI18n = { ...nuxtContext.$config.public.i18n, defaultLocale: defaultLocaleDomain };
    runtimeI18n.baseUrl = extendBaseUrl();
    const _detectBrowserLanguage = runtimeDetectBrowserLanguage();
    const vueI18nOptions = ([__temp, __restore] = executeAsync(() => loadVueI18nOptions(vueI18nConfigs, useNuxtApp())), __temp = await __temp, __restore(), __temp);
    vueI18nOptions.messages = vueI18nOptions.messages || {};
    vueI18nOptions.fallbackLocale = vueI18nOptions.fallbackLocale ?? false;
    const getLocaleFromRoute = createLocaleFromRouteGetter();
    const getDefaultLocale = (locale) => locale || vueI18nOptions.locale || "en-US";
    const localeCookie = getI18nCookie();
    let initialLocale = detectLocale(
      route,
      getLocaleFromRoute,
      getDefaultLocale(runtimeI18n.defaultLocale),
      {
        ssg: "normal",
        callType: "setup",
        firstAccess: true,
        localeCookie: getLocaleCookie(localeCookie, _detectBrowserLanguage, runtimeI18n.defaultLocale)
      },
      runtimeI18n
    );
    vueI18nOptions.messages = ([__temp, __restore] = executeAsync(() => loadInitialMessages(vueI18nOptions.messages, localeLoaders, {
      localeCodes,
      initialLocale,
      lazy: runtimeI18n.lazy,
      defaultLocale: runtimeI18n.defaultLocale,
      fallbackLocale: vueI18nOptions.fallbackLocale
    })), __temp = await __temp, __restore(), __temp);
    initialLocale = getDefaultLocale(initialLocale);
    const i18n = createI18n({ ...vueI18nOptions, locale: initialLocale });
    let notInitialSetup = true;
    const isInitialLocaleSetup = (locale) => initialLocale !== locale && notInitialSetup;
    extendI18n(i18n, {
      locales: runtimeI18n.configLocales,
      localeCodes,
      baseUrl: runtimeI18n.baseUrl,
      context: nuxtContext,
      hooks: {
        onExtendComposer(composer) {
          composer.strategy = runtimeI18n.strategy;
          composer.localeProperties = computed(
            () => normalizedLocales.find((l) => l.code === composer.locale.value) || { code: composer.locale.value }
          );
          composer.setLocale = async (locale) => {
            const localeSetup = isInitialLocaleSetup(locale);
            const modified = await loadAndSetLocale(locale, i18n, runtimeI18n, localeSetup);
            if (modified && localeSetup) {
              notInitialSetup = false;
            }
            const redirectPath = await nuxtContext.runWithContext(
              () => detectRedirect({
                route: { to: route },
                targetLocale: locale,
                routeLocaleGetter: getLocaleFromRoute
              })
            );
            await nuxtContext.runWithContext(
              async () => await navigate(
                {
                  nuxtApp: nuxtContext,
                  i18n,
                  redirectPath,
                  locale,
                  route
                },
                { enableNavigate: true }
              )
            );
          };
          composer.loadLocaleMessages = async (locale) => {
            const setter = (locale2, message) => mergeLocaleMessage(i18n, locale2, message);
            await loadLocale(locale, localeLoaders, setter);
          };
          composer.differentDomains = runtimeI18n.differentDomains;
          composer.defaultLocale = runtimeI18n.defaultLocale;
          composer.getBrowserLocale = () => getBrowserLocale();
          composer.getLocaleCookie = () => getLocaleCookie(localeCookie, _detectBrowserLanguage, runtimeI18n.defaultLocale);
          composer.setLocaleCookie = (locale) => setLocaleCookie(localeCookie, locale, _detectBrowserLanguage);
          composer.onBeforeLanguageSwitch = (oldLocale, newLocale, initialSetup, context) => nuxt.callHook("i18n:beforeLocaleSwitch", { oldLocale, newLocale, initialSetup, context });
          composer.onLanguageSwitched = (oldLocale, newLocale) => nuxt.callHook("i18n:localeSwitched", { oldLocale, newLocale });
          composer.finalizePendingLocaleChange = async () => {
            if (!i18n.__pendingLocale) {
              return;
            }
            setLocale(i18n, i18n.__pendingLocale);
            if (i18n.__resolvePendingLocalePromise) {
              await i18n.__resolvePendingLocalePromise();
            }
            i18n.__pendingLocale = void 0;
          };
          composer.waitForPendingLocaleChange = async () => {
            if (i18n.__pendingLocale && i18n.__pendingLocalePromise) {
              await i18n.__pendingLocalePromise;
            }
          };
        },
        onExtendExportedGlobal(g) {
          return {
            strategy: {
              get() {
                return g.strategy;
              }
            },
            localeProperties: {
              get() {
                return g.localeProperties.value;
              }
            },
            setLocale: {
              get() {
                return async (locale) => Reflect.apply(g.setLocale, g, [locale]);
              }
            },
            differentDomains: {
              get() {
                return g.differentDomains;
              }
            },
            defaultLocale: {
              get() {
                return g.defaultLocale;
              }
            },
            getBrowserLocale: {
              get() {
                return () => Reflect.apply(g.getBrowserLocale, g, []);
              }
            },
            getLocaleCookie: {
              get() {
                return () => Reflect.apply(g.getLocaleCookie, g, []);
              }
            },
            setLocaleCookie: {
              get() {
                return (locale) => Reflect.apply(g.setLocaleCookie, g, [locale]);
              }
            },
            onBeforeLanguageSwitch: {
              get() {
                return (oldLocale, newLocale, initialSetup, context) => Reflect.apply(g.onBeforeLanguageSwitch, g, [oldLocale, newLocale, initialSetup, context]);
              }
            },
            onLanguageSwitched: {
              get() {
                return (oldLocale, newLocale) => Reflect.apply(g.onLanguageSwitched, g, [oldLocale, newLocale]);
              }
            },
            finalizePendingLocaleChange: {
              get() {
                return () => Reflect.apply(g.finalizePendingLocaleChange, g, []);
              }
            },
            waitForPendingLocaleChange: {
              get() {
                return () => Reflect.apply(g.waitForPendingLocaleChange, g, []);
              }
            }
          };
        },
        onExtendVueI18n(composer) {
          return {
            strategy: {
              get() {
                return composer.strategy;
              }
            },
            localeProperties: {
              get() {
                return composer.localeProperties.value;
              }
            },
            setLocale: {
              get() {
                return async (locale) => Reflect.apply(composer.setLocale, composer, [locale]);
              }
            },
            loadLocaleMessages: {
              get() {
                return async (locale) => Reflect.apply(composer.loadLocaleMessages, composer, [locale]);
              }
            },
            differentDomains: {
              get() {
                return composer.differentDomains;
              }
            },
            defaultLocale: {
              get() {
                return composer.defaultLocale;
              }
            },
            getBrowserLocale: {
              get() {
                return () => Reflect.apply(composer.getBrowserLocale, composer, []);
              }
            },
            getLocaleCookie: {
              get() {
                return () => Reflect.apply(composer.getLocaleCookie, composer, []);
              }
            },
            setLocaleCookie: {
              get() {
                return (locale) => Reflect.apply(composer.setLocaleCookie, composer, [locale]);
              }
            },
            onBeforeLanguageSwitch: {
              get() {
                return (oldLocale, newLocale, initialSetup, context) => Reflect.apply(composer.onBeforeLanguageSwitch, composer, [
                  oldLocale,
                  newLocale,
                  initialSetup,
                  context
                ]);
              }
            },
            onLanguageSwitched: {
              get() {
                return (oldLocale, newLocale) => Reflect.apply(composer.onLanguageSwitched, composer, [oldLocale, newLocale]);
              }
            },
            finalizePendingLocaleChange: {
              get() {
                return () => Reflect.apply(composer.finalizePendingLocaleChange, composer, []);
              }
            },
            waitForPendingLocaleChange: {
              get() {
                return () => Reflect.apply(composer.waitForPendingLocaleChange, composer, []);
              }
            }
          };
        }
      }
    });
    const pluginOptions = {
      __composerExtend: (c) => {
        const g = getComposer$3(i18n);
        c.strategy = g.strategy;
        c.localeProperties = computed(() => g.localeProperties.value);
        c.setLocale = g.setLocale;
        c.differentDomains = g.differentDomains;
        c.getBrowserLocale = g.getBrowserLocale;
        c.getLocaleCookie = g.getLocaleCookie;
        c.setLocaleCookie = g.setLocaleCookie;
        c.onBeforeLanguageSwitch = g.onBeforeLanguageSwitch;
        c.onLanguageSwitched = g.onLanguageSwitched;
        c.finalizePendingLocaleChange = g.finalizePendingLocaleChange;
        c.waitForPendingLocaleChange = g.waitForPendingLocaleChange;
        return () => {
        };
      }
    };
    app.use(i18n, pluginOptions);
    injectNuxtHelpers(nuxtContext, i18n);
    let routeChangeCount = 0;
    addRouteMiddleware(
      "locale-changing",
      /* @__PURE__ */ defineNuxtRouteMiddleware(async (to, from) => {
        let __temp2, __restore2;
        const locale = detectLocale(
          to,
          getLocaleFromRoute,
          () => {
            return getLocale$1(i18n) || getDefaultLocale(runtimeI18n.defaultLocale);
          },
          {
            ssg: "normal",
            callType: "routing",
            firstAccess: routeChangeCount === 0,
            localeCookie: getLocaleCookie(localeCookie, _detectBrowserLanguage, runtimeI18n.defaultLocale)
          },
          runtimeI18n
        );
        const localeSetup = isInitialLocaleSetup(locale);
        const modified = ([__temp2, __restore2] = executeAsync(() => loadAndSetLocale(locale, i18n, runtimeI18n, localeSetup)), __temp2 = await __temp2, __restore2(), __temp2);
        if (modified && localeSetup) {
          notInitialSetup = false;
        }
        const redirectPath = ([__temp2, __restore2] = executeAsync(() => nuxtContext.runWithContext(
          () => detectRedirect({
            route: { to, from },
            targetLocale: locale,
            routeLocaleGetter: runtimeI18n.strategy === "no_prefix" ? () => locale : getLocaleFromRoute,
            calledWithRouting: true
          })
        )), __temp2 = await __temp2, __restore2(), __temp2);
        routeChangeCount++;
        return [__temp2, __restore2] = executeAsync(() => nuxtContext.runWithContext(
          async () => navigate({ nuxtApp: nuxtContext, i18n, redirectPath, locale, route: to })
        )), __temp2 = await __temp2, __restore2(), __temp2;
      }),
      { global: true }
    );
  }
});
const plugins = [
  unhead_k2P3m_ZDyjlr2mMYnoDPwavjsDN8hBlk9cFai0bbopU,
  plugin$1,
  revive_payload_server_MVtmlZaQpj6ApFmshWfUWl5PehCebzaBf2NuRMiIbms,
  plugin,
  components_plugin_z4hgvsiddfKkfXTP6M8M4zG5Cb7sGnDhcryKVM45Di4,
  switch_locale_path_ssr_NflG9_QeVcJ1jVig0vCfxB_cZhpEMQ9U2ujRUiYbbVw,
  i18n_EI7LsD1KYQADczz5hrChviGQCdVM8yUkvFEZLJpmnvM
];
const layouts = {
  default: defineAsyncComponent(() => import("./_nuxt/default-DV0JR71x.js").then((m) => m.default || m))
};
const LayoutLoader = defineComponent({
  name: "LayoutLoader",
  inheritAttrs: false,
  props: {
    name: String,
    layoutProps: Object
  },
  setup(props, context) {
    return () => h(layouts[props.name], props.layoutProps, context.slots);
  }
});
const nuxtLayoutProps = {
  name: {
    type: [String, Boolean, Object],
    default: null
  },
  fallback: {
    type: [String, Object],
    default: null
  }
};
const __nuxt_component_0 = defineComponent({
  name: "NuxtLayout",
  inheritAttrs: false,
  props: nuxtLayoutProps,
  setup(props, context) {
    const nuxtApp = useNuxtApp();
    const injectedRoute = inject(PageRouteSymbol);
    const shouldUseEagerRoute = !injectedRoute || injectedRoute === useRoute();
    const route = shouldUseEagerRoute ? useRoute$1() : injectedRoute;
    const layout = computed(() => {
      let layout2 = unref(props.name) ?? (route == null ? void 0 : route.meta.layout) ?? "default";
      if (layout2 && !(layout2 in layouts)) {
        if (props.fallback) {
          layout2 = unref(props.fallback);
        }
      }
      return layout2;
    });
    const layoutRef = shallowRef();
    context.expose({ layoutRef });
    const done = nuxtApp.deferHydration();
    let lastLayout;
    return () => {
      const hasLayout = layout.value && layout.value in layouts;
      const transitionProps = (route == null ? void 0 : route.meta.layoutTransition) ?? appLayoutTransition;
      const previouslyRenderedLayout = lastLayout;
      lastLayout = layout.value;
      return _wrapInTransition(hasLayout && transitionProps, {
        default: () => h(Suspense, { suspensible: true, onResolve: () => {
          nextTick(done);
        } }, {
          default: () => h(
            LayoutProvider,
            {
              layoutProps: mergeProps(context.attrs, { ref: layoutRef }),
              key: layout.value || void 0,
              name: layout.value,
              shouldProvide: !props.name,
              isRenderingNewLayout: (name) => {
                return name !== previouslyRenderedLayout && name === layout.value;
              },
              hasTransition: !!transitionProps
            },
            context.slots
          )
        })
      }).default();
    };
  }
});
const LayoutProvider = defineComponent({
  name: "NuxtLayoutProvider",
  inheritAttrs: false,
  props: {
    name: {
      type: [String, Boolean]
    },
    layoutProps: {
      type: Object
    },
    hasTransition: {
      type: Boolean
    },
    shouldProvide: {
      type: Boolean
    },
    isRenderingNewLayout: {
      type: Function,
      required: true
    }
  },
  setup(props, context) {
    const name = props.name;
    if (props.shouldProvide) {
      provide(LayoutMetaSymbol, {
        isCurrent: (route) => name === (route.meta.layout ?? "default")
      });
    }
    const injectedRoute = inject(PageRouteSymbol);
    const isNotWithinNuxtPage = injectedRoute && injectedRoute === useRoute();
    if (isNotWithinNuxtPage) {
      const vueRouterRoute = useRoute$1();
      const reactiveChildRoute = {};
      for (const _key in vueRouterRoute) {
        const key = _key;
        Object.defineProperty(reactiveChildRoute, key, {
          enumerable: true,
          get: () => {
            return props.isRenderingNewLayout(props.name) ? vueRouterRoute[key] : injectedRoute[key];
          }
        });
      }
      provide(PageRouteSymbol, shallowReactive(reactiveChildRoute));
    }
    return () => {
      var _a, _b;
      if (!name || typeof name === "string" && !(name in layouts)) {
        return (_b = (_a = context.slots).default) == null ? void 0 : _b.call(_a);
      }
      return h(
        LayoutLoader,
        { key: name, layoutProps: props.layoutProps, name },
        context.slots
      );
    };
  }
});
const defineRouteProvider = (name = "RouteProvider") => defineComponent({
  name,
  props: {
    route: {
      type: Object,
      required: true
    },
    vnode: Object,
    vnodeRef: Object,
    renderKey: String,
    trackRootNodes: Boolean
  },
  setup(props) {
    const previousKey = props.renderKey;
    const previousRoute = props.route;
    const route = {};
    for (const key in props.route) {
      Object.defineProperty(route, key, {
        get: () => previousKey === props.renderKey ? props.route[key] : previousRoute[key],
        enumerable: true
      });
    }
    provide(PageRouteSymbol, shallowReactive(route));
    return () => {
      if (!props.vnode) {
        return props.vnode;
      }
      return h(props.vnode, { ref: props.vnodeRef });
    };
  }
});
const RouteProvider = defineRouteProvider();
const __nuxt_component_1 = defineComponent({
  name: "NuxtPage",
  inheritAttrs: false,
  props: {
    name: {
      type: String
    },
    transition: {
      type: [Boolean, Object],
      default: void 0
    },
    keepalive: {
      type: [Boolean, Object],
      default: void 0
    },
    route: {
      type: Object
    },
    pageKey: {
      type: [Function, String],
      default: null
    }
  },
  setup(props, { attrs, slots, expose }) {
    const nuxtApp = useNuxtApp();
    const pageRef = ref();
    inject(PageRouteSymbol, null);
    expose({ pageRef });
    inject(LayoutMetaSymbol, null);
    nuxtApp.deferHydration();
    return () => {
      return h(RouterView, { name: props.name, route: props.route, ...attrs }, {
        default: (routeProps) => {
          return h(Suspense, { suspensible: true }, {
            default() {
              return h(RouteProvider, {
                vnode: slots.default ? normalizeSlot(slots.default, routeProps) : routeProps.Component,
                route: routeProps.route,
                vnodeRef: pageRef
              });
            }
          });
        }
      });
    };
  }
});
function normalizeSlot(slot, data) {
  const slotContent = slot(data);
  return slotContent.length === 1 ? h(slotContent[0]) : h(Fragment, void 0, slotContent);
}
const _sfc_main$2 = {
  __name: "app",
  __ssrInlineRender: true,
  setup(__props) {
    useHead({
      titleTemplate: "%s - BookYourCoach",
      meta: [
        { charset: "utf-8" },
        { name: "viewport", content: "width=device-width, initial-scale=1" },
        { hid: "description", name: "description", content: "Plateforme de réservation de cours équestres avec des coaches certifiés" }
      ],
      link: [
        { rel: "icon", type: "image/x-icon", href: "/favicon.ico" }
      ]
    });
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLayout = __nuxt_component_0;
      const _component_NuxtPage = __nuxt_component_1;
      _push(ssrRenderComponent(_component_NuxtLayout, _attrs, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(_component_NuxtPage, null, null, _parent2, _scopeId));
          } else {
            return [
              createVNode(_component_NuxtPage)
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
};
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("app.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const _sfc_main$1 = {
  __name: "nuxt-error-page",
  __ssrInlineRender: true,
  props: {
    error: Object
  },
  setup(__props) {
    const props = __props;
    const _error = props.error;
    _error.stack ? _error.stack.split("\n").splice(1).map((line) => {
      const text = line.replace("webpack:/", "").replace(".vue", ".js").trim();
      return {
        text,
        internal: line.includes("node_modules") && !line.includes(".cache") || line.includes("internal") || line.includes("new Promise")
      };
    }).map((i) => `<span class="stack${i.internal ? " internal" : ""}">${i.text}</span>`).join("\n") : "";
    const statusCode = Number(_error.statusCode || 500);
    const is404 = statusCode === 404;
    const statusMessage = _error.statusMessage ?? (is404 ? "Page Not Found" : "Internal Server Error");
    const description = _error.message || _error.toString();
    const stack = void 0;
    const _Error404 = defineAsyncComponent(() => import("./_nuxt/error-404-CmoTIHmI.js"));
    const _Error = defineAsyncComponent(() => import("./_nuxt/error-500-BtoCTMAi.js"));
    const ErrorTemplate = is404 ? _Error404 : _Error;
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(unref(ErrorTemplate), mergeProps({ statusCode: unref(statusCode), statusMessage: unref(statusMessage), description: unref(description), stack: unref(stack) }, _attrs), null, _parent));
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/nuxt/dist/app/components/nuxt-error-page.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const _sfc_main = {
  __name: "nuxt-root",
  __ssrInlineRender: true,
  setup(__props) {
    const IslandRenderer = () => null;
    const nuxtApp = useNuxtApp();
    nuxtApp.deferHydration();
    nuxtApp.ssrContext.url;
    const SingleRenderer = false;
    provide(PageRouteSymbol, useRoute());
    nuxtApp.hooks.callHookWith((hooks) => hooks.map((hook) => hook()), "vue:setup");
    const error = useError();
    const abortRender = error.value && !nuxtApp.ssrContext.error;
    onErrorCaptured((err, target, info) => {
      nuxtApp.hooks.callHook("vue:error", err, target, info).catch((hookError) => console.error("[nuxt] Error in `vue:error` hook", hookError));
      {
        const p = nuxtApp.runWithContext(() => showError(err));
        onServerPrefetch(() => p);
        return false;
      }
    });
    const islandContext = nuxtApp.ssrContext.islandContext;
    return (_ctx, _push, _parent, _attrs) => {
      ssrRenderSuspense(_push, {
        default: () => {
          if (unref(abortRender)) {
            _push(`<div></div>`);
          } else if (unref(error)) {
            _push(ssrRenderComponent(unref(_sfc_main$1), { error: unref(error) }, null, _parent));
          } else if (unref(islandContext)) {
            _push(ssrRenderComponent(unref(IslandRenderer), { context: unref(islandContext) }, null, _parent));
          } else if (unref(SingleRenderer)) {
            ssrRenderVNode(_push, createVNode(resolveDynamicComponent(unref(SingleRenderer)), null, null), _parent);
          } else {
            _push(ssrRenderComponent(unref(_sfc_main$2), null, null, _parent));
          }
        },
        _: 1
      });
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/nuxt/dist/app/components/nuxt-root.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
let entry;
{
  entry = async function createNuxtAppServer(ssrContext) {
    var _a;
    const vueApp = createApp(_sfc_main);
    const nuxt = createNuxtApp({ vueApp, ssrContext });
    try {
      await applyPlugins(nuxt, plugins);
      await nuxt.hooks.callHook("app:created", vueApp);
    } catch (error) {
      await nuxt.hooks.callHook("app:error", error);
      (_a = nuxt.payload).error || (_a.error = createError(error));
    }
    if (ssrContext == null ? void 0 : ssrContext._renderResponse) {
      throw new Error("skipping render");
    }
    return vueApp;
  };
}
const entry$1 = (ssrContext) => entry(ssrContext);
export {
  useRouter as a,
  useI18n as b,
  useCookie as c,
  useNuxtApp as d,
  entry$1 as default,
  useRoute as e,
  asyncDataDefaults as f,
  createError as g,
  fetchDefaults as h,
  useRequestFetch as i,
  useRuntimeConfig as j,
  defineNuxtRouteMiddleware as k,
  hasProtocol as l,
  joinURL as m,
  navigateTo as n,
  withoutTrailingSlash as o,
  parseQuery as p,
  nuxtLinkDefaults as q,
  resolveRouteObject as r,
  defineStore as s,
  useHead as u,
  withTrailingSlash as w
};
//# sourceMappingURL=server.mjs.map
