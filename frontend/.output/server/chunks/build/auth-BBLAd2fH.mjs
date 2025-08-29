import { j as defineStore, b as useNuxtApp, n as navigateTo } from './server.mjs';
import { ref } from 'vue';
import { r as destr, v as klona, x as getRequestHeader, y as isEqual, z as setCookie, A as getCookie, B as deleteCookie } from '../nitro/nitro.mjs';
import { a as useRequestEvent } from './ssr-B4FXEZKR.mjs';

function parse(str, options) {
  if (typeof str !== "string") {
    throw new TypeError("argument str must be a string");
  }
  const obj = {};
  const opt = options || {};
  const dec = opt.decode || decode;
  let index = 0;
  while (index < str.length) {
    const eqIdx = str.indexOf("=", index);
    if (eqIdx === -1) {
      break;
    }
    let endIdx = str.indexOf(";", index);
    if (endIdx === -1) {
      endIdx = str.length;
    } else if (endIdx < eqIdx) {
      index = str.lastIndexOf(";", eqIdx - 1) + 1;
      continue;
    }
    const key = str.slice(index, eqIdx).trim();
    if (opt?.filter && !opt?.filter(key)) {
      index = endIdx + 1;
      continue;
    }
    if (void 0 === obj[key]) {
      let val = str.slice(eqIdx + 1, endIdx).trim();
      if (val.codePointAt(0) === 34) {
        val = val.slice(1, -1);
      }
      obj[key] = tryDecode(val, dec);
    }
    index = endIdx + 1;
  }
  return obj;
}
function decode(str) {
  return str.includes("%") ? decodeURIComponent(str) : str;
}
function tryDecode(str, decode2) {
  try {
    return decode2(str);
  } catch {
    return str;
  }
}

const CookieDefaults = {
  path: "/",
  watch: true,
  decode: (val) => destr(decodeURIComponent(val)),
  encode: (val) => encodeURIComponent(typeof val === "string" ? val : JSON.stringify(val))
};
function useCookie(name, _opts) {
  var _a2, _b;
  var _a;
  const opts = { ...CookieDefaults, ..._opts };
  (_a2 = opts.filter) != null ? _a2 : opts.filter = (key) => key === name;
  const cookies = readRawCookies(opts) || {};
  let delay;
  if (opts.maxAge !== void 0) {
    delay = opts.maxAge * 1e3;
  } else if (opts.expires) {
    delay = opts.expires.getTime() - Date.now();
  }
  const hasExpired = delay !== void 0 && delay <= 0;
  const cookieValue = klona(hasExpired ? void 0 : (_b = cookies[name]) != null ? _b : (_a = opts.default) == null ? void 0 : _a.call(opts));
  const cookie = ref(cookieValue);
  {
    const nuxtApp = useNuxtApp();
    const writeFinalCookieValue = () => {
      if (opts.readonly || isEqual(cookie.value, cookies[name])) {
        return;
      }
      nuxtApp._cookies || (nuxtApp._cookies = {});
      if (name in nuxtApp._cookies) {
        if (isEqual(cookie.value, nuxtApp._cookies[name])) {
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
    return parse(getRequestHeader(useRequestEvent(), "cookie") || "", opts);
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
const useAuthStore = defineStore("auth", {
  state: () => ({
    user: null,
    token: null,
    isAuthenticated: false,
    loading: false
  }),
  getters: {
    isAdmin: (state) => {
      var _a;
      return ((_a = state.user) == null ? void 0 : _a.role) === "admin";
    },
    isTeacher: (state) => {
      var _a;
      return ((_a = state.user) == null ? void 0 : _a.role) === "teacher";
    },
    isStudent: (state) => {
      var _a;
      return ((_a = state.user) == null ? void 0 : _a.role) === "student";
    },
    canActAsTeacher: (state) => {
      var _a;
      return ((_a = state.user) == null ? void 0 : _a.can_act_as_teacher) || false;
    },
    canActAsStudent: (state) => {
      var _a;
      return ((_a = state.user) == null ? void 0 : _a.can_act_as_student) || false;
    },
    userName: (state) => {
      var _a;
      return ((_a = state.user) == null ? void 0 : _a.name) || "Utilisateur";
    }
  },
  actions: {
    async login(credentials) {
      console.log("\u{1F511} [LOGIN] D\xE9but de la connexion avec:", credentials.email);
      this.loading = true;
      try {
        const { $api } = useNuxtApp();
        console.log("\u{1F511} [LOGIN] Appel API /auth/login...");
        const response = await $api.post("/auth/login", credentials);
        console.log("\u{1F511} [LOGIN] R\xE9ponse API:", response.data);
        this.token = response.data.token;
        this.user = response.data.user;
        this.isAuthenticated = true;
        console.log("\u{1F511} [LOGIN] Utilisateur connect\xE9:", {
          id: this.user.id,
          email: this.user.email,
          role: this.user.role,
          name: this.user.name
        });
        const tokenCookie = useCookie("auth-token", {
          httpOnly: false,
          secure: false,
          maxAge: 60 * 60 * 24 * 7
          // 7 jours
        });
        tokenCookie.value = this.token;
        console.log("\u{1F511} [LOGIN] Token stock\xE9 dans cookie");
        return response.data;
      } catch (error) {
        console.error("\u{1F511} [LOGIN] Erreur de connexion:", error);
        throw error;
      } finally {
        this.loading = false;
      }
    },
    async register(userData) {
      this.loading = true;
      try {
        const { $api } = useNuxtApp();
        const response = await $api.post("/auth/register", userData);
        this.token = response.data.token;
        this.user = response.data.user;
        this.isAuthenticated = true;
        const tokenCookie = useCookie("auth-token");
        tokenCookie.value = this.token;
        return response.data;
      } catch (error) {
        throw error;
      } finally {
        this.loading = false;
      }
    },
    async logout() {
      try {
        const { $api } = useNuxtApp();
        await $api.post("/auth/logout");
      } catch (error) {
        console.error("Erreur lors de la d\xE9connexion:", error);
      } finally {
        this.user = null;
        this.token = null;
        this.isAuthenticated = false;
        const tokenCookie = useCookie("auth-token");
        tokenCookie.value = null;
        await navigateTo("/login");
      }
    },
    async fetchUser() {
      var _a;
      console.log("\u{1F50D} [FETCH USER] D\xE9but fetchUser, token pr\xE9sent:", !!this.token);
      if (!this.token) return;
      try {
        const { $api } = useNuxtApp();
        console.log("\u{1F50D} [FETCH USER] Appel API /auth/user...");
        const response = await $api.get("/auth/user");
        console.log("\u{1F50D} [FETCH USER] R\xE9ponse compl\xE8te:", JSON.stringify(response.data, null, 2));
        this.user = response.data.user || response.data;
        this.isAuthenticated = true;
        console.log("\u{1F50D} [FETCH USER] User assign\xE9:", {
          id: this.user.id,
          email: this.user.email,
          role: this.user.role,
          name: this.user.name
        });
        if (false) ;
      } catch (error) {
        console.error("\u{1F50D} [FETCH USER] Erreur lors de la r\xE9cup\xE9ration de l'utilisateur:", error);
        if (((_a = error.response) == null ? void 0 : _a.status) === 401) {
          this.user = null;
          this.token = null;
          this.isAuthenticated = false;
          const tokenCookie = useCookie("auth-token");
          tokenCookie.value = null;
        }
        throw error;
      }
    },
    async initializeAuth() {
      console.log("\u{1F50D} [AUTH DEBUG] D\xE9but initializeAuth");
    },
    // Nouvelle méthode pour forcer la vérification du token
    async verifyToken() {
      var _a;
      if (!this.token) {
        return false;
      }
      try {
        await this.fetchUser();
        return true;
      } catch (error) {
        if (((_a = error.response) == null ? void 0 : _a.status) === 401) {
          this.user = null;
          this.token = null;
          this.isAuthenticated = false;
          const tokenCookie = useCookie("auth-token");
          tokenCookie.value = null;
        }
        return false;
      }
    }
  }
});

export { useCookie as a, useAuthStore as u };
//# sourceMappingURL=auth-BBLAd2fH.mjs.map
