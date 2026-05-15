<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planning {{ $targetDateLabel }}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.55; color: #1f2937; max-width: 720px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%); color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .header p { margin: 0; font-size: 14px; opacity: .9; }
        .header h1 { margin: 8px 0 0 0; font-size: 22px; }
        .alert-stop { background: #fef2f2; border: 2px solid #dc2626; border-radius: 10px; padding: 16px 18px; margin-bottom: 20px; }
        .alert-stop h2 { margin: 0 0 10px 0; font-size: 17px; color: #991b1b; }
        .alert-stop p { margin: 0 0 12px 0; font-size: 14px; color: #450a0a; }
        .alert-stop .group { margin-top: 14px; padding-top: 12px; border-top: 1px solid #fecaca; }
        .alert-stop .group:first-of-type { border-top: none; padding-top: 0; }
        .alert-stop ul { margin: 8px 0 0 0; padding-left: 20px; }
        .alert-stop li { margin: 6px 0; font-size: 14px; }
        .section { background: #f9fafb; padding: 18px; border-radius: 10px; border: 1px solid #e5e7eb; margin-bottom: 18px; }
        .section h2 { margin: 0 0 12px 0; font-size: 17px; color: #111827; }
        .section p, .section li { font-size: 14px; color: #374151; }
        .muted { color: #6b7280; font-size: 13px; }
        table.plan { width: 100%; border-collapse: collapse; font-size: 13px; background: white; border-radius: 8px; overflow: hidden; }
        table.plan th, table.plan td { text-align: left; padding: 8px 10px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        table.plan th { background: #e5e7eb; color: #374151; font-weight: 600; }
        tr.row-stop td { background: #fef2f2; }
        .badge-stop { display: inline-block; background: #dc2626; color: white; font-size: 10px; font-weight: bold; padding: 2px 6px; border-radius: 4px; margin-right: 6px; vertical-align: middle; }
        .footer { margin-top: 24px; font-size: 12px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <p>Récapitulatif automatique — veille (planning du lendemain)</p>
        <h1>{{ $clubName }}</h1>
        <p style="margin-top:10px;font-size:15px;">{{ $targetDateLabel }}</p>
    </div>

    @php
        $conflicts = $payload['teacher_parallel_conflicts'] ?? [];
        $lessons = $payload['lessons'] ?? [];
        $hasConflict = is_array($conflicts) && count($conflicts) > 0;
        $inc = $analysis['economic_inconsistencies'] ?? [];
        $moves = $analysis['move_suggestions'] ?? [];
        $lims = $analysis['limitations'] ?? [];
    @endphp

    @if($hasConflict)
        <div class="alert-stop">
            <h2>Sens interdit — même enseignant sur des créneaux qui se chevauchent</h2>
            <p>
                Un coach ne peut pas encadrer deux cours en parallèle. Les situations ci-dessous sont <strong>bloquantes</strong> :
                corrigez-les dans BookYourCoach (changement d’enseignant, d’horaire ou annulation) avant la journée affichée.
            </p>
            @foreach($conflicts as $group)
                <div class="group">
                    <p style="margin:0 0 6px 0;"><strong>{{ $group['teacher'] ?? 'Enseignant' }}</strong></p>
                    <ul>
                        @foreach(($group['lessons'] ?? []) as $les)
                            <li>
                                Cours #{{ $les['id'] ?? '—' }}
                                — <strong>{{ $les['time_label'] ?? '' }}</strong>
                                @if(!empty($les['course_type']))
                                    — {{ $les['course_type'] }}
                                @endif
                                — élèves : {{ $les['students_summary'] ?? '—' }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    @endif

    @if(!empty($analysis['summary'] ?? null))
        <div class="section">
            <h2>Analyse (IA)</h2>
            <p style="white-space: pre-wrap;">{{ $analysis['summary'] }}</p>
        </div>
    @endif

    @if(is_array($inc) && count($inc) > 0)
        <div class="section">
            <h2>Points d’attention</h2>
            <ul style="margin:0; padding-left: 20px;">
                @foreach($inc as $item)
                    <li style="margin-bottom:8px;">
                        <strong>{{ $item['title'] ?? 'Point' }}</strong>
                        @if(!empty($item['severity']))
                            <span class="muted">({{ $item['severity'] }})</span>
                        @endif
                        @if(!empty($item['description']))
                            — {{ $item['description'] }}
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(is_array($moves) && count($moves) > 0)
        <div class="section">
            <h2>Suggestions de déplacement (à valider)</h2>
            <ul style="margin:0; padding-left: 20px;">
                @foreach($moves as $mv)
                    <li style="margin-bottom:8px;">
                        @if(!empty($mv['lesson_id']))
                            Cours #{{ $mv['lesson_id'] }}
                        @endif
                        @if(!empty($mv['rationale']))
                            — {{ $mv['rationale'] }}
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(is_array($lims) && count($lims) > 0)
        <div class="section">
            <h2>Limites de l’analyse</h2>
            <ul style="margin:0; padding-left: 20px;">
                @foreach($lims as $lim)
                    <li style="margin-bottom:6px;">{{ $lim }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(is_array($lessons) && count($lessons) > 0)
        <div class="section">
            <h2>Planning du jour ({{ count($lessons) }} cours)</h2>
            <p class="muted" style="margin-top:0;">Les lignes en rouge indiquent un sens interdit (même coach, créneaux qui se chevauchent).</p>
            <table class="plan">
                <thead>
                    <tr>
                        <th>Horaire</th>
                        <th>Type</th>
                        <th>Coach</th>
                        <th>Élèves</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lessons as $L)
                        @php
                            $stop = !empty($L['has_teacher_parallel_conflict']);
                            $boundsOk = !empty($L['start_local']);
                            $timeCell = '';
                            if ($boundsOk) {
                                try {
                                    $s = \Carbon\Carbon::parse($L['start_local']);
                                    $endAt = !empty($L['end_local']) ? \Carbon\Carbon::parse($L['end_local']) : $s->copy()->addMinutes(max(1, (int)($L['duration_minutes'] ?? 60)));
                                    $timeCell = $s->format('H:i').' – '.$endAt->format('H:i');
                                } catch (\Throwable) {
                                    $timeCell = (string)($L['start_local'] ?? '');
                                }
                            }
                            $studentBits = collect($L['students'] ?? [])->pluck('name')->filter()->take(3)->implode(', ');
                            $more = (count($L['students'] ?? []) > 3) ? '…' : '';
                        @endphp
                        <tr class="{{ $stop ? 'row-stop' : '' }}">
                            <td>
                                @if($stop)<span class="badge-stop">Sens interdit</span>@endif
                                {{ $timeCell }}
                            </td>
                            <td>{{ $L['course_type'] ?? '—' }}</td>
                            <td>{{ $L['teacher'] ?? '—' }}</td>
                            <td>{{ $studentBits }}{{ $more }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="footer">
        <p>Ce message est envoyé automatiquement par BookYourCoach (veille, matin selon l’horaire configuré sur le serveur). Connectez-vous au planning club pour modifier les cours.</p>
    </div>
</body>
</html>
