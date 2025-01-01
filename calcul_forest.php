<?php

$draw = ['boule_1', 'boule_2', 'boule_3', 'boule_4', 'boule_5'];
$path = 'all.csv';

$arrSegmented = convertToArray($path, $draw);

if ($arrSegmented === false) {
    die("Erreur : Impossible de lire le fichier.");
}

$numTrees       = 100;
$sampleSize     = 500;
$numCount       = 5;

$forest = buildRandomForest($arrSegmented, $numTrees, $sampleSize, $numCount);

$predictedCombination = predictWithForest($forest, $numCount);

echo "Prochaine combinaison probable (Forêt Aléatoire) : " . implode(", ", $predictedCombination) . PHP_EOL;

function buildRandomForest(array $data, int $numTrees, int $sampleSize, int $numCount): array {
    $forest = [];
    for ($i = 0; $i < $numTrees; $i++) {
        $sample = getRandomSample($data, $sampleSize);
        $tree = buildDecisionTree($sample, $numCount);
        $forest[] = $tree;
    }
    return $forest;
}

function predictWithForest(array $forest, int $numCount): array {
    $votes = [];
    foreach ($forest as $tree) {
        foreach ($tree as $num) {
            if (!isset($votes[$num])) {
                $votes[$num] = 0;
            }
            $votes[$num]++;
        }
    }
    arsort($votes);
    return array_slice(array_keys($votes), 0, $numCount);
}

function buildDecisionTree(array $data, int $numCount): array {
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
    return array_slice(array_keys($frequency), 0, $numCount);
}

function getRandomSample(array $data, int $sampleSize): array {
    $sample = [];
    $dataCount = count($data);
    for ($i = 0; $i < $sampleSize; $i++) {
        $randomIndex = rand(0, $dataCount - 1);
        $sample[] = $data[$randomIndex];
    }
    return $sample;
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
            $data[] = array_map('intval', $filtered_line);
        }
        fclose($handle);
    }

    return $data;
}

