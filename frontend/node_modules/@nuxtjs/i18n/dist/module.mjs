import { useLogger, resolvePath, useNuxt, directoryToURL, resolveModule, tryUseNuxt, addTemplate, updateTemplates, addBuildPlugin, addServerTemplate, addServerImports, addServerPlugin, addServerHandler, createResolver, addPlugin, addVitePlugin, useNitro, addTypeTemplate, addComponent, addImports, defineNuxtModule } from '@nuxt/kit';
import defu$1, { defu } from 'defu';
import { readFileSync, existsSync } from 'node:fs';
import { createHash } from 'node:crypto';
import { resolve, isAbsolute, parse, relative, dirname, basename, join as join$1 } from 'pathe';
import { isArray, isString, assign } from '@intlify/shared';
import { parseSync } from 'oxc-parser';
import { parse as parse$1 } from '@vue/compiler-sfc';
import { parseAndWalk, ScopeTracker, walk } from 'oxc-walker';
import { mkdir, readFile, writeFile } from 'node:fs/promises';
import { encodePath, parseURL, parseQuery } from 'ufo';
import { createRoutesContext } from 'unplugin-vue-router';
import { resolveOptions } from 'unplugin-vue-router/options';
import MagicString from 'magic-string';
import { createUnplugin } from 'unplugin';
import { pathToFileURL, fileURLToPath } from 'node:url';
import { findStaticImports, resolveModuleExportNames } from 'mlly';
import { transform as transform$1 } from 'oxc-transform';
import yamlPlugin from '@rollup/plugin-yaml';
import json5Plugin from '@miyaneee/rollup-plugin-json5';
import VueI18nPlugin from '@intlify/unplugin-vue-i18n';
import { addDefinePlugin } from 'nuxt-define';
import { genArrayFromRaw, genObjectFromRaw, genString, genObjectFromValues, genSafeVariableName, genDynamicImport } from 'knitwork';
import { mkdir as mkdir$1 } from 'fs/promises';

const NUXT_I18N_MODULE_ID = "@nuxtjs/i18n";
const VUE_I18N_PKG = "vue-i18n";
const SHARED_PKG = "@intlify/shared";
const MESSAGE_COMPILER_PKG = "@intlify/message-compiler";
const CORE_PKG = "@intlify/core";
const CORE_BASE_PKG = "@intlify/core-base";
const H3_PKG = "@intlify/h3";
const UTILS_PKG = "@intlify/utils";
const UTILS_H3_PKG = "@intlify/utils/h3";
const UFO_PKG = "ufo";
const STRATEGY_PREFIX_EXCEPT_DEFAULT = "prefix_except_default";
const DYNAMIC_PARAMS_KEY = "nuxtI18nInternal";
const DEFAULT_COOKIE_KEY = "i18n_redirected";
const SWITCH_LOCALE_PATH_LINK_IDENTIFIER = "nuxt-i18n-slp";
const FULL_STATIC_LIFETIME = 60 * 60 * 24;
const DEFAULT_OPTIONS = {
  restructureDir: "i18n",
  experimental: {
    localeDetector: "",
    typedPages: true,
    typedOptionsAndMessages: false,
    alternateLinkCanonicalQueries: true,
    devCache: false,
    cacheLifetime: void 0,
    stripMessagesPayload: false,
    preload: false,
    strictSeo: false,
    nitroContextDetection: true
  },
  bundle: {
    compositionOnly: true,
    runtimeOnly: false,
    fullInstall: true,
    dropMessageCompiler: false
  },
  compilation: {
    strictMessage: true,
    escapeHtml: false
  },
  customBlocks: {
    defaultSFCLang: "json",
    globalSFCScope: false
  },
  vueI18n: "",
  locales: [],
  defaultLocale: "",
  defaultDirection: "ltr",
  routesNameSeparator: "___",
  trailingSlash: false,
  defaultLocaleRouteNameSuffix: "default",
  strategy: STRATEGY_PREFIX_EXCEPT_DEFAULT,
  langDir: "locales",
  rootRedirect: void 0,
  redirectStatusCode: 302,
  detectBrowserLanguage: {
    alwaysRedirect: false,
    cookieCrossOrigin: false,
    cookieDomain: null,
    cookieKey: DEFAULT_COOKIE_KEY,
    cookieSecure: false,
    fallbackLocale: "",
    redirectOn: "root",
    useCookie: true
  },
  differentDomains: false,
  baseUrl: "",
  customRoutes: "page",
  pages: {},
  skipSettingLocaleOnNavigate: false,
  types: "composition",
  debug: false,
  parallelPlugin: false,
  multiDomainLocales: false,
  hmr: true,
  autoDeclare: true
};
const DEFINE_I18N_ROUTE_FN = "defineI18nRoute";
const DEFINE_I18N_LOCALE_FN = "defineI18nLocale";
const DEFINE_I18N_CONFIG_FN = "defineI18nConfig";
const DEFINE_LOCALE_DETECTOR_FN = "defineI18nLocaleDetector";
const NUXT_I18N_VIRTUAL_PREFIX = "#nuxt-i18n";
const TS_EXTENSIONS = [".ts", ".cts", ".mts"];
const JS_EXTENSIONS = [".js", ".cjs", ".mjs"];
const EXECUTABLE_EXTENSIONS = [...JS_EXTENSIONS, ...TS_EXTENSIONS];
const EXECUTABLE_EXT_RE = /\.[c|m]?[j|t]s$/;

function filterLocales(ctx, nuxt) {
  const project = getLayerI18n(nuxt.options._layers[0]);
  const include = toArray(project?.bundle?.onlyLocales ?? []).filter(isString);
  if (!include.length) {
    return ctx.options.locales;
  }
  return ctx.options.locales.filter((x) => include.includes(isString(x) ? x : x.code));
}
function resolveLocales(srcDir, locales) {
  const localesResolved = [];
  for (const locale of locales) {
    const resolved = assign({ meta: [] }, locale);
    delete resolved.file;
    delete resolved.files;
    for (const f of getLocaleFiles(locale)) {
      const path = resolve(srcDir, f.path);
      const type = getLocaleType(path);
      resolved.meta.push({
        type,
        path,
        hash: getHash(path),
        cache: f.cache ?? type !== "dynamic"
      });
    }
    localesResolved.push(resolved);
  }
  return localesResolved;
}
const analyzedMap = { object: "static", function: "dynamic", unknown: "unknown" };
function getLocaleType(path) {
  if (!EXECUTABLE_EXT_RE.test(path)) return "static";
  const parsed = parseSync(path, readFileSync(path, "utf-8"));
  return analyzedMap[scanProgram(parsed.program) || "unknown"];
}
function scanProgram(program) {
  let varDeclarationName;
  const varDeclarations = [];
  for (const node of program.body) {
    switch (node.type) {
      // collect variable declarations
      case "VariableDeclaration":
        for (const decl of node.declarations) {
          if (decl.type !== "VariableDeclarator" || decl.init == null) continue;
          if ("name" in decl.id === false) continue;
          varDeclarations.push(decl);
        }
        break;
      // check default export - store identifier if exporting variable name
      case "ExportDefaultDeclaration":
        if (node.declaration.type === "Identifier") {
          varDeclarationName = node.declaration;
          break;
        }
        if (node.declaration.type === "ObjectExpression") {
          return "object";
        }
        if (node.declaration.type === "CallExpression" && node.declaration.callee.type === "Identifier") {
          const [fnNode] = node.declaration.arguments;
          if (fnNode?.type === "FunctionExpression" || fnNode?.type === "ArrowFunctionExpression") {
            return "function";
          }
        }
        break;
    }
  }
  if (varDeclarationName) {
    const n = varDeclarations.find((x) => x.id.type === "Identifier" && x.id.name === varDeclarationName.name);
    if (n) {
      if (n.init?.type === "ObjectExpression") {
        return "object";
      }
      if (n.init?.type === "CallExpression" && n.init.callee.type === "Identifier") {
        const [fnNode] = n.init.arguments;
        if (fnNode?.type === "FunctionExpression" || fnNode?.type === "ArrowFunctionExpression") {
          return "function";
        }
      }
    }
  }
  return false;
}
async function resolveVueI18nConfigInfo(rootDir, configPath = "i18n.config") {
  const absolutePath = await resolvePath(configPath, { cwd: rootDir, extensions: EXECUTABLE_EXTENSIONS });
  if (!existsSync(absolutePath)) return void 0;
  return {
    path: absolutePath,
    // absolute
    hash: getHash(absolutePath),
    type: getLocaleType(absolutePath)
  };
}
const getLocaleFiles = (locale) => {
  return toArray(locale.file ?? locale.files).filter((x) => x != null).map((x) => isString(x) ? { path: x, cache: void 0 } : x);
};
function resolveRelativeLocales(locale, config) {
  return getLocaleFiles(locale).map((file) => ({
    path: resolve(config.langDir, file.path),
    cache: file.cache
  }));
}
const mergeConfigLocales = (configs) => {
  const merged = /* @__PURE__ */ new Map();
  for (const config of configs) {
    for (const locale of config.locales ?? []) {
      const current = isString(locale) ? { code: locale, language: locale } : assign({}, locale);
      const files = isString(locale) ? [] : resolveRelativeLocales(current, config);
      delete current.file;
      delete current.files;
      const existing = merged.get(current.code) ?? {
        code: current.code,
        language: current.language,
        files: []
      };
      existing.files = [...files, ...existing.files];
      merged.set(current.code, assign({}, current, existing));
    }
  }
  return Array.from(merged.values());
};
function getHash(text) {
  return createHash("sha256").update(text).digest("hex").substring(0, 8);
}
function getLayerI18n(configLayer) {
  const layerInlineOptions = (configLayer.config.modules || []).find(
    (mod) => isArray(mod) && isString(mod[0]) && [NUXT_I18N_MODULE_ID, `${NUXT_I18N_MODULE_ID}-edge`].includes(mod[0])
  )?.[1];
  if (configLayer.config.i18n) {
    return defu(configLayer.config.i18n, layerInlineOptions);
  }
  return layerInlineOptions;
}
function toArray(value) {
  return Array.isArray(value) ? value : [value];
}
const logger = useLogger("nuxt-i18n");

function checkLayerOptions(_options, nuxt) {
  const project = nuxt.options._layers[0];
  const layers = nuxt.options._layers;
  for (const layer of layers) {
    const layerI18n = getLayerI18n(layer);
    if (layerI18n == null) continue;
    const configLocation = project.config.rootDir === layer.config.rootDir ? "project" : "extended";
    const layerHint = `In ${configLocation} layer (\`${resolve(project.config.rootDir, layer.configFile)}\`) -`;
    try {
      if (!layerI18n.langDir) continue;
      if (isString(layerI18n.langDir) && isAbsolute(layerI18n.langDir)) {
        logger.warn(
          `${layerHint} \`langDir\` is set to an absolute path (\`${layerI18n.langDir}\`) but should be set a path relative to \`srcDir\` (\`${layer.config.srcDir}\`). Absolute paths will not work in production, see https://i18n.nuxtjs.org/docs/api/options#langdir for more details.`
        );
      }
      for (const locale of layerI18n.locales ?? []) {
        if (isString(locale)) {
          throw new Error("When using the `langDir` option the `locales` must be a list of objects.");
        }
        if (locale.file || locale.files) continue;
        throw new Error(
          `All locales must have the \`file\` or \`files\` property set when using \`langDir\`.
Found none in:
${JSON.stringify(locale, null, 2)}.`
        );
      }
    } catch (err) {
      if (!(err instanceof Error)) throw err;
      throw new Error(`[nuxt-i18n] ${layerHint} ${err.message}`);
    }
  }
}
function resolveI18nDir(layer, i18n, i18nDir = i18n.restructureDir ?? "i18n") {
  return resolve(layer.config.rootDir, i18nDir);
}
async function applyLayerOptions(ctx, nuxt) {
  const configs = [];
  if (isAbsolute(ctx.options.langDir || "")) {
    const config = { langDir: ctx.options.langDir, locales: [] };
    for (const locale of ctx.options.locales) {
      if (isString(locale) || !getLocaleFiles(locale)?.[0]?.path?.startsWith(config.langDir)) continue;
      config.locales.push(locale);
    }
    configs.push(config);
  }
  for (const layer of nuxt.options._layers) {
    const i18n = getLayerI18n(layer);
    if (i18n?.locales == null) continue;
    const langDir = resolve(resolveI18nDir(layer, i18n), i18n.langDir ?? "locales");
    configs.push(assign({}, i18n, { langDir, locales: i18n.locales }));
  }
  await nuxt.callHook(
    "i18n:registerModule",
    ({ langDir, locales }) => langDir && locales && configs.push({ langDir, locales })
  );
  return mergeConfigLocales(configs);
}
async function resolveLayerVueI18nConfigInfo(options, nuxt = useNuxt()) {
  const resolvers = [];
  if (options.vueI18n && isAbsolute(options.vueI18n)) {
    resolvers.push(resolveVueI18nConfigInfo(parse(options.vueI18n).dir, options.vueI18n));
  }
  for (const layer of nuxt.options._layers) {
    resolvers.push(resolveLayerVueI18n(layer));
  }
  return (await Promise.all(resolvers)).filter((x) => x != null);
}
async function resolveLayerVueI18n(layer) {
  const i18n = getLayerI18n(layer);
  const i18nDir = resolveI18nDir(layer, i18n || {});
  const resolved = await resolveVueI18nConfigInfo(i18nDir, i18n?.vueI18n);
  if (import.meta.dev && resolved == null && i18n?.vueI18n) {
    logger.warn(`Vue I18n configuration file \`${i18n.vueI18n}\` not found in \`${i18nDir}\`. Skipping...`);
  }
  return resolved;
}

function setupAlias({ userOptions: options }, nuxt) {
  const modules = {
    [VUE_I18N_PKG]: `${VUE_I18N_PKG}/dist/vue-i18n${!nuxt.options.dev && !nuxt.options._prepare && options.bundle?.runtimeOnly ? ".runtime" : ""}.mjs`,
    [SHARED_PKG]: `${SHARED_PKG}/dist/shared.mjs`,
    [MESSAGE_COMPILER_PKG]: `${MESSAGE_COMPILER_PKG}/dist/message-compiler.mjs`,
    [CORE_BASE_PKG]: `${CORE_BASE_PKG}/dist/core-base.mjs`,
    [CORE_PKG]: `${CORE_PKG}/dist/core.node.mjs`,
    [UTILS_H3_PKG]: `${UTILS_PKG}/dist/h3.mjs`,
    // for `@intlify/utils/h3`
    [UFO_PKG]: UFO_PKG
  };
  const layerI18nDirs = nuxt.options._layers.map((l) => {
    const i18n = getLayerI18n(l);
    if (i18n == null) return void 0;
    return relative(nuxt.options.buildDir, resolve(resolveI18nDir(l, i18n), "**/*"));
  }).filter((x) => !!x);
  const moduleIds = Object.keys(modules);
  nuxt.options.typescript = defu(nuxt.options.typescript, {
    hoist: moduleIds,
    tsConfig: {
      include: layerI18nDirs
    }
  });
  nuxt.options.vite = defu(nuxt.options.vite, {
    optimizeDeps: {
      include: moduleIds
    }
  });
  const moduleDirs = [].concat(
    nuxt.options.modulesDir,
    nuxt.options.modulesDir.map((dir) => `${dir}/${NUXT_I18N_MODULE_ID}/node_modules`)
  ).map((x) => directoryToURL(x));
  for (const [moduleName, moduleFile] of Object.entries(modules)) {
    const module = resolveModule(moduleFile, { url: moduleDirs });
    if (!module) throw new Error(`Could not resolve module "${moduleFile}"`);
    nuxt.options.alias[moduleName] = module;
    nuxt.options.build.transpile.push(moduleName);
  }
}

const COLON_RE = /:/g;
function getRoutePath(tokens) {
  return tokens.reduce((path, token) => {
    return path + (token.type === 2 /* optional */ ? `:${token.value}?` : token.type === 1 /* dynamic */ ? `:${token.value}()` : token.type === 3 /* catchall */ ? `:${token.value}(.*)*` : token.type === 4 /* group */ ? "" : encodePath(token.value).replace(COLON_RE, "\\:"));
  }, "/");
}
const PARAM_CHAR_RE = /[\w.]/;
function parseSegment(segment) {
  let state = 0 /* initial */;
  let i = 0;
  let buffer = "";
  const tokens = [];
  function consumeBuffer() {
    if (!buffer) {
      return;
    }
    if (state === 0 /* initial */) {
      throw new Error("wrong state");
    }
    tokens.push({
      type: state === 1 /* static */ ? 0 /* static */ : state === 2 /* dynamic */ ? 1 /* dynamic */ : state === 3 /* optional */ ? 2 /* optional */ : state === 4 /* catchall */ ? 3 /* catchall */ : 4 /* group */,
      value: buffer
    });
    buffer = "";
  }
  while (i < segment.length) {
    const c = segment[i];
    switch (state) {
      case 0 /* initial */:
        buffer = "";
        if (c === "[") {
          state = 2 /* dynamic */;
        } else if (c === "(") {
          state = 5 /* group */;
        } else {
          i--;
          state = 1 /* static */;
        }
        break;
      case 1 /* static */:
        if (c === "[") {
          consumeBuffer();
          state = 2 /* dynamic */;
        } else if (c === "(") {
          consumeBuffer();
          state = 5 /* group */;
        } else {
          buffer += c;
        }
        break;
      case 4 /* catchall */:
      case 2 /* dynamic */:
      case 3 /* optional */:
      case 5 /* group */:
        if (buffer === "...") {
          buffer = "";
          state = 4 /* catchall */;
        }
        if (c === "[" && state === 2 /* dynamic */) {
          state = 3 /* optional */;
        }
        if (c === "]" && (state !== 3 /* optional */ || segment[i - 1] === "]")) {
          if (!buffer) {
            throw new Error("Empty param");
          } else {
            consumeBuffer();
          }
          state = 0 /* initial */;
        } else if (c === ")" && state === 5 /* group */) {
          if (!buffer) {
            throw new Error("Empty group");
          } else {
            consumeBuffer();
          }
          state = 0 /* initial */;
        } else if (c && PARAM_CHAR_RE.test(c)) {
          buffer += c;
        } else ;
        break;
    }
    i++;
  }
  if (state === 2 /* dynamic */) {
    throw new Error(`Unfinished param "${buffer}"`);
  }
  consumeBuffer();
  return tokens;
}

const join = (...args) => args.filter(Boolean).join("");
function handlePathNesting(localizedPath, parentLocalizedPath = "") {
  if (!parentLocalizedPath) return localizedPath;
  if (localizedPath[0] !== "/") {
    return localizedPath;
  }
  const index = localizedPath.indexOf(parentLocalizedPath);
  if (index >= 0) {
    return localizedPath.slice(localizedPath.indexOf(parentLocalizedPath) + parentLocalizedPath.length + 1);
  }
  return localizedPath;
}
function createHandleTrailingSlash(ctx) {
  return (localizedPath, hasParent) => {
    if (!localizedPath) return "";
    const isChildWithRelativePath = hasParent && !localizedPath.startsWith("/");
    return localizedPath.replace(/\/+$/, "") + (ctx.trailingSlash ? "/" : "") || (isChildWithRelativePath ? "" : "/");
  };
}
function createLocalizeAliases(ctx) {
  return (route, locale, options) => {
    const aliases = toArray(route.alias).filter(Boolean);
    return aliases.map((x) => {
      const alias = ctx.handleTrailingSlash(x, !!options.parent);
      const shouldPrefix = options.shouldPrefix(x, locale, options);
      return shouldPrefix ? join("/", locale, alias) : alias;
    });
  };
}
function createLocalizeChildren(ctx) {
  return (route, parentLocalized, locale, opts) => {
    const localizeParams = { ...opts, parent: route, locales: [locale], parentLocalized };
    return route.children?.flatMap((child) => localizeSingleRoute(child, localizeParams, ctx)) ?? [];
  };
}
function getLocalizedRoute(route, locale, localizedPath, options, ctx) {
  const path = handlePathNesting(localizedPath, options.parentLocalized?.path);
  const localized = { ...route };
  localized.path = ctx.handleTrailingSlash(path, !!options.parent);
  localized.name &&= ctx.localizeRouteName(localized, locale, options.defaultTree);
  localized.alias &&= ctx.localizeAliases(localized, locale, options);
  localized.children &&= ctx.localizeChildren(route, localized, locale, options);
  return localized;
}
function localizeSingleRoute(route, options, ctx) {
  const routeOptions = ctx.optionsResolver(route, options.locales);
  if (!routeOptions) {
    return [route];
  }
  const resultRoutes = [];
  for (const locale of routeOptions.locales) {
    const unprefixed = routeOptions.paths?.[locale] ?? route.path;
    const prefixed = join("/", locale, unprefixed);
    const usePrefix = options.shouldPrefix(unprefixed, locale, options);
    const data = { route, prefixed, unprefixed, locale, usePrefix, ctx, options };
    for (const localizer of ctx.localizers) {
      if (!localizer.enabled(data)) continue;
      resultRoutes.push(...localizer.localizer(data));
    }
  }
  return resultRoutes;
}
function createDefaultOptionsResolver(opts) {
  return (route, locales) => {
    if (route.redirect && !route.file) return void 0;
    if (opts?.optionsResolver == null) return { locales, paths: {} };
    return opts.optionsResolver(route, locales);
  };
}
function createLocalizeRouteName(opts) {
  const separator = opts.routesNameSeparator || "___";
  const defaultSuffix = opts.defaultLocaleRouteNameSuffix || "default";
  return (route, locale, isDefault) => {
    if (route.name == null) return;
    return !isDefault ? route.name + separator + locale : route.name + separator + locale + separator + defaultSuffix;
  };
}
function createRouteContext(opts) {
  const ctx = { localizers: [] };
  ctx.trailingSlash = opts.trailingSlash ?? false;
  ctx.isDefaultLocale = (locale) => opts.defaultLocales.includes(locale);
  ctx.localizeRouteName = createLocalizeRouteName(opts);
  ctx.optionsResolver = createDefaultOptionsResolver(opts);
  ctx.localizeAliases = createLocalizeAliases(ctx);
  ctx.localizeChildren = createLocalizeChildren(ctx);
  ctx.handleTrailingSlash = createHandleTrailingSlash(ctx);
  ctx.localizers.push({
    enabled: () => true,
    localizer: ({ prefixed, unprefixed, route, usePrefix, ctx: ctx2, locale, options }) => [
      getLocalizedRoute(route, locale, usePrefix ? prefixed : unprefixed, options, ctx2)
    ]
  });
  return ctx;
}

function createShouldPrefix(opts, ctx) {
  if (opts.strategy === "no_prefix") return () => false;
  return (path, locale, options) => {
    if (options.defaultTree) return false;
    if (options.parent != null && !path.startsWith("/")) return false;
    if (ctx.isDefaultLocale(locale) && opts.strategy === "prefix_except_default") return false;
    return true;
  };
}
function shouldLocalizeRoutes(options) {
  if (options.strategy !== "no_prefix") return true;
  if (!options.differentDomains) return false;
  const domains = /* @__PURE__ */ new Set();
  for (const locale of options.locales) {
    if (!locale.domain) continue;
    if (domains.has(locale.domain)) {
      console.error(
        `Cannot use \`strategy: no_prefix\` when using multiple locales on the same domain - found multiple entries with ${locale.domain}`
      );
      return false;
    }
    domains.add(locale.domain);
  }
  return true;
}
function resolveDefaultLocales(config) {
  let defaultLocales = [config.defaultLocale ?? ""];
  if (config.differentDomains) {
    const domainDefaults = config.locales.filter((locale) => !!locale.domainDefault).map((locale) => locale.code);
    defaultLocales = defaultLocales.concat(domainDefaults);
  }
  return defaultLocales;
}
function localizeRoutes(routes, config) {
  if (!shouldLocalizeRoutes(config)) return routes;
  const ctx = createRouteContext({
    optionsResolver: config.optionsResolver,
    trailingSlash: config.trailingSlash ?? false,
    defaultLocales: resolveDefaultLocales(config),
    routesNameSeparator: config.routesNameSeparator,
    defaultLocaleRouteNameSuffix: config.defaultLocaleRouteNameSuffix
  });
  const strategy = config.strategy ?? "prefix_and_default";
  if (strategy === "prefix_and_default") {
    ctx.localizers.unshift({
      enabled: ({ options, locale }) => ctx.isDefaultLocale(locale) && !options.defaultTree && options.parent == null,
      localizer: ({ route, ctx: ctx2, locale, options }) => localizeSingleRoute(route, { ...options, locales: [locale], defaultTree: true }, ctx2)
    });
  }
  const multiDomainLocales = config.multiDomainLocales ?? false;
  if (multiDomainLocales && (config.strategy === "prefix_except_default" || config.strategy === "prefix_and_default")) {
    ctx.localizers.unshift({
      enabled: ({ usePrefix }) => usePrefix,
      localizer: ({ unprefixed, route, ctx: ctx2, locale }) => [
        { ...route, name: ctx2.localizeRouteName(route, locale, true), path: unprefixed }
      ]
    });
  }
  const includeUnprefixedFallback = config.includeUnprefixedFallback ?? false;
  if (strategy === "prefix" && includeUnprefixedFallback) {
    ctx.localizers.unshift({
      enabled: ({ usePrefix, locale }) => usePrefix && ctx.isDefaultLocale(locale),
      localizer: ({ route }) => [route]
    });
  }
  const locales = config.locales.map((x) => x.code);
  const params = { locales, defaultTree: false, shouldPrefix: createShouldPrefix(config, ctx) };
  return routes.flatMap((route) => localizeSingleRoute(route, params, ctx));
}

const VIRTUAL_PREFIX_HEX = "\0";
function asI18nVirtual(val) {
  return NUXT_I18N_VIRTUAL_PREFIX + "/" + val;
}
function isVue(id, opts = {}) {
  const { search } = parseURL(decodeURIComponent(pathToFileURL(id).href));
  if (id.endsWith(".vue") && !search) {
    return true;
  }
  if (!search) {
    return false;
  }
  const query = parseQuery(search);
  if (query.nuxt_component) {
    return false;
  }
  if (query.macro && (search === "?macro=true" || !opts.type || opts.type.includes("script"))) {
    return true;
  }
  const type = "setup" in query ? "script" : query.type;
  if (!("vue" in query) || opts.type && !opts.type.includes(type)) {
    return false;
  }
  return true;
}

function transform(id, input, options) {
  const oxcOptions = tryUseNuxt()?.options?.oxc?.transform?.options ?? {};
  return transform$1(id, input, { ...oxcOptions, ...options });
}
const pattern = [DEFINE_I18N_LOCALE_FN, DEFINE_I18N_CONFIG_FN].join("|");
const DEFINE_I18N_FN_RE = new RegExp(`\\b(${pattern})\\s*\\((.+)\\s*\\)`, "gms");
const ResourcePlugin = (options, ctx) => createUnplugin(() => {
  const i18nFileMetas = [...ctx.localeInfo.flatMap((x) => x.meta), ...ctx.vueI18nConfigPaths];
  const i18nPathSet = /* @__PURE__ */ new Set();
  const i18nFileHashSet = /* @__PURE__ */ new Map();
  for (const meta of i18nFileMetas) {
    if (i18nPathSet.has(meta.path)) continue;
    i18nPathSet.add(meta.path);
    i18nFileHashSet.set(asI18nVirtual(meta.hash), meta.path);
  }
  return {
    name: "nuxtjs:i18n-resource",
    enforce: "pre",
    // resolve virtual hash to file path
    resolveId(id) {
      if (!id || id.startsWith(VIRTUAL_PREFIX_HEX) || !id.startsWith(NUXT_I18N_VIRTUAL_PREFIX)) {
        return;
      }
      if (i18nFileHashSet.has(id)) {
        return i18nFileHashSet.get(id);
      }
    },
    transformInclude(id) {
      if (!id || id.startsWith(VIRTUAL_PREFIX_HEX)) {
        return false;
      }
      if (i18nPathSet.has(id)) {
        return /\.[cm]?[jt]s$/.test(id);
      }
    },
    /**
     * Match and replace `defineI18nX(<content>)` with its `<content>`
     */
    transform: {
      filter: {
        id: {
          include: [...i18nPathSet]
        }
      },
      async handler(_code, id) {
        let code = _code;
        const staticImports = findStaticImports(_code);
        for (const x of staticImports) {
          if (x.specifier.startsWith("\0")) continue;
          i18nPathSet.add(await resolvePath(resolve(dirname(id), x.specifier)));
        }
        if (/[cm]?ts$/.test(id)) {
          code = transform(id, _code).code;
        }
        const s = new MagicString(code);
        const matches = code.matchAll(DEFINE_I18N_FN_RE);
        for (const match of matches) {
          s.overwrite(match.index, match.index + match[0].length, match[2]);
        }
        if (s.hasChanged()) {
          return {
            code: s.toString(),
            map: options.sourcemap && !/\.[cm]?ts$/.test(id) ? s.generateMap({ hires: true }) : null
          };
        }
      }
    }
  };
});

class NuxtPageAnalyzeContext {
  config;
  pages = /* @__PURE__ */ new Map();
  pathToConfig = {};
  fileToPath = {};
  constructor(config) {
    this.config = config || {};
  }
  addPage(page, path, name) {
    this.pages.set(page.file, { path, name });
    const p = path === "index" ? "/" : "/" + path.replace(/\/index$/, "");
    this.fileToPath[page.file] = p;
  }
}
async function setupPages({ localeCodes, options, normalizedLocales }, nuxt) {
  const routeResources = {
    i18nPathToPath: {},
    pathToI18nConfig: {}
  };
  addTemplate({
    filename: "i18n-route-resources.mjs",
    write: true,
    getContents: () => {
      return `// Generated by @nuxtjs/i18n
export const pathToI18nConfig = ${JSON.stringify(routeResources.pathToI18nConfig, null, 2)};
export const i18nPathToPath = ${JSON.stringify(routeResources.i18nPathToPath, null, 2)};`;
    }
  });
  if (!localeCodes.length) return;
  let includeUnprefixedFallback = !nuxt.options.ssr;
  nuxt.hook("nitro:init", () => {
    includeUnprefixedFallback = options.strategy !== "prefix";
  });
  const projectLayer = nuxt.options._layers[0];
  const typedRouter = await setupExperimentalTypedRoutes(options, nuxt);
  nuxt.options.experimental.extraPageMetaExtractionKeys ??= [];
  nuxt.options.experimental.extraPageMetaExtractionKeys.push("i18n");
  nuxt.hook(
    nuxt.options.experimental.scanPageMeta === "after-resolve" ? "pages:resolved" : "pages:extend",
    async (pages) => {
      const ctx = new NuxtPageAnalyzeContext(options.pages);
      for (const layer of nuxt.options._layers) {
        const pagesDir = resolve(projectLayer.config.rootDir, layer.config.srcDir, layer.config.dir?.pages ?? "pages");
        analyzeNuxtPages(ctx, pagesDir, pages);
      }
      if (typedRouter) {
        await typedRouter.createContext(pages).scanPages(false);
      }
      const localizedPages = localizeRoutes(pages, {
        ...options,
        includeUnprefixedFallback,
        locales: normalizedLocales,
        optionsResolver: getRouteOptionsResolver(ctx, options.defaultLocale, options.customRoutes)
      });
      const indexPage = pages.find((x) => x.path === "/");
      if (options.strategy === "prefix" && indexPage != null) {
        localizedPages.unshift(indexPage);
      }
      const invertedMap = {};
      const localizedMapInvert = {};
      for (const [path, localeConfig] of Object.entries(ctx.pathToConfig)) {
        const resPath = resolveRoutePath(path);
        invertedMap[resPath] ??= {};
        for (const [locale, localePath] of Object.entries(localeConfig)) {
          const localized = localePath === true ? path : localePath;
          invertedMap[resPath][locale] = localized && resolveRoutePath(localized);
          if (invertedMap[resPath][locale]) {
            localizedMapInvert[invertedMap[resPath][locale]] = resPath;
          }
        }
      }
      routeResources.i18nPathToPath = localizedMapInvert;
      routeResources.pathToI18nConfig = invertedMap;
      await updateTemplates({
        filter: (template) => template.filename === "i18n-route-resources.mjs"
      });
      if (pages !== localizedPages) {
        pages.length = 0;
        pages.unshift(...localizedPages);
      }
    }
  );
}
const routeNamedMapTypeRE = /RouteNamedMap\b/;
const declarationFile = "./types/typed-router-i18n.d.ts";
async function setupExperimentalTypedRoutes(userOptions, nuxt) {
  if (!nuxt.options.experimental.typedPages || userOptions.experimental?.typedPages === false) {
    return void 0;
  }
  const dtsFile = resolve(nuxt.options.buildDir, declarationFile);
  function createContext(pages) {
    const typedRouteroptions = {
      routesFolder: [],
      dts: dtsFile,
      logs: !!nuxt.options.debug,
      watch: false,
      // eslint-disable-next-line @typescript-eslint/require-await
      async beforeWriteFiles(rootPage) {
        rootPage.children.forEach((child) => child.delete());
        function addPage(parent, page) {
          const route = parent.insert(page.path, page.file);
          if (page.meta) {
            route.addToMeta(page.meta);
          }
          if (page.alias) {
            route.addAlias(page.alias);
          }
          if (page.name) {
            route.name = page.name;
          }
          if (page.children) {
            page.children.forEach((child) => addPage(route, child));
          }
        }
        for (const page of pages) {
          addPage(rootPage, page);
        }
      }
    };
    const context = createRoutesContext(resolveOptions(typedRouteroptions));
    const originalScanPages = context.scanPages.bind(context);
    context.scanPages = async function(watchers = false) {
      await mkdir(dirname(dtsFile), { recursive: true });
      await originalScanPages(watchers);
      const dtsContent = await readFile(dtsFile, "utf-8");
      if (routeNamedMapTypeRE.test(dtsContent)) {
        await writeFile(dtsFile, dtsContent.replace(routeNamedMapTypeRE, "RouteNamedMapI18n"));
      }
    };
    return context;
  }
  addTemplate({
    filename: resolve(nuxt.options.buildDir, "./types/i18n-generated-route-types.d.ts"),
    getContents: () => {
      return `// Generated by @nuxtjs/i18n
declare module 'vue-router' {
  import type { RouteNamedMapI18n } from 'vue-router/auto-routes'

  export interface TypesConfig {
    RouteNamedMapI18n: RouteNamedMapI18n
  }
}

export {}`;
    }
  });
  nuxt.hook("prepare:types", ({ references }) => {
    references.push({ path: declarationFile });
    references.push({ types: "./types/i18n-generated-route-types.d.ts" });
  });
  await createContext(nuxt.apps.default?.pages ?? []).scanPages(false);
  return { createContext };
}
function analyzePagePath(pagePath, parents = 0) {
  const { dir, name } = parse(pagePath);
  if (parents > 0 || dir !== "/") {
    return `${dir.slice(1, dir.length)}/${name}`;
  }
  return name;
}
function analyzeNuxtPages(ctx, pagesDir, pages) {
  if (pages == null || pages.length === 0) return;
  for (const page of pages) {
    if (page.file == null) continue;
    const [, filePath] = page.file.split(pagesDir);
    if (filePath == null) continue;
    ctx.addPage(page, analyzePagePath(filePath), page.name ?? page.children?.find((x) => x.path.endsWith("/index"))?.name);
    analyzeNuxtPages(ctx, pagesDir, page.children);
  }
}
function getRouteOptionsResolver(ctx, defaultLocale, customRoutes) {
  return (route, localeCodes) => {
    const res = getRouteOptions(route, localeCodes, ctx, defaultLocale, customRoutes);
    if (route.file) {
      const localeCfg = res?.srcPaths;
      const mappedPath = ctx.fileToPath[route.file];
      ctx.pathToConfig[mappedPath] ??= {};
      for (const l of localeCodes) {
        ctx.pathToConfig[mappedPath][l] ??= localeCfg?.[l] ?? false;
      }
      for (const l of res?.locales ?? []) {
        ctx.pathToConfig[mappedPath][l] ||= true;
      }
    }
    return res;
  };
}
function resolveRoutePath(path) {
  const tokens = parseSegment(path.slice(1));
  return getRoutePath(tokens);
}
function getRouteFromConfig(ctx, route, localeCodes) {
  const pageMeta = ctx.pages.get(route.file);
  if (pageMeta == null) {
    return void 0;
  }
  const valueByName = pageMeta?.name ? ctx.config?.[pageMeta.name] : void 0;
  const valueByPath = pageMeta?.path != null ? ctx.config?.[pageMeta.path] : void 0;
  const resolved = valueByName ?? valueByPath;
  if (!resolved) return resolved;
  return {
    paths: resolved ?? {},
    locales: localeCodes.filter((locale) => resolved[locale] !== false)
  };
}
function getRouteFromResource(localeCodes, resolved) {
  if (!resolved) return resolved;
  return {
    paths: resolved.paths ?? {},
    locales: resolved?.locales || localeCodes
  };
}
function getRouteOptions(route, localeCodes, ctx, defaultLocale, mode = "config") {
  let resolvedOptions;
  if (mode === "config") {
    resolvedOptions = getRouteFromConfig(ctx, route, localeCodes);
  } else {
    resolvedOptions = getRouteFromResource(
      localeCodes,
      mode === "page" ? getI18nRouteConfig(route.file) : route.meta?.i18n
    );
  }
  if (resolvedOptions === false) {
    return void 0;
  }
  const locales = resolvedOptions?.locales || localeCodes;
  const paths = {};
  if (!resolvedOptions) {
    return { locales, paths };
  }
  for (const locale of resolvedOptions.locales) {
    if (isString(resolvedOptions.paths[locale])) {
      paths[locale] = resolveRoutePath(resolvedOptions.paths[locale]);
      continue;
    }
    if (isString(resolvedOptions.paths[defaultLocale])) {
      paths[locale] = resolveRoutePath(resolvedOptions.paths[defaultLocale]);
    }
  }
  return { locales, paths, srcPaths: resolvedOptions.paths };
}
function getI18nRouteConfig(absolutePath, vfs = {}) {
  let extract = void 0;
  try {
    const content = absolutePath in vfs ? vfs[absolutePath] : readFileSync(absolutePath, "utf-8");
    if (!content.includes(DEFINE_I18N_ROUTE_FN)) return void 0;
    const { descriptor } = parse$1(content);
    const script = descriptor.scriptSetup || descriptor.script;
    if (!script) return void 0;
    const lang = typeof script.attrs.lang === "string" && /j|tsx/.test(script.attrs.lang) ? "tsx" : "ts";
    let code = script.content;
    parseAndWalk(script.content, absolutePath.replace(/\.\w+$/, "." + lang), (node) => {
      if (extract != null) return;
      if (node.type !== "CallExpression" || node.callee.type !== "Identifier" || node.callee.name !== DEFINE_I18N_ROUTE_FN)
        return;
      let routeArgument = node.arguments[0];
      if (routeArgument == null) return;
      if (typeof script.attrs.lang === "string" && /tsx?/.test(script.attrs.lang)) {
        const transformed = transform("", script.content.slice(node.start, node.end).trim(), { lang });
        code = transformed.code;
        if (transformed.errors.length) {
          for (const error of transformed.errors) {
            console.warn(`Error while transforming \`${DEFINE_I18N_ROUTE_FN}()\`` + error.codeframe);
          }
          return;
        }
        routeArgument = parseSync("", transformed.code, { lang: "js" }).program.body[0].expression.arguments[0];
      }
      extract = evalAndValidateValue(code.slice(routeArgument.start, routeArgument.end).trim());
    });
  } catch (e) {
    console.warn(`[nuxt-i18n] Couldn't read component data at ${absolutePath}: (${e.message})`);
  }
  return extract;
}
function evalValue(value) {
  try {
    return new Function(`return (${value})`)();
  } catch {
    console.error(`[nuxt-i18n] Cannot evaluate value: ${value}`);
    return;
  }
}
function evalAndValidateValue(value) {
  const evaluated = evalValue(value);
  if (evaluated == null) return;
  if (typeof evaluated === "boolean" && evaluated === false) {
    return evaluated;
  }
  if (Object.prototype.toString.call(evaluated) === "[object Object]") {
    if (evaluated.locales) {
      if (!Array.isArray(evaluated.locales) || evaluated.locales.some((locale) => typeof locale !== "string")) {
        console.warn(`[nuxt-i18n] Invalid locale option used with \`defineI18nRoute\`: ${value}`);
        return;
      }
    }
    if (evaluated.paths && Object.prototype.toString.call(evaluated.paths) !== "[object Object]") {
      console.warn(`[nuxt-i18n] Invalid paths option used with \`defineI18nRoute\`: ${value}`);
      return;
    }
    return evaluated;
  }
  console.warn(`[nuxt-i18n] Invalid value passed to \`defineI18nRoute\`: ${value}`);
}

const I18N_MACRO_FN_RE = new RegExp(`\\b${DEFINE_I18N_ROUTE_FN}\\s*\\(\\s*`);
const TransformMacroPlugin = (options) => createUnplugin(() => {
  return {
    name: "nuxtjs:i18n-macros-transform",
    enforce: "pre",
    transformInclude(id) {
      if (!id || id.startsWith(VIRTUAL_PREFIX_HEX)) {
        return false;
      }
      return isVue(id, { type: ["script"] });
    },
    transform: {
      filter: {
        code: { include: I18N_MACRO_FN_RE }
      },
      handler(code) {
        const parsed = parse$1(code, { sourceMap: false });
        const script = parsed.descriptor.scriptSetup ?? parsed.descriptor.script;
        if (!script) {
          return;
        }
        const s = new MagicString(code);
        const match = script.content.match(I18N_MACRO_FN_RE);
        if (match?.[0]) {
          const scriptString = new MagicString(script.content);
          scriptString.overwrite(match.index, match.index + match[0].length, `false && /*#__PURE__*/ ${match[0]}`);
          s.overwrite(script.loc.start.offset, script.loc.end.offset, scriptString.toString());
        }
        if (s.hasChanged()) {
          return {
            code: s.toString(),
            map: options.sourcemap ? s.generateMap({ hires: true }) : void 0
          };
        }
      }
    }
  };
});

const TRANSLATION_FUNCTIONS = ["$t", "$rt", "$d", "$n", "$tm", "$te"];
const TRANSLATION_FUNCTIONS_RE = /\$([tdn]|rt|tm|te)\s*\(\s*/;
const TRANSLATION_FUNCTIONS_MAP = {
  $t: "t: $t",
  $rt: "rt: $rt",
  $d: "d: $d",
  $n: "n: $n",
  $tm: "tm: $tm",
  $te: "te: $te"
};
const QUERY_RE = /\?.*$/;
function withoutQuery(id) {
  return id.replace(QUERY_RE, "");
}
const TransformI18nFunctionPlugin = (options) => createUnplugin(() => {
  return {
    name: "nuxtjs:i18n-function-injection",
    enforce: "pre",
    transformInclude(id) {
      return isVue(id, { type: ["script"] });
    },
    transform: {
      filter: {
        code: { include: TRANSLATION_FUNCTIONS_RE }
      },
      handler(code, id) {
        const script = extractScriptSetupContent(code);
        if (!script) return;
        const filepath = withoutQuery(id).replace(/\.\w+$/, "." + script.loader);
        const missing = collectMissingI18nFunctions(script.code, filepath);
        if (!missing.size) return;
        const assignments = [];
        for (const entry of missing) {
          assignments.push(TRANSLATION_FUNCTIONS_MAP[entry]);
        }
        const s = new MagicString(code);
        s.appendLeft(script.start, `
const { ${assignments.join(", ")} } = useI18n()
`);
        return {
          code: s.toString(),
          map: options.sourcemap ? s.generateMap({ hires: true }) : void 0
        };
      }
    }
  };
});
function collectMissingI18nFunctions(script, id) {
  const scopeTracker = new ScopeTracker({ preserveExitedScopes: true });
  const ast = parseAndWalk(script, id, { scopeTracker });
  const missing = /* @__PURE__ */ new Set();
  walk(ast.program, {
    scopeTracker,
    enter(node) {
      if (node.type !== "CallExpression" || node.callee.type !== "Identifier") return;
      const name = node.callee.name;
      if (!name || !TRANSLATION_FUNCTIONS.includes(name) || scopeTracker.isDeclared(name)) return;
      missing.add(name);
    }
  });
  return missing;
}
const SFC_SCRIPT_COMPLEX_RE = /<script(?<attrs>[^>]*)>(?<content>[\s\S]*?)<\/script[^>]*>/i;
function extractScriptSetupContent(sfc) {
  const match = sfc.match(SFC_SCRIPT_COMPLEX_RE);
  if (match?.groups?.content && match.groups.attrs && match.groups.attrs.indexOf("setup") !== -1) {
    return {
      code: match.groups.content.trim(),
      loader: match.groups.attrs && /[tj]sx/.test(match.groups.attrs) ? "tsx" : "ts",
      start: sfc.indexOf(match.groups.content)
    };
  }
}

const HeistPlugin = (options, ctx, nuxt = useNuxt()) => {
  const shared = ctx.resolver.resolve(ctx.distDir, "runtime/shared/*");
  const replacementName = `__nuxtMock`;
  const replacementMock = `const ${replacementName} = { runWithContext: async (fn) => await fn() };`;
  const resources = ["i18n-route-resources.mjs", "i18n-options.mjs"];
  return createUnplugin(() => ({
    name: "nuxtjs:i18n-heist",
    enforce: "pre",
    transform: {
      filter: {
        id: [shared, relative(nuxt.options.rootDir, shared)]
      },
      handler(code) {
        const s = new MagicString(code);
        if (code.includes("useRuntimeConfig()")) {
          s.prepend('import { useRuntimeConfig } from "nitropack/runtime";\n');
        }
        s.replace(/import.+["']#app["'];?/, replacementMock);
        s.replaceAll(/useNuxtApp\(\)/g, replacementName);
        for (const resource of resources) {
          s.replaceAll(new RegExp(`#build/${resource}`, "g"), `#internal/${resource}`);
        }
        return {
          code: s.toString(),
          map: options.sourcemap ? s.generateMap({ hires: true }) : void 0
        };
      }
    }
  }));
};

const version = "10.0.6";

async function extendBundler(ctx, nuxt) {
  const pluginOptions = {
    sourcemap: !!nuxt.options.sourcemap.server || !!nuxt.options.sourcemap.client
  };
  const resourcePlugin = ResourcePlugin(pluginOptions, ctx);
  addBuildPlugin(resourcePlugin);
  nuxt.hook("nitro:config", async (cfg) => {
    cfg.rollupConfig.plugins = await cfg.rollupConfig.plugins || [];
    cfg.rollupConfig.plugins = toArray(cfg.rollupConfig.plugins);
    cfg.rollupConfig.plugins.push(HeistPlugin(pluginOptions, ctx).rollup());
    cfg.rollupConfig.plugins.push(resourcePlugin.rollup());
  });
  const localePaths = [...new Set(ctx.localeInfo.flatMap((x) => x.meta.map((m) => m.path)))];
  ctx.fullStatic = ctx.localeInfo.flatMap((x) => x.meta).every((x) => x.type === "static" || x.cache !== false);
  const vueI18nPluginOptions = {
    ...ctx.options.bundle,
    ...ctx.options.compilation,
    ...ctx.options.customBlocks,
    allowDynamic: true,
    optimizeTranslationDirective: false,
    include: localePaths.length ? localePaths : void 0
  };
  addBuildPlugin({
    vite: () => VueI18nPlugin.vite(vueI18nPluginOptions),
    // eslint-disable-next-line @typescript-eslint/no-unsafe-return
    webpack: () => VueI18nPlugin.webpack(vueI18nPluginOptions)
  });
  addBuildPlugin(TransformMacroPlugin(pluginOptions));
  if (ctx.options.autoDeclare && nuxt.options.imports.autoImport !== false) {
    addBuildPlugin(TransformI18nFunctionPlugin(pluginOptions));
  }
  const defineConfig = getDefineConfig(ctx);
  await addDefinePlugin(defineConfig);
}
function getDefineConfig({ options, fullStatic }, server = false, nuxt = useNuxt()) {
  const cacheLifetime = options.experimental.cacheLifetime ?? (fullStatic ? FULL_STATIC_LIFETIME : -1);
  const isCacheEnabled = cacheLifetime >= 0 && (!nuxt.options.dev || !!options.experimental.devCache);
  let stripMessagesPayload = !!options.experimental.preload;
  if (nuxt.options.i18n?.experimental?.stripMessagesPayload != null) {
    stripMessagesPayload = nuxt.options.i18n.experimental.stripMessagesPayload;
  }
  const common = {
    __IS_SSR__: String(nuxt.options.ssr),
    __IS_SSG__: String(!!nuxt.options.nitro.static),
    __PARALLEL_PLUGIN__: String(options.parallelPlugin),
    __DYNAMIC_PARAMS_KEY__: JSON.stringify(DYNAMIC_PARAMS_KEY),
    __DEFAULT_COOKIE_KEY__: JSON.stringify(DEFAULT_COOKIE_KEY),
    __NUXT_I18N_VERSION__: JSON.stringify(version),
    __NUXT_I18N_MODULE_ID__: JSON.stringify(NUXT_I18N_MODULE_ID),
    __SWITCH_LOCALE_PATH_LINK_IDENTIFIER__: JSON.stringify(SWITCH_LOCALE_PATH_LINK_IDENTIFIER),
    __I18N_STRATEGY__: JSON.stringify(options.strategy),
    __DIFFERENT_DOMAINS__: String(options.differentDomains),
    __MULTI_DOMAIN_LOCALES__: String(options.multiDomainLocales),
    __ROUTE_NAME_SEPARATOR__: JSON.stringify(options.routesNameSeparator),
    __ROUTE_NAME_DEFAULT_SUFFIX__: JSON.stringify(options.defaultLocaleRouteNameSuffix),
    __TRAILING_SLASH__: String(options.trailingSlash),
    __DEFAULT_DIRECTION__: JSON.stringify(options.defaultDirection),
    __I18N_CACHE__: String(isCacheEnabled),
    __I18N_CACHE_LIFETIME__: JSON.stringify(cacheLifetime),
    __I18N_FULL_STATIC__: String(fullStatic),
    __I18N_STRIP_UNUSED__: JSON.stringify(stripMessagesPayload),
    __I18N_PRELOAD__: JSON.stringify(!!options.experimental.preload),
    // eslint-disable-next-line @typescript-eslint/no-base-to-string
    __I18N_ROUTING__: JSON.stringify(nuxt.options.pages.toString() && options.strategy !== "no_prefix"),
    __I18N_STRICT_SEO__: JSON.stringify(!!options.experimental.strictSeo),
    __I18N_SERVER_REDIRECT__: JSON.stringify(!!options.experimental.nitroContextDetection)
  };
  if (nuxt.options.ssr || !server) {
    return {
      ...common,
      __VUE_I18N_LEGACY_API__: String(!(options.bundle?.compositionOnly ?? true)),
      __VUE_I18N_FULL_INSTALL__: String(options.bundle?.fullInstall ?? true),
      __INTLIFY_PROD_DEVTOOLS__: "false",
      __INTLIFY_DROP_MESSAGE_COMPILER__: String(options.bundle?.dropMessageCompiler ?? false)
    };
  }
  return common;
}

async function setupNitro(ctx, nuxt) {
  const [enableServerIntegration, localeDetectionPath] = await resolveLocaleDetectorPath(nuxt);
  addServerTemplate({
    filename: "#internal/i18n-options.mjs",
    getContents: () => nuxt.vfs["#build/i18n-options.mjs"].replace(/\/\*\* client \*\*\/[\s\S]*\/\*\* client-end \*\*\//, "")
  });
  addServerTemplate({
    filename: "#internal/i18n-route-resources.mjs",
    getContents: () => nuxt.vfs["#build/i18n-route-resources.mjs"] || ""
  });
  addServerTemplate({
    filename: "#internal/i18n-locale-detector.mjs",
    getContents: () => enableServerIntegration ? `import localeDetector from ${JSON.stringify(localeDetectionPath)}
export { localeDetector }` : `const localeDetector = undefined
        export { localeDetector }`
    // no-op
  });
  nuxt.hook("nitro:config", async (nitroConfig) => {
    nitroConfig.externals = defu(nitroConfig.externals ?? {}, { inline: [ctx.resolver.resolve("./runtime")] });
    nitroConfig.alias["#i18n"] = ctx.resolver.resolve("./runtime/composables/index-server");
    nitroConfig.rollupConfig.plugins = await nitroConfig.rollupConfig.plugins || [];
    nitroConfig.rollupConfig.plugins = toArray(nitroConfig.rollupConfig.plugins);
    const localePathsByType = getResourcePathsGrouped(ctx.localeInfo);
    if (localePathsByType.yaml.length > 0) {
      nitroConfig.rollupConfig.plugins.push(yamlPlugin({ include: localePathsByType.yaml }));
    }
    if (localePathsByType.json5.length > 0) {
      nitroConfig.rollupConfig.plugins.push(json5Plugin({ include: localePathsByType.json5 }));
    }
    if (nitroConfig.imports) {
      nitroConfig.imports.presets ||= [];
      nitroConfig.imports.presets.push({ from: H3_PKG, imports: ["useTranslation"] });
    }
    nitroConfig.replace = Object.assign({}, nitroConfig.replace, getDefineConfig(ctx, true));
  });
  addServerImports(
    [DEFINE_I18N_LOCALE_FN, DEFINE_I18N_CONFIG_FN].map((key) => ({
      name: key,
      as: key,
      from: ctx.resolver.resolve("runtime/composables/shared")
    }))
  );
  addServerImports([
    {
      name: DEFINE_LOCALE_DETECTOR_FN,
      as: DEFINE_LOCALE_DETECTOR_FN,
      from: ctx.resolver.resolve("runtime/composables/server")
    }
  ]);
  const h3UtilsExports = await resolveModuleExportNames(UTILS_H3_PKG, { url: import.meta.url });
  addServerImports(
    h3UtilsExports.map((key) => ({
      name: key,
      as: key,
      from: ctx.resolver.resolve(nuxt.options.alias[UTILS_H3_PKG])
    }))
  );
  addServerPlugin(ctx.resolver.resolve("runtime/server/plugin"));
  addServerHandler({
    route: `/_i18n/:locale/messages.json`,
    handler: ctx.resolver.resolve("./runtime/server/routes/messages")
  });
}
async function resolveLocaleDetectorPath(nuxt) {
  const i18nLayer = nuxt.options._layers.find((l) => !!getLayerI18n(l)?.experimental?.localeDetector);
  if (i18nLayer == null) {
    return [false, ""];
  }
  const i18nLayerConfig = getLayerI18n(i18nLayer);
  const i18nDir = resolveI18nDir(i18nLayer, i18nLayerConfig);
  const localeDetectorPath = await resolvePath(resolve(i18nDir, i18nLayerConfig.experimental.localeDetector), {
    cwd: nuxt.options.rootDir,
    extensions: EXECUTABLE_EXTENSIONS
  });
  const exists = existsSync(localeDetectorPath);
  if (!exists) {
    logger.warn(`localeDetector file '${localeDetectorPath}' does not exist. skip server-side integration ...`);
  }
  return [exists, localeDetectorPath];
}
function getResourcePathsGrouped(localeInfo) {
  const groups = { yaml: [], json5: [] };
  for (const locale of localeInfo) {
    groups.yaml = groups.yaml.concat(locale.meta.filter((meta) => /\.ya?ml$/.test(meta.path)).map((x) => x.path));
    groups.json5 = groups.json5.concat(locale.meta.filter((meta) => /\.json5?$/.test(meta.path)).map((x) => x.path));
  }
  return groups;
}

const resolver = createResolver(import.meta.url);
const distDir = dirname(fileURLToPath(import.meta.url));
const runtimeDir = fileURLToPath(new URL("./runtime", import.meta.url));
function createContext(userOptions) {
  const options = userOptions;
  return {
    options,
    resolver,
    userOptions,
    distDir,
    runtimeDir,
    localeInfo: void 0,
    localeCodes: void 0,
    normalizedLocales: void 0,
    vueI18nConfigPaths: void 0,
    fullStatic: void 0
  };
}

function prepareOptions({ options }, nuxt) {
  checkLayerOptions(options, nuxt);
  if (options.bundle.compositionOnly && options.types === "legacy") {
    throw new Error(
      `[nuxt-i18n] \`bundle.compositionOnly\` option and \`types\` option are conflicting: bundle.compositionOnly: ${options.bundle.compositionOnly}, types: ${JSON.stringify(options.types)}`
    );
  }
  if (nuxt.options.i18n?.autoDeclare && nuxt.options.imports.autoImport === false) {
    logger.warn(
      "Disabling `autoImports` in Nuxt is not compatible with `autoDeclare`, either enable `autoImports` or disable `autoDeclare`."
    );
  }
  const strategy = nuxt.options.i18n?.strategy || options.strategy;
  if (strategy.endsWith("_default") && !nuxt.options.i18n?.defaultLocale) {
    logger.warn(
      `The \`${strategy}\` i18n strategy${nuxt.options.i18n?.strategy == null ? " (used by default)" : ""} needs \`defaultLocale\` to be set.`
    );
  }
  if (nuxt.options.experimental.scanPageMeta === false) {
    logger.warn(
      "Route localization features (e.g. custom name, prefixed aliases) require Nuxt's `experimental.scanPageMeta` to be enabled.\nThis feature will be enabled in future Nuxt versions (https://github.com/nuxt/nuxt/pull/27134), check out the docs for more details: https://nuxt.com/docs/guide/going-further/experimental-features#scanpagemeta"
    );
  }
}

async function resolveLocaleInfo(ctx, nuxt) {
  ctx.options.locales = await applyLayerOptions(ctx, nuxt);
  ctx.options.locales = filterLocales(ctx, nuxt);
  ctx.normalizedLocales = ctx.options.locales.map((x) => isString(x) ? { code: x, language: x } : x);
  ctx.localeCodes = ctx.normalizedLocales.map((locale) => locale.code);
  ctx.localeInfo = resolveLocales(nuxt.options.srcDir, ctx.normalizedLocales);
  ctx.vueI18nConfigPaths = await resolveLayerVueI18nConfigInfo(ctx.options);
}

const loadConfigsFn = `
async function loadCfg(config) {
  const nuxt = useNuxtApp()
  const { default: resolver } = await config()
  return typeof resolver === 'function' ? await nuxt.runWithContext(() => resolver()) : resolver
}
`;
function genLocaleLoaderHMR(localeLoaders) {
  const statements = [];
  for (const locale in localeLoaders) {
    for (let i = 0; i < localeLoaders[locale].length; i++) {
      const loader = localeLoaders[locale][i];
      statements.push(
        [
          `  import.meta.hot.accept("${loader.relative}", async mod => {`,
          //   replace locale loader
          `    localeLoaders["${locale}"][${i}].load = () => Promise.resolve(mod.default)`,
          //   trigger locale messages reload for locale
          `    await useNuxtApp()._nuxtI18n.dev.resetI18nProperties("${locale}")`,
          `  })`
        ].join("\n")
      );
    }
  }
  return statements.join("\n\n");
}
function genVueI18nConfigHMR(configs) {
  const statements = [];
  for (let i = 0; i < configs.length; i++) {
    statements.push(
      [
        `  import.meta.hot.accept("${configs[i].relative}", async mod => {`,
        //   load configs before replacing loader
        `    const [oldData, newData] = await Promise.all([loadCfg(vueI18nConfigs[${i}]), loadCfg(() => Promise.resolve(mod))]);`,
        //   replace config loader
        `    vueI18nConfigs[${i}] = () => Promise.resolve(mod)`,
        //   compare data - reload configs if _only_ replaceable properties have changed
        `    if(useNuxtApp()._nuxtI18n.dev.deepEqual(oldData, newData, ['messages', 'numberFormats', 'datetimeFormats'])) {`,
        `      return await useNuxtApp()._nuxtI18n.dev.resetI18nProperties()`,
        `    }`,
        //   communicate to vite plugin to trigger a page load
        `    import.meta.hot.send('i18n:options-complex-invalidation', {})`,
        `  })`
      ].join("\n")
    );
  }
  return statements.join("\n\n");
}
function generateTemplateNuxtI18nOptions(ctx, opts, nuxt = useNuxt()) {
  const codeHMR = nuxt.options.dev && ctx.options.hmr && [
    `if(import.meta.hot) {`,
    loadConfigsFn,
    genLocaleLoaderHMR(opts.localeLoaders),
    genVueI18nConfigHMR(opts.vueI18nConfigs),
    "}"
  ].join("\n\n");
  const localeLoaderEntries = {};
  for (const locale in opts.localeLoaders) {
    localeLoaderEntries[locale] = opts.localeLoaders[locale].map(({ key, load, cache }) => ({ key, load, cache }));
  }
  return `// @ts-nocheck
export const localeCodes =  ${genArrayFromRaw(ctx.localeCodes.map((x) => genString(x)))}
export const localeLoaders = ${genObjectFromRaw(localeLoaderEntries)}
export const vueI18nConfigs = ${genArrayFromRaw(opts.vueI18nConfigs.map((x) => x.importer))}
export const normalizedLocales = ${genArrayFromRaw(opts.normalizedLocales.map((x) => genObjectFromValues(x, "  ")))}
/** client **/
${codeHMR || ""}
/** client-end **/`;
}

function stripLocaleFiles(locale) {
  delete locale.files;
  delete locale.file;
  return locale;
}
function simplifyLocaleOptions(ctx, _nuxt) {
  const locales = ctx.options.locales ?? [];
  const hasLocaleObjects = locales?.some((x) => !isString(x));
  return locales.map((locale) => !hasLocaleObjects ? locale.code : stripLocaleFiles(locale));
}
function generateLoaderOptions(ctx, nuxt) {
  const importMapper = /* @__PURE__ */ new Map();
  const localeLoaders = {};
  for (const locale of ctx.localeInfo) {
    localeLoaders[locale.code] ??= [];
    for (const meta of locale.meta) {
      if (!importMapper.has(meta.path)) {
        const key = genString(`locale_${genSafeVariableName(basename(meta.path))}_${meta.hash}`);
        importMapper.set(meta.path, {
          key,
          relative: relative(nuxt.options.buildDir, meta.path),
          cache: meta.cache ?? true,
          load: genDynamicImport(asI18nVirtual(meta.hash), { comment: `webpackChunkName: ${key}` })
        });
      }
      localeLoaders[locale.code].push(importMapper.get(meta.path));
    }
  }
  const vueI18nConfigs = [];
  for (let i = ctx.vueI18nConfigPaths.length - 1; i >= 0; i--) {
    const config = ctx.vueI18nConfigPaths[i];
    const key = genString(`config_${genSafeVariableName(basename(config.path))}_${config.hash}`);
    vueI18nConfigs.push({
      importer: genDynamicImport(asI18nVirtual(config.hash), { comment: `webpackChunkName: ${key}` }),
      relative: relative(nuxt.options.buildDir, config.path)
    });
  }
  const normalizedLocales = ctx.normalizedLocales.map((x) => stripLocaleFiles(x));
  return { localeLoaders, vueI18nConfigs, normalizedLocales };
}
const typedRouterAugmentations = `
declare module 'vue-router' {
  import type { RouteNamedMapI18n } from 'vue-router/auto-routes'

  export interface TypesConfig {
    RouteNamedMapI18n: RouteNamedMapI18n
  }

  export type RouteMapI18n =
    TypesConfig extends Record<'RouteNamedMapI18n', infer RouteNamedMap> ? RouteNamedMap : RouteMapGeneric
    
  // Prefer named resolution for i18n
  export type RouteLocationNamedI18n<Name extends keyof RouteMapI18n = keyof RouteMapI18n> =
      | Name
      | Omit<RouteLocationAsRelativeI18n, 'path'> & { path?: string }
      /**
       * Note: disabled route path string autocompletion, this can break depending on \`strategy\`
       * this can be enabled again after route resolve has been improved.
      */
      // | RouteLocationAsStringI18n
      // | RouteLocationAsPathI18n

  export type RouteLocationRawI18n<Name extends keyof RouteMapI18n = keyof RouteMapI18n> =
    RouteMapGeneric extends RouteMapI18n
      ? RouteLocationAsStringI18n | RouteLocationAsRelativeGeneric | RouteLocationAsPathGeneric
      :
          | _LiteralUnion<RouteLocationAsStringTypedList<RouteMapI18n>[Name], string>
          | RouteLocationAsRelativeTypedList<RouteMapI18n>[Name]

  export type RouteLocationResolvedI18n<Name extends keyof RouteMapI18n = keyof RouteMapI18n> =
    RouteMapGeneric extends RouteMapI18n
      ? RouteLocationResolvedGeneric
      : RouteLocationResolvedTypedList<RouteMapI18n>[Name]

  export interface RouteLocationNormalizedLoadedTypedI18n<
    RouteMapI18n extends RouteMapGeneric = RouteMapGeneric,
    Name extends keyof RouteMapI18n = keyof RouteMapI18n
  > extends RouteLocationNormalizedLoadedGeneric {
    name: Extract<Name, string | symbol>
    params: RouteMapI18n[Name]['params']
  }
  export type RouteLocationNormalizedLoadedTypedListI18n<RouteMapOriginal extends RouteMapGeneric = RouteMapGeneric> = {
    [N in keyof RouteMapOriginal]: RouteLocationNormalizedLoadedTypedI18n<RouteMapOriginal, N>
  }
  export type RouteLocationNormalizedLoadedI18n<Name extends keyof RouteMapI18n = keyof RouteMapI18n> =
    RouteMapGeneric extends RouteMapI18n
      ? RouteLocationNormalizedLoadedGeneric
      : RouteLocationNormalizedLoadedTypedListI18n<RouteMapI18n>[Name]

  type _LiteralUnion<LiteralType, BaseType extends string = string> = LiteralType | (BaseType & Record<never, never>)

  export type RouteLocationAsStringI18n<Name extends keyof RouteMapI18n = keyof RouteMapI18n> =
    RouteMapGeneric extends RouteMapI18n
      ? string
      : _LiteralUnion<RouteLocationAsStringTypedList<RouteMapI18n>[Name], string>

  export type RouteLocationAsRelativeI18n<Name extends keyof RouteMapI18n = keyof RouteMapI18n> =
    RouteMapGeneric extends RouteMapI18n
      ? RouteLocationAsRelativeGeneric
      : RouteLocationAsRelativeTypedList<RouteMapI18n>[Name]

  export type RouteLocationAsPathI18n<Name extends keyof RouteMapI18n = keyof RouteMapI18n> =
    RouteMapGeneric extends RouteMapI18n ? RouteLocationAsPathGeneric : RouteLocationAsPathTypedList<RouteMapI18n>[Name]

  /**
   * Helper to generate a type safe version of the {@link RouteLocationAsRelative} type.
   */
  export interface RouteLocationAsRelativeTypedI18n<
    RouteMapI18n extends RouteMapGeneric = RouteMapGeneric,
    Name extends keyof RouteMapI18n = keyof RouteMapI18n
  > extends RouteLocationAsRelativeGeneric {
    name?: Extract<Name, string | symbol>
    params?: RouteMapI18n[Name]['paramsRaw']
  }
}`;
function generateI18nTypes(nuxt, ctx) {
  const legacyTypes = ctx.userOptions.types === "legacy";
  const i18nType = legacyTypes ? "VueI18n" : "Composer";
  const generatedLocales = simplifyLocaleOptions(ctx);
  const resolvedLocaleType = isString(generatedLocales.at(0)) ? "Locale[]" : "LocaleObject[]";
  const narrowedLocaleType = ctx.localeCodes.map((x) => JSON.stringify(x)).join(" | ") || "string";
  const globalTranslationTypes = `
declare global {
  var $t: (${i18nType})['t']
  var $rt: (${i18nType})['rt']
  var $n: (${i18nType})['n']
  var $d: (${i18nType})['d']
  var $tm: (${i18nType})['tm']
  var $te: (${i18nType})['te']
}`;
  return `// Generated by @nuxtjs/i18n
import type { ${i18nType} } from 'vue-i18n'
import type { ComposerCustomProperties } from '${relative(
    join$1(nuxt.options.buildDir, "types"),
    resolve(ctx.runtimeDir, "types.ts")
  )}'
import type { Strategies, Directions, LocaleObject } from '${relative(
    join$1(nuxt.options.buildDir, "types"),
    resolve(ctx.distDir, "types.d.mts")
  )}'
import type { I18nRoute } from '#i18n'

declare module 'vue-i18n' {
  interface ComposerCustom extends ComposerCustomProperties<${resolvedLocaleType}> {}
  interface ExportedGlobalComposer extends ComposerCustomProperties<${resolvedLocaleType}> {}
  interface VueI18n extends ComposerCustomProperties<${resolvedLocaleType}> {}
}

declare module '@intlify/core-base' {
  // generated based on configured locales
  interface GeneratedTypeConfig { 
    locale: ${narrowedLocaleType}
    legacy: ${legacyTypes}
  }
}

interface I18nMeta {
  i18n?: I18nRoute | false
}

declare module '#app' {
  interface NuxtApp {
    $i18n: ${i18nType}
  }
  interface PageMeta extends I18nMeta {}
}


declare module 'vue-router' {
  interface RouteMeta extends I18nMeta {}
}

${typedRouterAugmentations}

${ctx.userOptions.autoDeclare && globalTranslationTypes || ""}

export {}`;
}

function prepareRuntime(ctx, nuxt) {
  const { options, resolver } = ctx;
  addPlugin(resolver.resolve("./runtime/plugins/i18n"));
  if (nuxt.options.dev || nuxt.options._prepare) {
    addPlugin(resolver.resolve("./runtime/plugins/dev"));
  }
  addPlugin(resolver.resolve("./runtime/plugins/preload"));
  addPlugin(resolver.resolve("./runtime/plugins/route-locale-detect"));
  addPlugin(resolver.resolve("./runtime/plugins/ssg-detect"));
  addPlugin(resolver.resolve("./runtime/plugins/switch-locale-path-ssr"));
  nuxt.options.alias["#i18n"] = resolver.resolve("./runtime/composables/index");
  nuxt.options.alias["#i18n-kit"] = resolver.resolve("./runtime/kit");
  nuxt.options.alias["#internal-i18n-types"] = resolver.resolve("./types");
  nuxt.options.build.transpile.push("#i18n");
  nuxt.options.build.transpile.push("#i18n-kit");
  nuxt.options.build.transpile.push("#internal-i18n-types");
  if (nuxt.options.dev && options.hmr) {
    addVitePlugin({
      name: "i18n:options-hmr",
      configureServer(server) {
        const reloadClient = () => server.ws.send({ type: "full-reload" });
        server.ws.on("i18n:options-complex-invalidation", () => {
          if (ctx.options.experimental.typedOptionsAndMessages) {
            useNitro().hooks.hookOnce("dev:reload", reloadClient);
            return;
          }
          reloadClient();
        });
      }
    });
  }
  addTemplate({
    filename: "i18n-options.mjs",
    getContents: () => generateTemplateNuxtI18nOptions(ctx, generateLoaderOptions(ctx, nuxt))
  });
  addTypeTemplate({
    filename: "types/i18n-plugin.d.ts",
    getContents: () => generateI18nTypes(nuxt, ctx)
  });
}

function prepareRuntimeConfig(ctx, nuxt) {
  nuxt.options.runtimeConfig.public.i18n = defu(nuxt.options.runtimeConfig.public.i18n, {
    baseUrl: ctx.options.baseUrl,
    defaultLocale: ctx.options.defaultLocale,
    rootRedirect: ctx.options.rootRedirect,
    redirectStatusCode: ctx.options.redirectStatusCode,
    skipSettingLocaleOnNavigate: ctx.options.skipSettingLocaleOnNavigate,
    locales: ctx.options.locales,
    detectBrowserLanguage: ctx.options.detectBrowserLanguage ?? DEFAULT_OPTIONS.detectBrowserLanguage,
    experimental: ctx.options.experimental,
    domainLocales: Object.fromEntries(
      ctx.options.locales.map((l) => {
        if (typeof l === "string") {
          return [l, { domain: "" }];
        }
        return [l.code, { domain: l.domain ?? "" }];
      })
    )
  });
  nuxt.options.runtimeConfig.public.i18n.locales = simplifyLocaleOptions(ctx);
}

function prepareAutoImports({ resolver, userOptions: options, runtimeDir }, nuxt = useNuxt()) {
  addComponent({
    name: "NuxtLinkLocale",
    filePath: resolver.resolve(runtimeDir, "components/NuxtLinkLocale")
  });
  addComponent({
    name: "SwitchLocalePathLink",
    filePath: resolver.resolve(runtimeDir, "components/SwitchLocalePathLink")
  });
  const vueI18nPath = `${VUE_I18N_PKG}/dist/vue-i18n${!nuxt.options.dev && !nuxt.options._prepare && options.bundle?.runtimeOnly ? ".runtime" : ""}.mjs`;
  const composablesIndex = resolver.resolve(runtimeDir, "composables/index");
  addImports([
    { name: "useI18n", from: resolveModule(vueI18nPath) },
    ...[
      "useRouteBaseName",
      "useLocalePath",
      "useLocaleRoute",
      "useSwitchLocalePath",
      "useLocaleHead",
      "useBrowserLocale",
      "useCookieLocale",
      "useSetI18nParams",
      "useI18nPreloadKeys",
      DEFINE_I18N_ROUTE_FN,
      DEFINE_I18N_LOCALE_FN,
      DEFINE_I18N_CONFIG_FN
    ].map((key) => ({ name: key, as: key, from: composablesIndex }))
  ]);
}

function prepareBuildManifest({ localeInfo }, nuxt) {
  nuxt.hook("build:manifest", (manifest) => {
    const langFiles = localeInfo.flatMap((locale) => locale.meta.map((m) => m.path)).map((x) => relative(nuxt.options.srcDir, x));
    const langPaths = [...new Set(langFiles)];
    for (const key in manifest) {
      if (langPaths.some((x) => key.startsWith(x))) {
        manifest[key].prefetch = false;
        manifest[key].preload = false;
      }
    }
  });
}

function prepareStrategy({ options, localeCodes }, nuxt) {
  if (options.strategy === "prefix" && nuxt.options.nitro.static) {
    const localizedEntryPages = localeCodes.map((x) => "/" + x);
    nuxt.hook("nitro:config", (config) => {
      config.prerender ??= {};
      config.prerender.ignore ??= [];
      config.prerender.ignore.push(/^\/$/);
      config.prerender.routes ??= [];
      config.prerender.routes.push(...localizedEntryPages);
    });
  }
}

async function prepareTypeGeneration({ resolver, options }, nuxt) {
  if (options.experimental.typedOptionsAndMessages === false || !nuxt.options.dev) return;
  const declarationFile = "./types/i18n-messages.d.ts";
  const dtsFile = resolver.resolve(nuxt.options.buildDir, declarationFile);
  addServerPlugin(resolver.resolve("runtime/server/type-generation"));
  nuxt.options.nitro = defu$1(nuxt.options.nitro, {
    externals: {
      inline: [/#internal\/i18n-type-generation-options/]
    },
    virtual: {
      "#internal/i18n-type-generation-options": () => `export const dtsFile = ${JSON.stringify(dtsFile)}`
    }
  });
  await mkdir$1(dirname(dtsFile), { recursive: true });
  nuxt.hook("prepare:types", ({ references }) => {
    references.push({ path: declarationFile });
  });
}

const module = defineNuxtModule({
  meta: {
    name: NUXT_I18N_MODULE_ID,
    configKey: "i18n",
    compatibility: {
      nuxt: ">=3.0.0-rc.11",
      // @ts-ignore property removed in Nuxt 4
      bridge: false
    }
  },
  defaults: DEFAULT_OPTIONS,
  async setup(i18nOptions, nuxt) {
    const ctx = createContext(i18nOptions);
    prepareOptions(ctx, nuxt);
    prepareAutoImports(ctx);
    setupAlias(ctx, nuxt);
    nuxt.options.build.transpile.push("@nuxtjs/i18n");
    nuxt.options.build.transpile.push("@nuxtjs/i18n-edge");
    nuxt.options.vite.optimizeDeps ||= {};
    nuxt.options.vite.optimizeDeps.exclude ||= [];
    nuxt.options.vite.optimizeDeps.exclude.push("vue-i18n");
    prepareRuntime(ctx, nuxt);
    await prepareTypeGeneration(ctx, nuxt);
    nuxt.hook("modules:done", async () => {
      await resolveLocaleInfo(ctx, nuxt);
      prepareRuntimeConfig(ctx, nuxt);
      await setupPages(ctx, nuxt);
      prepareStrategy(ctx, nuxt);
      prepareBuildManifest(ctx, nuxt);
      await extendBundler(ctx, nuxt);
      await setupNitro(ctx, nuxt);
    });
  }
});

export { module as default };
