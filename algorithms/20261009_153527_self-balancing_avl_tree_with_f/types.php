<?php
declare(strict_types=1);

/**
 * AVL Tree node definition.
 */
class AVLNode
{
    public int $key;
    public mixed $value;
    public ?AVLNode $left = null;
    public ?AVLNode $right = null;
    public int $height = 1; // Height of node in tree (leaf = 1)

    public function __construct(int $key, mixed $value)
    {
        $this->key   = $key;
        $this->value = $value;
    }
}
