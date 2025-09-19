export type MiddlewareKey = "admin" | "auth-admin" | "auth" | "role-control" | "student"
declare module 'nitropack' {
  interface NitroRouteConfig {
    appMiddleware?: MiddlewareKey | MiddlewareKey[] | Record<MiddlewareKey, boolean>
  }
}