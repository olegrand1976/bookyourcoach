import { describe, it, expect } from 'vitest'
import {
  resolveLessonPrimaryStudentId,
  resolveLessonTeacherId,
  participantDisplayNameFromStudent,
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
})
