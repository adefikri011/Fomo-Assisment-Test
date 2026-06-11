<?php

/**
 * Hidden Item Game
 *
 * A CLI game where the player navigates a grid (Up → Right → Down)
 * to find a hidden item. The program determines all valid destination
 * coordinates reachable by the given steps.
 */

// ---------------------------------------------------------------------------
// Grid definition
// ---------------------------------------------------------------------------

$GRID = [
    ['#', '#', '#', '#', '#', '#', '#', '#'],
    ['#', '.', '.', '.', '.', '.', '.', '#'],
    ['#', '.', '#', '#', '#', '.', '.', '#'],
    ['#', '.', '.', '.', '#', '.', '#', '#'],
    ['#', 'X', '#', '.', '.', '.', '.', '#'],
    ['#', '#', '#', '#', '#', '#', '#', '#'],
];

// ---------------------------------------------------------------------------
// ANSI colour helpers
// ---------------------------------------------------------------------------

function ansi(string $code): string
{
    return "\033[{$code}m";
}

function bold(string $text): string
{
    return ansi('1') . $text . ansi('0');
}

function color(string $text, string $fg): string
{
    $codes = [
        'red'     => '31',
        'green'   => '32',
        'yellow'  => '33',
        'cyan'    => '36',
        'white'   => '37',
        'gray'    => '90',
        'reset'   => '0',
    ];

    return ansi($codes[$fg] ?? '0') . $text . ansi('0');
}

// ---------------------------------------------------------------------------
// Grid helpers
// ---------------------------------------------------------------------------

/**
 * Find the player's starting position (cell marked 'X').
 *
 * @param  array<int, array<int, string>> $grid
 * @return array{r: int, c: int}
 */
function findStart(array $grid): array
{
    foreach ($grid as $r => $row) {
        foreach ($row as $c => $cell) {
            if ($cell === 'X') {
                return ['r' => $r, 'c' => $c];
            }
        }
    }

    throw new RuntimeException('Starting position X not found in grid.');
}

/**
 * Returns true when the coordinate exists and is walkable (not a wall).
 *
 * @param  array<int, array<int, string>> $grid
 */
function isWalkable(array $grid, int $r, int $c): bool
{
    return isset($grid[$r][$c]) && $grid[$r][$c] !== '#';
}

/**
 * Walk a single direction step-by-step, returning the final position or null
 * if the path is blocked at any point.
 *
 * @param  array<int, array<int, string>> $grid
 * @param  array{r: int, c: int}         $from
 * @return array{r: int, c: int}|null
 */
function walk(array $grid, array $from, int $dr, int $dc, int $steps): ?array
{
    $r = $from['r'];
    $c = $from['c'];

    for ($i = 0; $i < $steps; $i++) {
        $r += $dr;
        $c += $dc;

        if (!isWalkable($grid, $r, $c)) {
            return null;
        }
    }

    return ['r' => $r, 'c' => $c];
}

/**
 * Trace the full path Up → Right → Down.
 * Returns the final position, or null if the path is blocked anywhere.
 *
 * @param  array<int, array<int, string>> $grid
 * @param  array{r: int, c: int}         $start
 * @return array{r: int, c: int}|null
 */
function tracePath(array $grid, array $start, int $a, int $b, int $c): ?array
{
    $pos = walk($grid, $start, -1, 0, $a);   // Up
    if ($pos === null) return null;

    $pos = walk($grid, $pos,   0, +1, $b);  // Right
    if ($pos === null) return null;

    $pos = walk($grid, $pos,  +1, 0, $c);   // Down
    return $pos;
}

// ---------------------------------------------------------------------------
// Output helpers
// ---------------------------------------------------------------------------

function printSeparator(int $width = 44): void
{
    echo color(str_repeat('─', $width), 'gray') . "\n";
}

function printHeader(): void
{
    echo "\n";
    echo bold(color('  HIDDEN ITEM GAME', 'cyan')) . "\n";
    printSeparator();
    echo color("  Grid legend:", 'gray') . "  ";
    echo color('#', 'red')    . " wall   ";
    echo color('.', 'white')  . " path   ";
    echo color('X', 'yellow') . " start  ";
    echo color('$', 'green')  . " item\n";
    printSeparator();
}

/**
 * Render the grid, optionally marking a destination with '$'.
 *
 * @param  array<int, array<int, string>>  $grid
 * @param  array{r: int, c: int}|null     $destination
 */
function renderGrid(array $grid, ?array $destination): void
{
    $rows = count($grid);
    $cols = count($grid[0] ?? []);

    $labelW = strlen((string) ($rows - 1));

    echo "\n";

    for ($r = 0; $r < $rows; $r++) {
        echo color(str_pad((string) $r, $labelW + 1, ' ', STR_PAD_LEFT), 'gray') . '  ';

        for ($c = 0; $c < $cols; $c++) {
            $cell = $grid[$r][$c];
            $isTarget = $destination && $destination['r'] === $r && $destination['c'] === $c;

            if ($isTarget) {
                echo color('$', 'green');
            } elseif ($cell === '#') {
                echo color('#', 'gray');
            } elseif ($cell === 'X') {
                echo color('X', 'yellow');
            } else {
                echo color('.', 'white');
            }

            echo ' ';
        }

        echo "\n";
    }

    // Column index labels
    echo str_repeat(' ', $labelW + 3);
    for ($c = 0; $c < $cols; $c++) {
        echo color((string) $c, 'gray') . ' ';
    }

    echo "\n\n";
}

// ---------------------------------------------------------------------------
// Input helpers
// ---------------------------------------------------------------------------

function readInt(string $label): int
{
    while (true) {
        echo "  " . color($label, 'cyan') . ": ";
        $raw = trim((string) readline(''));

        if (ctype_digit($raw) && $raw !== '') {
            return (int) $raw;
        }

        echo color("  Please enter a non-negative integer.\n", 'red');
    }
}

// ---------------------------------------------------------------------------
// Entry point
// ---------------------------------------------------------------------------

printHeader();

$start = findStart($GRID);
echo color("  Starting position", 'gray') . ": row {$start['r']}, col {$start['c']}\n\n";
renderGrid($GRID, null);

echo bold("  Enter movement steps:\n\n");
$a = readInt('Up    (A)');
$b = readInt('Right (B)');
$c = readInt('Down  (C)');

echo "\n";
printSeparator();

$destination = tracePath($GRID, $start, $a, $b, $c);

if ($destination === null) {
    echo "\n";
    echo color("  [!] Path blocked", 'red') . " — the route hits a wall or leaves the grid.\n";
    echo color("      No valid item locations found.\n", 'gray');
} else {
    echo "\n";
    echo color("  [+] Item location found:\n\n", 'green');
    echo "      Row " . bold((string) $destination['r'])
       . ", Column " . bold((string) $destination['c']) . "\n";
    echo "\n";

    renderGrid($GRID, $destination);

    echo color("  Path taken:  ", 'gray')
       . color("Up {$a}", 'cyan')   . " → "
       . color("Right {$b}", 'cyan') . " → "
       . color("Down {$c}", 'cyan')  . "\n";
}

echo "\n";
printSeparator();
echo "\n";