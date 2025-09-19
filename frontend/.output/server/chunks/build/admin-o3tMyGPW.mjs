import { x as executeAsync } from '../nitro/nitro.mjs';
import { l as defineNuxtRouteMiddleware, a as useAuthStore, n as navigateTo } from './server.mjs';
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

export { admin as default };
//# sourceMappingURL=admin-o3tMyGPW.mjs.map
