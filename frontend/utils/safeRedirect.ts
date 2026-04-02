/**
 * Reject open redirects: only same-app absolute paths.
 */
export function getSafeRedirectPath(redirectParam: unknown): string | undefined {
  const raw = Array.isArray(redirectParam) ? redirectParam[0] : redirectParam
  if (typeof raw !== 'string') {
    return undefined
  }
  const p = raw.trim()
  if (!p.startsWith('/') || p.startsWith('//')) {
    return undefined
  }
  if (p.includes('://') || p.includes('\0')) {
    return undefined
  }
  return p
}
