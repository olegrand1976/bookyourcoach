<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Note d'Information au Volontaire</title>
    <style>
        /* Document officiel – mise en page sobre et lisible */
        body {
            font-family: 'DejaVu Serif', 'Times New Roman', serif;
            font-size: 11pt;
            line-height: 1.55;
            color: #1a1a1a;
            margin: 0;
            padding: 28px 32px;
            max-width: 100%;
        }
        h1 {
            text-align: center;
            font-size: 16pt;
            font-weight: bold;
            margin: 0 0 4px 0;
            page-break-after: avoid;
        }
        .intro {
            text-align: center;
            font-style: italic;
            font-size: 9pt;
            color: #444;
            margin: 0 0 20px 0;
            page-break-after: avoid;
        }
        h2 {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12pt;
            font-weight: bold;
            margin: 18px 0 8px 0;
            page-break-after: avoid;
        }
        h3 {
            font-size: 11pt;
            font-weight: bold;
            margin: 10px 0 4px 0;
            page-break-after: avoid;
        }
        p {
            margin: 6px 0;
            text-align: justify;
        }
        .partie {
            margin: 12px 0;
            margin-left: 16px;
            page-break-inside: avoid;
        }
        .section {
            margin: 14px 0;
        }
        .subsection {
            margin-left: 20px;
            margin-top: 6px;
        }
        .signatures {
            margin-top: 36px;
            page-break-inside: avoid;
        }
        .signature-block {
            width: 45%;
            display: inline-block;
            vertical-align: top;
            text-align: center;
        }
        .signature-line {
            margin-top: 50px;
            border-top: 1px solid #000;
            padding-top: 5px;
        }
        ul { margin: 8px 0; padding-left: 22px; }
        li { margin: 2px 0; }
        .bold { font-weight: bold; }
        .italic { font-style: italic; }
        .small { font-size: 9pt; color: #333; }
        @page { margin: 18mm; }
    </style>
</head>
<body>
    <h1>Note d'Information au Volontaire</h1>
    <p class="intro">(Conformément à la Loi du 3 juillet 2005 relative aux droits des volontaires)</p>

    <div class="section">
        <p>
            La présente note vise à informer le volontaire des conditions de son engagement au sein de l'ASBL, 
            conformément aux obligations légales.
        </p>
        <p>
            Il est rappelé que la relation entre le volontaire et l'ASBL ne relève pas d'un contrat de travail. 
            Le volontariat est une activité exercée sans rémunération et sans obligation de prestation, 
            dans un but désintéressé.
        </p>
    </div>

    <h2>Entre :</h2>
    
    <div class="partie">
        <p class="bold">L'ASBL : {{ $club->name }}</p>
        <p>Siège social : {{ $club->address }}@if($club->postal_code && $club->city), {{ $club->postal_code }} {{ $club->city }}@endif @if($club->country), {{ $club->country }}@endif</p>
        @if($club->company_number)
        <p>Numéro BCE : {{ $club->company_number }}</p>
        @endif
        @if($club->legal_representative_name && $club->legal_representative_role)
        <p>Représentée par : {{ $club->legal_representative_name }}, {{ $club->legal_representative_role }}</p>
        @endif
        <p class="italic">(Ci-après "l'Organisation")</p>
    </div>

    <div class="partie">
        <p class="bold">Et :</p>
        <p>Le/La Volontaire : {{ $teacher->user->name }}</p>
        @if($teacher->user->address || $teacher->user->city)
        <p>Adresse : 
            @if($teacher->user->address){{ $teacher->user->address }}@endif 
            @if($teacher->user->postal_code && $teacher->user->city), {{ $teacher->user->postal_code }} {{ $teacher->user->city }}@endif 
            @if($teacher->user->country), {{ $teacher->user->country }}@endif
        </p>
        @endif
        <p class="italic">(Ci-après "le Volontaire")</p>
    </div>

    <div class="section">
        <h2>1. Informations sur l'Organisation</h2>
        <p>
            L'Organisation est une Association Sans But Lucratif (ASBL). 
            Son but désintéressé (objectif social) est le suivant : 
            @if($club->description)
                {{ $club->description }}
            @else
                [Décrire la mission principale de votre ASBL]
            @endif
        </p>
    </div>

    <div class="section">
        <h2>2. Assurances</h2>
        <p>
            Pour couvrir les activités du Volontaire, l'Organisation a souscrit les assurances obligatoires suivantes :
        </p>
        
        <div class="subsection">
            <h3>Assurance en Responsabilité Civile (RC) :</h3>
            <p class="small">
                Cette assurance couvre les dommages corporels ou matériels que le Volontaire pourrait causer 
                à des tiers (non-membres de l'ASBL) durant l'exercice de sa mission.
            </p>
            @if($club->insurance_rc_company)
            <p>Compagnie d'assurance : <span class="bold">{{ $club->insurance_rc_company }}</span></p>
            @endif
            @if($club->insurance_rc_policy_number)
            <p>Numéro de police : <span class="bold">{{ $club->insurance_rc_policy_number }}</span></p>
            @endif
        </div>

        @if($club->insurance_additional_company)
        <div class="subsection">
            <h3>Assurance complémentaire :</h3>
            <p>Compagnie d'assurance : <span class="bold">{{ $club->insurance_additional_company }}</span></p>
            @if($club->insurance_additional_policy_number)
            <p>Numéro de police : <span class="bold">{{ $club->insurance_additional_policy_number }}</span></p>
            @endif
            @if($club->insurance_additional_details)
            <p class="small">{{ $club->insurance_additional_details }}</p>
            @endif
        </div>
        @endif
    </div>

    <div class="section">
        <h2>3. Régime des Défraiements</h2>
        <p>
            L'Organisation s'engage à rembourser les frais engagés par le Volontaire dans le cadre de sa mission, 
            selon les modalités suivantes :
        </p>

        @if($club->expense_reimbursement_type === 'forfait')
        <div class="subsection">
            <h3>Défraiement Forfaitaire</h3>
            @if($club->expense_reimbursement_details)
            <p>{{ $club->expense_reimbursement_details }}</p>
            @endif
            <p class="small">
                Ce montant est réputé couvrir l'ensemble des frais du Volontaire, sans que celui-ci n'ait à fournir de justificatifs. 
                L'Organisation s'assure que les plafonds légaux (journaliers et annuels) fixés par la loi ne sont pas dépassés.
            </p>
        </div>
        @elseif($club->expense_reimbursement_type === 'reel')
        <div class="subsection">
            <h3>Remboursement des Frais Réels</h3>
            <p class="small">
                L'Organisation s'engage à rembourser les frais réellement engagés par le Volontaire, 
                sur présentation des justificatifs originaux (tickets de transport, factures, souches TVA, etc.).
            </p>
            @if($club->expense_reimbursement_details)
            <p>{{ $club->expense_reimbursement_details }}</p>
            @endif
        </div>
        @else
        <div class="subsection">
            <h3>Absence de Défraiement</h3>
            <p>
                L'Organisation ne prévoit pas de système de remboursement pour les frais engagés par le Volontaire.
            </p>
        </div>
        @endif
    </div>

    <div class="section">
        <h2>4. Devoir de Discrétion et Confidentialité</h2>
        <p>
            Le Volontaire est informé que, dans le cadre de ses activités, il peut avoir accès à des informations 
            confidentielles concernant l'Organisation, ses membres, ou ses bénéficiaires.
        </p>
        <p>
            Le Volontaire s'engage à respecter un devoir de discrétion strict. Il s'interdit de divulguer ces informations 
            à des tiers, que ce soit pendant ou après la fin de son engagement volontaire.
        </p>
        <p class="small italic">
            Le Volontaire est informé qu'il est tenu au secret professionnel tel que défini par l'article 458 du Code pénal 
            concernant toutes les informations à caractère personnel dont il aurait connaissance.
        </p>
    </div>

    <div class="section">
        <h2>Déclaration du Volontaire</h2>
        <p>
            Le Volontaire atteste avoir reçu un exemplaire de cette note d'information, en avoir pris connaissance 
            et en accepter les termes avant le début de son activité de volontariat.
        </p>
        <p class="small">
            Le Volontaire est informé qu'il doit avertir son organisme de paiement (ONEM, mutuelle, CPAS) 
            s'il perçoit des allocations ou un revenu d'intégration, avant de débuter son activité.
        </p>
    </div>

    <div class="signatures">
        <p style="text-align: center; margin-bottom: 30px;">
            Fait à <span class="bold">{{ $club->city ?? '[Lieu]' }}</span>, 
            le <span class="bold">{{ now()->locale('fr')->isoFormat('D MMMM YYYY') }}</span>
        </p>
        <p style="text-align: center; font-style: italic; font-size: 9pt; margin-bottom: 30px;">
            En double exemplaire, chaque partie reconnaissant avoir reçu le sien.
        </p>

        <table style="width: 100%; margin-top: 30px;">
            <tr>
                <td style="width: 45%; text-align: center; vertical-align: top;">
                    <p class="bold">Pour l'ASBL :</p>
                    @if($club->legal_representative_name)
                    <p>{{ $club->legal_representative_name }}</p>
                    @endif
                    @if($club->legal_representative_role)
                    <p class="small italic">{{ $club->legal_representative_role }}</p>
                    @endif
                    <div style="margin-top: 60px; border-top: 1px solid #000; padding-top: 5px;">
                        <p class="small">[Signature]</p>
                    </div>
                </td>
                <td style="width: 10%;"></td>
                <td style="width: 45%; text-align: center; vertical-align: top;">
                    <p class="bold">Pour le Volontaire :</p>
                    <p class="small italic">(Précédé de la mention manuscrite "Lu et approuvé")</p>
                    <p style="margin-top: 10px;">{{ $teacher->user->name }}</p>
                    <div style="margin-top: 60px; border-top: 1px solid #000; padding-top: 5px;">
                        <p class="small">[Signature]</p>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>

