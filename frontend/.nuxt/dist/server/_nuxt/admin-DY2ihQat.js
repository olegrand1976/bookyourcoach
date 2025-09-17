import { executeAsync } from "/home/olivier/projets/bookyourcoach/frontend/node_modules/unctx/dist/index.mjs";
import { l as defineNuxtRouteMiddleware, a as useAuthStore, n as navigateTo } from "../server.mjs";
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
const admin = defineNuxtRouteMiddleware(async (to, from) => {
  let __temp, __restore;
  const authStore = useAuthStore();
  if (authStore.loading) {
    [__temp, __restore] = executeAsync(() => new Promise((resolve) => {
      const unsubscribe = authStore.$onAction(({ name, after }) => {
        if (name === "initializeAuth") {
          after(() => {
            unsubscribe();
            resolve();
          });
        }
      });
    })), await __temp, __restore();
  }
  if (!authStore.isAuthenticated) {
    return navigateTo("/login");
  }
  if (!authStore.isAdmin) {
    return navigateTo("/");
  }
});
export {
  admin as default
};
//# sourceMappingURL=admin-DY2ihQat.js.map
