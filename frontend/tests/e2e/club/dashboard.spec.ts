import { test, expect } from '@playwright/test';
import { loginAsClub } from '../utils/auth';

/**
 * Tests du Dashboard Club
 * Vérifie les éléments critiques et les indicateurs du tableau de bord
 */
test.describe('Dashboard Club', () => {
  
  test.beforeEach(async ({ page }) => {
    // Se connecter avant chaque test
    await loginAsClub(page);
  });

  test('Affichage des indicateurs principaux', async ({ page }) => {
    await page.goto('/club/dashboard');
    
    // Vérifier la présence des cartes d'indicateurs
    await expect(page.locator('text=/total.*élèves|élèves.*actifs/i')).toBeVisible();
    await expect(page.locator('text=/abonnements.*actifs/i')).toBeVisible();
    await expect(page.locator('text=/cours.*mois/i')).toBeVisible();
    await expect(page.locator('text=/revenus/i')).toBeVisible();
  });

  test('Section Élèves récents affiche les derniers élèves', async ({ page }) => {
    await page.goto('/club/dashboard');
    
    // Vérifier la présence de la section élèves récents
    const recentStudentsSection = page.locator('text=/élèves.*récents/i');
    await expect(recentStudentsSection).toBeVisible();
    
    // Vérifier qu'il y a au moins un élève ou un message "Aucun élève récent"
    const hasStudents = await page.locator('[data-testid="student-card"]').count() > 0;
    const hasEmptyMessage = await page.locator('text=/aucun.*élève/i').isVisible();
    
    expect(hasStudents || hasEmptyMessage).toBe(true);
  });

  test('Section Élèves avec données incomplètes', async ({ page }) => {
    await page.goto('/club/dashboard');
    
    // Vérifier la présence de la section
    const incompleteDataSection = page.locator('text=/données.*incomplètes/i');
    await expect(incompleteDataSection).toBeVisible();
    
    // Si des élèves avec données incomplètes existent, vérifier qu'on peut les voir
    const incompleteStudentsCount = await page.locator('[data-testid="incomplete-student"]').count();
    
    if (incompleteStudentsCount > 0) {
      // Vérifier qu'on peut voir les informations manquantes
      await expect(page.locator('text=/nom|prénom|email|téléphone/i').first()).toBeVisible();
    }
  });

  test('Clic sur un élève récent redirige vers sa fiche', async ({ page }) => {
    await page.goto('/club/dashboard');
    
    // Chercher le premier élève récent cliquable
    const firstStudent = page.locator('[data-testid="student-card"]').first();
    
    if (await firstStudent.isVisible()) {
      // Cliquer sur l'élève
      await firstStudent.click();
      
      // Devrait être redirigé vers la page de l'élève ou ouvrir un modal
      // (À adapter selon votre implémentation)
      await expect(page).toHaveURL(/\/club\/students\/\d+/);
    }
  });

  test('Navigation vers la liste des élèves depuis le dashboard', async ({ page }) => {
    await page.goto('/club/dashboard');
    
    // Cliquer sur le lien vers la liste des élèves
    await page.click('a:has-text("Voir tous les élèves"), a:has-text("Liste des élèves")');
    
    // Vérifier la redirection
    await expect(page).toHaveURL(/\/club\/students/);
  });

  test('Navigation vers la gestion des abonnements depuis le dashboard', async ({ page }) => {
    await page.goto('/club/dashboard');
    
    // Cliquer sur le lien vers les abonnements
    await page.click('a:has-text("Voir les abonnements"), a:has-text("Gestion des abonnements")');
    
    // Vérifier la redirection
    await expect(page).toHaveURL(/\/club\/subscriptions/);
  });

  test('Navigation vers le planning depuis le dashboard', async ({ page }) => {
    await page.goto('/club/dashboard');
    
    // Cliquer sur le lien vers le planning
    await page.click('a:has-text("Voir le planning"), a:has-text("Planning")');
    
    // Vérifier la redirection
    await expect(page).toHaveURL(/\/club\/planning/);
  });

  test('Rafraîchissement des données du dashboard', async ({ page }) => {
    await page.goto('/club/dashboard');
    
    // Attendre que les données initiales soient chargées
    await expect(page.locator('text=/total.*élèves/i')).toBeVisible();
    
    // Rafraîchir la page
    await page.reload();
    
    // Vérifier que les données sont toujours visibles après rafraîchissement
    await expect(page.locator('text=/total.*élèves/i')).toBeVisible();
    await expect(page.locator('text=/abonnements.*actifs/i')).toBeVisible();
  });

  test('Responsive : Dashboard s\'adapte sur mobile', async ({ page }) => {
    // Simuler un viewport mobile
    await page.setViewportSize({ width: 375, height: 667 });
    
    await page.goto('/club/dashboard');
    
    // Vérifier que les éléments essentiels sont toujours visibles
    await expect(page.locator('text=/dashboard|tableau de bord/i')).toBeVisible();
    await expect(page.locator('text=/élèves/i')).toBeVisible();
  });
});

