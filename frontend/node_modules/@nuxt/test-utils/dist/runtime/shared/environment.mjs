import { createFetch } from "ofetch";
import { joinURL } from "ufo";
import { createApp, defineEventHandler, toNodeListener } from "h3";
import { createRouter as createRadixRouter, exportMatcher, toRouteMatcher } from "radix3";
import { fetchNodeRequestHandler } from "node-mock-http";
export async function setupWindow(win, environmentOptions) {
  win.__NUXT_VITEST_ENVIRONMENT__ = true;
  win.__NUXT__ = {
    serverRendered: false,
    config: {
      public: {},
      app: { baseURL: "/" },
      ...environmentOptions?.nuxtRuntimeConfig
    },
    data: {},
    state: {}
  };
  const rootId = environmentOptions.nuxt.rootId || "nuxt-test";
  let el;
  try {
    el = win.document.querySelector(rootId);
  } catch {
  }
  if (el) {
    return () => {
    };
  }
  const consoleInfo = console.info;
  console.info = (...args) => {
    if (args[0] === "<Suspense> is an experimental feature and its API will likely change.") {
      return;
    }
    return consoleInfo(...args);
  };
  const app = win.document.createElement("div");
  app.id = rootId;
  win.document.body.appendChild(app);
  const h3App = createApp();
  if (!win.fetch) {
    await import("node-fetch-native/polyfill");
    win.URLSearchParams = globalThis.URLSearchParams;
  }
  const nodeHandler = toNodeListener(h3App);
  const registry = /* @__PURE__ */ new Set();
  win.fetch = async (url, init) => {
    if (typeof url === "string") {
      const base = url.split("?")[0];
      if (registry.has(base) || registry.has(url)) {
        url = "/_" + url;
      }
      if (url.startsWith("/")) {
        const response = await fetchNodeRequestHandler(nodeHandler, url, init);
        return normalizeFetchResponse(response);
      }
    }
    return fetch(url, init);
  };
  win.$fetch = createFetch({ fetch: win.fetch, Headers: win.Headers });
  win.__registry = registry;
  win.__app = h3App;
  const timestamp = Date.now();
  const routeRulesMatcher = toRouteMatcher(
    createRadixRouter({ routes: environmentOptions.nuxtRouteRules || {} })
  );
  const matcher = exportMatcher(routeRulesMatcher);
  const manifestOutputPath = joinURL(
    environmentOptions?.nuxtRuntimeConfig?.app?.baseURL || "/",
    environmentOptions?.nuxtRuntimeConfig?.app?.buildAssetsDir || "_nuxt",
    "builds"
  );
  const manifestBaseRoutePath = joinURL("/_", manifestOutputPath);
  const buildId = win.__NUXT__.config?.app.buildId || "test";
  h3App.use(
    `${manifestBaseRoutePath}/latest.json`,
    defineEventHandler(() => ({
      id: buildId,
      timestamp
    }))
  );
  h3App.use(
    `${manifestBaseRoutePath}/meta/${buildId}.json`,
    defineEventHandler(() => ({
      id: buildId,
      timestamp,
      matcher,
      prerendered: []
    }))
  );
  registry.add(`${manifestOutputPath}/latest.json`);
  registry.add(`${manifestOutputPath}/meta/${buildId}.json`);
  return () => {
    console.info = consoleInfo;
  };
}
function normalizeFetchResponse(response) {
  if (!response.headers.has("set-cookie")) {
    return response;
  }
  return new Response(response.body, {
    status: response.status,
    statusText: response.statusText,
    headers: normalizeCookieHeaders(response.headers)
  });
}
function normalizeCookieHeader(header = "") {
  return splitCookiesString(joinHeaders(header));
}
function normalizeCookieHeaders(headers) {
  const outgoingHeaders = new Headers();
  for (const [name, header] of headers) {
    if (name === "set-cookie") {
      for (const cookie of normalizeCookieHeader(header)) {
        outgoingHeaders.append("set-cookie", cookie);
      }
    } else {
      outgoingHeaders.set(name, joinHeaders(header));
    }
  }
  return outgoingHeaders;
}
function joinHeaders(value) {
  return Array.isArray(value) ? value.join(", ") : String(value);
}
