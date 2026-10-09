<?php

require_once 'types.php';

class MatroidSecretaryAlgorithm {
    private Matroid $matroid;
    private SubmodularFunction $function;
    private float $threshold;

    public function __construct(Matroid $matroid, SubmodularFunction $function) {
        $this->matroid = $matroid;
        $this->function = $function;
    }

    public function run(array $elements): array {
        shuffle($elements);
        $this->threshold = mt_rand() / mt_getrandmax();
        $selected = [];
        foreach ($elements as $e) {
            $marginal = $this->function->marginalGain($e, $selected);
            if ($marginal > $this->threshold && $this->matroid->isIndependent(array_merge($selected, [$e]))) {
                $selected[] = $e;
            }
        }
        return $selected;
    }

    public function getThreshold(): float {
        return $this->threshold;
    }
}
