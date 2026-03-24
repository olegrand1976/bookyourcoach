#!/usr/bin/env php
<?php

/**
 * Répare les exports phpMyAdmin où les PRIMARY KEY sont uniquement en fin de fichier
 * (ALTER TABLE … ADD PRIMARY KEY). Si l’import s’arrête avant cette section ou si
 * phpMyAdmin exécute les contraintes trop tôt, MySQL renvoie :
 *   Error 1822: Missing index for constraint … in the referenced table 'clubs'
 *
 * Usage :
 *   php scripts/fix-phpmyadmin-mysql-dump.php /chemin/vers/dump.sql > dump-fixed.sql
 *   php scripts/fix-phpmyadmin-mysql-dump.php /chemin/vers/dump.sql --in-place
 */

declare(strict_types=1);

$argv = $_SERVER['argv'] ?? [];
$inPlace = in_array('--in-place', $argv, true);
$paths = array_values(array_filter($argv, static fn ($a) => $a !== '--in-place' && !str_starts_with($a, '-')));
$path = $paths[1] ?? null;

if ($path === null || !is_readable($path)) {
    fwrite(STDERR, "Usage: php fix-phpmyadmin-mysql-dump.php <dump.sql> [--in-place]\n");
    exit(1);
}

$sql = file_get_contents($path);
if ($sql === false) {
    fwrite(STDERR, "Fichier illisible: {$path}\n");
    exit(1);
}

$lines = preg_split('/\R/', $sql) ?: [];
$out = [];
$n = count($lines);
/** @var array<string, true> Tables où une PRIMARY KEY (`id`) a été ajoutée dans le CREATE (bigint). */
$inlinePkTables = [];
/** Table courante pour un bloc ALTER (nom sans backticks). */
$alterTable = null;

for ($i = 0; $i < $n; $i++) {
    $line = $lines[$i];
    if (preg_match('/^CREATE TABLE `/', $line)) {
        $buf = [$line];
        $hasPk = false;
        $hasBigintId = false;
        $j = $i + 1;
        while ($j < $n) {
            $l = $lines[$j];
            if (preg_match('/`id`\s+bigint\s+UNSIGNED\s+NOT\s+NULL/i', $l)) {
                $hasBigintId = true;
            }
            if (preg_match('/\bPRIMARY\s+KEY\b/i', $l)) {
                $hasPk = true;
            }
            if (preg_match('/^\s*\)\s*ENGINE=InnoDB/i', $l)) {
                if ($hasBigintId && ! $hasPk) {
                    if (preg_match('/^CREATE TABLE `([^`]+)`/', $buf[0], $tm)) {
                        $inlinePkTables[$tm[1]] = true;
                    }
                    $lastIdx = count($buf) - 1;
                    $buf[$lastIdx] = rtrim($buf[$lastIdx], " \t");
                    if ($buf[$lastIdx] !== '' && ! str_ends_with($buf[$lastIdx], ',')) {
                        $buf[$lastIdx] .= ',';
                    }
                    $buf[] = '  PRIMARY KEY (`id`)';
                }
                $buf[] = $l;
                array_push($out, ...$buf);
                $i = $j;
                break;
            }
            $buf[] = $l;
            $j++;
        }
        continue;
    }

    if (preg_match('/^ALTER TABLE `([^`]+)`\s*$/', $line, $am)) {
        $alterTable = $am[1];
        $out[] = $line;

        continue;
    }

    // Ne retirer ADD PRIMARY KEY (`id`) que si la même table a déjà reçu la PK dans le CREATE.
    // Sinon (ex. job_batches.id en varchar) il faut garder l’ALTER.
    if ($alterTable !== null
        && isset($inlinePkTables[$alterTable])
        && preg_match('/^\s*ADD PRIMARY KEY \(`id`\)\s*(,?)\s*(;)?\s*$/', $line, $m)) {
        $endsWithComma = ($m[1] ?? '') === ',';
        $endsWithSemi = ($m[2] ?? '') === ';';
        if ($endsWithSemi && ! $endsWithComma && ! empty($out)) {
            $last = count($out) - 1;
            if (preg_match('/^ALTER TABLE `/', $out[$last])) {
                array_pop($out);
            }
            $alterTable = null;
        }

        continue;
    }

    $out[] = $line;
}

$fixed = implode("\n", $out);

// Dump incohérent : deux FK sur bookings.student_id (users + students). Laravel utilise students.
$fixed = preg_replace(
    '/\n\s*ADD CONSTRAINT `bookings_student_id_foreign` FOREIGN KEY \(`student_id`\) REFERENCES `users` \(`id`\) ON DELETE CASCADE,\s*\n/',
    "\n",
    $fixed
) ?? $fixed;

if ($inPlace) {
    if (file_put_contents($path, $fixed) === false) {
        fwrite(STDERR, "Écriture impossible: {$path}\n");
        exit(1);
    }
    fwrite(STDERR, "OK — fichier mis à jour: {$path}\n");
} else {
    echo $fixed;
}
