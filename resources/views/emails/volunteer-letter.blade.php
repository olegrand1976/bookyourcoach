<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Note d'Information au Volontaire</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #9333ea 0%, #ec4899 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            background-color: #f9fafb;
            padding: 25px;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
        }
        .content p {
            margin: 0 0 15px 0;
        }
        .signature {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #9333ea 0%, #ec4899 100%);
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 8px;
            margin-top: 20px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📄 Note d'Information au Volontaire</h1>
    </div>

    <div class="content">
        <p>Bonjour <strong>{{ $teacherName }}</strong>,</p>

        <p>
            Nous vous adressons ci-joint la <strong>Note d'Information au Volontaire</strong> 
            conformément à la Loi du 3 juillet 2005 relative aux droits des volontaires.
        </p>

        <p>
            Ce document présente les conditions de votre engagement au sein de <strong>{{ $clubName }}</strong> 
            et contient des informations importantes concernant :
        </p>

        <ul style="margin: 15px 0; padding-left: 25px;">
            <li>Les informations sur notre organisation</li>
            <li>Les assurances souscrites (Responsabilité Civile et complémentaire)</li>
            <li>Le régime des défraiements</li>
            <li>Le devoir de discrétion et confidentialité</li>
        </ul>

        <p>
            Nous vous invitons à <strong>lire attentivement ce document</strong>, à le signer et à nous en retourner 
            un exemplaire. Le second exemplaire est à conserver pour vos archives.
        </p>

        <p>
            <strong>Important :</strong> Si vous percevez des allocations (ONEM, mutuelle) ou un revenu d'intégration (CPAS), 
            vous devez informer votre organisme de paiement avant de débuter votre activité de volontariat.
        </p>

        <div class="signature">
            <p>Cordialement,</p>
            <p><strong>{{ $clubName }}</strong></p>
        </div>
    </div>

    <div class="footer">
        <p>
            Ce message a été envoyé automatiquement. Pour toute question, veuillez contacter {{ $clubName }}.
        </p>
    </div>
</body>
</html>

