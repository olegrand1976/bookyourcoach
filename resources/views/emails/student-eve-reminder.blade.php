<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rappel de cours</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.5; color: #1f2937; max-width: 640px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #059669 0%, #0d9488 100%); color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .header p { margin: 0; font-size: 14px; opacity: .9; }
        .header h1 { margin: 8px 0 0 0; font-size: 22px; }
        .lesson { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px 16px; margin: 10px 0; font-size: 15px; }
        .lesson .time { font-size: 20px; font-weight: bold; color: #065f46; }
        .footer { margin-top: 24px; font-size: 12px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <p>{{ $clubName }}</p>
        <h1>Rappel : cours de demain, {{ $dateLabel }}</h1>
    </div>

    <p>Bonjour{{ $recipient['name'] !== '' ? ' '.$recipient['name'] : '' }},</p>
    <p>Nous vous rappelons {{ count($recipient['lessons']) > 1 ? 'les cours prévus' : 'le cours prévu' }} demain :</p>

    @foreach ($recipient['lessons'] as $l)
        <div class="lesson">
            <div class="time">{{ $l['start'] }}@if ($l['end'] !== '') – {{ $l['end'] }}@endif</div>
            <div>{{ $l['student'] }} — avec <strong>{{ $l['teacher'] }}</strong></div>
        </div>
    @endforeach

    <p>Merci d’arriver quelques minutes en avance. En cas d’empêchement, annulez le cours depuis votre espace ou prévenez le club.</p>

    <div class="footer">
        <p>Rappel automatique envoyé la veille au soir par BookYourCoach.</p>
    </div>
</body>
</html>
