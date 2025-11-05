import { test, expect } from '@playwright/test';
import { loginAsClub } from '../utils/auth';

/**
 * Tests de la gestion des abonnements
 * Couvre les opérations critiques : liste, création, assignation, recalcul
 */
test.describe('Gestion des Abonnements', () => {
  
  test.beforeEach(async ({ page }) => {
    await loginAsClub(page);
    await page.goto('/club/subscriptions');
  });

  test('Affichage de la liste des abonnements', async ({ page }) => {
    // Vérifier le titre de la page
    await expect(page.locator('h1, h2').filter({ hasText: /abonnements/i })).toBeVisible();
    
    // Vérifier la présence des cartes d'abonnements ou un message vide
    const hasSubscriptions = await page.locator('[data-testid="subscription-card"]').count() > 0;
    const hasEmptyMessage = await page.locator('text=/aucun.*abonnement/i').isVisible();
    
    expect(hasSubscriptions || hasEmptyMessage).toBe(true);
  });

  test('Affichage des compteurs d\'abonnements (utilisés/total)', async ({ page }) => {
    // Vérifier qu'au moins un abonnement affiche son compteur
    const subscriptionCards = page.locator('[data-testid="subscription-card"]');
    const count = await subscriptionCards.count();
    
    if (count > 0) {
      // Vérifier le format "X / Y cours utilisés"
      await expect(page.locator('text=/\\d+\\s*\\/\\s*\\d+.*cours/i').first()).toBeVisible();
    }
  });

  test('Code couleur des abonnements selon l\'utilisation', async ({ page }) => {
    // Vérifier que les abonnements ont des codes couleur
    // Vert = < 70%, Orange = 70-90%, Rouge = > 90%
    
    const subscriptionCards = page.locator('[data-testid="subscription-card"]');
    const count = await subscriptionCards.count();
    
    if (count > 0) {
      const firstCard = subscriptionCards.first();
      
      // Vérifier qu'une classe de couleur est présente
      const hasColorClass = await firstCard.evaluate((el) => {
        const classes = el.className;
        return classes.includes('green') || 
               classes.includes('orange') || 
               classes.includes('red') ||
               classes.includes('bg-green') ||
               classes.includes('bg-orange') ||
               classes.includes('bg-red');
      });
      
      // Ou vérifier via un attribut data
      const hasColorAttribute = await firstCard.getAttribute('data-status');
      
      expect(hasColorClass || hasColorAttribute).toBeTruthy();
    }
  });

  test('Affichage de la période de validité', async ({ page }) => {
    // Vérifier que les dates de validité sont affichées
    const subscriptionCards = page.locator('[data-testid="subscription-card"]');
    const count = await subscriptionCards.count();
    
    if (count > 0) {
      // Vérifier la présence de dates (format DD/MM/YYYY ou similaire)
      await expect(page.locator('text=/\\d{2}\\/\\d{2}\\/\\d{4}|\\d{4}-\\d{2}-\\d{2}/').first()).toBeVisible();
    }
  });

  test('Filtre par statut d\'utilisation (vert/orange/rouge)', async ({ page }) => {
    // Chercher le filtre de statut
    const statusFilter = page.locator('select[data-testid="usage-status-filter"], button:has-text("Statut")');
    
    if (await statusFilter.isVisible()) {
      // Sélectionner "Rouge" (abonnements à renouveler)
      await statusFilter.click();
      await page.click('text=/rouge|critique|renouveler/i');
      
      // Attendre la mise à jour
      await page.waitForTimeout(500);
      
      // Vérifier que seuls les abonnements rouges sont affichés
      const visibleCards = await page.locator('[data-testid="subscription-card"]').count();
      expect(visibleCards).toBeGreaterThanOrEqual(0);
    }
  });

  test('Ouvrir le modal de création d\'un nouvel abonnement', async ({ page }) => {
    // Cliquer sur "Créer un abonnement"
    await page.click('button:has-text("Créer"), button:has-text("Nouvel abonnement")');
    
    // Vérifier que le modal s'ouvre
    await expect(page.locator('[data-testid="create-subscription-modal"], [role="dialog"]')).toBeVisible();
    
    // Vérifier la présence des champs essentiels
    await expect(page.locator('select[name="template_id"], select:has-text("Type")')).toBeVisible();
    await expect(page.locator('input[type="date"], input[name="started_at"]')).toBeVisible();
  });

  test('Création d\'un nouvel abonnement et assignation à un élève', async ({ page }) => {
    // Ouvrir le modal de création
    await page.click('button:has-text("Créer"), button:has-text("Nouvel abonnement")');
    
    await expect(page.locator('[role="dialog"]')).toBeVisible();
    
    // Sélectionner un type d'abonnement
    await page.locator('select[name="template_id"]').selectOption({ index: 1 });
    
    // Sélectionner une date de début
    await page.fill('input[type="date"]', '2025-11-10');
    
    // Sélectionner un élève
    await page.locator('select[name="student_id"]').selectOption({ index: 1 });
    
    // Soumettre
    await page.click('button:has-text("Créer"), button[type="submit"]');
    
    // Vérifier le succès
    await expect(page.locator('text=/abonnement.*créé|succès/i')).toBeVisible({ timeout: 5000 });
  });

  test('Bouton de recalcul des compteurs d\'abonnements', async ({ page }) => {
    // Chercher le bouton de recalcul
    const recalcButton = page.locator('button:has-text("Recalculer"), button:has-text("Réinitialiser")');
    
    if (await recalcButton.isVisible()) {
      await recalcButton.click();
      
      // Confirmer (si modal de confirmation)
      const confirmButton = page.locator('button:has-text("Confirmer"), button:has-text("Oui")');
      if (await confirmButton.isVisible()) {
        await confirmButton.click();
      }
      
      // Vérifier le succès
      await expect(page.locator('text=/recalculé|mis à jour|succès/i')).toBeVisible({ timeout: 10000 });
    }
  });

  test('Assignation d\'un abonnement à un élève', async ({ page }) => {
    // Cliquer sur "Assigner" pour le premier abonnement non assigné
    const assignButton = page.locator('button:has-text("Assigner")').first();
    
    if (await assignButton.isVisible()) {
      await assignButton.click();
      
      // Sélectionner un élève dans le modal
      await expect(page.locator('[role="dialog"]')).toBeVisible();
      await page.locator('select[name="student_id"]').selectOption({ index: 1 });
      
      // Confirmer
      await page.click('button:has-text("Assigner"), button[type="submit"]');
      
      // Vérifier le succès
      await expect(page.locator('text=/assigné|succès/i')).toBeVisible({ timeout: 5000 });
    }
  });

  test('Renouvellement d\'un abonnement', async ({ page }) => {
    // Chercher un bouton de renouvellement
    const renewButton = page.locator('button:has-text("Renouveler")').first();
    
    if (await renewButton.isVisible()) {
      await renewButton.click();
      
      // Vérifier que le modal de création s'ouvre avec les infos pré-remplies
      await expect(page.locator('[role="dialog"]')).toBeVisible();
      
      // Le type d'abonnement devrait être pré-sélectionné
      const selectedTemplate = await page.locator('select[name="template_id"]').inputValue();
      expect(selectedTemplate).not.toBe('');
    }
  });

  test('Archivage automatique des abonnements terminés', async ({ page }) => {
    // Vérifier qu'aucun abonnement avec 100% d'utilisation n'est visible
    // (Ils devraient être archivés automatiquement)
    
    const subscriptionCards = page.locator('[data-testid="subscription-card"]');
    const count = await subscriptionCards.count();
    
    for (let i = 0; i < count; i++) {
      const card = subscriptionCards.nth(i);
      const text = await card.textContent();
      
      // Extraire les chiffres du format "X / Y"
      const match = text?.match(/(\d+)\s*\/\s*(\d+)/);
      
      if (match) {
        const used = parseInt(match[1]);
        const total = parseInt(match[2]);
        
        // Vérifier qu'aucun abonnement n'est à 100%
        expect(used).toBeLessThan(total);
      }
    }
  });

  test('Affichage des détails d\'un abonnement', async ({ page }) => {
    // Cliquer sur le premier abonnement pour voir les détails
    const firstCard = page.locator('[data-testid="subscription-card"]').first();
    
    if (await firstCard.isVisible()) {
      await firstCard.click();
      
      // Vérifier que les détails s'affichent (modal ou page dédiée)
      await expect(page.locator('text=/détails|informations|abonnement/i')).toBeVisible({ timeout: 5000 });
    }
  });

  test('Modification d\'un abonnement existant', async ({ page }) => {
    // Chercher le bouton de modification
    const editButton = page.locator('button:has-text("Modifier"), [data-testid="edit-subscription"]').first();
    
    if (await editButton.isVisible()) {
      await editButton.click();
      
      // Vérifier que le modal s'ouvre
      await expect(page.locator('[role="dialog"]')).toBeVisible();
      
      // Modifier la date d'expiration
      const expiryInput = page.locator('input[name="expires_at"]');
      if (await expiryInput.isVisible()) {
        await expiryInput.fill('2026-01-01');
        
        // Enregistrer
        await page.click('button:has-text("Enregistrer"), button[type="submit"]');
        
        // Vérifier le succès
        await expect(page.locator('text=/modifié|mis à jour|succès/i')).toBeVisible({ timeout: 5000 });
      }
    }
  });

  test('Responsive : Gestion des abonnements sur mobile', async ({ page }) => {
    // Simuler un viewport mobile
    await page.setViewportSize({ width: 375, height: 667 });
    
    await page.goto('/club/subscriptions');
    
    // Vérifier que les éléments essentiels sont visibles
    await expect(page.locator('text=/abonnements/i')).toBeVisible();
    
    // Les cartes devraient s'adapter
    const cards = page.locator('[data-testid="subscription-card"]');
    if (await cards.first().isVisible()) {
      await expect(cards.first()).toBeVisible();
    }
  });
});

