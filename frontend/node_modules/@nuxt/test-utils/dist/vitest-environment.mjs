import { indexedDB } from 'fake-indexeddb';
import { joinURL } from 'ufo';
import defu from 'defu';
import { populateGlobal } from 'vitest/environments';
import { createFetch } from 'ofetch';
import { createApp, toNodeListener, defineEventHandler } from 'h3';
import { toRouteMatcher, createRouter, exportMatcher } from 'radix3';
import { fetchNodeRequestHandler } from 'node-mock-http';
import { importModule } from 'local-pkg';

async function setupWindow(win, environmentOptions) {
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
    await import('node-fetch-native/polyfill');
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
    createRouter({ routes: environmentOptions.nuxtRouteRules || {} })
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

const happyDom = (async function(_, { happyDom = {} }) {
  const { Window, GlobalWindow } = await importModule("happy-dom");
  const window = new (GlobalWindow || Window)(happyDom);
  return {
    window,
    teardown() {
      window.happyDOM.abort();
    }
  };
});

const jsdom = (async function(global, { jsdom = {} }) {
  const { CookieJar, JSDOM, ResourceLoader, VirtualConsole } = await importModule("jsdom");
  const jsdomOptions = defu(jsdom, {
    html: "<!DOCTYPE html>",
    url: "http://localhost:3000",
    contentType: "text/html",
    pretendToBeVisual: true,
    includeNodeLocations: false,
    runScripts: "dangerously",
    console: false,
    cookieJar: false
  });
  const window = new JSDOM(jsdomOptions.html, {
    ...jsdomOptions,
    resources: jsdomOptions.resources ?? (jsdomOptions.userAgent ? new ResourceLoader({ userAgent: jsdomOptions.userAgent }) : void 0),
    virtualConsole: jsdomOptions.console && global.console ? new VirtualConsole().sendTo(global.console) : void 0,
    cookieJar: jsdomOptions.cookieJar ? new CookieJar() : void 0
  }).window;
  window.scrollTo = () => {
  };
  return {
    window,
    teardown() {
    }
  };
});

const environmentMap = {
  "happy-dom": happyDom,
  jsdom
};
const index = {
  name: "nuxt",
  transformMode: "web",
  async setup(global, environmentOptions) {
    const url = joinURL(
      environmentOptions?.nuxt.url ?? "http://localhost:3000",
      environmentOptions?.nuxtRuntimeConfig.app?.baseURL || "/"
    );
    const environmentName = environmentOptions.nuxt.domEnvironment;
    const environment = environmentMap[environmentName] || environmentMap["happy-dom"];
    const { window: win, teardown } = await environment(global, defu(environmentOptions, {
      happyDom: { url },
      jsdom: { url }
    }));
    if (environmentOptions?.nuxt?.mock?.intersectionObserver) {
      win.IntersectionObserver = win.IntersectionObserver || class IntersectionObserver {
        observe() {
        }
        unobserve() {
        }
        disconnect() {
        }
      };
    }
    if (environmentOptions?.nuxt?.mock?.indexedDb) {
      win.indexedDB = indexedDB;
    }
    const teardownWindow = await setupWindow(win, environmentOptions);
    const { keys, originals } = populateGlobal(global, win, {
      bindFunctions: true
    });
    return {
      // called after all tests with this env have been run
      teardown() {
        keys.forEach((key) => delete global[key]);
        teardownWindow();
        originals.forEach((v, k) => global[k] = v);
        teardown();
      }
    };
  }
};

export { index as default };
