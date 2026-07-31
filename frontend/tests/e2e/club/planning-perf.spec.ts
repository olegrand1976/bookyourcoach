import { test, expect } from '@playwright/test'
import { loginAsClub } from '../utils/auth'

/**
 * Anti-régression perf planning club :
 * - GET /lessons?context=planning au chargement
 * - pas de GET /club/students ni /club/teachers au cold start
 * - grille visible (data-testid)
 */
test.describe('Planning club — perf & régression réseau', () => {
  test('cold start : context=planning, pas de students/teachers', async ({ page }) => {
    const lessonsUrls: string[] = []
    const studentsUrls: string[] = []
    const teachersUrls: string[] = []

    page.on('request', (req) => {
      const url = req.url()
      if (!/\/api\//.test(url) && !/lessons|students|teachers/.test(url)) {
        // encore matcher les paths relatifs via base
      }
      if (url.includes('/lessons') && !url.includes('pending-certificates') && req.method() === 'GET') {
        lessonsUrls.push(url)
      }
      if (url.includes('/club/students') && req.method() === 'GET') {
        studentsUrls.push(url)
      }
      if (url.includes('/club/teachers') && req.method() === 'GET') {
        teachersUrls.push(url)
      }
    })

    await loginAsClub(page)

    // Reset counters after login (dashboard peut appeler d'autres APIs)
    lessonsUrls.length = 0
    studentsUrls.length = 0
    teachersUrls.length = 0

    await page.goto('/club/planning', { waitUntil: 'networkidle' })
    await expect(page.getByTestId('planning-view')).toBeVisible({ timeout: 30000 })
    await expect(page.getByRole('heading', { name: /planning/i })).toBeVisible()

    // Attendre un peu que le Promise.all du mount se termine
    await page.waitForTimeout(1500)

    const planningLessons = lessonsUrls.filter((u) => u.includes('context=planning') || u.includes('context%3Dplanning'))
    expect(
      planningLessons.length,
      `Attendu GET /lessons?context=planning, reçu: ${JSON.stringify(lessonsUrls)}`,
    ).toBeGreaterThan(0)

    expect(
      studentsUrls.length,
      `GET /club/students ne doit pas partir au cold start, reçu: ${JSON.stringify(studentsUrls)}`,
    ).toBe(0)

    expect(
      teachersUrls.length,
      `GET /club/teachers ne doit pas partir au cold start, reçu: ${JSON.stringify(teachersUrls)}`,
    ).toBe(0)
  })

  test('sélection créneau affiche la section cours programmés', async ({ page }) => {
    await loginAsClub(page)
    await page.goto('/club/planning', { waitUntil: 'networkidle' })
    await expect(page.getByTestId('planning-view')).toBeVisible({ timeout: 30000 })

    const slot = page.getByTestId('open-slot').first()
    if (await slot.count() === 0) {
      test.skip(true, 'Aucun créneau ouvert en environnement de test')
      return
    }

    await slot.click()
    await expect(page.getByTestId('scheduled-lessons-section')).toBeVisible()
    await expect(page.getByText(/cours programmés/i)).toBeVisible()
  })

  test('ouverture créer un cours déclenche le lazy-load teachers/students', async ({ page }) => {
    const studentsUrls: string[] = []
    const teachersUrls: string[] = []

    page.on('request', (req) => {
      const url = req.url()
      if (url.includes('/club/students') && req.method() === 'GET') studentsUrls.push(url)
      if (url.includes('/club/teachers') && req.method() === 'GET') teachersUrls.push(url)
    })

    await loginAsClub(page)
    await page.goto('/club/planning', { waitUntil: 'networkidle' })
    await expect(page.getByTestId('planning-view')).toBeVisible({ timeout: 30000 })

    studentsUrls.length = 0
    teachersUrls.length = 0

    const slot = page.getByTestId('open-slot').first()
    if (await slot.count() === 0) {
      test.skip(true, 'Aucun créneau ouvert en environnement de test')
      return
    }
    await slot.click()

    const createBtn = page.getByRole('button', { name: /créer un cours/i })
    await expect(createBtn).toBeVisible({ timeout: 10000 })
    await createBtn.click()

    await expect(page.locator('[role="dialog"], [data-testid="create-lesson-modal"]').first()).toBeVisible({
      timeout: 15000,
    })

    await page.waitForTimeout(2000)

    expect(
      teachersUrls.length + studentsUrls.length,
      `Lazy teachers/students attendu à l'ouverture modale. teachers=${JSON.stringify(teachersUrls)} students=${JSON.stringify(studentsUrls)}`,
    ).toBeGreaterThan(0)
  })
})
