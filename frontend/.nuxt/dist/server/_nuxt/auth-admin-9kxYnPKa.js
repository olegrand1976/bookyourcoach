import { executeAsync } from "/workspace/frontend/node_modules/unctx/dist/index.mjs";
import { k as defineNuxtRouteMiddleware, n as navigateTo, g as createError } from "../server.mjs";
import { u as useAuthStore } from "./auth-SYtdBTeW.js";
import "vue";
import "ofetch";
import "#internal/nuxt/paths";
import "/workspace/frontend/node_modules/hookable/dist/index.mjs";
import "/workspace/frontend/node_modules/h3/dist/index.mjs";
import "vue-router";
import "/workspace/frontend/node_modules/radix3/dist/index.mjs";
import "/workspace/frontend/node_modules/defu/dist/defu.mjs";
import "/workspace/frontend/node_modules/klona/dist/index.mjs";
import "/workspace/frontend/node_modules/nuxt/node_modules/cookie-es/dist/index.mjs";
import "/workspace/frontend/node_modules/destr/dist/index.mjs";
import "/workspace/frontend/node_modules/ohash/dist/index.mjs";
import "@vue/devtools-api";
import "vue/server-renderer";
import "/workspace/frontend/node_modules/@unhead/vue/dist/index.mjs";
const authAdmin = defineNuxtRouteMiddleware(async (to) => {
  var _a;
  let __temp, __restore;
  const authStore = useAuthStore();
  if (!authStore.token) {
    return navigateTo("/login");
  }
  try {
    ;
    [__temp, __restore] = executeAsync(() => authStore.fetchUser()), await __temp, __restore();
    ;
    if (!authStore.isAdmin) {
      throw createError({
        statusCode: 403,
        statusMessage: "Accès refusé - Droits administrateur requis"
      });
    }
  } catch (error) {
    console.error("Erreur vérification admin:", error);
    if (((_a = error.response) == null ? void 0 : _a.status) === 401) {
      [__temp, __restore] = executeAsync(() => authStore.logout()), await __temp, __restore();
      return navigateTo("/login?expired=true");
    }
    throw createError({
      statusCode: 403,
      statusMessage: "Accès refusé - Droits administrateur requis"
    });
  }
});
export {
  authAdmin as default
};
//# sourceMappingURL=auth-admin-9kxYnPKa.js.map
