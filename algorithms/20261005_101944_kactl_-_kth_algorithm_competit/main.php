<?php
declare(strict_types=1);

/**
 * KACTL-like utility library in pure PHP.
 * All classes are self‑contained and type‑safe.
 */

assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_BAIL, 1);

/* ---------- Math utilities ---------- */
final class Math
{
    /** Greatest common divisor (Euclidean algorithm) */
    public static function gcd(int $a, int $b): int
    {
        $a = abs($a);
        $b = abs($b);
        while ($b !== 0) {
            $tmp = $a % $b;
            $a = $b;
            $b = $tmp;
        }
        return $a;
    }

    /** Least common multiple */
    public static function lcm(int $a, int $b): int
    {
        return intdiv($a / self::gcd($a, $b) * $b, 1);
    }

    /** Modular exponentiation (binary exponentiation) */
    public static function modPow(int $base, int $exp, int $mod): int
    {
        $base %= $mod;
        $result = 1;
        while ($exp > 0) {
            if ($exp & 1) {
                $result = (int)(($result * $base) % $mod);
            }
            $base = (int)(($base * $base) % $mod);
            $exp >>= 1;
        }
        return $result;
    }

    /** Extended Euclidean algorithm – returns [g, x, y] such that ax + by = g = gcd(a,b) */
    private static function egcd(int $a, int $b): array
    {
        if ($b === 0) {
            return [$a, 1, 0];
        }
        [$g, $x1, $y1] = self::egcd($b, $a % $b);
        return [$g, $y1, $x1 - intdiv($a, $b) * $y1];
    }

    /** Modular inverse (a and mod must be coprime) */
    public static function modInv(int $a, int $mod): int
    {
        [$g, $x, $y] = self::egcd($a, $mod);
        if ($g !== 1) {
            throw new \InvalidArgumentException('Inverse does not exist');
        }
        return ($x % $mod + $mod) % $mod;
    }

    /** Miller–Rabin deterministic for 32‑bit integers */
    public static function isPrime(int $n): bool
    {
        if ($n < 2) {
            return false;
        }
        foreach ([2, 3, 5, 7, 11, 13, 17, 19, 23, 29, 31, 37] as $p) {
            if ($n % $p === 0) {
                return $n === $p;
            }
        }
        $d = $n - 1;
        $s = 0;
        while (($d & 1) === 0) {
            $d >>= 1;
            $s++;
        }
        $witnesses = [2, 7, 61]; // enough for 32‑bit
        foreach ($witnesses as $a) {
            if ($a % $n === 0) {
                continue;
            }
            $x = self::modPow($a, $d, $n);
            if ($x === 1 || $x === $n - 1) {
                continue;
            }
            $composite = true;
            for ($r = 1; $r < $s; $r++) {
                $x = (int)(($x * $x) % $n);
                if ($x === $n - 1) {
                    $composite = false;
                    break;
                }
            }
            if ($composite) {
                return false;
            }
        }
        return true;
    }
}

/* ---------- Fenwick Tree (Binary Indexed Tree) ---------- */
final class FenwickTree
{
    private array $bit;
    private int $n;

    public function __construct(int $size)
    {
        $this->n = $size;
        $this->bit = array_fill(0, $size + 1, 0);
    }

    /** Add $delta at position $idx (1‑based) */
    public function add(int $idx, int $delta): void
    {
        for (; $idx <= $this->n; $idx += $idx & -$idx) {
            $this->bit[$idx] += $delta;
        }
    }

    /** Prefix sum [1..$idx] */
    public function sum(int $idx): int
    {
        $res = 0;
        for (; $idx > 0; $idx -= $idx & -$idx) {
            $res += $this->bit[$idx];
        }
        return $res;
    }

    /** Range sum [$l, $r] inclusive, 1‑based */
    public function rangeSum(int $l, int $r): int
    {
        return $this->sum($r) - $this->sum($l - 1);
    }
}

/* ---------- Segment Tree (range sum) ---------- */
final class SegmentTree
{
    private array $tree;
    private int $size;

    public function __construct(array $arr)
    {
        $n = count($arr);
        $this->size = 1;
        while ($this->size < $n) {
            $this->size <<= 1;
        }
        $this->tree = array_fill(0, $this->size * 2, 0);
        for ($i = 0; $i < $n; $i++) {
            $this->tree[$this->size + $i] = $arr[$i];
        }
        for ($i = $this->size - 1; $i > 0; $i--) {
            $this->tree[$i] = $this->tree[$i << 1] + $this->tree[($i << 1) | 1];
        }
    }

    /** Point update: set position $idx (0‑based) to $value */
    public function set(int $idx, int $value): void
    {
        $pos = $this->size + $idx;
        $this->tree[$pos] = $value;
        while ($pos > 1) {
            $pos >>= 1;
            $this->tree[$pos] = $this->tree[$pos << 1] + $this->tree[($pos << 1) | 1];
        }
    }

    /** Range sum query [$l, $r] inclusive, 0‑based */
    public function query(int $l, int $r): int
    {
        $l += $this->size;
        $r += $this->size;
        $res = 0;
        while ($l <= $r) {
            if (($l & 1) === 1) {
                $res += $this->tree[$l++];
            }
            if (($r & 1) === 0) {
                $res += $this->tree[$r--];
            }
            $l >>= 1;
            $r >>= 1;
        }
        return $res;
    }
}

/* ---------- Disjoint Set Union (Union‑Find) ---------- */
final class UnionFind
{
    private array $parent;
    private array $size;

    public function __construct(int $n)
    {
        $this->parent = range(0, $n - 1);
        $this->size = array_fill(0, $n, 1);
    }

    public function find(int $x): int
    {
        if ($this->parent[$x] !== $x) {
            $this->parent[$x] = $this->find($this->parent[$x]);
        }
        return $this->parent[$x];
    }

    public function union(int $a, int $b): bool
    {
        $ra = $this->find($a);
        $rb = $this->find($b);
        if ($ra === $rb) {
            return false;
        }
        if ($this->size[$ra] < $this->size[$rb]) {
            [$ra, $rb] = [$rb, $ra];
        }
        $this->parent[$rb] = $ra;
        $this->size[$ra] += $this->size[$rb];
        return true;
    }

    public function same(int $a, int $b): bool
    {
        return $this->find($a) === $this->find($b);
    }

    public function componentSize(int $x): int
    {
        return $this->size[$this->find($x)];
    }
}

/* ---------- Dijkstra's shortest path ---------- */
final class Dijkstra
{
    /**
     * @param array<int, array<int, array{int,int}>> $graph adjacency list: $graph[u] = [[v, w], ...]
     * @return array<int, int> distances from $src (INF = PHP_INT_MAX)
     */
    public static function shortestPath(array $graph, int $src): array
    {
        $n = count($graph);
        $dist = array_fill(0, $n, PHP_INT_MAX);
        $dist[$src] = 0;
        $pq = new SplPriorityQueue();
        $pq->setExtractFlags(SplPriorityQueue::EXTR_BOTH);
        $pq->insert($src, 0); // SplPriorityQueue is max‑heap, use negative priority
        while (!$pq->isEmpty()) {
            $item = $pq->extract();
            $u = $item['data'];
            $d = -$item['priority'];
            if ($d !== $dist[$u]) {
                continue;
            }
            foreach ($graph[$u] as $edge) {
                [$v, $w] = $edge;
                $nd = $d + $w;
                if ($nd < $dist[$v]) {
                    $dist[$v] = $nd;
                    $pq->insert($v, -$nd);
                }
            }
        }
        return $dist;
    }
}

/* ---------- Unit Tests ---------- */
function testMath(): void
{
    assert(Math::gcd(48, 18) === 6);
    assert(Math::lcm(4, 6) === 12);
    assert(Math::modPow(2, 10, 1000) === 24);
    assert(Math::modInv(3, 11) === 4);
    assert(Math::isPrime(2));
    assert(Math::isPrime(7919));
    assert(!Math::isPrime(8000));
}
function testFenwick(): void
{
    $ft = new FenwickTree(5);
    $ft->add(1, 3);
    $ft->add(2, 2);
    $ft->add(5, 5);
    assert($ft->sum(2) === 5);
    assert($ft->rangeSum(2,5) === 7);
}
function testSegmentTree(): void
{
    $arr = [1, 3, 5, 7, 9];
    $st = new SegmentTree($arr);
    assert($st->query(0,4) === 25);
    assert($st->query(1,3) === 15);
    $st->set(2, 10);
    assert($st->query(0,4) === 30);
}
function testUnionFind(): void
{
    $uf = new UnionFind(5);
    assert($uf->union(0,1));
    assert($uf->union(1,2));
    assert(!$uf->union(0,2));
    assert($uf->same(0,2));
    assert($uf->componentSize(0) === 3);
}
function testDijkstra(): void
{
    // graph: 0-1(4), 0-2(1), 2-1(2), 1-3(1), 2-3(5)
    $g = [
        [[1,4], [2,1]],
        [[0,4], [2,2], [3,1]],
        [[0,1], [1,2], [3,5]],
        [[1,1], [2,5]],
    ];
    $dist = Dijkstra::shortestPath($g, 0);
    assert($dist[0] === 0);
    assert($dist[1] === 3);
    assert($dist[2] === 1);
    assert($dist[3] === 4);
}
testMath();
testFenwick();
testSegmentTree();
testUnionFind();
testDijkstra();

echo "All tests passed.\n";
