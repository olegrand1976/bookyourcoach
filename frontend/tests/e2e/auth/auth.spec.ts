import { test, expect } from '@playwright/test';
import { loginAsClub, logout, TEST_CREDENTIALS } from '../utils/auth';

/**
 * Tests d'authentification
 * Couvre les scénarios critiques de connexion/déconnexion
 */
test.describe('Authentification', () => {
  
  test.beforeEach(async ({ page }) => {
    // S'assurer que nous sommes déconnectés avant chaque test
    await page.goto('/');
  });

  test('Connexion réussie avec identifiants valides', async ({ page }) => {
    await loginAsClub(page);
    
    // Vérifier que nous sommes bien sur le dashboard
    await expect(page).toHaveURL(/\/club\/dashboard/);
    
    // Vérifier la présence d'éléments clés du dashboard
    await expect(page.locator('h1, h2, h3')).toContainText(/Dashboard|Tableau de bord/i);
  });

  test('Échec de connexion avec mot de passe incorrect', async ({ page }) => {
    await page.goto('/login');
    
    // Remplir avec un mauvais mot de passe
    await page.fill('input[type="email"]', TEST_CREDENTIALS.club.email);
    await page.fill('input[type="password"]', 'mauvais_mot_de_passe');
    
    // Cliquer sur connexion [[memory:8269929]]
    await page.click('button:has-text("Connexion")');
    
    // Vérifier qu'un message d'erreur apparaît
    await expect(page.locator('text=/identifiants.*incorrects|erreur/i')).toBeVisible({ timeout: 5000 });
    
    // Vérifier que nous sommes toujours sur la page de login
    await expect(page).toHaveURL(/\/login/);
  });

  test('Échec de connexion avec email inexistant', async ({ page }) => {
    await page.goto('/login');
    
    // Remplir avec un email inexistant
    await page.fill('input[type="email"]', 'email.inexistant@example.com');
    await page.fill('input[type="password"]', 'password123');
    
    // Cliquer sur connexion [[memory:8269929]]
    await page.click('button:has-text("Connexion")');
    
    // Vérifier qu'un message d'erreur apparaît
    await expect(page.locator('text=/identifiants.*incorrects|erreur|utilisateur.*introuvable/i')).toBeVisible({ timeout: 5000 });
  });

  test('Déconnexion réussie', async ({ page }) => {
    // Se connecter d'abord
    await loginAsClub(page);
    
    // Vérifier que nous sommes bien connectés
    await expect(page).toHaveURL(/\/club\/dashboard/);
    
    // Se déconnecter
    await logout(page);
    
    // Vérifier que nous sommes redirigés vers login
    await expect(page).toHaveURL(/\/login/);
  });

  test('Redirection vers login si non authentifié', async ({ page }) => {
    // Tenter d'accéder à une page protégée sans être connecté
    await page.goto('/club/students');
    
    // Devrait être redirigé vers login
    await expect(page).toHaveURL(/\/login/, { timeout: 10000 });
  });

  test('Validation des champs du formulaire de connexion', async ({ page }) => {
    await page.goto('/login');
    
    // Cliquer sur connexion sans remplir les champs
    await page.click('button:has-text("Connexion")');
    
    // Vérifier que le formulaire ne se soumet pas (HTML5 validation ou message d'erreur)
    // Nous devrions rester sur la page de login
    await expect(page).toHaveURL(/\/login/);
  });
});

