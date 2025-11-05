import { test, expect } from '@playwright/test'

test.describe('Navigation et layout', () => {
    test('affiche la navigation principale', async ({ page }) => {
        await page.goto('/', { waitUntil: 'networkidle' })

        // Vérifier la présence du logo (premier élément avec "activibe")
        await expect(page.locator('a[href="/"]').first()).toBeVisible()

        // Vérifier les liens de navigation pour utilisateur non connecté
        await expect(page.locator('text=Se connecter')).toBeVisible()
        await expect(page.locator('text=S\'inscrire')).toBeVisible()
    })

    test('navigation vers la page de connexion', async ({ page }) => {
        await page.goto('/', { waitUntil: 'networkidle' })

        await page.click('text=Se connecter')
        await expect(page).toHaveURL(/\/login/)
        await page.waitForLoadState('networkidle')
        await expect(page.locator('text=Se connecter à votre compte')).toBeVisible()
    })

    test('navigation vers la page d\'inscription', async ({ page }) => {
        await page.goto('/', { waitUntil: 'networkidle' })

        await page.click('text=S\'inscrire')
        await expect(page).toHaveURL(/\/register/)
        await page.waitForLoadState('networkidle')
    })

    test('affiche le footer', async ({ page }) => {
        await page.goto('/', { waitUntil: 'networkidle' })

        // Vérifier la présence du footer (premier élément footer)
        await expect(page.locator('footer').first()).toBeVisible()
        await expect(page.locator('text=© 2025').first()).toBeVisible()
    })

    test('est responsive sur mobile', async ({ page }) => {
        // Tester la version mobile
        await page.setViewportSize({ width: 375, height: 667 })
        await page.goto('/', { waitUntil: 'networkidle' })

        // Vérifier que la page se charge correctement
        await expect(page.locator('h1')).toBeVisible()
        await expect(page.locator('a[href="/"]').first()).toBeVisible()
    })

    test('est responsive sur tablette', async ({ page }) => {
        // Tester la version tablette
        await page.setViewportSize({ width: 768, height: 1024 })
        await page.goto('/', { waitUntil: 'networkidle' })

        // Vérifier que la page se charge correctement
        await expect(page.locator('h1')).toBeVisible()
        // Vérifier un élément spécifique du CTA
        await expect(page.locator('h1, h2').first()).toBeVisible()
    })
})
