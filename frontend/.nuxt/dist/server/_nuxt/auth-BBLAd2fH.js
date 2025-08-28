import { b as useNuxtApp, j as defineStore, n as navigateTo } from "../server.mjs";
import { ref } from "vue";
import { parse } from "/workspace/frontend/node_modules/nuxt/node_modules/cookie-es/dist/index.mjs";
import { getRequestHeader, setCookie, getCookie, deleteCookie } from "/workspace/frontend/node_modules/h3/dist/index.mjs";
import destr from "/workspace/frontend/node_modules/destr/dist/index.mjs";
import { isEqual } from "/workspace/frontend/node_modules/ohash/dist/index.mjs";
import { klona } from "/workspace/frontend/node_modules/klona/dist/index.mjs";
import { a as useRequestEvent } from "./ssr-B4FXEZKR.js";
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
      console.log("🔑 [LOGIN] Début de la connexion avec:", credentials.email);
      this.loading = true;
      try {
        const { $api } = useNuxtApp();
        console.log("🔑 [LOGIN] Appel API /auth/login...");
        const response = await $api.post("/auth/login", credentials);
        console.log("🔑 [LOGIN] Réponse API:", response.data);
        this.token = response.data.token;
        this.user = response.data.user;
        this.isAuthenticated = true;
        console.log("🔑 [LOGIN] Utilisateur connecté:", {
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
        console.log("🔑 [LOGIN] Token stocké dans cookie");
        return response.data;
      } catch (error) {
        console.error("🔑 [LOGIN] Erreur de connexion:", error);
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
        console.error("Erreur lors de la déconnexion:", error);
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
      console.log("🔍 [FETCH USER] Début fetchUser, token présent:", !!this.token);
      if (!this.token) return;
      try {
        const { $api } = useNuxtApp();
        console.log("🔍 [FETCH USER] Appel API /auth/user...");
        const response = await $api.get("/auth/user");
        console.log("🔍 [FETCH USER] Réponse complète:", JSON.stringify(response.data, null, 2));
        this.user = response.data.user || response.data;
        this.isAuthenticated = true;
        console.log("🔍 [FETCH USER] User assigné:", {
          id: this.user.id,
          email: this.user.email,
          role: this.user.role,
          name: this.user.name
        });
        if (false) ;
      } catch (error) {
        console.error("🔍 [FETCH USER] Erreur lors de la récupération de l'utilisateur:", error);
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
      console.log("🔍 [AUTH DEBUG] Début initializeAuth");
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
export {
  useCookie as a,
  useAuthStore as u
};
//# sourceMappingURL=auth-BBLAd2fH.js.map
