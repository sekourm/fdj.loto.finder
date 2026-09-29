# Fdj.loto.finder

Analysis of French Loto (FDJ) draws from the raw `csv/*.csv` exports, producing the most probable grid based on observed frequencies.

## Data

The `csv/*.csv` files are the official FDJ exports, unchanged. The script detects each file's format from its header, normalizes dates (`Ymd` or `dd/mm/YYYY`) and weekdays (`SA` or `SAMEDI`), and removes duplicate draws across overlapping files.

All draws since 1976 are used for ball frequencies, including the 1976-2008 game (6 balls out of 49, no lucky number). The lucky number statistics only cover draws that have one, i.e. since 06/10/2008.

### Files

| File | Period | Notes |
| --- | --- | --- |
| `1976-2008.csv` | 19/05/1976 to 04/10/2008 | 6 balls, no lucky number |
| `2008-2017.csv` | 06/10/2008 to 04/03/2017 | 5 balls + lucky number |
| `2017-2019.csv` | 06/03/2017 to 25/02/2019 | |
| `2019.csv` | 27/02/2019 to 02/11/2019 | FDJ split 2019 when the file format changed |
| `2019-aujourdhui.csv` | 06/11/2019 to today | refreshed on every run |

### Automatic update

On every run the script downloads the FDJ archives published at https://www.fdj.fr/jeux-de-tirage/loto/historique, extracts the CSV from each ZIP and writes it to `csv/` only when its content differs from the local file. The four closed periods are downloaded only when the local file is missing; the current period (`2019-aujourdhui.csv`) is checked on every run. If the download fails, the script says so and continues with the local files.

Requires the PHP `curl` and `zip` extensions. Use `--hors-ligne` to skip the update, `--forcer-maj` to re-download every archive.

## Usage

```bash
php index.php
php index.php --jour=SAMEDI --depuis=01/01/2019
php index.php --grilles=5 --seed=42
php index.php --stats
php index.php --aide
```

| Option | Purpose |
| --- | --- |
| `--depuis=dd/mm/YYYY` | Keep only draws from this date onwards |
| `--jour=LUNDI` | Keep only draws of one weekday |
| `--fenetre=N` | Number of most recent draws for the recent component (default 100, 0 to disable) |
| `--poids-recent=X` | Weight of the recent component between 0 and 1 (default 0.3) |
| `--grilles=N` | Generate N alternative grids sampled proportionally to the probabilities |
| `--seed=N` | Make the alternative grids reproducible |
| `--hors-ligne` | Skip the FDJ update and use only the local files |
| `--forcer-maj` | Re-download every FDJ archive, including closed periods |
| `--stats` | Print the full table of the 49 balls and 10 lucky numbers |

## Method

For each number, the script computes its empirical probability of appearing in a draw (occurrences / draws), both over the whole dataset and over the recent window. The theoretical probability shown next to it is derived from the actual average number of balls per draw, so mixing 6-ball and 5-ball periods stays consistent. The score is the weighted average of the two. The main grid takes the 5 balls and the lucky number with the highest scores. The gap shown is the number of draws since the number last came out.

## Limitation

Every draw is independent and equiprobable: any grid has exactly 1 chance in 19,068,840. Frequencies describe the past and cannot predict the next draw. The script proposes the grid most consistent with the history, not a certainty.
