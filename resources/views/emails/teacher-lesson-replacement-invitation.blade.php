<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande de remplacement</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 640px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%); color: white; padding: 24px; border-radius: 10px; margin-bottom: 24px; }
        .content { background: #f9fafb; padding: 20px; border-radius: 10px; border: 1px solid #e5e7eb; }
        .cta { margin: 20px 0; padding: 14px 18px; background: #2563eb; color: #fff !important; text-decoration: none; border-radius: 8px; display: inline-block; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; font-size: 14px; }
        th, td { border: 1px solid #e5e7eb; padding: 8px 10px; text-align: left; }
        th { background: #f3f4f6; }
        .footer { margin-top: 24px; font-size: 12px; color: #6b7280; text-align: center; }
        .muted { color: #6b7280; font-size: 13px; }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin:0;font-size:20px;">Demande de remplacement</h1>
        <p style="margin:8px 0 0 0;opacity:.95;">{{ $clubName }}</p>
    </div>
    <div class="content">
        <p>Bonjour {{ $replacementTeacherName }},</p>
        <p><strong>{{ $originalTeacherName }}</strong> vous demande de le ou la remplacer sur le ou les cours ci-dessous.</p>
        <p class="muted">Merci de vous connecter à votre <strong>espace enseignant</strong> pour <strong>accepter</strong> ou <strong>refuser</strong> cette demande (section remplacements / notifications).</p>
        @if(!empty($teacherDashboardUrl))
            <p><a class="cta" href="{{ $teacherDashboardUrl }}">Ouvrir mon espace enseignant</a></p>
        @endif
        <p><strong>Motif indiqué par le demandeur :</strong> {{ $reason }}</p>
        @if(!empty($notes))
            <p><strong>Notes :</strong> {{ $notes }}</p>
        @endif
        <p><strong>Cours concernés :</strong></p>
        <table>
            <thead>
                <tr>
                    <th>Date / heure</th>
                    <th>Type</th>
                    <th>Élève</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lessons as $lesson)
                    <tr>
                        <td>
                            {{ $lesson->start_time ? $lesson->start_time->format('d/m/Y H:i') : '—' }}
                            @if($lesson->end_time)
                                – {{ $lesson->end_time->format('H:i') }}
                            @endif
                        </td>
                        <td>{{ $lesson->courseType?->name ?? '—' }}</td>
                        <td>
                            @if($lesson->relationLoaded('student') && $lesson->student?->user)
                                {{ $lesson->student->user->name }}
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p class="muted" style="margin-top:20px;">Le responsable du club est mis en copie de ce message.</p>
    </div>
    <div class="footer">
        Message automatique — Activibe / BookYourCoach
    </div>
</body>
</html>
