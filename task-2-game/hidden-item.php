<?php

$GRID = [
    ['#', '#', '#', '#', '#', '#', '#', '#'],
    ['#', '.', '.', '.', '.', '.', '.', '#'],
    ['#', '.', '#', '#', '#', '.', '.', '#'],
    ['#', '.', '.', '.', '#', '.', '#', '#'],
    ['#', 'X', '#', '.', '.', '.', '.', '#'],
    ['#', '#', '#', '#', '#', '#', '#', '#'],
];

function ansi(string $code): string
{
    return "\033[{$code}m";
}
function bold(string $text): string
{
    return ansi('1') . $text . ansi('0');
}
function col(string $text, string $fg): string
{
    $map = ['red' => '31', 'green' => '32', 'yellow' => '33', 'cyan' => '36', 'white' => '37', 'gray' => '90'];
    return ansi($map[$fg] ?? '0') . $text . ansi('0');
}

function findStart(array $grid): array
{
    foreach ($grid as $r => $row)
        foreach ($row as $cc => $cell)
            if ($cell === 'X') return ['r' => $r, 'c' => $cc];
    throw new RuntimeException('X tidak ditemukan.');
}

function walk(array $grid, array $from, int $dr, int $dc, int $steps): ?array
{
    $r = $from['r'];
    $cc = $from['c'];
    for ($i = 0; $i < $steps; $i++) {
        $r  += $dr;
        $cc += $dc;
        if (!isset($grid[$r][$cc])) return null;
        if ($grid[$r][$cc] !== '.') return null;
    }
    return ['r' => $r, 'c' => $cc];
}

function tracePath(array $grid, array $start, int $stepA, int $stepB, int $stepC): ?array
{
    if ($stepA === 0 && $stepB === 0 && $stepC === 0) return null;

    $pos = walk($grid, $start, -1, 0, $stepA);
    if ($pos === null) return null;

    $pos = walk($grid, $pos, 0, +1, $stepB);
    if ($pos === null) return null;

    $pos = walk($grid, $pos, +1, 0, $stepC);      
    if ($pos === null) return null;

    if ($grid[$pos['r']][$pos['c']] !== '.') return null;
    if ($pos['r'] === $start['r'] && $pos['c'] === $start['c']) return null;

    return $pos;
}

function findAllReachable(array $grid, array $start): array
{
    $rows    = count($grid);
    $cols    = count($grid[0]);
    $reached = [];

    for ($sa = 0; $sa < $rows; $sa++) {
        for ($sb = 0; $sb < $cols; $sb++) {
            for ($sc = 0; $sc < $rows; $sc++) {
                $result = tracePath($grid, $start, $sa, $sb, $sc);
                if ($result === null) continue;

                // Redundant check — tapi lebih aman
                if ($grid[$result['r']][$result['c']] !== '.') continue;

                $key = "{$result['r']},{$result['c']}";
                if (!isset($reached[$key])) {
                    $reached[$key] = [
                        'r'  => $result['r'],
                        'c'  => $result['c'],
                        'sa' => $sa,
                        'sb' => $sb,
                        'sc' => $sc,
                    ];
                }
            }
        }
    }
    return $reached;
}

function pickHiddenItem(array $reachable, array $excluded): ?array
{
    $candidates = array_filter($reachable, fn($k) => !isset($excluded[$k]), ARRAY_FILTER_USE_KEY);
    if (empty($candidates)) return null;
    $keys = array_keys($candidates);
    return $candidates[$keys[array_rand($keys)]];
}

function renderGrid(array $grid, ?array $guess, ?array $hiddenItem, bool $showItem): void
{
    $rows = count($grid);
    $cols = count($grid[0] ?? []);
    echo "\n";
    for ($r = 0; $r < $rows; $r++) {
        echo col(str_pad((string)$r, 2, ' ', STR_PAD_LEFT), 'gray') . '  ';
        for ($cc = 0; $cc < $cols; $cc++) {
            $cell    = $grid[$r][$cc];
            $isGuess = $guess      && $guess['r']      === $r && $guess['c']      === $cc;
            $isItem  = $hiddenItem && $hiddenItem['r'] === $r && $hiddenItem['c'] === $cc;

            if ($isGuess && $isItem)       echo bold(col('$', 'green'));
            elseif ($isItem && $showItem)  echo bold(col('*', 'red'));
            elseif ($isGuess)              echo col('?', 'yellow');
            elseif ($cell === '#')         echo col('#', 'gray');
            elseif ($cell === 'X')         echo col('X', 'cyan');
            else                           echo col('.', 'white');
            echo ' ';
        }
        echo "\n";
    }
    echo str_repeat(' ', 5);
    for ($cc = 0; $cc < $cols; $cc++) echo col((string)$cc, 'gray') . ' ';
    echo "\n\n";
}

function printSep(int $w = 48): void
{
    echo col(str_repeat('─', $w), 'gray') . "\n";
}

function readInt(string $label, int $min = 0): int
{
    while (true) {
        echo '  ' . col($label, 'cyan') . ': ';
        $raw = trim((string)readline(''));
        if (ctype_digit($raw) && (int)$raw >= $min) return (int)$raw;
        echo col("  Masukkan bilangan bulat >= {$min}.\n", 'red');
    }
}

function playGame(array $grid): void
{
    $start     = findStart($grid);
    $reachable = findAllReachable($grid, $start);

    if (empty($reachable)) {
        echo col("  Tidak ada titik yang bisa dicapai.\n", 'red');
        return;
    }

    echo "\n";
    echo bold(col('  HIDDEN ITEM GAME', 'cyan')) . "\n";
    printSep();
    echo col('  Legend: ', 'gray')
        . col('#', 'gray') . col(' dinding  ', 'gray')
        . col('.', 'white') . col(' jalur  ', 'gray')
        . col('X', 'cyan') . col(' start  ', 'gray')
        . col('?', 'yellow') . col(' tebakan salah  ', 'gray')
        . bold(col('$', 'green')) . col(' ditemukan  ', 'gray')
        . bold(col('*', 'red')) . col(' item asli', 'gray') . "\n";
    printSep();
    echo col("\n  Navigasi: Atas (A) → Kanan (B) → Bawah (C)\n", 'gray');
    echo col("  Temukan item sebelum 5 ronde! Item pindah tiap ronde salah.\n\n", 'gray');
    echo col("  Posisi awal: ", 'gray') . "baris " . bold((string)$start['r']) . ", kolom " . bold((string)$start['c']) . "\n";

    renderGrid($grid, null, null, false);

    $maxRounds    = 5;
    $won          = false;
    $revealedKeys = [];
    $hiddenItem   = pickHiddenItem($reachable, $revealedKeys);

    for ($round = 1; $round <= $maxRounds && !$won; $round++) {
        printSep();
        echo bold("  Ronde {$round} / {$maxRounds}") . "\n\n";

        $a = readInt('Atas  (A)', 0);
        $b = readInt('Kanan (B)', 0);
        $c = readInt('Bawah (C)', 0);

        $destination = tracePath($grid, $start, $a, $b, $c);

        echo "\n";
        printSep();

        if ($destination === null) {
            echo col("\n  [!] Jalur terblokir.\n", 'red');
            if ($round < $maxRounds)
                echo col("  Sisa kesempatan: ", 'gray') . bold((string)($maxRounds - $round)) . "\n\n";
            continue;
        }

        $hit = $destination['r'] === $hiddenItem['r'] && $destination['c'] === $hiddenItem['c'];

        if ($hit) {
            $won = true;
            echo "\n" . bold(col("  ITEM DITEMUKAN!", 'green')) . "\n";
            echo col("  Lokasi: baris ", 'gray') . bold((string)$destination['r']) . col(", kolom ", 'gray') . bold((string)$destination['c']) . "\n";
            renderGrid($grid, $destination, $hiddenItem, true);
            echo col("  Jalur: ", 'gray') . col("Atas {$a}", 'cyan') . " → " . col("Kanan {$b}", 'cyan') . " → " . col("Bawah {$c}", 'cyan') . "\n\n";
        } else {
            echo "\n";
            echo col("  Tebakan: ", 'gray') . "baris " . bold((string)$destination['r']) . ", kolom " . bold((string)$destination['c']) . col(" — item tidak di sini.\n", 'gray');
            echo col("  Lokasi item ronde ini: baris ", 'gray') . bold((string)$hiddenItem['r']) . col(", kolom ", 'gray') . bold((string)$hiddenItem['c']) . "\n";

            if ($round < $maxRounds) {
                echo col("  Sisa kesempatan: ", 'gray') . bold((string)($maxRounds - $round)) . "\n";
                echo col("  Item akan berpindah ke lokasi baru di ronde berikutnya.\n", 'gray');
            }

            renderGrid($grid, $destination, $hiddenItem, true);

            $itemKey = "{$hiddenItem['r']},{$hiddenItem['c']}";
            $revealedKeys[$itemKey] = true;
            if ($round < $maxRounds) {
                $newItem = pickHiddenItem($reachable, $revealedKeys);
                if ($newItem !== null) $hiddenItem = $newItem;
            }
        }
    }

    if (!$won) {
        printSep();
        echo "\n" . bold(col("  GAME OVER", 'red')) . "\n";
        echo col("  Item terakhir: baris ", 'gray') . bold((string)$hiddenItem['r']) . col(", kolom ", 'gray') . bold((string)$hiddenItem['c']) . "\n";
        echo col("  Kombinasi benar: ", 'gray')
            . col("Atas {$hiddenItem['sa']}", 'cyan') . " → "
            . col("Kanan {$hiddenItem['sb']}", 'cyan') . " → "
            . col("Bawah {$hiddenItem['sc']}", 'cyan') . "\n";
        renderGrid($grid, null, $hiddenItem, true);
    }

    printSep();
    echo "\n  " . col("Mau main lagi? (y/n): ", 'gray');
    $again = strtolower(trim((string)readline('')));
    if ($again === 'y') playGame($grid);
    else echo "\n  Terima kasih sudah bermain!\n\n";
}

playGame($GRID);
