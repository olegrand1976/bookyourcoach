// Mock file for #app alias in Vitest
// This file is used by vitest.config.ts to resolve #app imports
export const useNuxtApp = () => {
  throw new Error('useNuxtApp should be mocked in tests/setup.ts')
}

export const NuxtLink = {}

