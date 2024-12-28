<?php

$base               = basename(__FILE__);
$path               = str_replace('.php', '.csv', $base);

$index = [
    "1er_ou_2eme_tirage"
];
$simple = [
    "boule_1",
    "boule_2",
    "boule_3",
    "boule_4",
    "boule_5",
    "boule_6"
];
$dates = [
    "jour_de_tirage"
];
$winners = [
    "nombre_de_gagnant_au_rang1",
    "nombre_de_gagnant_au_rang2",
    "nombre_de_gagnant_au_rang3",
    "nombre_de_gagnant_au_rang4",
    "nombre_de_gagnant_au_rang5",
    "nombre_de_gagnant_au_rang6",
    "nombre_de_gagnant_au_rang7"
];

$keep = array_merge($index, $simple, $dates, $winners);

$dayOrder = [
    'LUNDI',
    'MARDI',
    'MERCREDI',
    'JEUDI',
    'VENDREDI',
    'SAMEDI',
    'DIMANCHE'
];

/**
 * Convert CSV to ARRAY
 *
 * @param string $path
 * @param array $keep
 * @return array|false
 */
function convertToArray(
    string $path,
    array $keep
): array|false {
    if (!is_readable($path)) {
        return false;
    }

    $days = [
        'ME' => 'MERCREDI',
        'JE' => 'JEUDI',
        'SA' => 'SAMEDI',
        'VE' => 'VENDREDI'
    ];

    $data = [
        'print_1' => [],
        'print_2' => [],
        'all' => []
    ];

    if (($handle = fopen($path, 'r')) !== false) {
        $header = fgetcsv($handle, 1000, ';', '"', '\\');
        while ($header && ($line = fgetcsv($handle, 1000, ';', '"', '\\')) !== false) {
            $filtered_line = array_filter(
                array_combine($header, $line),
                function ($key) use ($keep) {
                    return in_array($key, $keep);
                },
                ARRAY_FILTER_USE_KEY
            );

            $filtered_line['jour_de_tirage'] = $days[$filtered_line['jour_de_tirage']];

            $data['all'][] = $filtered_line;

            if ($filtered_line['1er_ou_2eme_tirage'] === '1') {
                $data['print_1'][] = $filtered_line;
            } elseif ($filtered_line['1er_ou_2eme_tirage'] === '2') {
                $data['print_2'][] = $filtered_line;
            }
        }
        fclose($handle);
    }

    return $data;
}

/**
 * Get frequencies
 *
 * @param array $arr
 * @param array $simple
 * @return array
 */
function getFrequencies(
    array $arr,
    array $simple
): array {
    $frequencies = array_fill(1, 49, 0);

    foreach ($arr as $value) {
        foreach ($value as $key => $v) {
            if (in_array($key, $simple)) {
                $nbr = (int)$v;
                if ($nbr >= 1 && $nbr <= 49) {
                    $frequencies[$nbr]++;
                }
            }
        }
    }

    asort($frequencies);

    return $frequencies;
}

/**
 * Get probabilities
 *
 * @param array $frequencies
 * @return array
 */
function getProbabilities(
    array $frequencies
): array
{
    $totalDraws = array_sum($frequencies);

    if ($totalDraws === 0) {
        return [];
    }

    $probabilities = array_map(function ($count) use ($totalDraws) {
        return $count / $totalDraws;
    }, $frequencies);

    arsort($probabilities);

    return $probabilities;
}

/**
 * Get the best combination
 *
 * @param array $probabilities
 * @param int $count
 * @return array
 */
function getBestCombination(
    array $probabilities,
    int $count = 6
): array {
    return array_slice(array_keys($probabilities), 0, $count);
}

/**
 * Process data by prints
 *
 * @param array $data
 * @param array $simple
 * @return array
 */
function processPrints(
    array $data,
    array $simple
): array {
    $results = [];
    foreach ($data as $key => $value) {
        $frequencies        = getFrequencies($value, $simple);
        $probabilities      = getProbabilities($frequencies);
        $bestCombination    = getBestCombination($probabilities);
        $results[$key]      = $bestCombination;
    }
    return $results;
}

/**
 * Process data by day and draw type
 *
 * @param array $data
 * @param array $simple
 * @return array
 */
function processByDayAndType(
    array $data,
    array $simple
): array {
    $resultsByDay = [];
    foreach ($data as $line) {
        $day = $line['jour_de_tirage'];
        $type = $line['1er_ou_2eme_tirage'];

        if (!isset($resultsByDay[$day])) {
            $resultsByDay[$day] = [
                'print_1' => [],
                'print_2' => [],
                'all' => []
            ];
        }

        if ($type === '1') {
            $resultsByDay[$day]['print_1'][] = $line;
        } elseif ($type === '2') {
            $resultsByDay[$day]['print_2'][] = $line;
        }
        $resultsByDay[$day]['all'][] = $line;
    }

    $finalResults = [];
    foreach ($resultsByDay as $day => $types) {
        foreach ($types as $key => $lines) {
            $frequencies = getFrequencies($lines, $simple);
            $probabilities = getProbabilities($frequencies);
            $bestCombination = getBestCombination($probabilities);
            $finalResults[$day][$key] = $bestCombination;
        }
    }

    return $finalResults;
}

/**
 * Sort results by day order
 *
 * @param array $resultsByDay
 * @param array $dayOrder
 * @return array
 */
function sortResultsByDay(
    array $resultsByDay,
    array $dayOrder
): array {
    uksort($resultsByDay, function ($a, $b) use ($dayOrder) {
        $posA = array_search($a, $dayOrder);
        $posB = array_search($b, $dayOrder);
        return $posA <=> $posB;
    });
    return $resultsByDay;
}

$arrSegmented = convertToArray($path, $keep);
if ($arrSegmented) {
    $resultsByDay = processByDayAndType($arrSegmented['all'], $simple);
    $resultsByDay = sortResultsByDay($resultsByDay, $dayOrder);

    echo PHP_EOL;
    foreach ($resultsByDay as $day => $combos) {
        echo "$day : " . PHP_EOL;
        echo "  Tirage 1 : " . implode(', ', $combos['print_1']) . PHP_EOL;
        echo "  Tirage 2 : " . implode(', ', $combos['print_2']) . PHP_EOL;
        echo "  Tirage 1 + 2 : " . implode(', ', $combos['all']) . PHP_EOL;
        echo PHP_EOL;
    }

    $results = processPrints($arrSegmented, $simple);
    echo "TOUS LES JOURS : " . PHP_EOL;
    echo " Tirage 1 : " . implode(', ', $results['print_1']) . PHP_EOL;
    echo " Tirage 2 : " . implode(', ', $results['print_2']) . PHP_EOL;
    echo " Tirage 1 + 2 : " . implode(', ', $results['all']) . PHP_EOL;
    echo PHP_EOL;
}