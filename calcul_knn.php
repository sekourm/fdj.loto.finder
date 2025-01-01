<?php

$draw = ['boule_1', 'boule_2', 'boule_3', 'boule_4', 'boule_5'];
$path = 'all.csv';
$arrSegmented = convertToArray($path, $draw);

if ($arrSegmented === false) {
    die("Erreur : Impossible de lire le fichier.");
}

$numCount = 5;
$predictedCombination = generateCombination($arrSegmented, $numCount);

echo "Prochaine combinaison probable : " . implode(", ", $predictedCombination) . PHP_EOL;

function generateCombination(array $data, int $numCount): array {
    $frequency = [];
    foreach ($data as $draw) {
        foreach ($draw as $num) {
            if (!isset($frequency[$num])) {
                $frequency[$num] = 0;
            }
            $frequency[$num]++;
        }
    }
    arsort($frequency);
    $mostFrequentNumbers = array_keys($frequency);
    return array_slice($mostFrequentNumbers, 0, $numCount);
}

function convertToArray(string $path, array $keep): array|false {
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
            $data[] = $filtered_line;
        }
        fclose($handle);
    }

    return $data;
}
