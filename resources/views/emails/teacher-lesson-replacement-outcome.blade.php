<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réponse à votre demande de remplacement</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 640px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, {{ $accepted ? '#059669' : '#dc2626' }} 0%, #2563eb 100%); color: white; padding: 24px; border-radius: 10px; margin-bottom: 24px; }
        .content { background: #f9fafb; padding: 20px; border-radius: 10px; border: 1px solid #e5e7eb; }
        .footer { margin-top: 24px; font-size: 12px; color: #6b7280; text-align: center; }
        .muted { color: #6b7280; font-size: 13px; }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin:0;font-size:20px;">
            @if($accepted)
                Remplacement accepté
            @else
                Remplacement refusé
            @endif
        </h1>
        <p style="margin:8px 0 0 0;opacity:.95;">{{ $clubName }}</p>
    </div>
    <div class="content">
        <p>Bonjour {{ $originalTeacherName }},</p>
        @if($accepted)
            <p><strong>{{ $replacementTeacherName }}</strong> a <strong>accepté</strong> votre demande de remplacement. Le cours a été <strong>attribué</strong> à cet enseignant dans le planning.</p>
        @else
            <p><strong>{{ $replacementTeacherName }}</strong> a <strong>refusé</strong> votre demande de remplacement pour le cours ci-dessous.</p>
        @endif
        <p><strong>Cours :</strong>
            @if($lesson->start_time)
                {{ $lesson->start_time->format('d/m/Y H:i') }}
                @if($lesson->end_time)
                    – {{ $lesson->end_time->format('H:i') }}
                @endif
            @else
                —
            @endif
        </p>
        <p><strong>Type :</strong> {{ $lesson->courseType?->name ?? '—' }}</p>
        <p class="muted" style="margin-top:20px;">Le responsable du club est mis en copie de ce message.</p>
    </div>
    <div class="footer">
        Message automatique — Activibe / BookYourCoach
    </div>
</body>
</html>
