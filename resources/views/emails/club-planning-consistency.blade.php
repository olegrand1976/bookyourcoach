<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planning du {{ $targetDateLabel }}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.5; color: #1f2937; max-width: 720px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #059669 0%, #0d9488 100%); color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .header p { margin: 0; font-size: 14px; opacity: .9; }
        .header h1 { margin: 8px 0 0 0; font-size: 22px; }
        .pill { display: inline-block; padding: 4px 10px; border-radius: 999px; font-weight: bold; font-size: 13px; margin-right: 6px; }
        .pill-critical { background: #dc2626; color: #fff; }
        .pill-warning { background: #f59e0b; color: #111827; }
        .group { border-radius: 10px; padding: 14px 16px; margin: 18px 0; }
        .group h2 { margin: 0 0 4px 0; font-size: 17px; }
        .group p.lead { margin: 0 0 10px 0; font-size: 13px; }
        .g-critical { background: #fef2f2; border: 2px solid #dc2626; }
        .g-critical h2 { color: #991b1b; }
        .g-warning { background: #fffbeb; border: 1px solid #f59e0b; }
        .g-warning h2 { color: #92400e; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; background: white; }
        th, td { text-align: left; padding: 7px 9px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        th { background: #f3f4f6; color: #374151; font-weight: 600; }
        .title { font-weight: bold; }
        .muted { color: #6b7280; font-size: 12px; }
        .cta { display: inline-block; background: #059669; color: #fff !important; text-decoration: none; padding: 10px 16px; border-radius: 8px; font-weight: bold; }
        .footer { margin-top: 24px; font-size: 12px; color: #6b7280; }
    </style>
</head>
<body>
    @php
        $issues = $report['issues'] ?? [];
        $counts = $report['counts'] ?? [];
        $meta = [
            'critical' => ['Incohérences à corriger avant les cours', 'Le planning tel qu’il est ne peut pas se dérouler correctement.'],
            'warning' => ['Points à vérifier', 'Peut être voulu, mais inhabituel.'],
        ];
    @endphp

    <div class="header">
        <p>Veille planning — contrôle de la veille</p>
        <h1>{{ $clubName }}</h1>
        <p style="margin-top:10px;font-size:15px;">Cours du {{ $targetDateLabel }}</p>
    </div>

    <div>
        <span class="pill pill-critical">{{ (int) ($counts['critical'] ?? 0) }} à corriger</span>
        <span class="pill pill-warning">{{ (int) ($counts['warning'] ?? 0) }} à vérifier</span>
        <div class="muted" style="margin-top:8px;">{{ (int) ($report['lessons_count'] ?? 0) }} cours planifié(s) ce jour-là.</div>
    </div>

    @foreach (['critical', 'warning'] as $level)
        @php $entries = array_values(array_filter($issues, fn ($i) => $i['level'] === $level)); @endphp
        @if (count($entries) > 0)
            <div class="group g-{{ $level }}">
                <h2>{{ $meta[$level][0] }} ({{ count($entries) }})</h2>
                <p class="lead">{{ $meta[$level][1] }}</p>
                <table>
                    <thead><tr><th>Heure</th><th>Problème</th></tr></thead>
                    <tbody>
                        @foreach ($entries as $e)
                            <tr>
                                <td>{{ $e['time'] !== '' ? $e['time'] : '—' }}</td>
                                <td><span class="title">{{ $e['title'] }}</span><br><span class="muted">{{ $e['detail'] }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endforeach

    @if ($planningUrl)
        <p><a class="cta" href="{{ $planningUrl }}">Ouvrir le planning du club</a></p>
    @endif

    <div class="footer">
        E-mail automatique envoyé la veille de chaque jour de cours, uniquement si le planning présente une incohérence.
    </div>
</body>
</html>
