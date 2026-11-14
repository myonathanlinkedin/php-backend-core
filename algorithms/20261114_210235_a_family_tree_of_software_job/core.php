<?php
declare(strict_types=1);

class JobTitleNode
{
    private string $title;
    private ?JobTitleNode $parent;
    /** @var JobTitleNode[] */
    private array $children = [];

    public function __construct(string $title, ?JobTitleNode $parent = null)
    {
        $this->title = $title;
        $this->parent = $parent;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getParent(): ?JobTitleNode
    {
        return $this->parent;
    }

    /**
     * @return JobTitleNode[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    public function addChild(string $title): JobTitleNode
    {
        $child = new self($title, $this);
        $this->children[] = $child;
        return $child;
    }

    public function find(string $title): ?JobTitleNode
    {
        if ($this->title === $title) {
            return $this;
        }
        foreach ($this->children as $child) {
            $found = $child->find($title);
            if ($found !== null) {
                return $found;
            }
        }
        return null;
    }

    /**
     * @return string[] ordered from immediate parent up to root
     */
    public function getAncestors(): array
    {
        $ancestors = [];
        $node = $this->parent;
        while ($node !== null) {
            $ancestors[] = $node->title;
            $node = $node->parent;
        }
        return $ancestors;
    }

    /**
     * @return string[] all descendant titles in pre‑order
     */
    public function getDescendants(): array
    {
        $desc = [];
        foreach ($this->children as $child) {
            $desc[] = $child->title;
            $desc = array_merge($desc, $child->getDescendants());
        }
        return $desc;
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        $result = ['title' => $this->title];
        if (!empty($this->children)) {
            $result['children'] = [];
            foreach ($this->children as $child) {
                $result['children'][] = $child->toArray();
            }
        }
        return $result;
    }
}

class JobTitleTree
{
    private JobTitleNode $root;

    public function __construct(string $rootTitle)
    {
        $this->root = new JobTitleNode($rootTitle);
    }

    public function getRoot(): JobTitleNode
    {
        return $this->root;
    }

    /**
     * Adds $childTitle under $parentTitle.
     * Returns true on success, false if parent not found or child already exists under that parent.
     */
    public function add(string $parentTitle, string $childTitle): bool
    {
        $parentNode = $this->find($parentTitle);
        if ($parentNode === null) {
            return false;
        }
        foreach ($parentNode->getChildren() as $existing) {
            if ($existing->getTitle() === $childTitle) {
                return false; // duplicate under same parent
            }
        }
        $parentNode->addChild($childTitle);
        return true;
    }

    public function find(string $title): ?JobTitleNode
    {
        return $this->root->find($title);
    }

    /**
     * @return string[] ancestors of $title, empty if not found
     */
    public function getAncestors(string $title): array
    {
        $node = $this->find($title);
        return $node !== null ? $node->getAncestors() : [];
    }

    /**
     * @return string[] descendants of $title, empty if not found
     */
    public function getDescendants(string $title): array
    {
        $node = $this->find($title);
        return $node !== null ? $node->getDescendants() : [];
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return $this->root->toArray();
    }
}
