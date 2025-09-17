import { l as defineNuxtRouteMiddleware, a as useAuthStore, g as createError } from './server.mjs';
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

const student = defineNuxtRouteMiddleware((to) => {
  const authStore = useAuthStore();
  if (!authStore.isAuthenticated) {
    throw createError({
      statusCode: 401,
      statusMessage: "Authentification requise"
    });
  }
  if (!authStore.canActAsStudent) {
    console.warn(`Tentative d'acc\xE8s non autoris\xE9 \xE0 ${to.path} - Capacit\xE9 \xE9tudiant requise`);
    throw createError({
      statusCode: 403,
      statusMessage: "Acc\xE8s r\xE9serv\xE9 aux \xE9tudiants"
    });
  }
});

export { student as default };
//# sourceMappingURL=student-C8RcdKBU.mjs.map
