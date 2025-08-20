import { h as defineNuxtRouteMiddleware, n as navigateTo } from "../server.mjs";
import { u as useAuthStore } from "./auth-yP0r1OGC.js";
import "vue";
import "ofetch";
import "#internal/nuxt/paths";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/hookable/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/unctx/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/h3/dist/index.mjs";
import "vue-router";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/radix3/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/defu/dist/defu.mjs";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/ufo/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/klona/dist/index.mjs";
import "vue/server-renderer";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/@unhead/vue/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/nuxt/node_modules/cookie-es/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/destr/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/copilot/frontend/node_modules/ohash/dist/index.mjs";
import "./ssr-B4FXEZKR.js";
const auth = defineNuxtRouteMiddleware((to) => {
  const authStore = useAuthStore();
  if (!authStore.isAuthenticated) {
    return navigateTo("/login");
  }
});
export {
  auth as default
};
//# sourceMappingURL=auth-D2d7LTht.js.map
