<?php

declare(strict_types=1);

const NB_BOULES         = 49;
const NB_CHANCE         = 10;
const BOULES_PAR_GRILLE = 5;
const DOSSIER_CSV       = __DIR__ . '/csv';
const URL_FDJ           = 'https://www.sto.api.fdj.fr/anonymous/service-draw-info/v3/documentations/1a2b3c4d-9876-4562-b3fc-2c963f66af';
const DELAI_HTTP        = 30;

const ARCHIVES = [
    '1976-2008.csv'       => ['id' => 'l6', 'fixe' => true],
    '2008-2017.csv'       => ['id' => 'm6', 'fixe' => true],
    '2017-2019.csv'       => ['id' => 'n6', 'fixe' => true],
    '2019.csv'            => ['id' => 'o6', 'fixe' => true],
    '2019-aujourdhui.csv' => ['id' => 'p6', 'fixe' => false],
];

const JOURS = [
    'LU' => 'LUNDI',
    'MA' => 'MARDI',
    'ME' => 'MERCREDI',
    'JE' => 'JEUDI',
    'VE' => 'VENDREDI',
    'SA' => 'SAMEDI',
    'DI' => 'DIMANCHE',
    'MONDAY'    => 'LUNDI',
    'TUESDAY'   => 'MARDI',
    'WEDNESDAY' => 'MERCREDI',
    'THURSDAY'  => 'JEUDI',
    'FRIDAY'    => 'VENDREDI',
    'SATURDAY'  => 'SAMEDI',
    'SUNDAY'    => 'DIMANCHE',
];

$options = lireOptions($argv ?? []);

if ($options['aide']) {
    afficherAide();
    exit(0);
}

if (!$options['hors_ligne']) {
    mettreAJourCsv(DOSSIER_CSV, $options['forcer_maj']);
}

$tirages = chargerTirages(DOSSIER_CSV);

if ($tirages === []) {
    fwrite(STDERR, 'Aucun tirage lisible dans ' . DOSSIER_CSV . PHP_EOL);
    exit(1);
}

$tirages = filtrerTirages($tirages, $options['depuis'], $options['jour']);

if ($tirages === []) {
    fwrite(STDERR, 'Aucun tirage ne correspond aux filtres demandés.' . PHP_EOL);
    exit(1);
}

$statsBoules = calculerProbabilites($tirages, 'boules', NB_BOULES, $options['fenetre'], $options['poids_recent']);
$statsChance = calculerProbabilites($tirages, 'chance', NB_CHANCE, $options['fenetre'], $options['poids_recent']);

$grillePrincipale = meilleureGrille($statsBoules, $statsChance);
$pTheorique       = probabiliteTheorique($tirages);

afficherContexte($tirages, $options);
afficherGrille('Grille la plus probable', $grillePrincipale, $statsBoules, $statsChance, $pTheorique);

if ($options['grilles'] > 0) {
    mt_srand($options['seed'] ?? random_int(1, PHP_INT_MAX));
    for ($i = 1; $i <= $options['grilles']; $i++) {
        $grille = grillePonderee($statsBoules, $statsChance);
        afficherGrille('Grille alternative ' . $i, $grille, $statsBoules, $statsChance, $pTheorique);
    }
}

if ($options['stats']) {
    afficherTableau('Boules', $statsBoules, $pTheorique);
    afficherTableau('Numéro chance', $statsChance, 1 / NB_CHANCE);
}

afficherRappelTheorique();

function lireOptions(array $argv): array
{
    $options = [
        'aide'         => false,
        'hors_ligne'   => false,
        'forcer_maj'   => false,
        'stats'        => false,
        'depuis'       => null,
        'jour'         => null,
        'fenetre'      => 100,
        'poids_recent' => 0.3,
        'grilles'      => 0,
        'seed'         => null,
    ];

    foreach (array_slice($argv, 1) as $arg) {
        if ($arg === '--help' || $arg === '-h') {
            $options['aide'] = true;
            continue;
        }
        if ($arg === '--offline') {
            $options['hors_ligne'] = true;
            continue;
        }
        if ($arg === '--force-update') {
            $options['forcer_maj'] = true;
            continue;
        }
        if ($arg === '--stats') {
            $options['stats'] = true;
            continue;
        }
        if (!str_contains($arg, '=')) {
            continue;
        }
        [$cle, $valeur] = explode('=', $arg, 2);
        switch ($cle) {
            case '--since':
                $date = DateTimeImmutable::createFromFormat('!d/m/Y', $valeur) ?: DateTimeImmutable::createFromFormat('!Y-m-d', $valeur);
                if ($date === false) {
                    fwrite(STDERR, 'Invalid --since format, expected dd/mm/YYYY or YYYY-mm-dd.' . PHP_EOL);
                    exit(1);
                }
                $options['depuis'] = $date;
                break;
            case '--day':
                $jour = strtoupper(trim($valeur));
                $options['jour'] = JOURS[$jour] ?? $jour;
                break;
            case '--window':
                $options['fenetre'] = max(0, (int) $valeur);
                break;
            case '--recent-weight':
                $options['poids_recent'] = min(1.0, max(0.0, (float) $valeur));
                break;
            case '--grids':
                $options['grilles'] = max(0, (int) $valeur);
                break;
            case '--seed':
                $options['seed'] = (int) $valeur;
                break;
        }
    }

    return $options;
}

function afficherAide(): void
{
    echo <<<TXT
    Usage: php index.php [options]

      --since=dd/mm/YYYY    Keep only draws from this date onwards (YYYY-mm-dd also accepted)
      --day=SATURDAY        Keep only draws of one weekday (MONDAY, WEDNESDAY, SATURDAY… or LUNDI, MERCREDI, SAMEDI…)
      --window=N            Number of most recent draws for the recent component (default 100, 0 = disabled)
      --recent-weight=X     Weight of the recent component between 0 and 1 (default 0.3)
      --grids=N             Generate N alternative grids sampled proportionally to the probabilities
      --seed=N              Seed to make the alternative grids reproducible
      --offline             Do not query the FDJ website, use only the files present in csv/
      --force-update        Re-download every FDJ archive, including closed periods
      --stats               Print the full probability table for every number
      --help, -h            Show this help

    TXT;
}

function mettreAJourCsv(string $dossier, bool $forcer): void
{
    if (!is_dir($dossier) && !mkdir($dossier, 0775, true)) {
        fwrite(STDERR, 'Impossible de créer ' . $dossier . PHP_EOL);
        return;
    }

    echo PHP_EOL . 'Mise à jour des données FDJ' . PHP_EOL;

    foreach (ARCHIVES as $nom => $archive) {
        $cible = $dossier . '/' . $nom;
        if ($archive['fixe'] && !$forcer && is_file($cible)) {
            echo '  ' . $nom . ' : période figée, conservé' . PHP_EOL;
            continue;
        }

        $contenu = telechargerCsv(URL_FDJ . $archive['id']);
        if ($contenu === null) {
            echo '  ' . $nom . ' : téléchargement impossible, ' . (is_file($cible) ? 'fichier local conservé' : 'fichier absent') . PHP_EOL;
            continue;
        }

        if (is_file($cible) && hash_file('sha256', $cible) === hash('sha256', $contenu)) {
            echo '  ' . $nom . ' : déjà à jour' . PHP_EOL;
            continue;
        }

        $existait = is_file($cible);
        if (file_put_contents($cible, $contenu) === false) {
            echo '  ' . $nom . ' : écriture impossible' . PHP_EOL;
            continue;
        }

        $lignes = max(0, substr_count($contenu, "\n") - 1);
        echo '  ' . $nom . ' : ' . ($existait ? 'mis à jour' : 'créé') . ' (' . $lignes . ' tirages)' . PHP_EOL;
    }
}

function telechargerCsv(string $url): ?string
{
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => DELAI_HTTP,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_USERAGENT      => 'loto-stats/1.0',
    ]);
    $zip  = curl_exec($curl);
    $code = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);

    if (!is_string($zip) || $code !== 200 || $zip === '') {
        return null;
    }

    $temporaire = tempnam(sys_get_temp_dir(), 'loto');
    if ($temporaire === false || file_put_contents($temporaire, $zip) === false) {
        return null;
    }

    $contenu = extraireCsv($temporaire);
    unlink($temporaire);

    return $contenu;
}

function extraireCsv(string $fichierZip): ?string
{
    $archive = new ZipArchive();
    if ($archive->open($fichierZip) !== true) {
        return null;
    }

    $contenu = null;
    for ($i = 0; $i < $archive->numFiles; $i++) {
        $nom = $archive->getNameIndex($i);
        if ($nom !== false && str_ends_with(strtolower($nom), '.csv')) {
            $lu = $archive->getFromIndex($i);
            $contenu = $lu === false ? null : $lu;
            break;
        }
    }
    $archive->close();

    return $contenu;
}

function chargerTirages(string $dossier): array
{
    $fichiers = glob($dossier . '/*.csv') ?: [];
    sort($fichiers);

    $tirages = [];
    foreach ($fichiers as $fichier) {
        foreach (lireFichier($fichier) as $tirage) {
            $cle = $tirage['date']->format('Ymd') . '-' . implode('-', $tirage['boules']);
            $tirages[$cle] = $tirage;
        }
    }

    usort($tirages, fn(array $a, array $b) => $a['date'] <=> $b['date']);

    return array_values($tirages);
}

function lireFichier(string $fichier): array
{
    $handle = fopen($fichier, 'r');
    if ($handle === false) {
        return [];
    }

    $entete = fgetcsv($handle, 0, ';', '"', '\\');
    if ($entete === false) {
        fclose($handle);
        return [];
    }

    $entete       = array_map('trim', $entete);
    $index        = array_flip($entete);
    $colonnesBoul = array_values(array_filter($entete, fn(string $c) => preg_match('/^boule_\d$/', $c) === 1));
    $lignes       = [];

    while (($ligne = fgetcsv($handle, 0, ';', '"', '\\')) !== false) {
        if (!isset($index['date_de_tirage'], $ligne[$index['date_de_tirage']])) {
            continue;
        }
        $date = analyserDate(trim($ligne[$index['date_de_tirage']]));
        if ($date === null) {
            continue;
        }

        $boules = [];
        foreach ($colonnesBoul as $colonne) {
            $valeur = (int) ($ligne[$index[$colonne]] ?? 0);
            if ($valeur >= 1 && $valeur <= NB_BOULES) {
                $boules[] = $valeur;
            }
        }
        if (count($boules) < BOULES_PAR_GRILLE) {
            continue;
        }
        sort($boules);

        $chance = null;
        if (isset($index['numero_chance'])) {
            $valeur = (int) ($ligne[$index['numero_chance']] ?? 0);
            $chance = ($valeur >= 1 && $valeur <= NB_CHANCE) ? $valeur : null;
        }

        $jour = strtoupper(trim((string) ($ligne[$index['jour_de_tirage']] ?? '')));
        $jour = JOURS[$jour] ?? $jour;

        $lignes[] = [
            'date'   => $date,
            'jour'   => $jour,
            'boules' => $boules,
            'chance' => $chance,
        ];
    }

    fclose($handle);

    return $lignes;
}

function analyserDate(string $valeur): ?DateTimeImmutable
{
    foreach (['!d/m/Y', '!Ymd'] as $format) {
        $date = DateTimeImmutable::createFromFormat($format, $valeur);
        if ($date !== false) {
            return $date;
        }
    }

    return null;
}

function filtrerTirages(array $tirages, ?DateTimeImmutable $depuis, ?string $jour): array
{
    return array_values(array_filter($tirages, function (array $tirage) use ($depuis, $jour) {
        if ($depuis !== null && $tirage['date'] < $depuis) {
            return false;
        }
        if ($jour !== null && $tirage['jour'] !== $jour) {
            return false;
        }
        return true;
    }));
}

function probabiliteTheorique(array $tirages): float
{
    $boules = array_sum(array_map(fn(array $t) => count($t['boules']), $tirages));

    return $tirages === [] ? 0.0 : $boules / count($tirages) / NB_BOULES;
}

function compterAvecChance(array $tirages): int
{
    return count(array_filter($tirages, fn(array $t) => $t['chance'] !== null));
}

function calculerProbabilites(array $tirages, string $type, int $max, int $fenetre, float $poidsRecent): array
{
    $global  = array_fill(1, $max, 0);
    $recent  = array_fill(1, $max, 0);
    $nGlobal = 0;
    $nRecent = 0;
    $dernier = array_fill(1, $max, null);
    $total   = count($tirages);

    foreach ($tirages as $position => $tirage) {
        $valeurs = $type === 'boules' ? $tirage['boules'] : ($tirage['chance'] === null ? [] : [$tirage['chance']]);
        if ($valeurs === []) {
            continue;
        }
        $estRecent = $fenetre > 0 && $position >= $total - $fenetre;
        $nGlobal++;
        if ($estRecent) {
            $nRecent++;
        }
        foreach ($valeurs as $valeur) {
            $global[$valeur]++;
            $dernier[$valeur] = $position;
            if ($estRecent) {
                $recent[$valeur]++;
            }
        }
    }

    $poidsRecent = $nRecent > 0 ? $poidsRecent : 0.0;
    $stats       = [];

    for ($numero = 1; $numero <= $max; $numero++) {
        $pGlobal = $nGlobal > 0 ? $global[$numero] / $nGlobal : 0.0;
        $pRecent = $nRecent > 0 ? $recent[$numero] / $nRecent : 0.0;
        $stats[$numero] = [
            'sorties'     => $global[$numero],
            'p_global'    => $pGlobal,
            'p_recent'    => $pRecent,
            'score'       => (1 - $poidsRecent) * $pGlobal + $poidsRecent * $pRecent,
            'ecart'       => $dernier[$numero] === null ? $total : $total - 1 - $dernier[$numero],
        ];
    }

    uasort($stats, function (array $a, array $b) {
        return $b['score'] <=> $a['score'] ?: $b['sorties'] <=> $a['sorties'] ?: $b['ecart'] <=> $a['ecart'];
    });

    return $stats;
}

function meilleureGrille(array $statsBoules, array $statsChance): array
{
    $boules = array_slice(array_keys($statsBoules), 0, BOULES_PAR_GRILLE);
    sort($boules);

    return [
        'boules' => $boules,
        'chance' => array_key_first($statsChance),
    ];
}

function grillePonderee(array $statsBoules, array $statsChance): array
{
    $boules = [];
    $poids  = array_map(fn(array $s) => $s['score'], $statsBoules);

    while (count($boules) < BOULES_PAR_GRILLE && $poids !== []) {
        $numero = tirerPondere($poids);
        $boules[] = $numero;
        unset($poids[$numero]);
    }
    sort($boules);

    $poidsChance = array_map(fn(array $s) => $s['score'], $statsChance);

    return [
        'boules' => $boules,
        'chance' => tirerPondere($poidsChance),
    ];
}

function tirerPondere(array $poids): int
{
    $somme = array_sum($poids);
    if ($somme <= 0) {
        return (int) array_rand($poids);
    }
    $cible = mt_rand() / mt_getrandmax() * $somme;
    $cumul = 0.0;
    foreach ($poids as $numero => $valeur) {
        $cumul += $valeur;
        if ($cible <= $cumul) {
            return (int) $numero;
        }
    }

    return (int) array_key_last($poids);
}

function afficherContexte(array $tirages, array $options): void
{
    $premier = $tirages[0]['date']->format('d/m/Y');
    $dernier = $tirages[count($tirages) - 1]['date']->format('d/m/Y');
    $jours   = array_count_values(array_column($tirages, 'jour'));
    ksort($jours);

    echo PHP_EOL;
    echo 'Tirages analysés : ' . count($tirages) . ' (du ' . $premier . ' au ' . $dernier . ')' . PHP_EOL;
    echo 'Avec numéro chance : ' . compterAvecChance($tirages) . PHP_EOL;
    echo 'Répartition par jour : ' . implode(', ', array_map(fn($j, $n) => $j . ' ' . $n, array_keys($jours), $jours)) . PHP_EOL;
    echo 'Fenêtre récente : ' . ($options['fenetre'] > 0 ? $options['fenetre'] . ' tirages, poids ' . $options['poids_recent'] : 'désactivée') . PHP_EOL;
}

function afficherGrille(string $titre, array $grille, array $statsBoules, array $statsChance, float $attenduBoule): void
{
    echo PHP_EOL . $titre . PHP_EOL;
    echo '  ' . implode(' - ', array_map(fn($b) => sprintf('%02d', $b), $grille['boules'])) . '  +  chance ' . $grille['chance'] . PHP_EOL;
    echo '  Détail des boules (probabilité observée vs théorique ' . pourcent($attenduBoule) . ') :' . PHP_EOL;
    foreach ($grille['boules'] as $boule) {
        $s = $statsBoules[$boule];
        echo sprintf(
            '    %02d : %d sorties, p=%s, récent=%s, écart=%d tirages',
            $boule,
            $s['sorties'],
            pourcent($s['p_global']),
            pourcent($s['p_recent']),
            $s['ecart']
        ) . PHP_EOL;
    }
    $c = $statsChance[$grille['chance']];
    echo sprintf(
        '    chance %d : %d sorties, p=%s (théorique %s), récent=%s, écart=%d tirages',
        $grille['chance'],
        $c['sorties'],
        pourcent($c['p_global']),
        pourcent(1 / NB_CHANCE),
        pourcent($c['p_recent']),
        $c['ecart']
    ) . PHP_EOL;
}

function afficherTableau(string $titre, array $stats, float $attendu): void
{
    echo PHP_EOL . $titre . ' (probabilité théorique ' . pourcent($attendu) . ')' . PHP_EOL;
    echo sprintf('  %-4s %8s %10s %10s %10s %6s', 'N°', 'Sorties', 'P globale', 'P récente', 'Score', 'Écart') . PHP_EOL;
    foreach ($stats as $numero => $s) {
        echo sprintf(
            '  %-4s %8d %10s %10s %10s %6d',
            sprintf('%02d', $numero),
            $s['sorties'],
            pourcent($s['p_global']),
            pourcent($s['p_recent']),
            pourcent($s['score']),
            $s['ecart']
        ) . PHP_EOL;
    }
}

function afficherRappelTheorique(): void
{
    $combinaisons = combinaisons(NB_BOULES, BOULES_PAR_GRILLE) * NB_CHANCE;

    echo PHP_EOL;
    echo 'Rappel : chaque tirage est indépendant, toute grille a exactement 1 chance sur '
        . number_format($combinaisons, 0, ',', ' ')
        . ' de sortir. Les probabilités ci-dessus décrivent le passé, pas l\'avenir.' . PHP_EOL . PHP_EOL;
}

function combinaisons(int $n, int $k): float
{
    $resultat = 1.0;
    for ($i = 1; $i <= $k; $i++) {
        $resultat = $resultat * ($n - $k + $i) / $i;
    }

    return round($resultat);
}

function pourcent(float $valeur): string
{
    return number_format($valeur * 100, 2, ',', '') . ' %';
}
