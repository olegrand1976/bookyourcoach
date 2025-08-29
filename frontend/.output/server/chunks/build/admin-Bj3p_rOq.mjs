import { r as executeAsync } from '../nitro/nitro.mjs';
import { k as defineNuxtRouteMiddleware, n as navigateTo, g as createError } from './server.mjs';
import { u as useAuthStore } from './auth-SYtdBTeW.mjs';
import 'node:http';
import 'node:https';
import 'node:events';
import 'node:buffer';
import 'node:fs';
import 'node:path';
import 'node:crypto';
import 'node:url';
import 'vue';
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'vue/server-renderer';
import 'unhead/server';
import 'devalue';
import 'unhead/utils';
import 'unhead/plugins';
import 'vue-router';

const admin = defineNuxtRouteMiddleware(async (to) => {
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
        statusMessage: "Acc\xE8s refus\xE9 - Droits administrateur requis"
      });
    }
  } catch (error) {
    console.error("Erreur v\xE9rification admin:", error);
    if (((_a = error.response) == null ? void 0 : _a.status) === 401) {
      [__temp, __restore] = executeAsync(() => authStore.logout()), await __temp, __restore();
      return navigateTo("/login?expired=true");
    }
    throw createError({
      statusCode: 403,
      statusMessage: "Acc\xE8s refus\xE9 - Droits administrateur requis"
    });
  }
});

export { admin as default };
//# sourceMappingURL=admin-Bj3p_rOq.mjs.map
