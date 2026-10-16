<?php
declare(strict_types=1);

require_once __DIR__ . '/types.php';

/**
 * Self‑balancing AVL Tree implementation.
 *
 * Supports insertion, deletion, lookup and inorder traversal.
 * All operations maintain the AVL balance invariant:
 *   for every node, |height(left) – height(right)| ≤ 1
 */
class AVLTree
{
    private ?AVLNode $root = null;

    /** Insert a key/value pair. If key exists, its value is replaced. */
    public function insert(int $key, mixed $value): void
    {
        $this->root = $this->insertNode($this->root, $key, $value);
    }

    /** Delete a key from the tree. No effect if key not present. */
    public function delete(int $key): void
    {
        $this->root = $this->deleteNode($this->root, $key);
    }

    /** Find value by key; returns null if not found. */
    public function find(int $key): mixed
    {
        $node = $this->searchNode($this->root, $key);
        return $node?->value;
    }

    /** Return an array of keys in inorder (sorted) order. */
    public function inorder(): array
    {
        $result = [];
        $this->inorderTraversal($this->root, $result);
        return $result;
    }

    /** Return an array of keys in preorder order. */
    public function preorder(): array
    {
        $result = [];
        $this->preorderTraversal($this->root, $result);
        return $result;
    }

    /** Return an array of keys in postorder order. */
    public function postorder(): array
    {
        $result = [];
        $this->postorderTraversal($this->root, $result);
        return $result;
    }

    /** Validate AVL invariant for the whole tree; returns true if valid. */
    public function isValidAVL(): bool
    {
        return $this->validateAVL($this->root) !== -1;
    }

    // -----------------------------------------------------------------
    // Internal recursive helpers
    // -----------------------------------------------------------------

    private function height(?AVLNode $node): int
    {
        return $node?->height ?? 0;
    }

    private function getBalance(?AVLNode $node): int
    {
        if ($node === null) {
            return 0;
        }
        return $this->height($node->left) - $this->height($node->right);
    }

    private function rightRotate(AVLNode $y): AVLNode
    {
        $x = $y->left;
        $T2 = $x?->right;

        // Perform rotation
        $x->right = $y;
        $y->left = $T2;

        // Update heights
        $y->height = max($this->height($y->left), $this->height($y->right)) + 1;
        $x->height = max($this->height($x->left), $this->height($x->right)) + 1;

        // New root
        return $x;
    }

    private function leftRotate(AVLNode $x): AVLNode
    {
        $y = $x->right;
        $T2 = $y?->left;

        // Perform rotation
        $y->left = $x;
        $x->right = $T2;

        // Update heights
        $x->height = max($this->height($x->left), $this->height($x->right)) + 1;
        $y->height = max($this->height($y->left), $this->height($y->right)) + 1;

        // New root
        return $y;
    }

    private function insertNode(?AVLNode $node, int $key, mixed $value): AVLNode
    {
        // Normal BST insertion
        if ($node === null) {
            return new AVLNode($key, $value);
        }

        if ($key < $node->key) {
            $node->left = $this->insertNode($node->left, $key, $value);
        } elseif ($key > $node->key) {
            $node->right = $this->insertNode($node->right, $key, $value);
        } else {
            // Duplicate key – replace value
            $node->value = $value;
            return $node;
        }

        // Update height
        $node->height = max($this->height($node->left), $this->height($node->right)) + 1;

        // Rebalance if needed
        $balance = $this->getBalance($node);

        // Left Left Case
        if ($balance > 1 && $key < $node->left->key) {
            return $this->rightRotate($node);
        }

        // Right Right Case
        if ($balance < -1 && $key > $node->right->key) {
            return $this->leftRotate($node);
        }

        // Left Right Case
        if ($balance > 1 && $key > $node->left->key) {
            $node->left = $this->leftRotate($node->left);
            return $this->rightRotate($node);
        }

        // Right Left Case
        if ($balance < -1 && $key < $node->right->key) {
            $node->right = $this->rightRotate($node->right);
            return $this->leftRotate($node);
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

        // Standard BST delete
        if ($key < $root->key) {
            $root->left = $this->deleteNode($root->left, $key);
        } elseif ($key > $root->key) {
            $root->right = $this->deleteNode($root->right, $key);
        } else {
            // Node with only one child or no child
            if ($root->left === null) {
                return $root->right;
            } elseif ($root->right === null) {
                return $root->left;
            }

            // Node with two children: get inorder successor
            $temp = $this->minValueNode($root->right);
            $root->key   = $temp->key;
            $root->value = $temp->value;
            $root->right = $this->deleteNode($root->right, $temp->key);
        }

        // Update height
        $root->height = max($this->height($root->left), $this->height($root->right)) + 1;

        // Rebalance
        $balance = $this->getBalance($root);

        // Left Left
        if ($balance > 1 && $this->getBalance($root->left) >= 0) {
            return $this->rightRotate($root);
        }

        // Left Right
        if ($balance > 1 && $this->getBalance($root->left) < 0) {
            $root->left = $this->leftRotate($root->left);
            return $this->rightRotate($root);
        }

        // Right Right
        if ($balance < -1 && $this->getBalance($root->right) <= 0) {
            return $this->leftRotate($root);
        }

        // Right Left
        if ($balance < -1 && $this->getBalance($root->right) > 0) {
            $root->right = $this->rightRotate($root->right);
            return $this->leftRotate($root);
        }

        return $root;
    }

    private function searchNode(?AVLNode $node, int $key): ?AVLNode
    {
        while ($node !== null) {
            if ($key === $node->key) {
                return $node;
            }
            $node = ($key < $node->key) ? $node->left : $node->right;
        }
        return null;
    }

    private function inorderTraversal(?AVLNode $node, array &$out): void
    {
        if ($node === null) {
            return;
        }
        $this->inorderTraversal($node->left, $out);
        $out[] = $node->key;
        $this->inorderTraversal($node->right, $out);
    }

    private function preorderTraversal(?AVLNode $node, array &$out): void
    {
        if ($node === null) {
            return;
        }
        $out[] = $node->key;
        $this->preorderTraversal($node->left, $out);
        $this->preorderTraversal($node->right, $out);
    }

    private function postorderTraversal(?AVLNode $node, array &$out): void
    {
        if ($node === null) {
            return;
        }
        $this->postorderTraversal($node->left, $out);
        $this->postorderTraversal($node->right, $out);
        $out[] = $node->key;
    }

    /**
     * Returns height of subtree if AVL, otherwise -1.
     */
    private function validateAVL(?AVLNode $node): int
    {
        if ($node === null) {
            return 0;
        }

        $lh = $this->validateAVL($node->left);
        if ($lh === -1) {
            return -1;
        }
        $rh = $this->validateAVL($node->right);
        if ($rh === -1) {
            return -1;
        }

        if (abs($lh - $rh) > 1) {
            return -1;
        }

        return max($lh, $rh) + 1;
    }
}
