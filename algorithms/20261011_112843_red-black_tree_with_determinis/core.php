<?php
declare(strict_types=1);

final class RBNode
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

final class RedBlackTree
{
    private ?RBNode $root = null;

    public function insert(int $key, $value): void
    {
        $newNode = new RBNode($key, $value);
        $parent = null;
        $current = $this->root;

        while ($current !== null) {
            $parent = $current;
            if ($key < $current->key) {
                $current = $current->left;
            } elseif ($key > $current->key) {
                $current = $current->right;
            } else {
                // Replace existing value
                $current->value = $value;
                return;
            }
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

    public function find(int $key)
    {
        $node = $this->root;
        while ($node !== null) {
            if ($key < $node->key) {
                $node = $node->left;
            } elseif ($key > $node->key) {
                $node = $node->right;
            } else {
                return $node->value;
            }
        }
        return null;
    }

    public function isValid(): bool
    {
        if ($this->root === null) {
            return true;
        }
        // Property 2: root is black
        if ($this->root->isRed()) {
            return false;
        }
        // Property 4: red nodes have black children
        // Property 5: same black-height for all leaves
        $blackHeight = null;
        return $this->validateNode($this->root, 0, $blackHeight);
    }

    private function validateNode(?RBNode $node, int $blackCount, ?int &$blackHeight): bool
    {
        if ($node === null) {
            if ($blackHeight === null) {
                $blackHeight = $blackCount;
                return true;
            }
            return $blackCount === $blackHeight;
        }

        if ($node->isBlack()) {
            $blackCount++;
        } else {
            // Red node must have black children
            if (($node->left !== null && $node->left->isRed()) ||
                ($node->right !== null && $node->right->isRed())) {
                return false;
            }
        }

        return $this->validateNode($node->left, $blackCount, $blackHeight) &&
               $this->validateNode($node->right, $blackCount, $blackHeight);
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
                        $parent = $z->parent;
                        $grandparent = $parent->parent;
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
                        $parent = $z->parent;
                        $grandparent = $parent->parent;
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

    // Helper for testing: in-order traversal returning keys
    public function inorderKeys(): array
    {
        $result = [];
        $this->inorder($this->root, $result);
        return $result;
    }

    private function inorder(?RBNode $node, array &$out): void
    {
        if ($node === null) {
            return;
        }
        $this->inorder($node->left, $out);
        $out[] = $node->key;
        $this->inorder($node->right, $out);
    }
}
