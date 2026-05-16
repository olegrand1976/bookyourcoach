<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport de paie détaillé — {{ $month_name }} {{ $year }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 8pt;
            color: #1a1a1a;
            margin: 0;
            padding: 10px 12px;
        }
        h1 {
            font-size: 13pt;
            margin: 0 0 2px 0;
            text-align: center;
        }
        h2.section-sub {
            font-size: 9.5pt;
            margin: 12px 0 5px 0;
            font-weight: bold;
            color: #1e3a5f;
        }
        .meta {
            text-align: center;
            font-size: 8pt;
            color: #333;
            margin-bottom: 10px;
        }
        .meta strong { color: #111; }
        .summary {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .summary th, .summary td {
            border: 1px solid #ccc;
            padding: 5px 6px;
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
            margin-bottom: 14px;
            page-break-inside: auto;
        }
        .teacher-title {
            font-size: 10pt;
            font-weight: bold;
            margin: 10px 0 4px 0;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 2px;
            page-break-after: avoid;
        }
        .teacher-sub {
            font-size: 7.5pt;
            color: #4b5563;
            font-weight: normal;
            margin-top: 2px;
        }
        .lines {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }
        .lines thead {
            display: table-header-group;
        }
        .lines th, .lines td {
            border: 1px solid #ccc;
            padding: 3px 4px;
            vertical-align: top;
        }
        .lines th {
            background: #f3f4f6;
            font-size: 6.5pt;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }
        .lines td.num, .lines th.num {
            text-align: right;
            white-space: nowrap;
        }
        .lines td.wrap, .lines th.wrap {
            word-wrap: break-word;
            max-width: 140px;
        }
        .lines td.note {
            font-size: 6.5pt;
            color: #374151;
            max-width: 95px;
        }
        .lines td.idx {
            text-align: center;
            width: 22px;
        }
        .subtotal-row td {
            font-weight: bold;
            background: #eff6ff;
        }
        .teacher-footer {
            font-size: 7.5pt;
            color: #4b5563;
            margin-top: 2px;
        }
        .footnote {
            font-size: 7pt;
            color: #374151;
            margin-top: 14px;
            padding: 8px;
            border: 1px solid #e5e7eb;
            line-height: 1.4;
        }
    </style>
</head>
<body>
    <h1>Rapport de paie détaillé (admin)</h1>
    <p class="meta">
        <strong>Période couverte :</strong> {{ $period_range_label ?? (ucfirst($month_name).' '.$year) }}
        <br>
        Généré le {{ $generated_at }} (fuseau {{ config('app.timezone') }})
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

    <h2 class="section-sub">Détail chronologique par enseignant (toutes les lignes de la période)</h2>
    <p style="font-size: 7pt; color: #6b7280; margin: 0 0 8px 0;">
        Chaque tableau liste l’intégralité des mouvements retenus pour l’intervenant sur la période (cours et, le cas échéant, paiements carnet sans séance liée), triés par date et heure.
    </p>

    @foreach ($teachers as $t)
        <div class="teacher-block">
            <div class="teacher-title">
                {{ $t['name'] }} — ID {{ $t['id'] }}
                <div class="teacher-sub">
                    {{ $period_range_label ?? '' }}
                    · Σ <strong>{{ $t['total_duree_cours_minutes'] ?? 0 }}</strong> min de cours
                    ⇒ VH <strong>{{ $t['total_heures_cours_display'] }}</strong>
                    · À payer : <strong>{{ number_format($t['total'], 2, ',', ' ') }} €</strong>
                    (DCL {{ number_format($t['total_dcl'], 2, ',', ' ') }} € — NDCL {{ number_format($t['total_ndcl'], 2, ',', ' ') }} €)
                    @if(isset($t['lines']) && count($t['lines']))
                        · <strong>{{ count($t['lines']) }}</strong> ligne(s)
                    @endif
                </div>
            </div>
            <table class="lines">
                <thead>
                    <tr>
                        <th class="idx">#</th>
                        <th>Type</th>
                        <th>Date / heure</th>
                        <th>Réf.</th>
                        <th class="wrap">Détail</th>
                        <th class="wrap">Base (calcul)</th>
                        <th>Seg.</th>
                        <th class="num">Min</th>
                        <th class="wrap">Durée (affichage)</th>
                        <th class="num">€</th>
                        <th class="wrap">Remarque</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($t['lines'] as $idx => $line)
                        <tr>
                            <td class="idx">{{ $loop->iteration }}</td>
                            <td>{{ $line['line_type_label'] ?? ($line['kind'] === 'lesson' ? 'Cours' : 'Autre') }}</td>
                            <td>{{ $line['datetime_display'] ?? $line['date_display'] ?? '—' }}</td>
                            <td>{{ $line['reference'] ?? '—' }}</td>
                            <td class="wrap">{{ $line['label'] ?? '' }}</td>
                            <td class="wrap">{{ $line['basis_label'] ?? ($line['basis'] ?? '—') }}</td>
                            <td>{{ $line['segment'] ?? '—' }}</td>
                            <td class="num">
                                @if(isset($line['duree_minutes']) && $line['duree_minutes'] !== null)
                                    {{ (int) $line['duree_minutes'] }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="wrap" style="font-size: 7pt;">{{ $line['hours_display'] ?? '—' }}</td>
                            <td class="num">{{ number_format($line['amount'] ?? 0, 2, ',', ' ') }}</td>
                            <td class="note">{{ $line['notification'] ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11">Aucune ligne sur cette période pour cet enseignant (vérifiez les filtres du rapport).</td>
                        </tr>
                    @endforelse
                    <tr class="subtotal-row">
                        <td colspan="7" style="text-align: right;">Sous-total {{ $t['name'] }} — période</td>
                        <td class="num">{{ (int) ($t['total_duree_cours_minutes'] ?? 0) }}</td>
                        <td style="font-size: 7pt;">{{ $t['total_heures_cours_display'] ?? '—' }}</td>
                        <td class="num">{{ number_format($t['total'], 2, ',', ' ') }}</td>
                        <td>—</td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endforeach

    @if(count($teachers))
        <h2 class="section-sub">Synthèse par intervenant</h2>
        <table class="lines">
            <thead>
                <tr>
                    <th>Intervenant</th>
                    <th class="num">Σ minutes</th>
                    <th class="num">VH</th>
                    <th class="num">% attente / presté</th>
                    <th class="num">DCL (€)</th>
                    <th class="num">NDCL (€)</th>
                    <th class="num">Total (€)</th>
                    <th class="num">Nb lignes</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($teachers as $t)
                    <tr>
                        <td>{{ $t['name'] }} — ID {{ $t['id'] }}</td>
                        <td class="num">{{ (int) ($t['total_duree_cours_minutes'] ?? 0) }}</td>
                        <td class="num">{{ $t['total_heures_cours_display'] }}</td>
                        <td class="num">{{ $t['waiting_share_display'] ?? '—' }}</td>
                        <td class="num">{{ number_format($t['total_dcl'], 2, ',', ' ') }}</td>
                        <td class="num">{{ number_format($t['total_ndcl'], 2, ',', ' ') }}</td>
                        <td class="num">{{ number_format($t['total'], 2, ',', ' ') }}</td>
                        <td class="num">{{ isset($t['lines']) ? count($t['lines']) : 0 }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footnote">
        <strong>VH et minutes :</strong> le cumul officiel ajoute les <strong>minutes</strong> de chaque séance (différence
        début / fin puis conversion <strong>VH = Σ minutes ÷ 60</strong> à la fin), sans arrondir séance par séance en « heures
        décimales » avant somme — ce qui garantit que des créneaux réguliers (ex.&nbsp;: 20&nbsp;min) ne produisent pas de VH
        erronées (type 6&nbsp;×&nbsp;0,33&nbsp;h ≠ 2&nbsp;h). Les paiements carnet sans séance comptabilisée n’ajoutent pas
        de minutes. Les montants par ligne suivent la règle métier (tarif horaire sur minutes réelles lorsque disponible, puis secours montant / prix / prorata).
    </div>
</body>
</html>
