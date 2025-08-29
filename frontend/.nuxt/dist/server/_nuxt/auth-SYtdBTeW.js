import { s as defineStore, c as useCookie, d as useNuxtApp, n as navigateTo } from "../server.mjs";
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
  useAuthStore as u
};
//# sourceMappingURL=auth-SYtdBTeW.js.map
