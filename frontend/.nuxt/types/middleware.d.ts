import type { NavigationGuard } from 'vue-router'
export type MiddlewareKey = "admin" | "auth-admin" | "auth" | "role-control" | "student"
declare module 'nuxt/app' {
  interface PageMeta {
    middleware?: MiddlewareKey | NavigationGuard | Array<MiddlewareKey | NavigationGuard>
  }
}