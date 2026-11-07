import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import StudentHistoryLessonsPanel from '~/components/student-history/StudentHistoryLessonsPanel.vue'

const day = 24 * 3600 * 1000
const lesson = (id: number, offsetDays: number) => {
  const start = new Date(Date.now() + offsetDays * day)
  return {
    id,
    status: 'confirmed',
    start_time: start.toISOString(),
    end_time: new Date(start.getTime() + 20 * 60000).toISOString(),
    subscription_instances: [{ id: 169 }],
  }
}

const stubs = { StudentHistoryLessonRow: { props: ['lesson'], template: '<div class="row" :data-id="lesson.id" />' }, NuxtLink: true }

describe('StudentHistoryLessonsPanel — onglets À venir / Passés', () => {
  it('sépare les cours à venir et passés dans deux onglets', async () => {
    const w = mount(StudentHistoryLessonsPanel, {
      props: { lessons: [lesson(1, 7), lesson(2, 14), lesson(3, -7)], isLessonUncovered: () => false },
      global: { stubs },
    })

    expect(w.find('[data-testid="lessons-tab-upcoming"]').text()).toContain('2')
    expect(w.find('[data-testid="lessons-tab-past"]').text()).toContain('1')
    expect(w.findAll('[data-testid="lessons-panel-upcoming"] .row').map((r) => r.attributes('data-id'))).toEqual(['1', '2'])
    expect(w.find('[data-testid="lessons-panel-past"]').exists()).toBe(false)

    await w.find('[data-testid="lessons-tab-past"]').trigger('click')
    expect(w.findAll('[data-testid="lessons-panel-past"] .row').map((r) => r.attributes('data-id'))).toEqual(['3'])
    expect(w.find('[data-testid="lessons-panel-upcoming"]').exists()).toBe(false)
  })

  it('ouvre « Passés » quand il n’y a aucun cours à venir', () => {
    const w = mount(StudentHistoryLessonsPanel, {
      props: { lessons: [lesson(3, -7)], isLessonUncovered: () => false },
      global: { stubs },
    })
    expect(w.find('[data-testid="lessons-panel-past"]').exists()).toBe(true)
  })
})
