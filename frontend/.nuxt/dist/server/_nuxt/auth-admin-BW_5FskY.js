import { executeAsync } from "/home/olivier/projets/bookyourcoach/frontend/node_modules/unctx/dist/index.mjs";
import { l as defineNuxtRouteMiddleware, a as useAuthStore, n as navigateTo, g as createError } from "../server.mjs";
import "vue";
import "ofetch";
import "#internal/nuxt/paths";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/hookable/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/h3/dist/index.mjs";
import "vue-router";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/radix3/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/defu/dist/defu.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/ufo/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/nuxt/node_modules/cookie-es/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/destr/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/ohash/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/klona/dist/index.mjs";
import "vue/server-renderer";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/@unhead/vue/dist/index.mjs";
const authAdmin = defineNuxtRouteMiddleware(async (to) => {
  var _a, _b;
  let __temp, __restore;
  const authStore = useAuthStore();
  if (!authStore.token) {
    return navigateTo("/login");
  }
  try {
    ;
    [__temp, __restore] = executeAsync(() => authStore.fetchUser()), await __temp, __restore();
    ;
    if (!authStore.user || authStore.user.role !== "admin") {
      console.error("Utilisateur non-admin détecté:", (_a = authStore.user) == null ? void 0 : _a.role);
      throw createError({
        statusCode: 403,
        statusMessage: "Accès refusé - Droits administrateur requis"
      });
    }
  } catch (error) {
    console.error("Erreur vérification admin:", error);
    if (((_b = error.response) == null ? void 0 : _b.status) === 401) {
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
//# sourceMappingURL=auth-admin-BW_5FskY.js.map
