import { describe, it, expect } from 'vitest'
import {
  resolveLessonPrimaryStudentId,
  resolveLessonTeacherId,
  participantDisplayNameFromStudent,
  resolveStudentDisplayName,
  formatLessonStudentsLabel,
} from '~/composables/planning/usePlanningParticipant'

describe('usePlanningParticipant', () => {
  it('resolveLessonPrimaryStudentId priorise student_id', () => {
    expect(resolveLessonPrimaryStudentId({ student_id: 5, student: { id: 9 } })).toBe(5)
  })

  it('resolveLessonTeacherId priorise teacher_id', () => {
    expect(resolveLessonTeacherId({ teacher_id: 3, teacher: { id: 7 } })).toBe(3)
  })

  it('participantDisplayNameFromStudent compose le nom', () => {
    expect(participantDisplayNameFromStudent({ first_name: 'Achille', last_name: 'Guerit' })).toBe('Achille Guerit')
  })

  it('participantDisplayNameFromStudent priorise user.name', () => {
    expect(
      participantDisplayNameFromStudent({
        first_name: 'A',
        last_name: 'B',
        user: { name: 'Basile Petit' },
      }),
    ).toBe('Basile Petit')
  })

  it('resolveStudentDisplayName renvoie null sans données', () => {
    expect(resolveStudentDisplayName(null)).toBeNull()
    expect(resolveStudentDisplayName({ id: 105, user: null })).toBeNull()
  })

  it('formatLessonStudentsLabel utilise first_name/last_name sans user', () => {
    expect(
      formatLessonStudentsLabel({
        student_id: 105,
        student: { id: 105, first_name: 'Léa', last_name: 'Martin', user: null },
      }),
    ).toBe('Léa Martin')
  })

  it('formatLessonStudentsLabel priorise user.name', () => {
    expect(
      formatLessonStudentsLabel({
        student_id: 1,
        student: {
          id: 1,
          first_name: 'A',
          last_name: 'B',
          user: { name: 'Basile Petit' },
        },
      }),
    ).toBe('Basile Petit')
  })

  it('formatLessonStudentsLabel fallback Élève #id sans relation', () => {
    expect(formatLessonStudentsLabel({ student_id: 105, student: null, students: [] })).toBe(
      'Élève #105',
    )
  })

  it('formatLessonStudentsLabel déduplique BelongsTo et M2M', () => {
    expect(
      formatLessonStudentsLabel({
        student_id: 10,
        student: { id: 10, first_name: 'Léa', last_name: 'Martin' },
        students: [
          { id: 10, first_name: 'Léa', last_name: 'Martin' },
          { id: 11, user: { name: 'Antony Benigno' } },
        ],
      }),
    ).toBe('Léa Martin, Antony Benigno')
  })

  it('formatLessonStudentsLabel remplace #id BelongsTo par nom M2M même id', () => {
    expect(
      formatLessonStudentsLabel({
        student_id: 105,
        student: { id: 105, user: null },
        students: [{ id: 105, first_name: 'Léa', last_name: 'Martin' }],
      }),
    ).toBe('Léa Martin')
  })

  it('formatLessonStudentsLabel emptyLabel personnalisé', () => {
    expect(formatLessonStudentsLabel(null, { emptyLabel: 'Élève —' })).toBe('Élève —')
    expect(formatLessonStudentsLabel({ students: [] }, { emptyLabel: 'Élève —' })).toBe('Élève —')
  })
})
