import { defineConfig, devices } from '@playwright/test';

/**
 * Configuration Playwright pour les tests E2E de BookYourCoach
 * @see https://playwright.dev/docs/test-configuration
 */
export default defineConfig({
  testDir: './tests/e2e',
  
  /* Configuration globale des tests */
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,
  
  /* Reporter pour les résultats de test */
  reporter: [
    ['html', { outputFolder: 'playwright-report' }],
    ['list']
  ],
  
  /* Configuration partagée pour tous les tests */
  use: {
    /* URL de base de l'application */
    baseURL: process.env.PLAYWRIGHT_BASE_URL || 'http://localhost:3000',
    
    /* Collecter les traces en cas d'échec */
    trace: 'on-first-retry',
    
    /* Capturer les screenshots en cas d'échec */
    screenshot: 'only-on-failure',
    
    /* Enregistrer la vidéo en cas d'échec */
    video: 'retain-on-failure',
    
    /* Timeouts augmentés pour environnement Docker */
    actionTimeout: 30000, // 30s au lieu de 10s
    navigationTimeout: 60000, // 60s au lieu de 30s
  },

  /* Configuration des projets de test (navigateurs) */
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },

    // Décommenter pour tester sur d'autres navigateurs
    // {
    //   name: 'firefox',
    //   use: { ...devices['Desktop Firefox'] },
    // },
    // {
    //   name: 'webkit',
    //   use: { ...devices['Desktop Safari'] },
    // },

    /* Tests mobiles */
    // {
    //   name: 'Mobile Chrome',
    //   use: { ...devices['Pixel 5'] },
    // },
    // {
    //   name: 'Mobile Safari',
    //   use: { ...devices['iPhone 12'] },
    // },
  ],

  /* Lancer le serveur de développement avant les tests */
  webServer: {
    command: 'npm run dev',
    url: 'http://localhost:3000',
    reuseExistingServer: !process.env.CI,
    timeout: 120000,
  },
});
