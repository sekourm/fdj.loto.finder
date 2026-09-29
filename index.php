<?php

declare(strict_types=1);

const NB_BOULES         = 49;
const NB_CHANCE         = 10;
const BOULES_PAR_GRILLE = 5;
const DOSSIER_CSV       = __DIR__ . '/csv';
const URL_FDJ           = 'https://www.sto.api.fdj.fr/anonymous/service-draw-info/v3/documentations/1a2b3c4d-9876-4562-b3fc-2c963f66af';
const DELAI_HTTP        = 30;
const LANGUE_DEFAUT     = 'en';
const LANGUES           = ['en', 'fr'];

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

const JOURS_EN = [
    'LUNDI'    => 'MONDAY',
    'MARDI'    => 'TUESDAY',
    'MERCREDI' => 'WEDNESDAY',
    'JEUDI'    => 'THURSDAY',
    'VENDREDI' => 'FRIDAY',
    'SAMEDI'   => 'SATURDAY',
    'DIMANCHE' => 'SUNDAY',
];

const TEXTES = [
    'en' => [
        'sep_decimal'          => '.',
        'format_date'          => 'd/m/Y',
        'err_langue'           => 'Invalid --lang value, expected en or fr.',
        'err_since'            => 'Invalid --since format, expected dd/mm/YYYY or YYYY-mm-dd.',
        'err_aucun_tirage'     => 'No readable draw in %s',
        'err_aucun_filtre'     => 'No draw matches the requested filters.',
        'err_dossier'          => 'Unable to create %s',
        'maj_titre'            => 'Updating FDJ data',
        'maj_fige'             => '  %s: closed period, kept',
        'maj_echec'            => '  %s: download failed, %s',
        'maj_local_conserve'   => 'local file kept',
        'maj_absent'           => 'file missing',
        'maj_a_jour'           => '  %s: already up to date',
        'maj_ecriture'         => '  %s: write failed',
        'maj_ecrit'            => '  %s: %s (%d draws)',
        'maj_mis_a_jour'       => 'updated',
        'maj_cree'             => 'created',
        'ctx_tirages'          => 'Draws analysed: %d (from %s to %s)',
        'ctx_chance'           => 'With lucky number: %d',
        'ctx_jours'            => 'Breakdown by weekday: %s',
        'ctx_fenetre'          => 'Recent window: %s',
        'ctx_fenetre_active'   => '%d draws, weight %s',
        'ctx_fenetre_inactive' => 'disabled',
        'grille_principale'    => 'Most probable grid',
        'grille_alternative'   => 'Alternative grid %d',
        'grille_ligne'         => '  %s  +  lucky %d',
        'grille_detail'        => '  Ball details (observed vs theoretical probability %s):',
        'grille_boule'         => '    %02d: %d hits, p=%s, recent=%s, gap=%d draws',
        'grille_chance'        => '    lucky %d: %d hits, p=%s (theoretical %s), recent=%s, gap=%d draws',
        'tableau_boules'       => 'Balls',
        'tableau_chance'       => 'Lucky number',
        'tableau_titre'        => '%s (theoretical probability %s)',
        'tableau_entete'       => ['No.', 'Hits', 'P global', 'P recent', 'Score', 'Gap'],
        'aide'                 => <<<TXT
        Usage: php index.php [options]

          --lang=en|fr          Output language (default en)
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

        TXT,
    ],
    'fr' => [
        'sep_decimal'          => ',',
        'format_date'          => 'd/m/Y',
        'err_langue'           => 'Valeur --lang invalide, attendu en ou fr.',
        'err_since'            => 'Format --since invalide, attendu jj/mm/AAAA ou AAAA-mm-jj.',
        'err_aucun_tirage'     => 'Aucun tirage lisible dans %s',
        'err_aucun_filtre'     => 'Aucun tirage ne correspond aux filtres demandés.',
        'err_dossier'          => 'Impossible de créer %s',
        'maj_titre'            => 'Mise à jour des données FDJ',
        'maj_fige'             => '  %s : période figée, conservé',
        'maj_echec'            => '  %s : téléchargement impossible, %s',
        'maj_local_conserve'   => 'fichier local conservé',
        'maj_absent'           => 'fichier absent',
        'maj_a_jour'           => '  %s : déjà à jour',
        'maj_ecriture'         => '  %s : écriture impossible',
        'maj_ecrit'            => '  %s : %s (%d tirages)',
        'maj_mis_a_jour'       => 'mis à jour',
        'maj_cree'             => 'créé',
        'ctx_tirages'          => 'Tirages analysés : %d (du %s au %s)',
        'ctx_chance'           => 'Avec numéro chance : %d',
        'ctx_jours'            => 'Répartition par jour : %s',
        'ctx_fenetre'          => 'Fenêtre récente : %s',
        'ctx_fenetre_active'   => '%d tirages, poids %s',
        'ctx_fenetre_inactive' => 'désactivée',
        'grille_principale'    => 'Grille la plus probable',
        'grille_alternative'   => 'Grille alternative %d',
        'grille_ligne'         => '  %s  +  chance %d',
        'grille_detail'        => '  Détail des boules (probabilité observée vs théorique %s) :',
        'grille_boule'         => '    %02d : %d sorties, p=%s, récent=%s, écart=%d tirages',
        'grille_chance'        => '    chance %d : %d sorties, p=%s (théorique %s), récent=%s, écart=%d tirages',
        'tableau_boules'       => 'Boules',
        'tableau_chance'       => 'Numéro chance',
        'tableau_titre'        => '%s (probabilité théorique %s)',
        'tableau_entete'       => ['N°', 'Sorties', 'P globale', 'P récente', 'Score', 'Écart'],
        'aide'                 => <<<TXT
        Usage : php index.php [options]

          --lang=en|fr          Langue d'affichage (en par défaut)
          --since=jj/mm/AAAA    Ne garder que les tirages à partir de cette date (AAAA-mm-jj accepté aussi)
          --day=SAMEDI          Ne garder que les tirages d'un jour de la semaine (LUNDI, MERCREDI, SAMEDI… ou MONDAY, WEDNESDAY, SATURDAY…)
          --window=N            Nombre de tirages les plus récents pour la composante récente (100 par défaut, 0 = désactivée)
          --recent-weight=X     Poids de la composante récente entre 0 et 1 (0.3 par défaut)
          --grids=N             Générer N grilles alternatives tirées proportionnellement aux probabilités
          --seed=N              Graine pour rendre les grilles alternatives reproductibles
          --offline             Ne pas interroger le site FDJ, utiliser uniquement les fichiers présents dans csv/
          --force-update        Retélécharger toutes les archives FDJ, y compris les périodes figées
          --stats               Afficher le tableau complet des probabilités de chaque numéro
          --help, -h            Afficher cette aide

        TXT,
    ],
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
    fwrite(STDERR, t('err_aucun_tirage', DOSSIER_CSV) . PHP_EOL);
    exit(1);
}

$tirages = filtrerTirages($tirages, $options['depuis'], $options['jour']);

if ($tirages === []) {
    fwrite(STDERR, t('err_aucun_filtre') . PHP_EOL);
    exit(1);
}

$statsBoules = calculerProbabilites($tirages, 'boules', NB_BOULES, $options['fenetre'], $options['poids_recent']);
$statsChance = calculerProbabilites($tirages, 'chance', NB_CHANCE, $options['fenetre'], $options['poids_recent']);

$grillePrincipale = meilleureGrille($statsBoules, $statsChance);
$pTheorique       = probabiliteTheorique($tirages);

afficherContexte($tirages, $options);
afficherGrille(t('grille_principale'), $grillePrincipale, $statsBoules, $statsChance, $pTheorique);

if ($options['grilles'] > 0) {
    mt_srand($options['seed'] ?? random_int(1, PHP_INT_MAX));
    for ($i = 1; $i <= $options['grilles']; $i++) {
        $grille = grillePonderee($statsBoules, $statsChance);
        afficherGrille(t('grille_alternative', $i), $grille, $statsBoules, $statsChance, $pTheorique);
    }
}

if ($options['stats']) {
    afficherTableau(t('tableau_boules'), $statsBoules, $pTheorique);
    afficherTableau(t('tableau_chance'), $statsChance, 1 / NB_CHANCE);
}

echo PHP_EOL;

function langue(?string $nouvelle = null): string
{
    static $langue = LANGUE_DEFAUT;

    if ($nouvelle !== null) {
        $langue = $nouvelle;
    }

    return $langue;
}

function t(string $cle, mixed ...$args): string
{
    $texte = TEXTES[langue()][$cle] ?? TEXTES[LANGUE_DEFAUT][$cle] ?? $cle;

    return $args === [] ? $texte : sprintf($texte, ...$args);
}

function nomJour(string $jour): string
{
    return langue() === 'fr' ? $jour : (JOURS_EN[$jour] ?? $jour);
}

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

    $arguments = array_slice($argv, 1);

    foreach ($arguments as $arg) {
        if (!str_starts_with($arg, '--lang=')) {
            continue;
        }
        $valeur = strtolower(trim(substr($arg, 7)));
        if (!in_array($valeur, LANGUES, true)) {
            fwrite(STDERR, t('err_langue') . PHP_EOL);
            exit(1);
        }
        langue($valeur);
    }

    foreach ($arguments as $arg) {
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
                    fwrite(STDERR, t('err_since') . PHP_EOL);
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
    echo t('aide');
}

function mettreAJourCsv(string $dossier, bool $forcer): void
{
    if (!is_dir($dossier) && !mkdir($dossier, 0775, true)) {
        fwrite(STDERR, t('err_dossier', $dossier) . PHP_EOL);
        return;
    }

    echo PHP_EOL . t('maj_titre') . PHP_EOL;

    foreach (ARCHIVES as $nom => $archive) {
        $cible = $dossier . '/' . $nom;
        if ($archive['fixe'] && !$forcer && is_file($cible)) {
            echo t('maj_fige', $nom) . PHP_EOL;
            continue;
        }

        $contenu = telechargerCsv(URL_FDJ . $archive['id']);
        if ($contenu === null) {
            echo t('maj_echec', $nom, t(is_file($cible) ? 'maj_local_conserve' : 'maj_absent')) . PHP_EOL;
            continue;
        }

        if (is_file($cible) && hash_file('sha256', $cible) === hash('sha256', $contenu)) {
            echo t('maj_a_jour', $nom) . PHP_EOL;
            continue;
        }

        $existait = is_file($cible);
        if (file_put_contents($cible, $contenu) === false) {
            echo t('maj_ecriture', $nom) . PHP_EOL;
            continue;
        }

        $lignes = max(0, substr_count($contenu, "\n") - 1);
        echo t('maj_ecrit', $nom, t($existait ? 'maj_mis_a_jour' : 'maj_cree'), $lignes) . PHP_EOL;
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
    $premier = $tirages[0]['date']->format(t('format_date'));
    $dernier = $tirages[count($tirages) - 1]['date']->format(t('format_date'));
    $jours   = array_count_values(array_column($tirages, 'jour'));
    ksort($jours);

    $repartition = implode(', ', array_map(fn($j, $n) => nomJour($j) . ' ' . $n, array_keys($jours), $jours));
    $fenetre     = $options['fenetre'] > 0
        ? t('ctx_fenetre_active', $options['fenetre'], nombre($options['poids_recent'], 1))
        : t('ctx_fenetre_inactive');

    echo PHP_EOL;
    echo t('ctx_tirages', count($tirages), $premier, $dernier) . PHP_EOL;
    echo t('ctx_chance', compterAvecChance($tirages)) . PHP_EOL;
    echo t('ctx_jours', $repartition) . PHP_EOL;
    echo t('ctx_fenetre', $fenetre) . PHP_EOL;
}

function afficherGrille(string $titre, array $grille, array $statsBoules, array $statsChance, float $attenduBoule): void
{
    $boules = implode(' - ', array_map(fn($b) => sprintf('%02d', $b), $grille['boules']));

    echo PHP_EOL . $titre . PHP_EOL;
    echo t('grille_ligne', $boules, $grille['chance']) . PHP_EOL;
    echo t('grille_detail', pourcent($attenduBoule)) . PHP_EOL;
    foreach ($grille['boules'] as $boule) {
        $s = $statsBoules[$boule];
        echo t('grille_boule', $boule, $s['sorties'], pourcent($s['p_global']), pourcent($s['p_recent']), $s['ecart']) . PHP_EOL;
    }
    $c = $statsChance[$grille['chance']];
    echo t(
        'grille_chance',
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
    $entete = TEXTES[langue()]['tableau_entete'];

    echo PHP_EOL . t('tableau_titre', $titre, pourcent($attendu)) . PHP_EOL;
    echo sprintf('  %-4s %8s %10s %10s %10s %6s', ...$entete) . PHP_EOL;
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

function nombre(float $valeur, int $decimales): string
{
    return number_format($valeur, $decimales, t('sep_decimal'), '');
}

function pourcent(float $valeur): string
{
    return nombre($valeur * 100, 2) . ' %';
}
