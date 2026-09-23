<template>
  <div
    v-if="open && announcement"
    class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center bg-black/50 p-0 sm:p-4"
    role="dialog"
    aria-modal="true"
    :aria-label="announcement.title"
    data-testid="release-announcement"
    @click.self="close"
  >
    <div class="w-full sm:max-w-2xl max-h-[90vh] overflow-y-auto rounded-t-2xl sm:rounded-2xl bg-white shadow-xl">
      <div class="sticky top-0 bg-gradient-to-r from-blue-600 to-blue-700 px-5 py-4 flex items-start justify-between gap-3">
        <div class="min-w-0">
          <h2 class="text-white font-semibold text-lg leading-tight m-0">{{ announcement.title }}</h2>
          <p v-if="announcement.intro" class="text-blue-50 text-sm mt-1 mb-0 leading-snug">
            {{ announcement.intro }}
          </p>
        </div>
        <button
          type="button"
          class="shrink-0 rounded-md p-1 text-white/90 hover:text-white hover:bg-white/15 transition-colors"
          aria-label="Fermer"
          data-testid="release-announcement-close-icon"
          @click="close"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <ul class="px-5 py-4 flex flex-col gap-3 m-0 list-none">
        <li
          v-for="(item, index) in announcement.items"
          :key="index"
          class="rounded-lg border px-3 py-2.5"
          :class="itemClass(item.kind)"
          :data-kind="item.kind"
        >
          <p class="flex flex-wrap items-center gap-2 m-0">
            <span
              class="inline-flex items-center rounded px-1.5 py-0.5 text-[11px] font-bold uppercase tracking-wide"
              :class="badgeClass(item.kind)"
            >{{ badgeLabel(item.kind) }}</span>
            <span class="font-semibold text-gray-900 text-sm">{{ item.title }}</span>
          </p>
          <p class="mt-1.5 mb-0 text-sm text-gray-700 leading-snug">{{ item.detail }}</p>
        </li>
      </ul>

      <div class="sticky bottom-0 bg-white border-t border-gray-200 px-5 py-3 flex justify-end">
        <button
          type="button"
          class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition-colors"
          data-testid="release-announcement-dismiss"
          @click="close"
        >
          J'ai compris
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { ReleaseAnnouncement, ReleaseAnnouncementItem } from '~/composables/useReleaseAnnouncement'

defineProps<{
  open: boolean
  announcement: ReleaseAnnouncement | null
}>()

const emit = defineEmits<{ (e: 'close'): void }>()

function close(): void {
  emit('close')
}

function badgeLabel(kind: ReleaseAnnouncementItem['kind']): string {
  return kind === 'change' ? 'À noter' : kind === 'fix' ? 'Corrigé' : 'Nouveau'
}

function badgeClass(kind: ReleaseAnnouncementItem['kind']): string {
  return kind === 'change'
    ? 'bg-amber-100 text-amber-900'
    : kind === 'fix'
      ? 'bg-emerald-100 text-emerald-900'
      : 'bg-violet-100 text-violet-900'
}

function itemClass(kind: ReleaseAnnouncementItem['kind']): string {
  return kind === 'change'
    ? 'border-amber-200 bg-amber-50/60'
    : kind === 'fix'
      ? 'border-emerald-200 bg-emerald-50/50'
      : 'border-violet-200 bg-violet-50/50'
}
</script>
