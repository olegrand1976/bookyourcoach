import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import PlanningTimeSlotHeader from '~/components/planning/PlanningTimeSlotHeader.vue'
import PlanningSlotAnomaliesDrawer from '~/components/planning/PlanningSlotAnomaliesDrawer.vue'
import PlanningDayAnomalyBar from '~/components/planning/PlanningDayAnomalyBar.vue'
import {
  buildRecurringPlaceholder,
  buildTimeSlotAvailability,
  classifyOccurrence,
  type PlanningAnomaly,
} from '~/composables/planning/usePlanningPlaceholders'

const DATE = '2026-07-01'
const NuxtLink = { template: '<a><slot /></a>' }

const lesson = (id: number) => ({ id, student_id: id, start_time: `${DATE}T10:00:00`, end_time: `${DATE}T11:00:00`, status: 'confirmed', course_type: { name: 'Dressage' } })
const series = (id: number, student_id = 7) => ({
  id, status: 'active', student_id, teacher_id: 3, day_of_week: 3,
  start_time: '10:00:00', end_time: '11:00:00', start_date: '2026-06-03', end_date: '2026-12-02', recurring_interval: 1,
})

const gapPh = buildRecurringPlaceholder(series(42), DATE)
const gapAnomalies = classifyOccurrence(gapPh, null, false)
const studentPh = buildRecurringPlaceholder(series(43, 8), DATE, { id: 9, start_time: `${DATE}T10:00:00`, status: 'cancelled', cancelled_by_role: 'student', cancellation_count_in_subscription: true })
const studentAnomalies = classifyOccurrence(studentPh, { id: 9, start_time: `${DATE}T10:00:00`, status: 'cancelled', cancelled_by_role: 'student', cancellation_count_in_subscription: true }, false)

describe('PlanningTimeSlotHeader', () => {
  const availability = buildTimeSlotAvailability([lesson(1), lesson(2), gapPh], 4)

  it('affiche la jauge de voies, le compteur, et aucune pastille « places libres »', () => {
    const w = mount(PlanningTimeSlotHeader, { props: { time: '16:00', availability, anomalies: gapAnomalies } })
    expect(w.text()).toContain('16:00')
    expect(w.text()).toContain('3/4 voies')
    expect(w.text()).toContain('1 libre')
    const lanes = w.findAll('[data-lane]').map((n) => n.attributes('data-lane'))
    expect(lanes).toEqual(['lesson', 'lesson', 'series', 'free'])
    expect(w.text()).not.toContain('place libre')
  })

  it('condense la jauge au-delà de 4 voies', () => {
    const many = buildTimeSlotAvailability([1, 2, 3, 4, 5].map(lesson), 6)
    const w = mount(PlanningTimeSlotHeader, { props: { time: '16:00', availability: many } })
    expect(w.findAll('[data-lane]')).toHaveLength(0)
    expect(w.find('[data-testid="lane-gauge"]').text()).toBe('5/6')
  })

  it('une puce par famille d\'anomalie, qui bascule le tiroir ; rien sans anomalie', async () => {
    const w = mount(PlanningTimeSlotHeader, { props: { time: '16:00', availability, anomalies: [...gapAnomalies, ...studentAnomalies] } })
    const chips = w.findAll('[data-anomaly-kind]')
    expect(chips.map((c) => c.attributes('data-anomaly-kind'))).toEqual(['generation_gap', 'student_cancellation'])
    expect(chips[0].text()).toContain('1 trou de génération')
    await chips[0].trigger('click')
    expect(w.emitted('toggle-drawer')).toHaveLength(1)

    const none = mount(PlanningTimeSlotHeader, { props: { time: '16:00', availability, anomalies: [] } })
    expect(none.findAll('[data-anomaly-kind]')).toHaveLength(0)
  })

  it('« Créer un cours » émet, et se désactive un jour de fermeture', async () => {
    const w = mount(PlanningTimeSlotHeader, { props: { time: '16:00', availability } })
    await w.find('[data-testid="create-lesson"]').trigger('click')
    expect(w.emitted('create-lesson')).toHaveLength(1)
    const closed = mount(PlanningTimeSlotHeader, { props: { time: '16:00', availability, isClosure: true } })
    expect((closed.find('[data-testid="create-lesson"]').element as HTMLButtonElement).disabled).toBe(true)
  })
})

describe('PlanningSlotAnomaliesDrawer', () => {
  const entries = [
    { placeholder: gapPh, anomalies: gapAnomalies, studentsLabel: 'Emma R.', teacherName: 'C. Martin' },
    { placeholder: studentPh, anomalies: studentAnomalies, studentsLabel: 'Léo D.' },
  ]
  const opts = { global: { stubs: { NuxtLink } } }

  it('caché quand fermé ou vide', () => {
    expect(mount(PlanningSlotAnomaliesDrawer, { props: { open: false, entries }, ...opts }).find('[data-testid="planning-anomalies-drawer"]').exists()).toBe(false)
    expect(mount(PlanningSlotAnomaliesDrawer, { props: { open: true, entries: [] }, ...opts }).find('[data-testid="planning-anomalies-drawer"]').exists()).toBe(false)
  })

  it('une ligne par occurrence, typée, avec horaire, élève, série et description', () => {
    const w = mount(PlanningSlotAnomaliesDrawer, { props: { open: true, entries }, ...opts })
    expect(w.text()).toContain('À traiter sur cette plage (2)')
    const rows = w.findAll('li')
    expect(rows).toHaveLength(2)
    expect(rows[0].attributes('data-anomaly-kind')).toBe('generation_gap')
    expect(rows[0].text()).toContain('10:00–11:00')
    expect(rows[0].text()).toContain('Emma R.')
    expect(rows[0].text()).toContain('série n°42')
    expect(rows[0].text()).toContain('trou de génération')
    expect(rows[1].attributes('data-anomaly-kind')).toBe('student_cancellation')
    expect(rows[1].text()).toContain('séance comptée dans l’abonnement')
  })

  it('les trois actions émettent le placeholder ; réactiver/ajouter désactivés un jour fermé', async () => {
    const w = mount(PlanningSlotAnomaliesDrawer, { props: { open: true, entries }, ...opts })
    const row = w.findAll('li')[0]
    await row.find('[data-action="materialize"]').trigger('click')
    await row.find('[data-action="create-here"]').trigger('click')
    await row.find('[data-action="release"]').trigger('click')
    // Vue expose les props via un proxy réactif : comparer par identité de série, pas par référence.
    for (const evt of ['materialize', 'create-here', 'release'] as const) {
      const emitted = w.emitted(evt)?.[0]?.[0] as { recurring_slot_id?: number; occurrence_date?: string }
      expect(emitted).toMatchObject({ recurring_slot_id: 42, occurrence_date: DATE })
    }

    const closed = mount(PlanningSlotAnomaliesDrawer, { props: { open: true, entries, isClosure: true }, ...opts })
    const r = closed.findAll('li')[0]
    expect((r.find('[data-action="materialize"]').element as HTMLButtonElement).disabled).toBe(true)
    expect((r.find('[data-action="create-here"]').element as HTMLButtonElement).disabled).toBe(true)
    expect((r.find('[data-action="release"]').element as HTMLButtonElement).disabled).toBe(false)
  })

  it('état de chargement par série', () => {
    const w = mount(PlanningSlotAnomaliesDrawer, { props: { open: true, entries, materializingId: 42, releasingId: 43 }, ...opts })
    const rows = w.findAll('li')
    expect(rows[0].find('[data-action="materialize"]').text()).toBe('…')
    expect(rows[1].find('[data-action="release"]').text()).toBe('…')
    expect(rows[1].find('[data-action="materialize"]').text()).toContain('Réactiver')
  })
})

describe('PlanningDayAnomalyBar', () => {
  const anomalies: PlanningAnomaly[] = [...gapAnomalies, ...gapAnomalies, ...studentAnomalies]

  it('caché sans anomalie', () => {
    expect(mount(PlanningDayAnomalyBar, { props: { anomalies: [] } }).find('[data-testid="planning-day-anomaly-bar"]').exists()).toBe(false)
  })

  it('résumé compté par famille, tous les filtres sélectionnables avec leur compteur', async () => {
    const w = mount(PlanningDayAnomalyBar, { props: { anomalies } })
    expect(w.text()).toContain('3 à traiter')
    expect(w.text()).toContain('2 trous de génération')
    expect(w.text()).toContain('1 annulation élève')
    expect(w.find('[data-filter="all"]').text()).toBe('Tous (3)')
    expect(w.find('[data-filter="generation_gap"]').text()).toBe('Trous (2)')
    expect(w.find('[data-filter="club_cancellation"]').text()).toBe('Club (0)')
    for (const kind of ['generation_gap', 'student_cancellation', 'club_cancellation', 'inconsistency']) {
      expect((w.find(`[data-filter="${kind}"]`).element as HTMLButtonElement).disabled).toBe(false)
    }
    await w.find('[data-filter="generation_gap"]').trigger('click')
    expect(w.emitted('update:activeFilter')?.[0]).toEqual(['generation_gap'])
    // Incohérences : cliquable même à 0 (déclenche le chargement paresseux des diagnostics)
    await w.find('[data-filter="inconsistency"]').trigger('click')
    expect(w.emitted('update:activeFilter')?.[1]).toEqual(['inconsistency'])
    await w.find('[data-filter="all"]').trigger('click')
    expect(w.emitted('update:activeFilter')?.[2]).toEqual(['all'])
  })
})
