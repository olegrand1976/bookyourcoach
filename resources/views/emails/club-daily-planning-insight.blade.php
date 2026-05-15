<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planning {{ $targetDateLabel }}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.55; color: #1f2937; max-width: 720px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #0d9488 0%, #2563eb 100%); color: white; padding: 20px; border-radius: 10px; margin-bottom: 24px; }
        .section { background: #f9fafb; padding: 18px; border-radius: 10px; border: 1px solid #e5e7eb; margin-bottom: 18px; }
        .section h2 { margin: 0 0 12px 0; font-size: 17px; color: #111827; }
        .sev-high { color: #b91c1c; font-weight: bold; }
        .sev-medium { color: #c2410c; font-weight: bold; }
        .sev-low { color: #4b5563; }
        ul { margin: 8px 0 0 1.1em; padding: 0; }
        .footer { margin-top: 24px; font-size: 12px; color: #6b7280; }
        .muted { color: #6b7280; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 8px; }
        th, td { border: 1px solid #e5e7eb; padding: 8px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; }
        pre { white-space: pre-wrap; word-break: break-word; background: #fff; padding: 10px; border-radius: 8px; border: 1px solid #e5e7eb; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <p style="margin:0;font-size:14px;opacity:.9;">Préparation du lendemain</p>
        <h1 style="margin:8px 0 0 0;font-size:22px;">{{ $clubName }}</h1>
        <p style="margin:12px 0 0 0;font-size:16px;">Journée du <strong>{{ $targetDateLabel }}</strong></p>
    </div>

    <div class="section">
        <h2>Résumé (IA)</h2>
        @if (!empty($analysis['summary']))
            <p style="margin:0;">{{ $analysis['summary'] }}</p>
        @else
            <p class="muted" style="margin:0;">Analyse automatique non disponible (clé API ou parsing). Voir le tableau des cours ci-dessous.</p>
        @endif
    </div>

    @if (!empty($analysis['economic_inconsistencies']))
        <div class="section">
            <h2>Incohérences ou points économiques</h2>
            <ul>
                @foreach ($analysis['economic_inconsistencies'] as $row)
                    <li>
                        <span class="sev-{{ $row['severity'] ?? 'low' }}">{{ strtoupper($row['severity'] ?? 'low') }}</span>
                        — <strong>{{ $row['title'] ?? 'Sans titre' }}</strong>
                        @if (!empty($row['description']))
                            <div class="muted" style="margin-top:4px;">{{ $row['description'] }}</div>
        @endif
                        @if (!empty($row['related_lesson_ids']))
                            <div class="muted" style="margin-top:4px;">Cours : {{ implode(', ', $row['related_lesson_ids']) }}</div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (!empty($analysis['move_suggestions']))
        <div class="section">
            <h2>Suggestions de déplacement (à valider)</h2>
            <ul>
                @foreach ($analysis['move_suggestions'] as $s)
                    <li>
                        <strong>Cours #{{ $s['lesson_id'] ?? '?' }}</strong>
                        @if (!empty($s['suggested_start_local']))
                            → {{ $s['suggested_start_local'] }}
                            @if (!empty($s['suggested_end_local']))
                                – {{ $s['suggested_end_local'] }}
                            @endif
                        @endif
                        <div class="muted" style="margin-top:4px;">{{ $s['rationale'] ?? '' }}</div>
                        @if (array_key_exists('family_constraint_safe', $s))
                            <div class="muted" style="margin-top:4px;">
                                Respect groupes famille (approx.) : {{ !empty($s['family_constraint_safe']) ? 'oui' : 'non — à vérifier' }}
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (!empty($analysis['limitations']))
        <div class="section">
            <h2>Limites de l’analyse</h2>
            <ul>
                @foreach ($analysis['limitations'] as $lim)
                    <li>{{ $lim }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="section">
        <h2>Groupes « proches » (fratrie / même abo / nom)</h2>
        @if (!empty($payload['family_constraint_groups']))
            <ul>
                @foreach ($payload['family_constraint_groups'] as $g)
                    <li>
                        Groupe {{ $g['group_id'] ?? '?' }} :
                        {{ implode(', ', $g['student_labels'] ?? []) }}
                        (ids {{ implode(', ', $g['student_ids'] ?? []) }})
                    </li>
                @endforeach
            </ul>
        @else
            <p class="muted" style="margin:0;">Aucun groupe multi-élèves détecté pour cette journée.</p>
        @endif
    </div>

    <div class="section">
        <h2>Planning brut</h2>
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Début</th>
                <th>Fin</th>
                <th>Cours</th>
                <th>Coach</th>
                <th>Élèves</th>
                <th>Prix</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($payload['lessons'] ?? [] as $l)
                <tr>
                    <td>{{ $l['id'] }}</td>
                    <td>{{ $l['start_local'] }}</td>
                    <td>{{ $l['end_local'] }}</td>
                    <td>{{ $l['course_type'] ?? '—' }}</td>
                    <td>{{ $l['teacher'] ?? '—' }}</td>
                    <td>
                        @foreach ($l['students'] ?? [] as $st)
                            {{ $st['name'] ?? $st['id'] }}@if (!$loop->last), @endif
                        @endforeach
                    </td>
                    <td>{{ isset($l['price']) ? number_format($l['price'], 2, ',', ' ') : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="7">Aucun cours actif ce jour-là.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p>Message automatique BookYourCoach (veille du jour J). Les suggestions d’IA sont indicatives : contrôle humain obligatoire avant tout déplacement réel.</p>
    </div>
</body>
</html>
