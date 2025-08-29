import { test, expect } from '@playwright/test'

test.describe('Navigation et layout', () => {
    test('affiche la navigation principale', async ({ page }) => {
        await page.goto('/')

        // Vérifier la présence du logo/nav
        await expect(page.locator('[data-testid="nav"]')).toBeVisible()
        await expect(page.locator('[data-testid="logo"]')).toBeVisible()

        // Vérifier les liens de navigation pour utilisateur non connecté
        await expect(page.locator('[data-testid="login-link"]')).toBeVisible()
        await expect(page.locator('[data-testid="register-link"]')).toBeVisible()
    })

    test('navigation vers la page de connexion', async ({ page }) => {
        await page.goto('/')

        await page.locator('[data-testid="login-link"]').click()
        await page.waitForURL(/\/login/)
        await expect(page.locator('[data-testid="login-form"]')).toBeVisible()
    })

    test('navigation vers la page d\'inscription', async ({ page }) => {
        await page.goto('/')

        await page.locator('[data-testid="register-link"]').click()
        await page.waitForURL(/\/register/)
        await expect(page).toHaveURL(/\/register/)
    })

    test('affiche le footer', async ({ page }) => {
        await page.goto('/')

        await expect(page.locator('[data-testid="footer"]')).toBeVisible()
    })

    test('est responsive sur mobile', async ({ page }) => {
        // Tester la version mobile
        await page.setViewportSize({ width: 375, height: 667 })
        await page.goto('/')

        // Vérifier que la page se charge correctement
        await expect(page.getByRole('heading', { level: 1 })).toBeVisible()
    })

    test('est responsive sur tablette', async ({ page }) => {
        // Tester la version tablette
        await page.setViewportSize({ width: 768, height: 1024 })
        await page.goto('/')

        // Vérifier que la page se charge correctement
        await expect(page.getByRole('heading', { level: 1 })).toBeVisible()
    })
})
