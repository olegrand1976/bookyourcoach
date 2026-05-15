<?php

namespace App\Support;

/**
 * Disjoint-set for merging family / subscription groups by integer IDs.
 */
final class UnionFind
{
    /** @var array<int, int> */
    private array $parent = [];

    /**
     * @param  array<int>  $elements
     */
    public function __construct(array $elements)
    {
        foreach ($elements as $e) {
            $this->parent[(int) $e] = (int) $e;
        }
    }

    public function find(int $x): int
    {
        if (! isset($this->parent[$x])) {
            $this->parent[$x] = $x;
        }

        if ($this->parent[$x] !== $x) {
            $this->parent[$x] = $this->find($this->parent[$x]);
        }

        return $this->parent[$x];
    }

    public function union(int $a, int $b): void
    {
        $ra = $this->find($a);
        $rb = $this->find($b);
        if ($ra !== $rb) {
            $this->parent[$rb] = $ra;
        }
    }
}
