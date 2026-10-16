<?php

interface Element {
    public function getId(): int;
    public function getWeight(): float;
}

class SimpleElement implements Element {
    private int $id;
    private float $weight;

    public function __construct(int $id, float $weight) {
        $this->id = $id;
        $this->weight = $weight;
    }

    public function getId(): int {
        return $this->id;
    }

    public function getWeight(): float {
        return $this->weight;
    }
}

interface Matroid {
    public function isIndependent(array $elements): bool;
    public function rank(): int;
}

class UniformMatroid implements Matroid {
    private int $rank;

    public function __construct(int $rank) {
        $this->rank = $rank;
    }

    public function isIndependent(array $elements): bool {
        return count($elements) <= $this->rank;
    }

    public function rank(): int {
        return $this->rank;
    }
}

interface SubmodularFunction {
    public function value(array $elements): float;
    public function marginalGain(Element $e, array $elements): float;
}

class WeightedCardinalityFunction implements SubmodularFunction {
    public function value(array $elements): float {
        $sum = 0.0;
        foreach ($elements as $e) {
            $sum += $e->getWeight();
        }
        return $sum;
    }

    public function marginalGain(Element $e, array $elements): float {
        return $e->getWeight();
    }
}
