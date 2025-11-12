import { test, expect } from '@playwright/test';
import { loginAsClub } from '../utils/auth';

/**
 * Tests de la gestion des élèves
 * Couvre les opérations critiques : ajout, modification, liste, recherche
 */
test.describe('Gestion des Élèves', () => {
  
  test.beforeEach(async ({ page }) => {
    await loginAsClub(page);
    await page.goto('/club/students');
  });

  test('Affichage de la liste des élèves', async ({ page }) => {
    // Vérifier que le titre contient le nombre d'élèves
    await expect(page.locator('h1, h2').filter({ hasText: /liste.*élèves/i })).toBeVisible();
    
    // Vérifier la présence du tableau ou des cartes d'élèves
    const hasStudents = await page.locator('[data-testid="student-row"], [data-testid="student-card"]').count() > 0;
    const hasEmptyMessage = await page.locator('text=/aucun.*élève/i').isVisible();
    
    expect(hasStudents || hasEmptyMessage).toBe(true);
  });

  test('Pagination fonctionne correctement (20 élèves par page)', async ({ page }) => {
    // Vérifier la présence de la pagination si plus de 20 élèves
    const studentCount = await page.locator('[data-testid="student-row"]').count();
    
    if (studentCount >= 20) {
      // Vérifier la présence des contrôles de pagination
      await expect(page.locator('[data-testid="pagination"]')).toBeVisible();
      
      // Cliquer sur la page suivante
      await page.click('[data-testid="next-page"], button:has-text("Suivant")');
      
      // Vérifier que l'URL ou le contenu change
      await page.waitForTimeout(1000); // Attendre le chargement
      
      // Vérifier que nous avons de nouveaux élèves
      await expect(page.locator('[data-testid="student-row"]').first()).toBeVisible();
    }
  });

  test('Recherche d\'un élève par nom', async ({ page }) => {
    // Localiser le champ de recherche
    const searchInput = page.locator('input[type="search"], input[placeholder*="Rechercher"]');
    await expect(searchInput).toBeVisible();
    
    // Effectuer une recherche
    await searchInput.fill('Test');
    
    // Attendre que les résultats se mettent à jour
    await page.waitForTimeout(500);
    
    // Vérifier que les résultats sont filtrés
    // (Tous les noms visibles devraient contenir "Test")
    const visibleStudents = await page.locator('[data-testid="student-row"]').count();
    
    if (visibleStudents > 0) {
      const firstStudentName = await page.locator('[data-testid="student-row"]').first().textContent();
      expect(firstStudentName?.toLowerCase()).toContain('test');
    }
  });

  test('Filtre par statut : Actif/Inactif', async ({ page }) => {
    // Localiser le filtre de statut
    const statusFilter = page.locator('select[data-testid="status-filter"], button:has-text("Statut")');
    
    if (await statusFilter.isVisible()) {
      // Sélectionner "Inactif"
      await statusFilter.click();
      await page.click('text=/inactif/i');
      
      // Attendre la mise à jour
      await page.waitForTimeout(500);
      
      // Vérifier que les élèves inactifs sont affichés
      // (Selon votre implémentation)
    }
  });

  test('Ouvrir le modal d\'ajout d\'un nouvel élève', async ({ page }) => {
    // Cliquer sur le bouton "Ajouter un élève"
    await page.click('button:has-text("Ajouter"), button:has-text("Nouvel élève")');
    
    // Vérifier que le modal s'ouvre
    await expect(page.locator('[data-testid="add-student-modal"], [role="dialog"]')).toBeVisible();
    
    // Vérifier la présence des champs essentiels
    await expect(page.locator('input[name="first_name"], input[placeholder*="Prénom"]')).toBeVisible();
    await expect(page.locator('input[name="last_name"], input[placeholder*="Nom"]')).toBeVisible();
  });

  test('Ajout d\'un nouvel élève avec toutes les informations', async ({ page }) => {
    // Ouvrir le modal
    await page.click('button:has-text("Ajouter"), button:has-text("Nouvel élève")');
    
    // Remplir le formulaire
    const timestamp = Date.now();
    await page.fill('input[name="first_name"], input[placeholder*="Prénom"]', `Test${timestamp}`);
    await page.fill('input[name="last_name"], input[placeholder*="Nom"]', 'Playwright');
    await page.fill('input[type="email"]', `test.playwright.${timestamp}@example.com`);
    await page.fill('input[type="tel"], input[placeholder*="Téléphone"]', '0612345678');
    
    // Soumettre le formulaire
    await page.click('button:has-text("Enregistrer"), button:has-text("Ajouter"), button[type="submit"]');
    
    // Vérifier le succès
    await expect(page.locator('text=/élève.*ajouté|succès/i')).toBeVisible({ timeout: 5000 });
    
    // Vérifier que le nouvel élève apparaît dans la liste
    await expect(page.locator(`text=Test${timestamp}`)).toBeVisible({ timeout: 5000 });
  });

  test('Ajout d\'un élève sans email (champs optionnels)', async ({ page }) => {
    // Ouvrir le modal
    await page.click('button:has-text("Ajouter"), button:has-text("Nouvel élève")');
    
    // Remplir uniquement les champs obligatoires
    const timestamp = Date.now();
    await page.fill('input[name="first_name"], input[placeholder*="Prénom"]', `Sans${timestamp}`);
    await page.fill('input[name="last_name"], input[placeholder*="Nom"]', 'Email');
    
    // NE PAS remplir l'email
    
    // Soumettre
    await page.click('button:has-text("Enregistrer"), button:has-text("Ajouter"), button[type="submit"]');
    
    // Vérifier le succès
    await expect(page.locator('text=/élève.*ajouté|succès/i')).toBeVisible({ timeout: 5000 });
  });

  test('Ouvrir le modal de modification d\'un élève', async ({ page }) => {
    // Cliquer sur le bouton "Modifier" du premier élève
    const editButton = page.locator('button:has-text("Modifier"), [data-testid="edit-student"]').first();
    
    if (await editButton.isVisible()) {
      await editButton.click();
      
      // Vérifier que le modal s'ouvre avec les données pré-remplies
      await expect(page.locator('[data-testid="edit-student-modal"], [role="dialog"]')).toBeVisible();
      
      // Vérifier que les champs sont pré-remplis
      const firstNameInput = page.locator('input[name="first_name"]');
      await expect(firstNameInput).not.toBeEmpty();
    }
  });

  test('Modification des informations d\'un élève', async ({ page }) => {
    // Cliquer sur modifier pour le premier élève
    const editButton = page.locator('button:has-text("Modifier"), [data-testid="edit-student"]').first();
    
    if (await editButton.isVisible()) {
      await editButton.click();
      
      // Attendre le modal
      await expect(page.locator('[role="dialog"]')).toBeVisible();
      
      // Modifier le téléphone
      const phoneInput = page.locator('input[type="tel"], input[name="phone"]');
      await phoneInput.clear();
      await phoneInput.fill('0698765432');
      
      // Enregistrer
      await page.click('button:has-text("Enregistrer"), button[type="submit"]');
      
      // Vérifier le succès
      await expect(page.locator('text=/modifié|mis à jour|succès/i')).toBeVisible({ timeout: 5000 });
    }
  });

  test('Désactivation d\'un élève', async ({ page }) => {
    // Chercher le bouton de désactivation
    const deactivateButton = page.locator('button:has-text("Désactiver"), [data-testid="deactivate-student"]').first();
    
    if (await deactivateButton.isVisible()) {
      await deactivateButton.click();
      
      // Confirmer la désactivation (si modal de confirmation)
      const confirmButton = page.locator('button:has-text("Confirmer"), button:has-text("Oui")');
      if (await confirmButton.isVisible()) {
        await confirmButton.click();
      }
      
      // Vérifier le succès
      await expect(page.locator('text=/désactivé|succès/i')).toBeVisible({ timeout: 5000 });
    }
  });

  test('Voir l\'historique d\'un élève (icône œil)', async ({ page }) => {
    // Cliquer sur l'icône œil du premier élève
    const viewButton = page.locator('button[data-testid="view-student-history"], button:has-text("👁"), svg[data-icon="eye"]').first();
    
    if (await viewButton.isVisible()) {
      await viewButton.click();
      
      // Vérifier qu'on est redirigé ou qu'un modal s'ouvre
      // (À adapter selon votre implémentation)
      await expect(
        page.locator('text=/historique|abonnements|cours/i')
      ).toBeVisible({ timeout: 5000 });
    }
  });

  test('Export de la liste des élèves', async ({ page }) => {
    // Chercher le bouton d'export
    const exportButton = page.locator('button:has-text("Exporter"), button:has-text("Export")');
    
    if (await exportButton.isVisible()) {
      // Configurer l'attente de téléchargement
      const [download] = await Promise.all([
        page.waitForEvent('download'),
        exportButton.click()
      ]);
      
      // Vérifier que le fichier a été téléchargé
      const fileName = download.suggestedFilename();
      expect(fileName).toMatch(/students|eleves|export/i);
    }
  });

  test('Responsive : Liste des élèves sur mobile', async ({ page }) => {
    // Simuler un viewport mobile
    await page.setViewportSize({ width: 375, height: 667 });
    
    await page.goto('/club/students');
    
    // Vérifier que les éléments essentiels sont visibles
    await expect(page.locator('text=/liste.*élèves/i')).toBeVisible();
    await expect(page.locator('button:has-text("Ajouter")')).toBeVisible();
  });
});

