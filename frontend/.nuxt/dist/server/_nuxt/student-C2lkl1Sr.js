import { l as defineNuxtRouteMiddleware, a as useAuthStore, g as createError } from "../server.mjs";
import "vue";
import "ofetch";
import "#internal/nuxt/paths";
import "/workspace/frontend/node_modules/hookable/dist/index.mjs";
import "/workspace/frontend/node_modules/unctx/dist/index.mjs";
import "/workspace/frontend/node_modules/h3/dist/index.mjs";
import "vue-router";
import "/workspace/frontend/node_modules/radix3/dist/index.mjs";
import "/workspace/frontend/node_modules/defu/dist/defu.mjs";
import "/workspace/frontend/node_modules/ufo/dist/index.mjs";
import "/workspace/frontend/node_modules/nuxt/node_modules/cookie-es/dist/index.mjs";
import "/workspace/frontend/node_modules/destr/dist/index.mjs";
import "/workspace/frontend/node_modules/ohash/dist/index.mjs";
import "/workspace/frontend/node_modules/klona/dist/index.mjs";
import "vue/server-renderer";
import "/workspace/frontend/node_modules/@unhead/vue/dist/index.mjs";
const student = defineNuxtRouteMiddleware((to) => {
  const authStore = useAuthStore();
  if (!authStore.isAuthenticated) {
    throw createError({
      statusCode: 401,
      statusMessage: "Authentification requise"
    });
  }
  if (!authStore.canActAsStudent) {
    console.warn(`Tentative d'accès non autorisé à ${to.path} - Capacité étudiant requise`);
    throw createError({
      statusCode: 403,
      statusMessage: "Accès réservé aux étudiants"
    });
  }
});
export {
  student as default
};
//# sourceMappingURL=student-C2lkl1Sr.js.map
