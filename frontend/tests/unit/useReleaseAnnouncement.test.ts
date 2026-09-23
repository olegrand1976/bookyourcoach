import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import {
  CURRENT_CLUB_ANNOUNCEMENT,
  dismissAnnouncement,
  isAnnouncementDismissed,
  readDismissedAnnouncements,
} from '~/composables/useReleaseAnnouncement'
import ReleaseAnnouncementModal from '~/components/ReleaseAnnouncementModal.vue'

const KEY = 'club-release-announcements-dismissed'

describe('useReleaseAnnouncement', () => {
  beforeEach(() => {
    localStorage.clear()
  })

  afterEach(() => {
    vi.restoreAllMocks()
  })

  it('une annonce non lue n’est pas considérée comme fermée', () => {
    expect(isAnnouncementDismissed('a')).toBe(false)
    expect(readDismissedAnnouncements()).toEqual([])
  })

  it('fermer une annonce la retient définitivement', () => {
    dismissAnnouncement('a')

    expect(isAnnouncementDismissed('a')).toBe(true)
    expect(JSON.parse(localStorage.getItem(KEY) ?? '[]')).toEqual(['a'])
  })

  it('les annonces se cumulent sans écraser les précédentes, et sans doublon', () => {
    dismissAnnouncement('a')
    dismissAnnouncement('b')
    dismissAnnouncement('a')

    expect(readDismissedAnnouncements()).toEqual(['a', 'b'])
    expect(isAnnouncementDismissed('b')).toBe(true)
  })

  it('une nouvelle annonce (identifiant différent) reste à afficher', () => {
    dismissAnnouncement('2026-09-planning')

    expect(isAnnouncementDismissed('2026-10-autre-chose')).toBe(false)
  })

  it('un contenu stocké corrompu est ignoré sans casser', () => {
    localStorage.setItem(KEY, 'pas du json')

    expect(readDismissedAnnouncements()).toEqual([])
    expect(isAnnouncementDismissed('a')).toBe(false)
  })

  it('un contenu stocké du mauvais type est ignoré', () => {
    localStorage.setItem(KEY, JSON.stringify({ a: true }))
    expect(readDismissedAnnouncements()).toEqual([])

    localStorage.setItem(KEY, JSON.stringify(['a', 42, null, 'b']))
    expect(readDismissedAnnouncements()).toEqual(['a', 'b'])
  })

  it('un stockage indisponible ne fait pas échouer la page', () => {
    vi.spyOn(Storage.prototype, 'getItem').mockImplementation(() => {
      throw new Error('stockage bloqué')
    })

    expect(() => readDismissedAnnouncements()).not.toThrow()
    expect(readDismissedAnnouncements()).toEqual([])
    expect(() => dismissAnnouncement('a')).not.toThrow()
  })

  it('une écriture refusée (quota) ne fait pas échouer la page', () => {
    vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => {
      throw new Error('quota dépassé')
    })

    expect(() => dismissAnnouncement('a')).not.toThrow()
  })

  it('l’annonce en cours est exploitable et versionnée', () => {
    expect(CURRENT_CLUB_ANNOUNCEMENT).not.toBeNull()
    expect(CURRENT_CLUB_ANNOUNCEMENT!.id).toMatch(/^\d{4}-\d{2}-/)
    expect(CURRENT_CLUB_ANNOUNCEMENT!.items.length).toBeGreaterThan(0)
    for (const item of CURRENT_CLUB_ANNOUNCEMENT!.items) {
      expect(['change', 'fix', 'new']).toContain(item.kind)
      expect(item.title.length).toBeGreaterThan(0)
      expect(item.detail.length).toBeGreaterThan(0)
    }
  })
})

describe('ReleaseAnnouncementModal', () => {
  const announcement = {
    id: 'test-1',
    title: 'Ce qui change',
    intro: 'Deux points à connaître.',
    items: [
      { kind: 'change' as const, title: 'Places disponibles', detail: 'Les réservations comptent.' },
      { kind: 'fix' as const, title: 'Récurrence', detail: 'Modification à nouveau possible.' },
      { kind: 'new' as const, title: 'Fin estimée', detail: 'Nouvelle date sur la fiche.' },
    ],
  }

  it('ne rend rien quand elle est fermée ou sans annonce', () => {
    expect(
      mount(ReleaseAnnouncementModal, { props: { open: false, announcement } })
        .find('[data-testid="release-announcement"]').exists()
    ).toBe(false)

    expect(
      mount(ReleaseAnnouncementModal, { props: { open: true, announcement: null } })
        .find('[data-testid="release-announcement"]').exists()
    ).toBe(false)
  })

  it('affiche le titre, l’intro et une ligne typée par point', () => {
    const w = mount(ReleaseAnnouncementModal, { props: { open: true, announcement } })

    expect(w.text()).toContain('Ce qui change')
    expect(w.text()).toContain('Deux points à connaître.')
    expect(w.findAll('[data-kind]').map((n) => n.attributes('data-kind'))).toEqual(['change', 'fix', 'new'])
    expect(w.text()).toContain('À noter')
    expect(w.text()).toContain('Corrigé')
    expect(w.text()).toContain('Nouveau')
    expect(w.text()).toContain('Les réservations comptent.')
  })

  it('émet close depuis le bouton, la croix et le fond', async () => {
    const w = mount(ReleaseAnnouncementModal, { props: { open: true, announcement } })

    await w.find('[data-testid="release-announcement-dismiss"]').trigger('click')
    await w.find('[data-testid="release-announcement-close-icon"]').trigger('click')
    await w.find('[data-testid="release-announcement"]').trigger('click')

    expect(w.emitted('close')).toHaveLength(3)
  })
})
