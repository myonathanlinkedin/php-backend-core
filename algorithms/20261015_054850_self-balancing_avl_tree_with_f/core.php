<?php
declare(strict_types=1);

final class AVLNode
{
    public int $key;
    public mixed $value;
    public ?AVLNode $left = null;
    public ?AVLNode $right = null;
    public int $height = 1;

    public function __construct(int $key, mixed $value = null)
    {
        $this->key   = $key;
        $this->value = $value;
    }
}

final class AVLTree
{
    private ?AVLNode $root = null;

    public function insert(int $key, mixed $value = null): void
    {
        $this->root = $this->insertNode($this->root, $key, $value);
    }

    public function delete(int $key): void
    {
        $this->root = $this->deleteNode($this->root, $key);
    }

    public function find(int $key): ?AVLNode
    {
        $cur = $this->root;
        while ($cur !== null) {
            if ($key === $cur->key) {
                return $cur;
            }
            $cur = ($key < $cur->key) ? $cur->left : $cur->right;
        }
        return null;
    }

    /** @return int[] */
    public function inorder(): array
    {
        $result = [];
        $this->inorderTraverse($this->root, $result);
        return $result;
    }

    public function getRoot(): ?AVLNode
    {
        return $this->root;
    }

    // ---------- internal helpers ----------
    private function height(?AVLNode $node): int
    {
        return $node?->height ?? 0;
    }

    private function getBalance(?AVLNode $node): int
    {
        return $node ? $this->height($node->left) - $this->height($node->right) : 0;
    }

    private function updateHeight(AVLNode $node): void
    {
        $node->height = max($this->height($node->left), $this->height($node->right)) + 1;
    }

    private function rotateRight(AVLNode $y): AVLNode
    {
        $x = $y->left;
        $T2 = $x?->right;

        // rotation
        $x->right = $y;
        $y->left = $T2;

        // update heights
        $this->updateHeight($y);
        $this->updateHeight($x);

        return $x;
    }

    private function rotateLeft(AVLNode $x): AVLNode
    {
        $y = $x->right;
        $T2 = $y?->left;

        // rotation
        $y->left = $x;
        $x->right = $T2;

        // update heights
        $this->updateHeight($x);
        $this->updateHeight($y);

        return $y;
    }

    private function insertNode(?AVLNode $node, int $key, mixed $value): AVLNode
    {
        if ($node === null) {
            return new AVLNode($key, $value);
        }

        if ($key < $node->key) {
            $node->left = $this->insertNode($node->left, $key, $value);
        } elseif ($key > $node->key) {
            $node->right = $this->insertNode($node->right, $key, $value);
        } else {
            // duplicate keys are ignored (or could update value)
            return $node;
        }

        $this->updateHeight($node);
        $balance = $this->getBalance($node);

        // LL
        if ($balance > 1 && $key < $node->left->key) {
            return $this->rotateRight($node);
        }
        // RR
        if ($balance < -1 && $key > $node->right->key) {
            return $this->rotateLeft($node);
        }
        // LR
        if ($balance > 1 && $key > $node->left->key) {
            $node->left = $this->rotateLeft($node->left);
            return $this->rotateRight($node);
        }
        // RL
        if ($balance < -1 && $key < $node->right->key) {
            $node->right = $this->rotateRight($node->right);
            return $this->rotateLeft($node);
        }

        return $node;
    }

    private function minValueNode(AVLNode $node): AVLNode
    {
        $current = $node;
        while ($current->left !== null) {
            $current = $current->left;
        }
        return $current;
    }

    private function deleteNode(?AVLNode $root, int $key): ?AVLNode
    {
        if ($root === null) {
            return null;
        }

        if ($key < $root->key) {
            $root->left = $this->deleteNode($root->left, $key);
        } elseif ($key > $root->key) {
            $root->right = $this->deleteNode($root->right, $key);
        } else {
            // node with only one child or no child
            if ($root->left === null) {
                return $root->right;
            } elseif ($root->right === null) {
                return $root->left;
            }

            // node with two children: get inorder successor
            $temp = $this->minValueNode($root->right);
            $root->key = $temp->key;
            $root->value = $temp->value;
            $root->right = $this->deleteNode($root->right, $temp->key);
        }

        $this->updateHeight($root);
        $balance = $this->getBalance($root);

        // LL
        if ($balance > 1 && $this->getBalance($root->left) >= 0) {
            return $this->rotateRight($root);
        }
        // LR
        if ($balance > 1 && $this->getBalance($root->left) < 0) {
            $root->left = $this->rotateLeft($root->left);
            return $this->rotateRight($root);
        }
        // RR
        if ($balance < -1 && $this->getBalance($root->right) <= 0) {
            return $this->rotateLeft($root);
        }
        // RL
        if ($balance < -1 && $this->getBalance($root->right) > 0) {
            $root->right = $this->rotateRight($root->right);
            return $this->rotateLeft($root);
        }

        return $root;
    }

    private function inorderTraverse(?AVLNode $node, array &$out): void
    {
        if ($node === null) {
            return;
        }
        $this->inorderTraverse($node->left, $out);
        $out[] = $node->key;
        $this->inorderTraverse($node->right, $out);
    }
}
