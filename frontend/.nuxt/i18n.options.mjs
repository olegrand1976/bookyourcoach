
// @ts-nocheck
import locale__workspace_frontend_locales_fr_json from "../locales/fr.json";
import locale__workspace_frontend_locales_en_json from "../locales/en.json";
import locale__workspace_frontend_locales_nl_json from "../locales/nl.json";
import locale__workspace_frontend_locales_de_json from "../locales/de.json";
import locale__workspace_frontend_locales_it_json from "../locales/it.json";
import locale__workspace_frontend_locales_es_json from "../locales/es.json";
import locale__workspace_frontend_locales_pt_json from "../locales/pt.json";
import locale__workspace_frontend_locales_hu_json from "../locales/hu.json";
import locale__workspace_frontend_locales_pl_json from "../locales/pl.json";
import locale__workspace_frontend_locales_zh_json from "../locales/zh.json";
import locale__workspace_frontend_locales_ja_json from "../locales/ja.json";
import locale__workspace_frontend_locales_sv_json from "../locales/sv.json";
import locale__workspace_frontend_locales_no_json from "../locales/no.json";
import locale__workspace_frontend_locales_fi_json from "../locales/fi.json";
import locale__workspace_frontend_locales_da_json from "../locales/da.json";


export const localeCodes =  [
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
]

export const localeLoaders = {
  "fr": [{ key: "../locales/fr.json", load: () => Promise.resolve(locale__workspace_frontend_locales_fr_json), cache: true }],
  "en": [{ key: "../locales/en.json", load: () => Promise.resolve(locale__workspace_frontend_locales_en_json), cache: true }],
  "nl": [{ key: "../locales/nl.json", load: () => Promise.resolve(locale__workspace_frontend_locales_nl_json), cache: true }],
  "de": [{ key: "../locales/de.json", load: () => Promise.resolve(locale__workspace_frontend_locales_de_json), cache: true }],
  "it": [{ key: "../locales/it.json", load: () => Promise.resolve(locale__workspace_frontend_locales_it_json), cache: true }],
  "es": [{ key: "../locales/es.json", load: () => Promise.resolve(locale__workspace_frontend_locales_es_json), cache: true }],
  "pt": [{ key: "../locales/pt.json", load: () => Promise.resolve(locale__workspace_frontend_locales_pt_json), cache: true }],
  "hu": [{ key: "../locales/hu.json", load: () => Promise.resolve(locale__workspace_frontend_locales_hu_json), cache: true }],
  "pl": [{ key: "../locales/pl.json", load: () => Promise.resolve(locale__workspace_frontend_locales_pl_json), cache: true }],
  "zh": [{ key: "../locales/zh.json", load: () => Promise.resolve(locale__workspace_frontend_locales_zh_json), cache: true }],
  "ja": [{ key: "../locales/ja.json", load: () => Promise.resolve(locale__workspace_frontend_locales_ja_json), cache: true }],
  "sv": [{ key: "../locales/sv.json", load: () => Promise.resolve(locale__workspace_frontend_locales_sv_json), cache: true }],
  "no": [{ key: "../locales/no.json", load: () => Promise.resolve(locale__workspace_frontend_locales_no_json), cache: true }],
  "fi": [{ key: "../locales/fi.json", load: () => Promise.resolve(locale__workspace_frontend_locales_fi_json), cache: true }],
  "da": [{ key: "../locales/da.json", load: () => Promise.resolve(locale__workspace_frontend_locales_da_json), cache: true }]
}

export const vueI18nConfigs = [
  
]

export const nuxtI18nOptions = {
  "experimental": {
    "localeDetector": "",
    "switchLocalePathLinkSSR": false,
    "autoImportTranslationFunctions": false
  },
  "bundle": {
    "compositionOnly": true,
    "runtimeOnly": false,
    "fullInstall": true,
    "dropMessageCompiler": false
  },
  "compilation": {
    "jit": true,
    "strictMessage": true,
    "escapeHtml": false
  },
  "customBlocks": {
    "defaultSFCLang": "json",
    "globalSFCScope": false
  },
  "vueI18n": "",
  "locales": [
    {
      "code": "fr",
      "name": "Français",
      "files": [
        "/workspace/frontend/locales/fr.json"
      ]
    },
    {
      "code": "en",
      "name": "English",
      "files": [
        "/workspace/frontend/locales/en.json"
      ]
    },
    {
      "code": "nl",
      "name": "Nederlands",
      "files": [
        "/workspace/frontend/locales/nl.json"
      ]
    },
    {
      "code": "de",
      "name": "Deutsch",
      "files": [
        "/workspace/frontend/locales/de.json"
      ]
    },
    {
      "code": "it",
      "name": "Italiano",
      "files": [
        "/workspace/frontend/locales/it.json"
      ]
    },
    {
      "code": "es",
      "name": "Español",
      "files": [
        "/workspace/frontend/locales/es.json"
      ]
    },
    {
      "code": "pt",
      "name": "Português",
      "files": [
        "/workspace/frontend/locales/pt.json"
      ]
    },
    {
      "code": "hu",
      "name": "Magyar",
      "files": [
        "/workspace/frontend/locales/hu.json"
      ]
    },
    {
      "code": "pl",
      "name": "Polski",
      "files": [
        "/workspace/frontend/locales/pl.json"
      ]
    },
    {
      "code": "zh",
      "name": "中文",
      "files": [
        "/workspace/frontend/locales/zh.json"
      ]
    },
    {
      "code": "ja",
      "name": "日本語",
      "files": [
        "/workspace/frontend/locales/ja.json"
      ]
    },
    {
      "code": "sv",
      "name": "Svenska",
      "files": [
        "/workspace/frontend/locales/sv.json"
      ]
    },
    {
      "code": "no",
      "name": "Norsk",
      "files": [
        "/workspace/frontend/locales/no.json"
      ]
    },
    {
      "code": "fi",
      "name": "Suomi",
      "files": [
        "/workspace/frontend/locales/fi.json"
      ]
    },
    {
      "code": "da",
      "name": "Dansk",
      "files": [
        "/workspace/frontend/locales/da.json"
      ]
    }
  ],
  "defaultLocale": "fr",
  "defaultDirection": "ltr",
  "routesNameSeparator": "___",
  "trailingSlash": false,
  "defaultLocaleRouteNameSuffix": "default",
  "strategy": "prefix_except_default",
  "lazy": false,
  "langDir": "locales/",
  "detectBrowserLanguage": {
    "alwaysRedirect": false,
    "cookieCrossOrigin": false,
    "cookieDomain": null,
    "cookieKey": "i18n_redirected",
    "cookieSecure": false,
    "fallbackLocale": "",
    "redirectOn": "root",
    "useCookie": true
  },
  "differentDomains": false,
  "baseUrl": "",
  "dynamicRouteParams": false,
  "customRoutes": "page",
  "pages": {},
  "skipSettingLocaleOnNavigate": false,
  "types": "composition",
  "debug": false,
  "parallelPlugin": false,
  "multiDomainLocales": false,
  "i18nModules": []
}

export const normalizedLocales = [
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
]

export const NUXT_I18N_MODULE_ID = "@nuxtjs/i18n"
export const parallelPlugin = false
export const isSSG = false

export const DEFAULT_DYNAMIC_PARAMS_KEY = "nuxtI18n"
export const DEFAULT_COOKIE_KEY = "i18n_redirected"
export const SWITCH_LOCALE_PATH_LINK_IDENTIFIER = "nuxt-i18n-slp"
