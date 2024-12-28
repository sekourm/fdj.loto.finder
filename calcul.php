<?php

$draw     = [
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
$keep = array_merge($draw, $dates);

$days = [
    'LU' => 'LUNDI',
    'MA' => 'MARDI',
    'ME' => 'MERCREDI',
    'JE' => 'JEUDI',
    'VE' => 'VENDREDI',
    'SA' => 'SAMEDI',
    'DI' => 'DIMANCHE'
];

$path           = '1976-2008.csv';
$arrSegmented   = convertToArray($path, $keep, $days);
$resultsByDay   = processByDayAndType($arrSegmented, $draw);
$resultsByDay   = sortResultsByDay($resultsByDay);

echo PHP_EOL;
foreach ($resultsByDay as $day => $combos) {
    echo "$day: " . PHP_EOL;
    echo implode(', ', $combos) . PHP_EOL;
    echo PHP_EOL;
}

$results = processPrints($arrSegmented, $draw);
echo "TOUS LES JOURS: " . PHP_EOL;
echo implode(', ', $results) . PHP_EOL;
echo PHP_EOL;

//-----------------------------------------------------

/**
 * Convert CSV to ARRAY
 *
 * @param string $path
 * @param array $keep
 * @param array $days
 * @return array|false
 */
function convertToArray(
    string $path,
    array $keep,
    array $days
): array|false {
    if (!is_readable($path)) {
        return false;
    }

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
 * @param array $draw
 * @return array
 */
function getFrequencies(
    array $arr,
    array $draw
): array {
    $frequencies = array_fill(1, 49, 0);

    foreach ($arr as $value) {
        foreach ($value as $key => $v) {
            if (in_array($key, $draw)) {
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
 * @param array $draw
 * @return array
 */
function processPrints(
    array $data,
    array $draw
): array {
    $frequencies    = getFrequencies($data, $draw);
    $probabilities  = getProbabilities($frequencies);
    return getBestCombination($probabilities);
}

/**
 * Process data by day and draw type
 *
 * @param array $data
 * @param array $draw
 * @return array
 */
function processByDayAndType(
    array $data,
    array $draw
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
        $frequencies        = getFrequencies($lines, $draw);
        $probabilities      = getProbabilities($frequencies);
        $bestCombination    = getBestCombination($probabilities);
        $finalResults[$day] = $bestCombination;
    }

    return $finalResults;
}

/**
 * Sort results by day order
 *
 * @param array $resultsByDay
 * @return array
 */
function sortResultsByDay(
    array $resultsByDay
): array {
    $order = [
        'LUNDI'    => 0,
        'MARDI'    => 1,
        'MERCREDI' => 2,
        'JEUDI'    => 3,
        'VENDREDI' => 4,
        'SAMEDI'   => 5,
        'DIMANCHE' => 6
    ];
    uksort($resultsByDay, function ($a, $b) use ($order) {
        return $order[$a] <=> $order[$b];
    });
    return $resultsByDay;
}