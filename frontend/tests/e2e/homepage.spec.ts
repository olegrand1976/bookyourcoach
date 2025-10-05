import { test, expect } from '@playwright/test'

test.describe('Page d\'accueil', () => {
    test('affiche correctement la page d\'accueil', async ({ page }) => {
        await page.goto('/')

        // Vérifier le titre de la page
        await expect(page).toHaveTitle(/activibe/)

        // Vérifier le titre principal
        await expect(page.locator('h1')).toContainText('Trouvez votre coach parfait')

        // Vérifier la présence des boutons d'action
        await expect(page.locator('text=Commencer maintenant')).toBeVisible()
        await expect(page.locator('text=Découvrir les coaches')).toBeVisible()
    })

    test('navigation vers la page d\'inscription', async ({ page }) => {
        await page.goto('/')

        // Cliquer sur le bouton "Commencer maintenant"
        await page.click('text=Commencer maintenant')

        // Vérifier la redirection vers la page d'inscription
        await expect(page).toHaveURL(/\/register/)
    })

    test('affiche les statistiques de la plateforme', async ({ page }) => {
        await page.goto('/')

        // Vérifier que les statistiques sont visibles
        await expect(page.locator('text=150+')).toBeVisible() // Coaches
        await expect(page.locator('text=2500+')).toBeVisible() // Students
        await expect(page.locator('text=8500+')).toBeVisible() // Lessons
        await expect(page.locator('text=45+')).toBeVisible() // Locations
    })

    test('affiche les sections principales', async ({ page }) => {
        await page.goto('/')

        // Section des fonctionnalités
        await expect(page.locator('text=Pourquoi choisir activibe ?')).toBeVisible()
        await expect(page.locator('text=Coaches certifiés')).toBeVisible()
        await expect(page.locator('text=Réservation facile')).toBeVisible()
        await expect(page.locator('text=Paiement sécurisé')).toBeVisible()

        // Section CTA
        await expect(page.locator('text=Prêt à commencer votre aventure équestre ?')).toBeVisible()
    })
})
