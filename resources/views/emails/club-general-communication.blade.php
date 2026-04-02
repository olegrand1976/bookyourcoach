<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $clubName }}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #1f2937; max-width: 640px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%); color: white; padding: 20px; border-radius: 10px; margin-bottom: 24px; }
        .content { background: #f9fafb; padding: 20px; border-radius: 10px; border: 1px solid #e5e7eb; white-space: pre-wrap; word-break: break-word; }
        .footer { margin-top: 24px; font-size: 12px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <p style="margin:0;font-size:14px;opacity:.9;">Message de votre club</p>
        <h1 style="margin:8px 0 0 0;font-size:22px;">{{ $clubName }}</h1>
    </div>
    <div class="content">{{ $bodyText }}</div>
    <div class="footer">
        <p>Ce message vous a été envoyé via BookYourCoach. Pour répondre, utilisez la fonction « répondre » de votre messagerie si une adresse de réponse est indiquée.</p>
    </div>
</body>
</html>
