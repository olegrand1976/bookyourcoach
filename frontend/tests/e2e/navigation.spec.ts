import { test, expect } from '@playwright/test'

test.describe('Navigation et layout', () => {
    test('affiche la navigation principale', async ({ page }) => {
        await page.goto('/')

        // Vérifier la présence du logo
        await expect(page.locator('text=activibe')).toBeVisible()

        // Vérifier les liens de navigation pour utilisateur non connecté
        await expect(page.locator('text=Se connecter')).toBeVisible()
        await expect(page.locator('text=S\'inscrire')).toBeVisible()
    })

    test('navigation vers la page de connexion', async ({ page }) => {
        await page.goto('/')

        await page.click('text=Se connecter')
        await expect(page).toHaveURL(/\/login/)
        await expect(page.locator('text=Se connecter à votre compte')).toBeVisible()
    })

    test('navigation vers la page d\'inscription', async ({ page }) => {
        await page.goto('/')

        await page.click('text=S\'inscrire')
        await expect(page).toHaveURL(/\/register/)
    })

    test('affiche le footer', async ({ page }) => {
        await page.goto('/')

        await expect(page.locator('footer')).toBeVisible()
        await expect(page.locator('text=© 2025 activibe. Tous droits réservés.')).toBeVisible()
    })

    test('est responsive sur mobile', async ({ page }) => {
        // Tester la version mobile
        await page.setViewportSize({ width: 375, height: 667 })
        await page.goto('/')

        // Vérifier que la page se charge correctement
        await expect(page.locator('h1')).toBeVisible()
        await expect(page.locator('text=activibe')).toBeVisible()
    })

    test('est responsive sur tablette', async ({ page }) => {
        // Tester la version tablette
        await page.setViewportSize({ width: 768, height: 1024 })
        await page.goto('/')

        // Vérifier que la page se charge correctement
        await expect(page.locator('h1')).toBeVisible()
        await expect(page.locator('text=Commencer maintenant')).toBeVisible()
    })
})
