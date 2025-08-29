import { test, expect } from '@playwright/test'

test.describe('Page d\'accueil', () => {
    test('affiche correctement la page d\'accueil', async ({ page }) => {
        await page.goto('/')

        // Vérifier le titre de la page
        await expect(page).toHaveTitle(/BookYourCoach/)

        // Vérifier le titre principal (i18n)
        await expect(page.getByRole('heading', { level: 1 })).toBeVisible()

        // Vérifier la présence des boutons d'action (i18n)
        await expect(page.getByRole('link', { name: /Commencer l'aventure|Start your journey/i })).toBeVisible()
        await expect(page.getByRole('link', { name: /Découvrir les coaches|Discover coaches/i })).toBeVisible()
    })

    test('navigation vers la page d\'inscription', async ({ page }) => {
        await page.goto('/')

        // Cliquer sur le CTA d'inscription
        await page.getByRole('link', { name: /S'inscrire Gratuitement|Sign up for free/i }).click()

        // Vérifier la redirection vers la page d'inscription
        await expect(page).toHaveURL(/\/register/)
    })

    test('affiche les statistiques de la plateforme', async ({ page }) => {
        await page.goto('/')

        // Vérifier que la section statistiques est visible
        await expect(page.locator('[data-testid="home-stats"]')).toBeVisible()
    })

    test('affiche les sections principales', async ({ page }) => {
        await page.goto('/')

        // Section des fonctionnalités
        await expect(page.locator('[data-testid="home-features"]')).toBeVisible()
        // Section CTA
        await expect(page.locator('[data-testid="home-cta"]')).toBeVisible()
    })
})
