<?php

$date = null;
$limitDate = '21/12/2024';

if (isset($argv)) {
    foreach ($argv as $index => $arg) {
        if ($arg === 'date' && isset($argv[$index + 1])) {
            $date = $argv[$index + 1];
            break;
        }
    }
}

if ($date !== null) {
    $dateObject = DateTime::createFromFormat('d/m/Y', $date);
    $referenceDate = DateTime::createFromFormat('d/m/Y', $limitDate);
    if ($dateObject && $dateObject->format('d/m/Y') === $date) {
        if ($dateObject > $referenceDate) {
            echo PHP_EOL.'La valeur de date ('.$date.') dépasse la date de début autorisée '.$limitDate.PHP_EOL;
            exit;
        } else {
            echo PHP_EOL.'Calcul effectué à partir du : '.$date.PHP_EOL;
        }
    } else {
        echo PHP_EOL.'La valeur de date n\'est pas valide. Veuillez utiliser le format dd/mm/YYYY.'.PHP_EOL;
        exit;
    }
}

$draw   = ['boule_1', 'boule_2', 'boule_3', 'boule_4', 'boule_5', 'numero_chance'];
$dates  = ['jour_de_tirage', 'date_de_tirage'];
$keep   = array_merge($draw, $dates);

$days = ['LU' => 'LUNDI', 'MA' => 'MARDI', 'ME' => 'MERCREDI', 'JE' => 'JEUDI', 'VE' => 'VENDREDI', 'SA' => 'SAMEDI', 'DI' => 'DIMANCHE'];

$path           = 'all.csv';
$arrSegmented   = convertToArray($path, $keep, $days, $date);
$resultsByDay   = processByDayAndType($arrSegmented, $draw);
$resultsByDay   = sortResultsByDay($resultsByDay);

echo PHP_EOL;
foreach ($resultsByDay as $day => $combos) {
    echo $day.':'  . PHP_EOL;
    echo implode(', ', $combos) . PHP_EOL;
    echo PHP_EOL;
}

$results = processPrints($arrSegmented, $draw);
echo 'GLOBAL: ' . PHP_EOL;
echo implode(', ', $results) . PHP_EOL;
echo PHP_EOL;

//-------------------------------//

/**
 * Convert CSV to ARRAY
 *
 * @param string $path
 * @param array $keep
 * @param array $days
 * @param string|null $date
 * @return array|false
 */
function convertToArray(
    string $path,
    array $keep,
    array $days,
    ?string $date
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
            if (isset($days[$filtered_line['jour_de_tirage']])) {
                $filtered_line['jour_de_tirage'] = $days[$filtered_line['jour_de_tirage']];
                $dateObject = DateTime::createFromFormat('Ymd', $filtered_line['date_de_tirage']);
                $filtered_line['date_de_tirage'] = $dateObject->format('d/m/Y');
            } else {
                $filtered_line['jour_de_tirage'] = trim($filtered_line['jour_de_tirage']);
            }
            if (null !== $date) {
                if (DateTime::createFromFormat('d/m/Y', $filtered_line['date_de_tirage']) >= DateTime::createFromFormat('d/m/Y', $date)) {
                    $data[] = $filtered_line;
                }
            } else {
                $data[] = $filtered_line;
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
 * @param array $draw
 * @return array
 */
function getFrequencies(array $arr, array $draw): array {
    $frequencies = array_fill(1, 49, 0);
    $frequenciesChance = array_fill(1, 10, 0); // Pour numero_chance

    foreach ($arr as $value) {
        foreach ($value as $key => $v) {
            $nbr = (int)$v;
            if (in_array($key, $draw)) {
                if (strpos($key, 'numero_chance') !== false) {
                    if ($nbr >= 1 && $nbr <= 10) {
                        $frequenciesChance[$nbr]++;
                    }
                } elseif ($nbr >= 1 && $nbr <= 49) {
                    $frequencies[$nbr]++;
                }
            }
        }
    }

    asort($frequencies);
    asort($frequenciesChance);

    return [
        'main' => $frequencies,
        'chance' => $frequenciesChance
    ];
}

/**
 * Get probabilities
 *
 * @param array $frequencies
 * @return array
 */
function getProbabilities(array $frequencies): array {
    $totalDrawsMain     = array_sum($frequencies['main']);
    $totalDrawsChance   = array_sum($frequencies['chance']);

    $probabilitiesMain = $totalDrawsMain > 0
        ? array_map(fn($count) => $count / $totalDrawsMain, $frequencies['main'])
        : [];
    $probabilitiesChance = $totalDrawsChance > 0
        ? array_map(fn($count) => $count / $totalDrawsChance, $frequencies['chance'])
        : [];

    arsort($probabilitiesMain);
    arsort($probabilitiesChance);

    return [
        'main' => $probabilitiesMain,
        'chance' => $probabilitiesChance
    ];
}

/**
 * Get the best combination
 *
 * @param array $probabilities
 * @param array $draw
 * @return array
 */
function getBestCombination(
    array $probabilities,
    array $draw
): array {
    $bestMain = array_slice(array_keys($probabilities['main']), 0, count($draw) - 1);
    $bestChance = array_keys($probabilities['chance'])[0] ?? null;
    return array_merge($bestMain, [$bestChance]);
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
    return getBestCombination($probabilities, $draw);
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
        $bestCombination    = getBestCombination($probabilities, $draw);
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
    $order = ['LUNDI' => 0, 'MARDI' => 1, 'MERCREDI' => 2, 'JEUDI' => 3, 'VENDREDI' => 4, 'SAMEDI' => 5, 'DIMANCHE' => 6];
    uksort($resultsByDay, function ($a, $b) use ($order) {
        return $order[$a] <=> $order[$b];
    });
    return $resultsByDay;
}