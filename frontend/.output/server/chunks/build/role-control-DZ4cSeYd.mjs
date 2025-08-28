import { h as defineNuxtRouteMiddleware, e as createError } from './server.mjs';
import { u as useAuthStore } from './auth-BBLAd2fH.mjs';
import 'vue';
import '../nitro/nitro.mjs';
import 'node:http';
import 'node:https';
import 'node:events';
import 'node:buffer';
import 'node:fs';
import 'node:path';
import 'node:crypto';
import 'node:url';
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'vue/server-renderer';
import 'unhead/server';
import 'devalue';
import 'unhead/utils';
import 'unhead/plugins';
import 'vue-router';
import './ssr-B4FXEZKR.mjs';

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
    console.warn(`Tentative d'acc\xE8s non autoris\xE9 \xE0 ${to.path} - R\xF4le requis: ${requiredRole}, R\xF4le utilisateur: ${(_b = authStore.user) == null ? void 0 : _b.role}`);
    throw createError({
      statusCode: 403,
      statusMessage: "Acc\xE8s non autoris\xE9 pour ce r\xF4le"
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

export { roleControl as default };
//# sourceMappingURL=role-control-DZ4cSeYd.mjs.map
