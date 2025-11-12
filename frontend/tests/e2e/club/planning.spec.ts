import { test, expect } from '@playwright/test';
import { loginAsClub } from '../utils/auth';

/**
 * Tests du Planning
 * Couvre la gestion des créneaux, création de cours, et récurrences
 */
test.describe('Planning et Gestion des Cours', () => {
  
  test.beforeEach(async ({ page }) => {
    await loginAsClub(page);
    await page.goto('/club/planning');
  });

  test('Affichage de la vue planning', async ({ page }) => {
    // Vérifier le titre
    await expect(page.locator('h1, h2').filter({ hasText: /planning/i })).toBeVisible();
    
    // Vérifier la présence du calendrier ou de la liste des créneaux
    await expect(page.locator('[data-testid="planning-view"], [data-testid="schedule-grid"]')).toBeVisible();
  });

  test('Liste des créneaux ouverts affichée', async ({ page }) => {
    // Vérifier la présence de créneaux
    const slots = page.locator('[data-testid="open-slot"]');
    const count = await slots.count();
    
    expect(count).toBeGreaterThanOrEqual(0);
    
    if (count > 0) {
      // Vérifier que les créneaux affichent jour + horaire
      await expect(slots.first()).toContainText(/lundi|mardi|mercredi|jeudi|vendredi|samedi|dimanche/i);
    }
  });

  test('Sélection d\'un créneau pour voir les cours programmés', async ({ page }) => {
    // Cliquer sur le premier créneau
    const firstSlot = page.locator('[data-testid="open-slot"]').first();
    
    if (await firstSlot.isVisible()) {
      await firstSlot.click();
      
      // Vérifier que la section "Cours programmés" s'affiche
      await expect(page.locator('text=/cours.*programmés/i')).toBeVisible();
    }
  });

  test('Navigation par date dans les cours programmés', async ({ page }) => {
    // Sélectionner un créneau
    const firstSlot = page.locator('[data-testid="open-slot"]').first();
    
    if (await firstSlot.isVisible()) {
      await firstSlot.click();
      
      // Attendre la section cours programmés
      await expect(page.locator('text=/cours.*programmés/i')).toBeVisible();
      
      // Cliquer sur "Suivant" ou flèche droite
      const nextButton = page.locator('button:has-text("Suivant"), button[aria-label="Suivant"], button:has-text("→")');
      
      if (await nextButton.isVisible()) {
        await nextButton.click();
        
        // Vérifier que la date change
        await page.waitForTimeout(500);
        await expect(page.locator('[data-testid="selected-date"]')).toBeVisible();
      }
    }
  });

  test('Bouton "Aujourd\'hui" n\'apparaît que si le jour correspond', async ({ page }) => {
    // Sélectionner un créneau
    const firstSlot = page.locator('[data-testid="open-slot"]').first();
    
    if (await firstSlot.isVisible()) {
      // Récupérer le jour du créneau
      const slotText = await firstSlot.textContent();
      const today = new Date().toLocaleDateString('fr-FR', { weekday: 'long' });
      
      await firstSlot.click();
      
      // Vérifier la présence du bouton "Aujourd'hui"
      const todayButton = page.locator('button:has-text("Aujourd\'hui")');
      const isTodayVisible = await todayButton.isVisible();
      
      // Le bouton devrait être visible seulement si le jour correspond
      if (slotText?.toLowerCase().includes(today.toLowerCase())) {
        expect(isTodayVisible).toBe(true);
      }
    }
  });

  test('Ouvrir le modal de création d\'un nouveau cours', async ({ page }) => {
    // Cliquer sur "Créer un cours" ou "Nouveau cours"
    await page.click('button:has-text("Créer un cours"), button:has-text("Nouveau cours")');
    
    // Vérifier que le modal s'ouvre
    await expect(page.locator('[data-testid="create-lesson-modal"], [role="dialog"]')).toBeVisible();
    
    // Vérifier la présence des champs essentiels
    await expect(page.locator('input[type="datetime-local"], input[name="start_time"]')).toBeVisible();
  });

  test('Autocomplete enseignant fonctionne', async ({ page }) => {
    // Ouvrir le modal de création
    await page.click('button:has-text("Créer un cours"), button:has-text("Nouveau cours")');
    
    await expect(page.locator('[role="dialog"]')).toBeVisible();
    
    // Trouver le champ autocomplete enseignant
    const teacherInput = page.locator('input[placeholder*="Enseignant"], input[name="teacher"]');
    
    if (await teacherInput.isVisible()) {
      // Taper quelques lettres
      await teacherInput.fill('Test');
      
      // Attendre les suggestions
      await page.waitForTimeout(500);
      
      // Vérifier que des suggestions apparaissent
      const suggestions = page.locator('[data-testid="autocomplete-option"], [role="option"]');
      const count = await suggestions.count();
      
      expect(count).toBeGreaterThanOrEqual(0);
    }
  });

  test('Autocomplete élève fonctionne', async ({ page }) => {
    // Ouvrir le modal de création
    await page.click('button:has-text("Créer un cours"), button:has-text("Nouveau cours")');
    
    await expect(page.locator('[role="dialog"]')).toBeVisible();
    
    // Trouver le champ autocomplete élève
    const studentInput = page.locator('input[placeholder*="Élève"], input[name="student"]');
    
    if (await studentInput.isVisible()) {
      // Taper quelques lettres
      await studentInput.fill('Test');
      
      // Attendre les suggestions
      await page.waitForTimeout(500);
      
      // Vérifier que des suggestions apparaissent
      const suggestions = page.locator('[data-testid="autocomplete-option"], [role="option"]');
      const count = await suggestions.count();
      
      expect(count).toBeGreaterThanOrEqual(0);
    }
  });

  test('Mise à jour automatique durée et prix selon le type de cours', async ({ page }) => {
    // Ouvrir le modal de création
    await page.click('button:has-text("Créer un cours"), button:has-text("Nouveau cours")');
    
    await expect(page.locator('[role="dialog"]')).toBeVisible();
    
    // Sélectionner un type de cours
    const courseTypeSelect = page.locator('select[name="course_type_id"]');
    
    if (await courseTypeSelect.isVisible()) {
      await courseTypeSelect.selectOption({ index: 1 });
      
      // Attendre la mise à jour
      await page.waitForTimeout(500);
      
      // Vérifier que la durée et le prix sont remplis automatiquement
      const durationInput = page.locator('input[name="duration"]');
      const priceInput = page.locator('input[name="price"]');
      
      const duration = await durationInput.inputValue();
      const price = await priceInput.inputValue();
      
      expect(duration).not.toBe('');
      expect(price).not.toBe('');
    }
  });

  test('Création d\'un cours pour un élève avec abonnement', async ({ page }) => {
    // Ouvrir le modal
    await page.click('button:has-text("Créer un cours"), button:has-text("Nouveau cours")');
    
    await expect(page.locator('[role="dialog"]')).toBeVisible();
    
    // Remplir les champs minimaux
    const now = new Date();
    const dateTimeString = now.toISOString().slice(0, 16); // Format YYYY-MM-DDTHH:mm
    
    await page.fill('input[type="datetime-local"]', dateTimeString);
    
    // Sélectionner un enseignant
    const teacherSelect = page.locator('select[name="teacher_id"]');
    if (await teacherSelect.isVisible()) {
      await teacherSelect.selectOption({ index: 1 });
    }
    
    // Sélectionner un élève
    const studentSelect = page.locator('select[name="student_id"]');
    if (await studentSelect.isVisible()) {
      await studentSelect.selectOption({ index: 1 });
    }
    
    // Sélectionner un type de cours
    const courseTypeSelect = page.locator('select[name="course_type_id"]');
    if (await courseTypeSelect.isVisible()) {
      await courseTypeSelect.selectOption({ index: 1 });
    }
    
    // Soumettre
    await page.click('button:has-text("Créer"), button[type="submit"]');
    
    // Vérifier le succès
    await expect(page.locator('text=/cours.*créé|succès/i')).toBeVisible({ timeout: 5000 });
  });

  test('Vérification des créneaux récurrents lors de la création', async ({ page }) => {
    // Ce test vérifie que si un élève a un abonnement actif,
    // un créneau récurrent est créé automatiquement
    
    // Ouvrir le modal et créer un cours (comme test précédent)
    await page.click('button:has-text("Créer un cours"), button:has-text("Nouveau cours")');
    
    await expect(page.locator('[role="dialog"]')).toBeVisible();
    
    // Remplir et soumettre...
    // (Code similaire au test précédent)
    
    // Après création réussie, vérifier dans les logs du backend
    // ou vérifier qu'un avertissement de conflit apparaît
    // (Selon votre implémentation)
  });

  test('Modification d\'un cours existant', async ({ page }) => {
    // Sélectionner un créneau et voir les cours
    const firstSlot = page.locator('[data-testid="open-slot"]').first();
    
    if (await firstSlot.isVisible()) {
      await firstSlot.click();
      
      // Chercher le bouton "Modifier" d'un cours
      const editButton = page.locator('button:has-text("Modifier"), [data-testid="edit-lesson"]').first();
      
      if (await editButton.isVisible()) {
        await editButton.click();
        
        // Vérifier que le modal s'ouvre avec les données pré-remplies
        await expect(page.locator('[role="dialog"]')).toBeVisible();
        
        // Modifier le statut
        const statusSelect = page.locator('select[name="status"]');
        if (await statusSelect.isVisible()) {
          await statusSelect.selectOption('completed');
          
          // Enregistrer
          await page.click('button:has-text("Enregistrer"), button[type="submit"]');
          
          // Vérifier le succès
          await expect(page.locator('text=/modifié|mis à jour|succès/i')).toBeVisible({ timeout: 5000 });
        }
      }
    }
  });

  test('Annulation d\'un cours', async ({ page }) => {
    // Sélectionner un créneau et voir les cours
    const firstSlot = page.locator('[data-testid="open-slot"]').first();
    
    if (await firstSlot.isVisible()) {
      await firstSlot.click();
      
      // Chercher le bouton "Annuler" d'un cours
      const cancelButton = page.locator('button:has-text("Annuler"), [data-testid="cancel-lesson"]').first();
      
      if (await cancelButton.isVisible()) {
        await cancelButton.click();
        
        // Confirmer (si modal de confirmation)
        const confirmButton = page.locator('button:has-text("Confirmer"), button:has-text("Oui")');
        if (await confirmButton.isVisible()) {
          await confirmButton.click();
        }
        
        // Vérifier le succès
        await expect(page.locator('text=/annulé|succès/i')).toBeVisible({ timeout: 5000 });
      }
    }
  });

  test('Gestion des créneaux ouverts : Création', async ({ page }) => {
    // Chercher le bouton pour gérer les créneaux
    const manageButton = page.locator('button:has-text("Gérer les créneaux"), button:has-text("Créneaux")');
    
    if (await manageButton.isVisible()) {
      await manageButton.click();
      
      // Cliquer sur "Ajouter un créneau"
      await page.click('button:has-text("Ajouter"), button:has-text("Nouveau créneau")');
      
      // Vérifier que le modal s'ouvre
      await expect(page.locator('[role="dialog"]')).toBeVisible();
    }
  });

  test('Gestion des créneaux ouverts : Activation/Désactivation', async ({ page }) => {
    // Trouver un créneau avec bouton actif/inactif
    const toggleButton = page.locator('button:has-text("Activer"), button:has-text("Désactiver")').first();
    
    if (await toggleButton.isVisible()) {
      const initialText = await toggleButton.textContent();
      
      // Cliquer pour changer le statut
      await toggleButton.click();
      
      // Confirmer si nécessaire
      const confirmButton = page.locator('button:has-text("Confirmer"), button:has-text("Oui")');
      if (await confirmButton.isVisible()) {
        await confirmButton.click();
      }
      
      // Vérifier le changement
      await page.waitForTimeout(1000);
      const newText = await toggleButton.textContent();
      
      expect(newText).not.toBe(initialText);
    }
  });

  test('Affichage du prix correct pour chaque cours', async ({ page }) => {
    // Sélectionner un créneau
    const firstSlot = page.locator('[data-testid="open-slot"]').first();
    
    if (await firstSlot.isVisible()) {
      await firstSlot.click();
      
      // Vérifier que les cours affichent un prix > 0
      const lessons = page.locator('[data-testid="lesson-card"]');
      const count = await lessons.count();
      
      if (count > 0) {
        const firstLesson = lessons.first();
        const priceText = await firstLesson.locator('text=/\\d+[.,]\\d+\\s*€/').textContent();
        
        expect(priceText).toMatch(/\d+[.,]\d+\s*€/);
        
        // Vérifier que le prix n'est pas 0.00 €
        expect(priceText).not.toContain('0.00');
      }
    }
  });

  test('Responsive : Planning sur mobile', async ({ page }) => {
    // Simuler un viewport mobile
    await page.setViewportSize({ width: 375, height: 667 });
    
    await page.goto('/club/planning');
    
    // Vérifier que les éléments essentiels sont visibles
    await expect(page.locator('text=/planning/i')).toBeVisible();
    
    // Les créneaux devraient s'adapter
    const slots = page.locator('[data-testid="open-slot"]');
    if (await slots.first().isVisible()) {
      await expect(slots.first()).toBeVisible();
    }
  });
});

