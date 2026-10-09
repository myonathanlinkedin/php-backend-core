<?php
declare(strict_types=1);

class RBNode
{
    public const RED = true;
    public const BLACK = false;

    public int $key;
    public $value;
    public bool $color;
    public ?RBNode $left = null;
    public ?RBNode $right = null;
    public ?RBNode $parent = null;

    public function __construct(int $key, $value, bool $color = self::RED, ?RBNode $parent = null)
    {
        $this->key = $key;
        $this->value = $value;
        $this->color = $color;
        $this->parent = $parent;
    }

    public function isRed(): bool
    {
        return $this->color === self::RED;
    }

    public function isBlack(): bool
    {
        return $this->color === self::BLACK;
    }

    public function setColor(bool $color): void
    {
        $this->color = $color;
    }
}

class RedBlackTree
{
    private ?RBNode $root = null;

    public function getRoot(): ?RBNode
    {
        return $this->root;
    }

    public function find(int $key): ?RBNode
    {
        $current = $this->root;
        while ($current !== null) {
            if ($key === $current->key) {
                return $current;
            }
            $current = ($key < $current->key) ? $current->left : $current->right;
        }
        return null;
    }

    public function insert(int $key, $value): void
    {
        $newNode = new RBNode($key, $value);
        $parent = null;
        $current = $this->root;

        while ($current !== null) {
            $parent = $current;
            $current = ($key < $current->key) ? $current->left : $current->right;
        }

        $newNode->parent = $parent;

        if ($parent === null) {
            $this->root = $newNode;
        } elseif ($key < $parent->key) {
            $parent->left = $newNode;
        } else {
            $parent->right = $newNode;
        }

        $this->fixInsert($newNode);
    }

    private function rotateLeft(RBNode $x): void
    {
        $y = $x->right;
        if ($y === null) {
            return;
        }

        $x->right = $y->left;
        if ($y->left !== null) {
            $y->left->parent = $x;
        }

        $y->parent = $x->parent;
        if ($x->parent === null) {
            $this->root = $y;
        } elseif ($x === $x->parent->left) {
            $x->parent->left = $y;
        } else {
            $x->parent->right = $y;
        }

        $y->left = $x;
        $x->parent = $y;
    }

    private function rotateRight(RBNode $y): void
    {
        $x = $y->left;
        if ($x === null) {
            return;
        }

        $y->left = $x->right;
        if ($x->right !== null) {
            $x->right->parent = $y;
        }

        $x->parent = $y->parent;
        if ($y->parent === null) {
            $this->root = $x;
        } elseif ($y === $y->parent->right) {
            $y->parent->right = $x;
        } else {
            $y->parent->left = $x;
        }

        $x->right = $y;
        $y->parent = $x;
    }

    private function fixInsert(RBNode $z): void
    {
        while ($z->parent !== null && $z->parent->isRed()) {
            $parent = $z->parent;
            $grandparent = $parent->parent;
            if ($grandparent === null) {
                break;
            }

            if ($parent === $grandparent->left) {
                $uncle = $grandparent->right;
                if ($uncle !== null && $uncle->isRed()) {
                    $parent->setColor(RBNode::BLACK);
                    $uncle->setColor(RBNode::BLACK);
                    $grandparent->setColor(RBNode::RED);
                    $z = $grandparent;
                } else {
                    if ($z === $parent->right) {
                        $z = $parent;
                        $this->rotateLeft($z);
                    }
                    $parent->setColor(RBNode::BLACK);
                    $grandparent->setColor(RBNode::RED);
                    $this->rotateRight($grandparent);
                }
            } else {
                $uncle = $grandparent->left;
                if ($uncle !== null && $uncle->isRed()) {
                    $parent->setColor(RBNode::BLACK);
                    $uncle->setColor(RBNode::BLACK);
                    $grandparent->setColor(RBNode::RED);
                    $z = $grandparent;
                } else {
                    if ($z === $parent->left) {
                        $z = $parent;
                        $this->rotateRight($z);
                    }
                    $parent->setColor(RBNode::BLACK);
                    $grandparent->setColor(RBNode::RED);
                    $this->rotateLeft($grandparent);
                }
            }
        }
        if ($this->root !== null) {
            $this->root->setColor(RBNode::BLACK);
        }
    }

    public function inorder(): array
    {
        $result = [];
        $this->inorderRecursive($this->root, $result);
        return $result;
    }

    private function inorderRecursive(?RBNode $node, array &$out): void
    {
        if ($node === null) {
            return;
        }
        $this->inorderRecursive($node->left, $out);
        $out[] = $node->key;
        $this->inorderRecursive($node->right, $out);
    }

    /**
     * Validates Red-Black properties.
     * Returns true if tree satisfies all invariants.
     */
    public function validate(): bool
    {
        if ($this->root === null) {
            return true;
        }

        // Property 2: root is black
        if ($this->root->isRed()) {
            return false;
        }

        // Property 4: no red node has red child
        // Property 5: all paths have same black-height
        $blackCount = -1;
        return $this->validateRecursive($this->root, 0, $blackCount);
    }

    private function validateRecursive(?RBNode $node, int $blackSoFar, int &$expectedBlack): bool
    {
        if ($node === null) {
            // Reached leaf (null), treat as black node
            if ($expectedBlack === -1) {
                $expectedBlack = $blackSoFar;
                return true;
            }
            return $blackSoFar === $expectedBlack;
        }

        if ($node->isRed()) {
            // Red node must have black children
            if (($node->left !== null && $node->left->isRed()) ||
                ($node->right !== null && $node->right->isRed())) {
                return false;
            }
        } else {
            $blackSoFar++;
        }

        $leftOk = $this->validateRecursive($node->left, $blackSoFar, $expectedBlack);
        if (!$leftOk) {
            return false;
        }
        $rightOk = $this->validateRecursive($node->right, $blackSoFar, $expectedBlack);
        return $rightOk;
    }
}
