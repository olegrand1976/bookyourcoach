import { Page, expect } from '@playwright/test';

/**
 * Identifiants du compte club de test, lus dans l'environnement.
 *
 * Aucune valeur par défaut : un identifiant réel avait été codé en dur ici, dans un
 * dépôt public. Mieux vaut un échec explicite qu'un repli silencieux sur un compte
 * qui pourrait exister en production.
 *
 * Copier frontend/.env.test.example vers frontend/.env.test, puis créer le compte
 * localement : php artisan db:seed --class=E2eClubAccountSeeder
 */
function requireEnv(name: string): string {
  const value = process.env[name];
  if (!value) {
    throw new Error(
      `Variable d'environnement ${name} manquante. ` +
        `Copiez frontend/.env.test.example vers frontend/.env.test et renseignez-la, ` +
        `puis créez le compte avec : php artisan db:seed --class=E2eClubAccountSeeder`
    );
  }
  return value;
}

/**
 * Accesseur paresseux : évalué à l'appel, et non à l'import. Une constante de module
 * ferait échouer la collecte de toute la suite, y compris les specs qui n'ont besoin
 * d'aucun identifiant.
 */
export function clubCredentials(): { email: string; password: string } {
  return {
    email: requireEnv('E2E_CLUB_EMAIL'),
    password: requireEnv('E2E_CLUB_PASSWORD'),
  };
}

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
  const { email, password } = clubCredentials();
  await page.fill('input[type="email"]', email);
  await page.fill('input[type="password"]', password);
  
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

