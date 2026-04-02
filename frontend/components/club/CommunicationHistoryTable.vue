<template>
  <div>
    <div v-if="loading" class="py-8 text-center text-sm text-gray-500">
      Chargement…
    </div>
    <p v-else-if="rows.length === 0" class="py-6 text-center text-sm text-gray-500">
      {{ emptyText }}
    </p>
    <div v-else class="overflow-x-auto rounded-lg border border-gray-200">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th scope="col" class="px-3 py-2 text-left font-semibold text-gray-700">Date</th>
            <th scope="col" class="px-3 py-2 text-left font-semibold text-gray-700">Objet</th>
            <th scope="col" class="px-3 py-2 text-left font-semibold text-gray-700">Aperçu</th>
            <th scope="col" class="px-3 py-2 text-left font-semibold text-gray-700">Envoyé par</th>
            <th scope="col" class="px-3 py-2 text-right font-semibold text-gray-700">Dest.</th>
            <th scope="col" class="px-3 py-2 text-right font-semibold text-gray-700">OK / échec</th>
            <th scope="col" class="px-3 py-2 text-left font-semibold text-gray-700">Mode</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
          <tr v-for="row in rows" :key="row.id" class="hover:bg-gray-50/80">
            <td class="px-3 py-2 whitespace-nowrap text-gray-600">
              {{ formatDate(row.created_at) }}
            </td>
            <td
              class="px-3 py-2 font-medium text-gray-900 max-w-[12rem] truncate cursor-help"
              :title="row.subject"
            >
              {{ row.subject }}
            </td>
            <td
              class="px-3 py-2 text-gray-600 max-w-[14rem] cursor-help"
              :title="previewTooltip(row.body_preview)"
            >
              <span class="line-clamp-2 pointer-events-none">{{ row.body_preview }}</span>
            </td>
            <td
              class="px-3 py-2 text-gray-600 max-w-[10rem] truncate cursor-help"
              :title="(row.sent_by?.name || row.sent_by?.email || '—') ?? ''"
            >
              {{ row.sent_by?.name || row.sent_by?.email || '—' }}
            </td>
            <td class="px-3 py-2 text-right tabular-nums text-gray-700">
              {{ row.recipient_count }}
            </td>
            <td class="px-3 py-2 text-right tabular-nums">
              <span class="text-emerald-700">{{ row.sent_count }}</span>
              <span class="text-gray-400"> / </span>
              <span class="text-red-600">{{ row.failed_count }}</span>
            </td>
            <td class="px-3 py-2 text-gray-600 text-xs">
              <span class="block">{{ modeLabel(row.selection_mode) }}</span>
              <span
                v-if="row.teacher_recipient_count != null || row.student_recipient_count != null"
                class="text-gray-500"
              >
                En.&nbsp;: {{ row.teacher_recipient_count ?? '—' }} - Él.&nbsp;: {{ row.student_recipient_count ?? '—' }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
type HistoryRow = {
  id: number
  subject: string
  body_preview: string
  created_at: string | null
  sent_by?: { name?: string; email?: string }
  recipient_count: number
  sent_count: number
  failed_count: number
  teacher_recipient_count: number | null
  student_recipient_count: number | null
  audience: string
  selection_mode: string
}

defineProps<{
  rows: HistoryRow[]
  loading: boolean
  emptyText: string
}>()

function formatDate(iso: string | null): string {
  if (!iso) {
    return '—'
  }
  try {
    const d = new Date(iso)
    return new Intl.DateTimeFormat('fr-FR', {
      dateStyle: 'short',
      timeStyle: 'short'
    }).format(d)
  } catch {
    return iso
  }
}

function modeLabel(mode: string): string {
  if (mode === 'selected') {
    return 'Sélection'
  }
  if (mode === 'all') {
    return 'Tout le groupe'
  }
  return mode || '—'
}

/** Infobulle navigateur : texte lisible (retours ligne conservés côté attribut title). */
function previewTooltip(text: string | undefined | null): string {
  if (text == null || text === '') {
    return 'Aucun aperçu'
  }
  return text.replace(/\s+/g, ' ').trim()
}
</script>
