export type MiddlewareKey = "admin" | "auth-admin" | "auth" | "role-control"
declare module 'nitropack' {
  interface NitroRouteConfig {
    appMiddleware?: MiddlewareKey | MiddlewareKey[] | Record<MiddlewareKey, boolean>
  }
}