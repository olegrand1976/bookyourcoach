<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abonnements en fin de vie — {{ $targetDateLabel }}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.5; color: #1f2937; max-width: 720px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #059669 0%, #0d9488 100%); color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .header p { margin: 0; font-size: 14px; opacity: .9; }
        .header h1 { margin: 8px 0 0 0; font-size: 22px; }
        .summary { display: block; margin-bottom: 20px; font-size: 14px; }
        .pill { display: inline-block; padding: 4px 10px; border-radius: 999px; font-weight: bold; font-size: 13px; margin-right: 6px; }
        .pill-critical { background: #dc2626; color: #fff; }
        .pill-urgent { background: #f59e0b; color: #111827; }
        .pill-watch { background: #e5e7eb; color: #374151; }
        .group { border-radius: 10px; padding: 14px 16px; margin-bottom: 18px; }
        .group h2 { margin: 0 0 4px 0; font-size: 17px; }
        .group p.lead { margin: 0 0 10px 0; font-size: 13px; }
        .g-critical { background: #fef2f2; border: 2px solid #dc2626; }
        .g-critical h2 { color: #991b1b; }
        .g-urgent { background: #fffbeb; border: 1px solid #f59e0b; }
        .g-urgent h2 { color: #92400e; }
        .g-watch { background: #f9fafb; border: 1px solid #e5e7eb; }
        .g-watch h2 { color: #374151; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; background: white; }
        th, td { text-align: left; padding: 7px 9px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        th { background: #f3f4f6; color: #374151; font-weight: 600; }
        .student { font-weight: bold; }
        .reasons { color: #374151; }
        .muted { color: #6b7280; font-size: 12px; }
        .cta { display: inline-block; background: #059669; color: #fff !important; text-decoration: none; padding: 10px 16px; border-radius: 8px; font-weight: bold; }
        .footer { margin-top: 24px; font-size: 12px; color: #6b7280; }
    </style>
</head>
<body>
    @php
        $groups = $report['groups'] ?? [];
        $counts = $report['counts'] ?? [];
        $meta = [
            'critical' => ['Critique — à traiter avant le cours', 'Dernier cours (ou aucun) restant, expiration sous 3 jours, ou plus d’abonnement actif.'],
            'urgent' => ['Urgent — à renouveler cette semaine', '2 cours restants ou expiration sous 7 jours.'],
            'watch' => ['À surveiller', '3 cours restants ou expiration sous 14 jours.'],
        ];
    @endphp

    <div class="header">
        <p>Veille abonnements — élèves ayant cours prochainement</p>
        <h1>{{ $clubName }}</h1>
        <p style="margin-top:10px;font-size:15px;">Cours du {{ $targetDateLabel }}</p>
    </div>

    <div class="summary">
        <span class="pill pill-critical">{{ (int) ($counts['critical'] ?? 0) }} critique(s)</span>
        <span class="pill pill-urgent">{{ (int) ($counts['urgent'] ?? 0) }} urgent(s)</span>
        <span class="pill pill-watch">{{ (int) ($counts['watch'] ?? 0) }} à surveiller</span>
        <div class="muted" style="margin-top:8px;">{{ (int) ($report['students_with_lessons'] ?? 0) }} élève(s) ont cours ce jour-là.</div>
    </div>

    @foreach (['critical', 'urgent', 'watch'] as $level)
        @php $entries = $groups[$level] ?? []; @endphp
        @if (count($entries) > 0)
            <div class="group g-{{ $level }}">
                <h2>{{ $meta[$level][0] }} ({{ count($entries) }})</h2>
                <p class="lead">{{ $meta[$level][1] }}</p>
                <table>
                    <thead>
                        <tr>
                            <th>Élève</th>
                            <th>Cours</th>
                            <th>Abonnement</th>
                            <th>Situation</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($entries as $e)
                            <tr>
                                <td><span class="student">{{ $e['student_name'] }}</span>
                                    @if (!empty($e['student_email']))<br><span class="muted">{{ $e['student_email'] }}</span>@endif
                                </td>
                                <td>{{ \Illuminate\Support\Str::after($e['next_lesson_at'] ?? '', ' ') }}
                                    @if (!empty($e['next_lesson_course_type']))<br><span class="muted">{{ $e['next_lesson_course_type'] }}</span>@endif
                                </td>
                                <td>{{ $e['subscription_label'] ?? '—' }}
                                    @if (!empty($e['expires_at']))<br><span class="muted">expire le {{ \Carbon\Carbon::parse($e['expires_at'])->format('d/m/Y') }}</span>@endif
                                </td>
                                <td class="reasons">{{ implode(' · ', $e['reasons'] ?? []) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endforeach

    @php $planned = $report['planned_endings'] ?? []; @endphp
    @if (count($planned) > 0)
        <div class="group g-watch">
            <h2>Fins prévues — dernier abonnement ({{ count($planned) }})</h2>
            <p class="lead">Pas de renouvellement à proposer : la récurrence s’arrête à la fin de l’abonnement.</p>
            <table>
                <tbody>
                    @foreach ($planned as $e)
                        <tr>
                            <td><span class="student">{{ $e['student_name'] }}</span></td>
                            <td>{{ $e['subscription_label'] ?? '—' }}
                                @if (!empty($e['expires_at']))<br><span class="muted">expire le {{ \Carbon\Carbon::parse($e['expires_at'])->format('d/m/Y') }}</span>@endif
                            </td>
                            <td class="reasons">{{ implode(' · ', $e['reasons'] ?? []) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if ($manageUrl)
        <p><a class="cta" href="{{ $manageUrl }}">Ouvrir les abonnements du club</a></p>
    @endif

    <div class="footer">
        E-mail automatique envoyé {{ (int) config('bookyourcoach.subscription_expiry_watch.lead_days', 2) }} jour(s) avant chaque jour de cours. Les jours de congé du club sont ignorés.
    </div>
</body>
</html>
