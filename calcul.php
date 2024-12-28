<?php

$path       = '1976-2008.csv';

$simple     = [
    "boule_1",
    "boule_2",
    "boule_3",
    "boule_4",
    "boule_5",
    "boule_6"
];
$dates      = [
    "jour_de_tirage"
];

$keep = array_merge($simple, $dates);

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
        'LU' => 'LUNDI',
        'MA' => 'MARDI',
        'ME' => 'MERCREDI',
        'JE' => 'JEUDI',
        'VE' => 'VENDREDI',
        'SA' => 'SAMEDI',
        'DI' => 'DIMANCHE'
    ];

    $data = [];

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

            $data[] = $filtered_line;
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
    $frequencies = getFrequencies($data, $simple);
    $probabilities = getProbabilities($frequencies);
    $bestCombination = getBestCombination($probabilities);
    $results = $bestCombination;

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

        if (!isset($resultsByDay[$day])) {
            $resultsByDay[$day] = [];
        }

        $resultsByDay[$day][] = $line;
    }

    $finalResults = [];
    foreach ($resultsByDay as $day => $lines) {
        $frequencies = getFrequencies($lines, $simple);
        $probabilities = getProbabilities($frequencies);
        $bestCombination = getBestCombination($probabilities);
        $finalResults[$day] = $bestCombination;
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
    $resultsByDay = processByDayAndType($arrSegmented, $simple);
    $resultsByDay = sortResultsByDay($resultsByDay, $dayOrder);

    echo PHP_EOL;
    foreach ($resultsByDay as $day => $combos) {
        echo "$day : " . PHP_EOL;
        echo implode(', ', $combos) . PHP_EOL;
        echo PHP_EOL;
    }

    $results = processPrints($arrSegmented, $simple);
    echo "TOUS LES JOURS : " . PHP_EOL;
    echo implode(', ', $results) . PHP_EOL;
    echo PHP_EOL;
}
