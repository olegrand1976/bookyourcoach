import { l as defineNuxtRouteMiddleware, a as useAuthStore, g as createError } from "../server.mjs";
import "vue";
import "ofetch";
import "#internal/nuxt/paths";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/hookable/dist/index.mjs";
import "/home/olivier/projets/bookyourcoach/frontend/node_modules/unctx/dist/index.mjs";
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
const roleControl = defineNuxtRouteMiddleware((to) => {
  var _a, _b, _c;
  const authStore = useAuthStore();
  if (!authStore.isAuthenticated) {
    throw createError({
      statusCode: 401,
      statusMessage: "Authentification requise"
    });
  }
  const requiredRole = to.meta.requiresRole;
  if (requiredRole && ((_a = authStore.user) == null ? void 0 : _a.role) !== requiredRole) {
    console.warn(`Tentative d'accès non autorisé à ${to.path} - Rôle requis: ${requiredRole}, Rôle utilisateur: ${(_b = authStore.user) == null ? void 0 : _b.role}`);
    throw createError({
      statusCode: 403,
      statusMessage: "Accès non autorisé pour ce rôle"
    });
  }
  const requiredPermissions = to.meta.requiresPermissions;
  if (requiredPermissions && requiredPermissions.length > 0) {
    const userPermissions = getUserPermissions((_c = authStore.user) == null ? void 0 : _c.role);
    const hasAllPermissions = requiredPermissions.every(
      (permission) => userPermissions.includes(permission)
    );
    if (!hasAllPermissions) {
      throw createError({
        statusCode: 403,
        statusMessage: "Permissions insuffisantes"
      });
    }
  }
});
function getUserPermissions(role) {
  switch (role) {
    case "admin":
      return ["read", "write", "delete", "manage_users", "manage_settings"];
    case "teacher":
      return ["read", "write", "manage_lessons", "view_students"];
    case "student":
      return ["read", "book_lessons", "view_profile"];
    default:
      return [];
  }
}
export {
  roleControl as default
};
//# sourceMappingURL=role-control-C06R57lR.js.map
