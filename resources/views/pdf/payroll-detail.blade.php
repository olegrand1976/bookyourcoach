<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport de paie détaillé — {{ $month_name }} {{ $year }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9pt;
            color: #1a1a1a;
            margin: 0;
            padding: 12px 14px;
        }
        h1 {
            font-size: 14pt;
            margin: 0 0 4px 0;
            text-align: center;
        }
        h2.section-sub {
            font-size: 10pt;
            margin: 14px 0 6px 0;
            font-weight: bold;
            color: #1e3a5f;
        }
        .meta {
            text-align: center;
            font-size: 8pt;
            color: #444;
            margin-bottom: 12px;
        }
        .summary {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .summary th, .summary td {
            border: 1px solid #ccc;
            padding: 6px 8px;
            text-align: left;
        }
        .summary th {
            background: #f3f4f6;
            font-weight: bold;
        }
        .summary td.num, .summary th.num {
            text-align: right;
            white-space: nowrap;
        }
        .teacher-block {
            margin-bottom: 18px;
            page-break-inside: avoid;
        }
        .teacher-title {
            font-size: 11pt;
            font-weight: bold;
            margin: 12px 0 6px 0;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 2px;
        }
        .lines {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        .lines th, .lines td {
            border: 1px solid #ddd;
            padding: 4px 6px;
            vertical-align: top;
        }
        .lines th {
            background: #f9fafb;
            font-size: 8pt;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }
        .lines td.amount, .lines th.amount {
            text-align: right;
            white-space: nowrap;
        }
        .subtotal-row td {
            font-weight: bold;
            background: #eff6ff;
        }
        .footnote {
            font-size: 7.5pt;
            color: #374151;
            margin-top: 16px;
            padding: 10px;
            border: 1px solid #e5e7eb;
            line-height: 1.45;
        }
    </style>
</head>
<body>
    <h1>Rapport de paie détaillé (admin)</h1>
    <p class="meta">
        Période : {{ ucfirst($month_name) }} {{ $year }} — Généré le {{ $generated_at }}
        ({{ config('app.timezone') }})
    </p>

    <table class="summary">
        <tr>
            <th>Enseignants</th>
            <th class="num">Σ minutes séances</th>
            <th class="num">VH (= Σ min ÷ 60)</th>
            <th class="num">Total DCL (€)</th>
            <th class="num">Total NDCL (€)</th>
            <th class="num">Total à payer (€)</th>
        </tr>
        <tr>
            <td>{{ $statistics['nombre_enseignants'] ?? count($teachers) }}</td>
            <td class="num"><strong>{{ $statistics['total_duree_cours_display'] ?? '0 min' }}</strong></td>
            <td class="num"><strong>{{ $statistics['total_heures_cours_display'] ?? '0 h' }}</strong></td>
            <td class="num">{{ number_format($statistics['total_commissions_dcl'] ?? 0, 2, ',', ' ') }}</td>
            <td class="num">{{ number_format($statistics['total_commissions_ndcl'] ?? 0, 2, ',', ' ') }}</td>
            <td class="num"><strong>{{ number_format($statistics['total_a_payer'] ?? 0, 2, ',', ' ') }}</strong></td>
        </tr>
    </table>

    @if(count($teachers))
        <h2 class="section-sub">Récapitulatif par enseignant — heures données et total à payer</h2>
        <table class="lines">
            <thead>
                <tr>
                    <th>Intervenant</th>
                    <th class="amount">Σ minutes</th>
                    <th class="amount">VH</th>
                    <th class="amount">Total € (DCL + NDCL)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($teachers as $t)
                    <tr>
                        <td>{{ $t['name'] }} — ID {{ $t['id'] }}</td>
                        <td class="amount">{{ $t['total_duree_cours_minutes'] ?? 0 }} min</td>
                        <td class="amount">{{ $t['total_heures_cours_display'] }}</td>
                        <td class="amount">{{ number_format($t['total'], 2, ',', ' ') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @foreach ($teachers as $t)
        <div class="teacher-block">
            <div class="teacher-title">
                Détail lignes — {{ $t['name'] }} (ID {{ $t['id'] }})
                <span style="font-size: 9pt; font-weight: normal;">
                    · Σ {{ $t['total_duree_cours_minutes'] ?? 0 }} min ⇒ VH {{ $t['total_heures_cours_display'] }}
                </span>
            </div>
            <table class="lines">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Réf.</th>
                        <th>Ligne</th>
                        <th>Base utilisée</th>
                        <th>Segment</th>
                        <th class="amount">Durée (ligne)</th>
                        <th class="amount">Montant (€)</th>
                        <th>Note</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($t['lines'] as $line)
                        <tr>
                            <td>{{ $line['date_display'] ?? '—' }}</td>
                            <td>{{ $line['reference'] ?? '—' }}</td>
                            <td>{{ $line['label'] ?? '' }}</td>
                            <td>{{ $line['basis_label'] ?? ($line['basis'] ?? '—') }}</td>
                            <td>{{ $line['segment'] ?? '—' }}</td>
                            <td class="amount">{{ $line['hours_display'] ?? '—' }}</td>
                            <td class="amount">{{ number_format($line['amount'] ?? 0, 2, ',', ' ') }}</td>
                            <td style="font-size: 7pt; max-width: 120px;">{{ $line['notification'] ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">Aucune ligne détaillée (totaux issus d’agrégats uniquement).</td>
                        </tr>
                    @endforelse
                    <tr class="subtotal-row">
                        <td colspan="5">Sous-total enseignant</td>
                        <td class="amount">{{ $t['total_duree_cours_minutes'] ?? 0 }} min ({{ $t['total_heures_cours_display'] }})</td>
                        <td class="amount">{{ number_format($t['total'], 2, ',', ' ') }}</td>
                        <td>—</td>
                    </tr>
                </tbody>
            </table>
            <div style="font-size: 7.5pt; color: #6b7280;">
                DCL : {{ number_format($t['total_dcl'], 2, ',', ' ') }} € — NDCL : {{ number_format($t['total_ndcl'], 2, ',', ' ') }} €
            </div>
        </div>
    @endforeach

    <div class="footnote">
        <strong>VH et minutes :</strong> le cumul officiel ajoute les <strong>minutes</strong> de chaque séance (différence
        début / fin puis conversion <strong>VH = Σ minutes ÷ 60</strong> à la fin), sans arrondir séance par séance en « heures
        décimales » avant somme — ce qui garantit que des créneaux réguliers (ex.&nbsp;: 20&nbsp;min) ne produisent pas de VH
        erronées (type 6&nbsp;×&nbsp;0,33&nbsp;h ≠ 2&nbsp;h). Les paiements carnet sans séance comptabilisée n’ajoutent pas
        de minutes. Les montants par ligne suivent la règle métier décrite précédemment (tarif horaire sur minutes réelles, puis secours montant/prorata).
    </div>
</body>
</html>
