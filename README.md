# fdj.loto.finder

Analysis of French Loto (FDJ) draws from the raw `csv/*.csv` exports. The script recommends a grid whose numbers score high on observed frequencies **and** whose overall profile (even/odd split, consecutive numbers, decades, sum) is a common one, while rejecting patterns the public over-plays. A built-in backtest replays the whole history to show what each method is really worth.

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

Requires the PHP `curl` and `zip` extensions. Use `--offline` to skip the update, `--force-update` to re-download every archive.

## Usage

Terminal output is in English by default; pass `--lang=fr` for French.

```bash
php index.php
php index.php --lang=fr
php index.php --day=SATURDAY --since=01/01/2019
php index.php --grids=5 --seed=42
php index.php --stats
php index.php --backtest --seed=2026
php index.php --help
```

| Option | Purpose |
| --- | --- |
| `--lang=en\|fr` | Output language, `en` by default |
| `--since=dd/mm/YYYY` | Keep only draws from this date onwards (`YYYY-mm-dd` also accepted) |
| `--day=MONDAY` | Keep only draws of one weekday (English or French name, e.g. `SATURDAY` or `SAMEDI`) |
| `--window=N` | Number of most recent draws for the recent component (default 100, 0 to disable) |
| `--recent-weight=X` | Weight of the recent component between 0 and 1 (default 0.3) |
| `--pool=N` | Number of top-scored balls the recommended grid is chosen from (default 12) |
| `--grids=N` | Generate N alternative grids sampled proportionally to the probabilities |
| `--seed=N` | Make the alternative grids reproducible |
| `--offline` | Skip the FDJ update and use only the local files |
| `--force-update` | Re-download every FDJ archive, including closed periods |
| `--stats` | Print the full table of the 49 balls and 10 lucky numbers, plus the grid profile table (theoretical vs observed) |
| `--backtest` | Replay the history and compare grid-selection methods on the real draws |
| `--help`, `-h` | Show the built-in help |

## Method

### 1. Number scores

For each number, the script computes its empirical probability of appearing in a draw (occurrences / draws), both over the whole dataset and over the recent window. The theoretical probability shown next to it is derived from the actual average number of balls per draw, so mixing 6-ball and 5-ball periods stays consistent. The score is the weighted average of the two. The gap shown is the number of draws since the number last came out.

### 2. Grid profile and typicity

Every one of the 1,906,884 possible 5/49 grids is enumerated on each run (under a second) and classified by its profile: number of even balls, number of consecutive pairs, number of distinct decades and sum range (20-wide buckets). The exact share of grids having each profile is stored. A grid is **atypical** when its profile belongs to the 20 % rarest ones; for example a grid with 5 even numbers, or with two consecutive pairs, or a sum above 180.

Two **popular patterns** are also rejected because many players use them, which splits the jackpot when they come out: grids where every number is 31 or below (birth dates) and grids with a constant step between numbers (1-2-3-4-5, multiples of 5 or 7, same last digit).

### 3. Grid selection

The recommended grid is the highest-scoring combination of 5 among the `--pool` best-scored numbers (792 combinations for 12) that is typical and not popular. Alternative grids are sampled proportionally to the scores and redrawn while they fail the same filter. Each grid is printed with its profile and the share of grids sharing it.

### 4. What the backtest says

`--backtest` replays the 2,814 five-ball draws; for each one it builds the grids from the previous draws only and counts the balls found. Result with `--seed=2026`:

| Method | Grids | Avg balls found | P(>=2) | P(>=3) |
| --- | --- | --- | --- | --- |
| Theory, any grid | | 0.5102 | 7.453 % | 0.508 % |
| Uniform random grid | 56 280 | 0.5099 | 7.546 % | 0.514 % |
| Hot: 5 most frequent overall | 2 814 | 0.5025 | 7.356 % | 0.426 % |
| Hot: 5 most frequent in the recent window | 2 814 | 0.4954 | 6.965 % | 0.711 % |
| Frequency mix, top 5 without filter | 2 814 | 0.4972 | 7.178 % | 0.533 % |
| Cold: 5 largest gaps | 2 814 | 0.5188 | 8.067 % | 0.569 % |
| Recommended grid (mix + typicity filter) | 2 814 | 0.4947 | 7.178 % | 0.604 % |
| Alternative grids (weighted sampling + filter) | 56 280 | 0.5072 | 7.388 % | 0.510 % |

The noise band for an average over 2 814 grids is 0.4858 to 0.5346 (two standard deviations). Every method sits inside it: none finds more balls than a random grid, hot and cold numbers included. The lucky number behaves the same way (hottest 10.61 %, coldest 10.02 %, theory 10 %).

## Limitation

Every draw is independent and equiprobable: any grid has exactly 1 chance in 19,068,840. Frequencies describe the past and cannot predict the next draw, and the backtest above confirms it on the real history. What the method does change is the **expected payout**: by avoiding grids that many players choose, a winning grid is shared with fewer people. The typicity filter itself is cosmetic, it only keeps the recommendation looking like an ordinary draw.
