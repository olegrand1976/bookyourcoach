<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vos cours de demain</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.5; color: #1f2937; max-width: 640px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%); color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .header p { margin: 0; font-size: 14px; opacity: .9; }
        .header h1 { margin: 8px 0 0 0; font-size: 22px; }
        .alone { background: #fffbeb; border: 2px solid #f59e0b; border-radius: 10px; padding: 14px 16px; margin: 0 0 18px 0; }
        .alone strong { color: #92400e; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { text-align: left; padding: 8px 10px; border-bottom: 1px solid #e5e7eb; }
        th { background: #f3f4f6; color: #374151; font-weight: 600; }
        .footer { margin-top: 24px; font-size: 12px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <p>{{ $clubName }}</p>
        <h1>Vos cours de demain, {{ $dateLabel }}</h1>
    </div>

    <p>Bonjour {{ $teacher['name'] }},</p>

    @if ($teacher['opens_alone'])
        <div class="alone">
            <strong>Vous serez seul(e) au premier cours de la matinée ({{ $teacher['lessons'][0]['start'] }}).</strong><br>
            Merci d’arriver un peu plus tôt pour ouvrir et tout préparer avant l’arrivée de l’élève.
        </div>
    @endif

    <p>Voici vos {{ count($teacher['lessons']) > 1 ? count($teacher['lessons']).' cours' : 'cours' }} de demain :</p>
    <table>
        <thead><tr><th>Heure</th><th>Élève(s)</th></tr></thead>
        <tbody>
        @foreach ($teacher['lessons'] as $l)
            <tr><td>{{ $l['start'] }}@if ($l['end'] !== '') – {{ $l['end'] }}@endif</td><td>{{ $l['students'] }}</td></tr>
        @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Rappel automatique envoyé la veille au soir par BookYourCoach. En cas d’empêchement, prévenez le club au plus vite.</p>
    </div>
</body>
</html>
