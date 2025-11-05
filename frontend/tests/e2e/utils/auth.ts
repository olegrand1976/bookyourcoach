import { Page, expect } from '@playwright/test';

/**
 * Credentials de test pour l'authentification
 */
export const TEST_CREDENTIALS = {
  club: {
    email: 'b.murgo1976@gmail.com',
    password: 'password123', // À adapter selon votre environnement de test
  },
  student: {
    email: 'test.student@example.com',
    password: 'password123',
  },
  teacher: {
    email: 'test.teacher@example.com',
    password: 'password123',
  },
};

/**
 * État d'authentification sauvegardé pour réutilisation
 */
export const AUTH_STATE_PATH = 'tests/e2e/.auth/user.json';

/**
 * Se connecter en tant que club
 */
export async function loginAsClub(page: Page) {
  // Naviguer vers la page de login
  await page.goto('/login', { waitUntil: 'networkidle' });
  
  // Attendre que le formulaire soit visible et prêt
  await page.waitForSelector('input[type="email"]', { state: 'visible' });
  await page.waitForSelector('input[type="password"]', { state: 'visible' });
  await page.waitForSelector('button:has-text("Connexion")', { state: 'visible' });
  
  // Remplir le formulaire de connexion
  await page.fill('input[type="email"]', TEST_CREDENTIALS.club.email);
  await page.fill('input[type="password"]', TEST_CREDENTIALS.club.password);
  
  // Cliquer sur le bouton de connexion [[memory:8269929]]
  await page.click('button:has-text("Connexion")');
  
  // Attendre la redirection vers le dashboard (timeout augmenté)
  await page.waitForURL(/\/club\/dashboard/, { timeout: 30000 });
  
  // Attendre que le dashboard soit chargé
  await page.waitForLoadState('networkidle');
  
  // Vérifier que nous sommes bien connectés
  await expect(page).toHaveURL(/\/club\/dashboard/);
}

/**
 * Se déconnecter
 */
export async function logout(page: Page) {
  // Cliquer sur le menu utilisateur (selon votre implémentation)
  await page.click('[data-testid="user-menu"]');
  
  // Cliquer sur déconnexion
  await page.click('button:has-text("Déconnexion")');
  
  // Attendre la redirection vers la page de login
  await page.waitForURL(/\/login/, { timeout: 5000 });
}

/**
 * Vérifier que l'utilisateur est connecté
 */
export async function expectToBeLoggedIn(page: Page) {
  // Vérifier la présence d'éléments typiques d'un utilisateur connecté
  await expect(page.locator('[data-testid="user-menu"]')).toBeVisible();
}

/**
 * Vérifier que l'utilisateur n'est pas connecté
 */
export async function expectToBeLoggedOut(page: Page) {
  // Vérifier que nous sommes sur la page de login
  await expect(page).toHaveURL(/\/login/);
}

/**
 * Se connecter et sauvegarder l'état pour réutilisation
 * Utilisé dans un setup global pour éviter de se reconnecter à chaque test
 */
export async function setupAuthenticatedSession(page: Page) {
  await loginAsClub(page);
  
  // Sauvegarder l'état d'authentification
  await page.context().storageState({ path: AUTH_STATE_PATH });
}

