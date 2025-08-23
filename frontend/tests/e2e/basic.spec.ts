import { test, expect } from '@playwright/test'

test.describe('Tests E2E - Application Frontend', () => {
    test('page d\'accueil se charge correctement', async ({ page }) => {
        await page.goto('http://localhost:3000')

        // Vérifier que la page se charge
        await expect(page).toHaveTitle(/BookYourCoach/)

        // Vérifier le contenu principal
        const heading = page.locator('h1').first()
        await expect(heading).toBeVisible()
    })

    test('navigation vers les pages principales', async ({ page }) => {
        await page.goto('http://localhost:3000')

        // Vérifier que les liens de navigation existent
        const navigation = page.locator('nav, header')
        await expect(navigation).toBeVisible()
    })

    test('affichage responsive sur mobile', async ({ page }) => {
        // Simuler un viewport mobile
        await page.setViewportSize({ width: 375, height: 667 })
        await page.goto('http://localhost:3000')

        // Vérifier que la page s'affiche correctement
        await expect(page.locator('body')).toBeVisible()
    })

    test('affichage responsive sur tablette', async ({ page }) => {
        // Simuler un viewport tablette
        await page.setViewportSize({ width: 768, height: 1024 })
        await page.goto('http://localhost:3000')

        // Vérifier que la page s'affiche correctement
        await expect(page.locator('body')).toBeVisible()
    })

    test('affichage responsive sur desktop', async ({ page }) => {
        // Simuler un viewport desktop
        await page.setViewportSize({ width: 1920, height: 1080 })
        await page.goto('http://localhost:3000')

        // Vérifier que la page s'affiche correctement
        await expect(page.locator('body')).toBeVisible()
    })
})
