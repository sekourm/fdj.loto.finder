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
const LARGEUR_SOMME     = 20;
const SEUIL_ATYPIQUE    = 0.20;
const TAILLE_POOL       = 12;
const ESSAIS_TIRAGE     = 200;
const MAX_DATE          = 31;
const BACKTEST_MIN      = 100;
const BACKTEST_REP      = 20;

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
        'ctx_typicite'         => 'Typicity filter: %s',
        'ctx_typicite_active'  => 'rejects the %s rarest grid profiles and popular patterns, pool of %d numbers',
        'grille_principale'    => 'Recommended grid',
        'grille_alternative'   => 'Alternative grid %d',
        'grille_ligne'         => '  %s  +  lucky %d',
        'grille_profil'        => '  Profile: %d even, %d consecutive pair(s), %d decades, sum %d, shared by %s of all grids',
        'grille_populaire'     => '  Popular pattern: %s',
        'motif_dates'          => 'every number is 31 or below (birth dates)',
        'motif_progression'    => 'constant step between the numbers',
        'grille_detail'        => '  Ball details (observed vs theoretical probability %s):',
        'grille_boule'         => '    %02d: %d hits, p=%s, recent=%s, gap=%d draws',
        'grille_chance'        => '    lucky %d: %d hits, p=%s (theoretical %s), recent=%s, gap=%d draws',
        'tableau_boules'       => 'Balls',
        'tableau_chance'       => 'Lucky number',
        'tableau_titre'        => '%s (theoretical probability %s)',
        'tableau_entete'       => ['No.', 'Hits', 'P global', 'P recent', 'Score', 'Gap'],
        'profils_titre'        => 'Grid profiles (theoretical over the %s possible grids vs observed over %d five-ball draws)',
        'profils_entete'       => ['Value', 'Theoretical', 'Observed'],
        'profil_pairs'         => 'Even numbers',
        'profil_suites'        => 'Consecutive pairs',
        'profil_dizaines'      => 'Distinct decades',
        'profil_sommes'        => 'Sum of the 5 balls',
        'bt_titre'             => 'Backtest over %d five-ball draws, each grid built only from the draws before it',
        'bt_entete'            => ['Method', 'Grids', 'Avg hits', 'P(>=2)', 'P(>=3)'],
        'bt_theorie'           => 'theory, any grid',
        'bt_aleatoire'         => 'uniform random grid',
        'bt_chaud'             => 'hot: 5 most frequent overall',
        'bt_recent'            => 'hot: 5 most frequent in the recent window',
        'bt_mix'               => 'frequency mix, top 5 without filter',
        'bt_froid'             => 'cold: 5 largest gaps',
        'bt_recommande'        => 'recommended grid (mix + typicity filter)',
        'bt_echant'            => 'alternative grids (weighted sampling + filter)',
        'bt_chance'            => 'Lucky number hit rate over %d draws (theory 10 %%): random %s, hottest %s, coldest %s',
        'bt_bande'             => 'Noise band for the average over %d grids: %s to %s (2 standard deviations). A method only beats chance outside this band.',
        'aide'                 => <<<TXT
        Usage: php index.php [options]

          --lang=en|fr          Output language (default en)
          --since=dd/mm/YYYY    Keep only draws from this date onwards (YYYY-mm-dd also accepted)
          --day=SATURDAY        Keep only draws of one weekday (MONDAY, WEDNESDAY, SATURDAY… or LUNDI, MERCREDI, SAMEDI…)
          --window=N            Number of most recent draws for the recent component (default 100, 0 = disabled)
          --recent-weight=X     Weight of the recent component between 0 and 1 (default 0.3)
          --pool=N              Number of top-scored balls the recommended grid is picked from (default 12)
          --grids=N             Generate N alternative grids sampled proportionally to the probabilities
          --seed=N              Seed to make the alternative grids reproducible
          --backtest            Replay the history and compare grid-selection methods on the real draws
          --offline             Do not query the FDJ website, use only the files present in csv/
          --force-update        Re-download every FDJ archive, including closed periods
          --stats               Print the full probability table for every number and the grid profile table
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
        'ctx_typicite'         => 'Filtre de typicité : %s',
        'ctx_typicite_active'  => 'écarte les %s de profils de grille les plus rares et les motifs populaires, réservoir de %d numéros',
        'grille_principale'    => 'Grille recommandée',
        'grille_alternative'   => 'Grille alternative %d',
        'grille_ligne'         => '  %s  +  chance %d',
        'grille_profil'        => '  Profil : %d pairs, %d suite(s), %d dizaines, somme %d, partagé par %s des grilles possibles',
        'grille_populaire'     => '  Motif populaire : %s',
        'motif_dates'          => 'tous les numéros inférieurs ou égaux à 31 (dates de naissance)',
        'motif_progression'    => 'pas constant entre les numéros',
        'grille_detail'        => '  Détail des boules (probabilité observée vs théorique %s) :',
        'grille_boule'         => '    %02d : %d sorties, p=%s, récent=%s, écart=%d tirages',
        'grille_chance'        => '    chance %d : %d sorties, p=%s (théorique %s), récent=%s, écart=%d tirages',
        'tableau_boules'       => 'Boules',
        'tableau_chance'       => 'Numéro chance',
        'tableau_titre'        => '%s (probabilité théorique %s)',
        'tableau_entete'       => ['N°', 'Sorties', 'P globale', 'P récente', 'Score', 'Écart'],
        'profils_titre'        => 'Profils de grille (théorique sur les %s grilles possibles vs observé sur %d tirages à 5 boules)',
        'profils_entete'       => ['Valeur', 'Théorique', 'Observé'],
        'profil_pairs'         => 'Numéros pairs',
        'profil_suites'        => 'Suites (couples consécutifs)',
        'profil_dizaines'      => 'Dizaines distinctes',
        'profil_sommes'        => 'Somme des 5 boules',
        'bt_titre'             => 'Backtest sur %d tirages à 5 boules, chaque grille construite uniquement avec les tirages précédents',
        'bt_entete'            => ['Méthode', 'Grilles', 'Moy. trouvés', 'P(>=2)', 'P(>=3)'],
        'bt_theorie'           => 'théorie, grille quelconque',
        'bt_aleatoire'         => 'grille aléatoire uniforme',
        'bt_chaud'             => 'chaud : 5 plus fréquents sur tout l\'historique',
        'bt_recent'            => 'chaud : 5 plus fréquents sur la fenêtre récente',
        'bt_mix'               => 'mix de fréquences, top 5 sans filtre',
        'bt_froid'             => 'froid : 5 plus grands écarts',
        'bt_recommande'        => 'grille recommandée (mix + filtre de typicité)',
        'bt_echant'            => 'grilles alternatives (tirage pondéré + filtre)',
        'bt_chance'            => 'Taux de réussite du numéro chance sur %d tirages (théorie 10 %%) : aléatoire %s, plus chaud %s, plus froid %s',
        'bt_bande'             => 'Bande de bruit pour la moyenne sur %d grilles : %s à %s (2 écarts-types). Une méthode ne bat le hasard qu\'en dehors de cette bande.',
        'aide'                 => <<<TXT
        Usage : php index.php [options]

          --lang=en|fr          Langue d'affichage (en par défaut)
          --since=jj/mm/AAAA    Ne garder que les tirages à partir de cette date (AAAA-mm-jj accepté aussi)
          --day=SAMEDI          Ne garder que les tirages d'un jour de la semaine (LUNDI, MERCREDI, SAMEDI… ou MONDAY, WEDNESDAY, SATURDAY…)
          --window=N            Nombre de tirages les plus récents pour la composante récente (100 par défaut, 0 = désactivée)
          --recent-weight=X     Poids de la composante récente entre 0 et 1 (0.3 par défaut)
          --pool=N              Nombre de boules les mieux notées parmi lesquelles la grille recommandée est choisie (12 par défaut)
          --grids=N             Générer N grilles alternatives tirées proportionnellement aux probabilités
          --seed=N              Graine pour rendre les grilles alternatives reproductibles
          --backtest            Rejouer l'historique et comparer les méthodes de choix de grille sur les tirages réels
          --offline             Ne pas interroger le site FDJ, utiliser uniquement les fichiers présents dans csv/
          --force-update        Retélécharger toutes les archives FDJ, y compris les périodes figées
          --stats               Afficher le tableau complet des probabilités de chaque numéro et la table des profils de grille
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

$profils = construireProfils();

if ($options['backtest']) {
    mt_srand($options['seed'] ?? random_int(1, PHP_INT_MAX));
    afficherBacktest($tirages, $profils, $options);
    echo PHP_EOL;
    exit(0);
}

$statsBoules = calculerProbabilites($tirages, 'boules', NB_BOULES, $options['fenetre'], $options['poids_recent']);
$statsChance = calculerProbabilites($tirages, 'chance', NB_CHANCE, $options['fenetre'], $options['poids_recent']);

$grillePrincipale = meilleureGrille($statsBoules, $statsChance, $profils, $options['pool']);
$pTheorique       = probabiliteTheorique($tirages);

afficherContexte($tirages, $options);
afficherGrille(t('grille_principale'), $grillePrincipale, $statsBoules, $statsChance, $pTheorique, $profils);

if ($options['grilles'] > 0) {
    mt_srand($options['seed'] ?? random_int(1, PHP_INT_MAX));
    for ($i = 1; $i <= $options['grilles']; $i++) {
        $grille = grillePonderee($statsBoules, $statsChance, $profils);
        afficherGrille(t('grille_alternative', $i), $grille, $statsBoules, $statsChance, $pTheorique, $profils);
    }
}

if ($options['stats']) {
    afficherTableau(t('tableau_boules'), $statsBoules, $pTheorique);
    afficherTableau(t('tableau_chance'), $statsChance, 1 / NB_CHANCE);
    afficherProfils($tirages, $profils);
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
        'backtest'     => false,
        'depuis'       => null,
        'jour'         => null,
        'fenetre'      => 100,
        'poids_recent' => 0.3,
        'pool'         => TAILLE_POOL,
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
        if ($arg === '--backtest') {
            $options['backtest'] = true;
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
            case '--pool':
                $options['pool'] = min(NB_BOULES, max(BOULES_PAR_GRILLE, (int) $valeur));
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

function profilGrille(array $boules): array
{
    $pairs    = 0;
    $suites   = 0;
    $dizaines = [];
    $somme    = 0;
    $n        = count($boules);

    for ($i = 0; $i < $n; $i++) {
        $b = $boules[$i];
        $somme += $b;
        if ($b % 2 === 0) {
            $pairs++;
        }
        if ($i > 0 && $b === $boules[$i - 1] + 1) {
            $suites++;
        }
        $dizaines[intdiv($b - 1, 10)] = true;
    }

    return [
        'pairs'    => $pairs,
        'suites'   => $suites,
        'dizaines' => count($dizaines),
        'somme'    => $somme,
        'tranche'  => intdiv($somme, LARGEUR_SOMME),
    ];
}

function cleProfil(array $profil): string
{
    return $profil['pairs'] . '|' . $profil['suites'] . '|' . $profil['dizaines'] . '|' . $profil['tranche'];
}

function construireProfils(): array
{
    $total      = 0;
    $profils    = [];
    $marginales = ['pairs' => [], 'suites' => [], 'dizaines' => [], 'sommes' => []];
    $bits       = [];
    for ($m = 0; $m < 32; $m++) {
        $bits[$m] = substr_count(decbin($m), '1');
    }

    $n = NB_BOULES;
    for ($a = 1; $a <= $n - 4; $a++) {
        $pa = $a % 2 === 0 ? 1 : 0;
        $da = 1 << intdiv($a - 1, 10);
        for ($b = $a + 1; $b <= $n - 3; $b++) {
            $pb = $pa + ($b % 2 === 0 ? 1 : 0);
            $sb = $b === $a + 1 ? 1 : 0;
            $db = $da | (1 << intdiv($b - 1, 10));
            for ($c = $b + 1; $c <= $n - 2; $c++) {
                $pc = $pb + ($c % 2 === 0 ? 1 : 0);
                $sc = $sb + ($c === $b + 1 ? 1 : 0);
                $dc = $db | (1 << intdiv($c - 1, 10));
                for ($d = $c + 1; $d <= $n - 1; $d++) {
                    $pd = $pc + ($d % 2 === 0 ? 1 : 0);
                    $sd = $sc + ($d === $c + 1 ? 1 : 0);
                    $dd = $dc | (1 << intdiv($d - 1, 10));
                    $somme4 = $a + $b + $c + $d;
                    for ($e = $d + 1; $e <= $n; $e++) {
                        $pairs    = $pd + ($e % 2 === 0 ? 1 : 0);
                        $suites   = $sd + ($e === $d + 1 ? 1 : 0);
                        $dizaines = $bits[$dd | (1 << intdiv($e - 1, 10))];
                        $somme    = $somme4 + $e;
                        $tranche  = intdiv($somme, LARGEUR_SOMME);
                        $cle      = $pairs . '|' . $suites . '|' . $dizaines . '|' . $tranche;

                        $total++;
                        $profils[$cle] = ($profils[$cle] ?? 0) + 1;
                        $marginales['pairs'][$pairs]       = ($marginales['pairs'][$pairs] ?? 0) + 1;
                        $marginales['suites'][$suites]     = ($marginales['suites'][$suites] ?? 0) + 1;
                        $marginales['dizaines'][$dizaines] = ($marginales['dizaines'][$dizaines] ?? 0) + 1;
                        $marginales['sommes'][$tranche]    = ($marginales['sommes'][$tranche] ?? 0) + 1;
                    }
                }
            }
        }
    }

    foreach ($marginales as &$m) {
        ksort($m);
    }
    unset($m);

    $tries = $profils;
    asort($tries);
    $cumul = 0;
    $seuil = 0;
    foreach ($tries as $compte) {
        $cumul += $compte;
        $seuil  = $compte;
        if ($cumul / $total >= SEUIL_ATYPIQUE) {
            break;
        }
    }

    return [
        'total'      => $total,
        'seuil'      => $seuil,
        'profils'    => $profils,
        'marginales' => $marginales,
    ];
}

function frequenceProfil(array $profil, array $profils): float
{
    return ($profils['profils'][cleProfil($profil)] ?? 0) / $profils['total'];
}

function estTypique(array $profil, array $profils): bool
{
    return ($profils['profils'][cleProfil($profil)] ?? 0) > $profils['seuil'];
}

function motifPopulaire(array $boules): ?string
{
    if (max($boules) <= MAX_DATE) {
        return 'motif_dates';
    }

    $pas = $boules[1] - $boules[0];
    for ($i = 2; $i < count($boules); $i++) {
        if ($boules[$i] - $boules[$i - 1] !== $pas) {
            return null;
        }
    }

    return 'motif_progression';
}

function grilleAcceptable(array $boules, array $profils): bool
{
    return estTypique(profilGrille($boules), $profils) && motifPopulaire($boules) === null;
}

function combinaisonsDe(array $elements, int $k): Generator
{
    $n = count($elements);
    if ($k > $n) {
        return;
    }
    $indices = range(0, $k - 1);
    while (true) {
        yield array_map(fn(int $i) => $elements[$i], $indices);
        $i = $k - 1;
        while ($i >= 0 && $indices[$i] === $n - $k + $i) {
            $i--;
        }
        if ($i < 0) {
            return;
        }
        $indices[$i]++;
        for ($j = $i + 1; $j < $k; $j++) {
            $indices[$j] = $indices[$j - 1] + 1;
        }
    }
}

function meilleureGrille(array $statsBoules, array $statsChance, array $profils, int $taillePool): array
{
    $scores = array_map(fn(array $s) => $s['score'], $statsBoules);

    return [
        'boules' => meilleuresBoules($scores, $profils, $taillePool),
        'chance' => array_key_first($statsChance),
    ];
}

function meilleuresBoules(array $scores, array $profils, int $taillePool): array
{
    arsort($scores);
    $pool   = array_slice(array_keys($scores), 0, $taillePool);
    $defaut = array_slice($pool, 0, BOULES_PAR_GRILLE);
    sort($defaut);

    $meilleure     = null;
    $meilleurScore = -1.0;
    $meilleureFreq = -1.0;

    foreach (combinaisonsDe($pool, BOULES_PAR_GRILLE) as $boules) {
        sort($boules);
        if (!grilleAcceptable($boules, $profils)) {
            continue;
        }
        $score = array_sum(array_map(fn(int $b) => $scores[$b], $boules));
        $freq  = frequenceProfil(profilGrille($boules), $profils);
        if ($score > $meilleurScore || ($score === $meilleurScore && $freq > $meilleureFreq)) {
            $meilleure     = $boules;
            $meilleurScore = $score;
            $meilleureFreq = $freq;
        }
    }

    return $meilleure ?? $defaut;
}

function grillePonderee(array $statsBoules, array $statsChance, array $profils): array
{
    $poidsBoules = array_map(fn(array $s) => $s['score'], $statsBoules);
    $poidsChance = array_map(fn(array $s) => $s['score'], $statsChance);

    return [
        'boules' => boulesPonderees($poidsBoules, $profils, true),
        'chance' => tirerPondere($poidsChance),
    ];
}

function boulesPonderees(array $poidsBoules, array $profils, bool $filtrer): array
{
    $boules = [];

    for ($essai = 0; $essai < ESSAIS_TIRAGE; $essai++) {
        $boules = [];
        $poids  = $poidsBoules;
        while (count($boules) < BOULES_PAR_GRILLE && $poids !== []) {
            $numero   = tirerPondere($poids);
            $boules[] = $numero;
            unset($poids[$numero]);
        }
        sort($boules);
        if (!$filtrer || grilleAcceptable($boules, $profils)) {
            break;
        }
    }

    return $boules;
}

function topBoules(array $scores): array
{
    arsort($scores);
    $boules = array_slice(array_keys($scores), 0, BOULES_PAR_GRILLE);
    sort($boules);

    return $boules;
}

function afficherBacktest(array $tirages, array $profils, array $options): void
{
    $fenetre     = $options['fenetre'];
    $poidsRecent = $options['poids_recent'];
    $global      = array_fill(1, NB_BOULES, 0);
    $dernier     = array_fill(1, NB_BOULES, -1);
    $chanceGlob  = array_fill(1, NB_CHANCE, 0);
    $chanceDern  = array_fill(1, NB_CHANCE, -1);
    $recents     = [];
    $nGlobal     = 0;
    $uniformes   = array_fill(1, NB_BOULES, 1.0);

    $methodes = ['aleatoire', 'chaud', 'recent', 'mix', 'froid', 'recommande', 'echant'];
    $resultats = [];
    foreach ($methodes as $m) {
        $resultats[$m] = ['n' => 0, 'trouves' => 0, 'ge2' => 0, 'ge3' => 0];
    }
    $chance = ['n' => 0, 'aleatoire' => 0, 'chaud' => 0, 'froid' => 0];

    foreach ($tirages as $position => $tirage) {
        if (count($tirage['boules']) === BOULES_PAR_GRILLE && $nGlobal >= BACKTEST_MIN) {
            $pGlobal = array_map(fn(int $v) => $v / $nGlobal, $global);
            $compteR = array_fill(1, NB_BOULES, 0);
            foreach ($recents as $boules) {
                foreach ($boules as $b) {
                    $compteR[$b]++;
                }
            }
            $nRecent = count($recents);
            $pRecent = array_map(fn(int $v) => $nRecent > 0 ? $v / $nRecent : 0.0, $compteR);
            $poids   = $nRecent > 0 ? $poidsRecent : 0.0;
            $mix     = [];
            $ecarts  = [];
            foreach ($pGlobal as $n => $v) {
                $mix[$n]    = (1 - $poids) * $v + $poids * $pRecent[$n];
                $ecarts[$n] = $position - $dernier[$n];
            }

            $grilles = [
                'chaud'      => [topBoules($pGlobal)],
                'recent'     => [topBoules($pRecent)],
                'mix'        => [topBoules($mix)],
                'froid'      => [topBoules($ecarts)],
                'recommande' => [meilleuresBoules($mix, $profils, $options['pool'])],
                'aleatoire'  => [],
                'echant'     => [],
            ];
            for ($r = 0; $r < BACKTEST_REP; $r++) {
                $grilles['aleatoire'][] = boulesPonderees($uniformes, $profils, false);
                $grilles['echant'][]    = boulesPonderees($mix, $profils, true);
            }

            foreach ($grilles as $m => $liste) {
                foreach ($liste as $grille) {
                    $k = count(array_intersect($grille, $tirage['boules']));
                    $resultats[$m]['n']++;
                    $resultats[$m]['trouves'] += $k;
                    if ($k >= 2) {
                        $resultats[$m]['ge2']++;
                    }
                    if ($k >= 3) {
                        $resultats[$m]['ge3']++;
                    }
                }
            }

            if ($tirage['chance'] !== null && array_sum($chanceGlob) >= BACKTEST_MIN) {
                $chance['n']++;
                if (mt_rand(1, NB_CHANCE) === $tirage['chance']) {
                    $chance['aleatoire']++;
                }
                $tri = $chanceGlob;
                arsort($tri);
                if (array_key_first($tri) === $tirage['chance']) {
                    $chance['chaud']++;
                }
                $ecartsChance = [];
                foreach ($chanceDern as $n => $d) {
                    $ecartsChance[$n] = $position - $d;
                }
                arsort($ecartsChance);
                if (array_key_first($ecartsChance) === $tirage['chance']) {
                    $chance['froid']++;
                }
            }
        }

        $nGlobal++;
        foreach ($tirage['boules'] as $b) {
            $global[$b]++;
            $dernier[$b] = $position;
        }
        if ($fenetre > 0) {
            $recents[] = $tirage['boules'];
            if (count($recents) > $fenetre) {
                array_shift($recents);
            }
        }
        if ($tirage['chance'] !== null) {
            $chanceGlob[$tirage['chance']]++;
            $chanceDern[$tirage['chance']] = $position;
        }
    }

    $total    = $profils['total'];
    $theorie  = [];
    for ($k = 0; $k <= BOULES_PAR_GRILLE; $k++) {
        $theorie[$k] = combinaisons(BOULES_PAR_GRILLE, $k) * combinaisons(NB_BOULES - BOULES_PAR_GRILLE, BOULES_PAR_GRILLE - $k) / $total;
    }
    $moyenne  = BOULES_PAR_GRILLE * BOULES_PAR_GRILLE / NB_BOULES;
    $ge2      = $theorie[2] + $theorie[3] + $theorie[4] + $theorie[5];
    $ge3      = $theorie[3] + $theorie[4] + $theorie[5];
    $nTirages = $resultats['chaud']['n'];
    $entete   = TEXTES[langue()]['bt_entete'];

    echo PHP_EOL . t('bt_titre', $nTirages) . PHP_EOL . PHP_EOL;
    echo sprintf('  %-52s %8s %12s %9s %9s', ...$entete) . PHP_EOL;
    echo sprintf('  %-52s %8s %12s %9s %9s', t('bt_theorie'), '', nombre($moyenne, 4), pourcent($ge2, 3), pourcent($ge3, 3)) . PHP_EOL;
    foreach ($resultats as $m => $r) {
        echo sprintf(
            '  %-52s %8d %12s %9s %9s',
            t('bt_' . $m),
            $r['n'],
            nombre($r['trouves'] / $r['n'], 4),
            pourcent($r['ge2'] / $r['n'], 3),
            pourcent($r['ge3'] / $r['n'], 3)
        ) . PHP_EOL;
    }

    $variance  = $moyenne * (1 - BOULES_PAR_GRILLE / NB_BOULES) * (NB_BOULES - BOULES_PAR_GRILLE) / (NB_BOULES - 1);
    $ecartType = sqrt($variance / max(1, $nTirages));
    echo PHP_EOL . t('bt_bande', $nTirages, nombre($moyenne - 2 * $ecartType, 4), nombre($moyenne + 2 * $ecartType, 4)) . PHP_EOL;

    if ($chance['n'] > 0) {
        echo t(
            'bt_chance',
            $chance['n'],
            pourcent($chance['aleatoire'] / $chance['n']),
            pourcent($chance['chaud'] / $chance['n']),
            pourcent($chance['froid'] / $chance['n'])
        ) . PHP_EOL;
    }
}

function combinaisons(int $n, int $k): float
{
    if ($k < 0 || $k > $n) {
        return 0.0;
    }
    $resultat = 1.0;
    for ($i = 1; $i <= $k; $i++) {
        $resultat = $resultat * ($n - $k + $i) / $i;
    }

    return round($resultat);
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
    $typicite    = t('ctx_typicite_active', pourcent(SEUIL_ATYPIQUE, 0), $options['pool']);

    echo PHP_EOL;
    echo t('ctx_tirages', count($tirages), $premier, $dernier) . PHP_EOL;
    echo t('ctx_chance', compterAvecChance($tirages)) . PHP_EOL;
    echo t('ctx_jours', $repartition) . PHP_EOL;
    echo t('ctx_fenetre', $fenetre) . PHP_EOL;
    echo t('ctx_typicite', $typicite) . PHP_EOL;
}

function afficherGrille(string $titre, array $grille, array $statsBoules, array $statsChance, float $attenduBoule, array $profils): void
{
    $boules = implode(' - ', array_map(fn($b) => sprintf('%02d', $b), $grille['boules']));
    $profil = profilGrille($grille['boules']);
    $motif  = motifPopulaire($grille['boules']);

    echo PHP_EOL . $titre . PHP_EOL;
    echo t('grille_ligne', $boules, $grille['chance']) . PHP_EOL;
    echo t('grille_profil', $profil['pairs'], $profil['suites'], $profil['dizaines'], $profil['somme'], pourcent(frequenceProfil($profil, $profils))) . PHP_EOL;
    if ($motif !== null) {
        echo t('grille_populaire', t($motif)) . PHP_EOL;
    }
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

function afficherProfils(array $tirages, array $profils): void
{
    $observe = ['pairs' => [], 'suites' => [], 'dizaines' => [], 'sommes' => []];
    $n       = 0;

    foreach ($tirages as $tirage) {
        if (count($tirage['boules']) !== BOULES_PAR_GRILLE) {
            continue;
        }
        $n++;
        $p = profilGrille($tirage['boules']);
        $observe['pairs'][$p['pairs']]       = ($observe['pairs'][$p['pairs']] ?? 0) + 1;
        $observe['suites'][$p['suites']]     = ($observe['suites'][$p['suites']] ?? 0) + 1;
        $observe['dizaines'][$p['dizaines']] = ($observe['dizaines'][$p['dizaines']] ?? 0) + 1;
        $observe['sommes'][$p['tranche']]    = ($observe['sommes'][$p['tranche']] ?? 0) + 1;
    }

    $entete = TEXTES[langue()]['profils_entete'];

    echo PHP_EOL . t('profils_titre', number_format($profils['total'], 0, '', ' '), $n) . PHP_EOL;

    foreach (['pairs', 'suites', 'dizaines', 'sommes'] as $axe) {
        echo PHP_EOL . '  ' . t('profil_' . $axe) . PHP_EOL;
        echo sprintf('  %-10s %12s %12s', ...$entete) . PHP_EOL;
        foreach ($profils['marginales'][$axe] as $valeur => $compte) {
            $libelle = $axe === 'sommes'
                ? ($valeur * LARGEUR_SOMME) . '-' . ($valeur * LARGEUR_SOMME + LARGEUR_SOMME - 1)
                : (string) $valeur;
            $obs = $n > 0 ? ($observe[$axe][$valeur] ?? 0) / $n : 0.0;
            echo sprintf('  %-10s %12s %12s', $libelle, pourcent($compte / $profils['total']), pourcent($obs)) . PHP_EOL;
        }
    }
}

function nombre(float $valeur, int $decimales): string
{
    return number_format($valeur, $decimales, t('sep_decimal'), '');
}

function pourcent(float $valeur, int $decimales = 2): string
{
    return nombre($valeur * 100, $decimales) . ' %';
}
