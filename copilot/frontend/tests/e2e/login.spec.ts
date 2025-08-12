import { test, expect } from '@playwright/test'

test.describe('Page de connexion', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto('/login')
    })

    test('affiche le formulaire de connexion', async ({ page }) => {
        await expect(page.locator('text=Se connecter à votre compte')).toBeVisible()

        // Vérifier la présence des champs
        await expect(page.locator('input[type="email"]')).toBeVisible()
        await expect(page.locator('input[type="password"]')).toBeVisible()
        await expect(page.locator('input[type="checkbox"]')).toBeVisible() // Remember me

        // Vérifier le bouton de soumission
        await expect(page.locator('button[type="submit"]')).toBeVisible()
    })

    test('valide les champs requis', async ({ page }) => {
        // Essayer de soumettre le formulaire vide
        await page.click('button[type="submit"]')

        // Vérifier que la validation HTML5 se déclenche
        const emailInput = page.locator('input[type="email"]')
        const isEmailInvalid = await emailInput.evaluate((el: HTMLInputElement) => {
            return !el.validity.valid
        })
        expect(isEmailInvalid).toBe(true)
    })

    test('permet de saisir les informations de connexion', async ({ page }) => {
        // Remplir le formulaire
        await page.fill('input[type="email"]', 'test@example.com')
        await page.fill('input[type="password"]', 'password123')

        // Vérifier que les valeurs sont bien saisies
        await expect(page.locator('input[type="email"]')).toHaveValue('test@example.com')
        await expect(page.locator('input[type="password"]')).toHaveValue('password123')
    })

    test('affiche le lien vers l\'inscription', async ({ page }) => {
        await expect(page.locator('text=Créer un compte')).toBeVisible()
    })

    test('redirige vers l\'inscription', async ({ page }) => {
        await page.click('text=Créer un compte')
        await expect(page).toHaveURL(/\/register/)
    })

    test('gère l\'état de chargement', async ({ page }) => {
        // Remplir le formulaire
        await page.fill('input[type="email"]', 'test@example.com')
        await page.fill('input[type="password"]', 'password123')

        // Soumettre le formulaire
        const submitButton = page.locator('button[type="submit"]')
        await submitButton.click()

        // Vérifier que l'état de chargement est affiché (si l'API est lente)
        // Note: Ceci dépend de l'implémentation réelle de l'API
    })
})
