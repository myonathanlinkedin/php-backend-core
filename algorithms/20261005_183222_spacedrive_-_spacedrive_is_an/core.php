<?php
declare(strict_types=1);

/**
 * Core file system abstraction for a virtual explorer.
 */
class FileSystemException extends \Exception {}

abstract class FileSystemNode
{
    protected string $name;
    protected ?DirectoryNode $parent;

    public function __construct(string $name, ?DirectoryNode $parent = null)
    {
        $this->setName($name);
        $this->parent = $parent;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $trimmed = trim($name);
        if ($trimmed === '' || strpos($trimmed, '/') !== false) {
            throw new FileSystemException('Invalid node name.');
        }
        $this->name = $trimmed;
    }

    public function getParent(): ?DirectoryNode
    {
        return $this->parent;
    }

    public function setParent(?DirectoryNode $parent): void
    {
        $this->parent = $parent;
    }

    public function getPath(): string
    {
        $segments = [];
        $node = $this;
        while ($node !== null) {
            array_unshift($segments, $node->getName());
            $node = $node->getParent();
        }
        return '/' . implode('/', $segments);
    }

    abstract public function isDirectory(): bool;
}

final class FileNode extends FileSystemNode
{
    private string $content;

    public function __construct(string $name, string $content = '', ?DirectoryNode $parent = null)
    {
        parent::__construct($name, $parent);
        $this->content = $content;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function isDirectory(): bool
    {
        return false;
    }
}

final class DirectoryNode extends FileSystemNode implements \IteratorAggregate
{
    /** @var array<string, FileSystemNode> */
    private array $children = [];

    public function __construct(string $name, ?DirectoryNode $parent = null)
    {
        parent::__construct($name, $parent);
    }

    public function isDirectory(): bool
    {
        return true;
    }

    /**
     * @return \Traversable<FileSystemNode>
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->children);
    }

    public function addChild(FileSystemNode $node): void
    {
        $key = $node->getName();
        if (isset($this->children[$key])) {
            throw new FileSystemException("A node named '{$key}' already exists in '{$this->getPath()}'.");
        }
        $node->setParent($this);
        $this->children[$key] = $node;
    }

    public function getChild(string $name): ?FileSystemNode
    {
        return $this->children[$name] ?? null;
    }

    public function removeChild(string $name): void
    {
        if (!isset($this->children[$name])) {
            throw new FileSystemException("No child named '{$name}' in '{$this->getPath()}'.");
        }
        $this->children[$name]->setParent(null);
        unset($this->children[$name]);
    }

    /**
     * @return FileSystemNode[]
     */
    public function listChildren(): array
    {
        return array_values($this->children);
    }
}

/**
 * High‑level API for managing the virtual file system.
 */
final class FileSystem
{
    private DirectoryNode $root;

    public function __construct()
    {
        $this->root = new DirectoryNode(''); // root name empty, path will be "/"
    }

    public function getRoot(): DirectoryNode
    {
        return $this->root;
    }

    /**
     * Resolve a path like "/foo/bar.txt" to its node.
     *
     * @throws FileSystemException if any segment is missing.
     */
    public function resolve(string $path): FileSystemNode
    {
        $clean = trim($path);
        if ($clean === '' || $clean[0] !== '/') {
            throw new FileSystemException('Path must be absolute and start with "/".');
        }
        $segments = array_filter(explode('/', $clean), fn($s) => $s !== '');
        $current = $this->root;
        foreach ($segments as $segment) {
            if (!$current instanceof DirectoryNode) {
                throw new FileSystemException("Path segment '{$segment}' is not a directory.");
            }
            $next = $current->getChild($segment);
            if ($next === null) {
                throw new FileSystemException("Path segment '{$segment}' does not exist.");
            }
            $current = $next;
        }
        return $current;
    }

    public function addFile(string $path, string $content = ''): FileNode
    {
        $dirPath = dirname($path);
        $fileName = basename($path);
        $parent = $dirPath === '/' ? $this->root : $this->resolve($dirPath);
        if (!($parent instanceof DirectoryNode)) {
            throw new FileSystemException('Parent path is not a directory.');
        }
        $file = new FileNode($fileName, $content);
        $parent->addChild($file);
        return $file;
    }

    public function addDirectory(string $path): DirectoryNode
    {
        $dirPath = dirname($path);
        $dirName = basename($path);
        $parent = $dirPath === '/' ? $this->root : $this->resolve($dirPath);
        if (!($parent instanceof DirectoryNode)) {
            throw new FileSystemException('Parent path is not a directory.');
        }
        $dir = new DirectoryNode($dirName);
        $parent->addChild($dir);
        return $dir;
    }

    public function remove(string $path): void
    {
        $parentPath = dirname($path);
        $name = basename($path);
        $parent = $parentPath === '/' ? $this->root : $this->resolve($parentPath);
        if (!($parent instanceof DirectoryNode)) {
            throw new FileSystemException('Parent path is not a directory.');
        }
        $parent->removeChild($name);
    }

    public function move(string $sourcePath, string $destPath): void
    {
        $node = $this->resolve($sourcePath);
        $destDirPath = dirname($destPath);
        $newName = basename($destPath);
        $destDir = $destDirPath === '/' ? $this->root : $this->resolve($destDirPath);
        if (!($destDir instanceof DirectoryNode)) {
            throw new FileSystemException('Destination parent is not a directory.');
        }
        // detach from old parent
        $oldParent = $node->getParent();
        if ($oldParent === null) {
            throw new FileSystemException('Cannot move root.');
        }
        $oldParent->removeChild($node->getName());
        // rename if needed
        $node->setName($newName);
        $destDir->addChild($node);
    }

    /**
     * Recursively search for nodes whose name contains $needle (case‑insensitive).
     *
     * @return FileSystemNode[]
     */
    public function search(string $needle): array
    {
        $result = [];
        $this->searchRecursive($this->root, $needle, $result);
        return $result;
    }

    private function searchRecursive(DirectoryNode $dir, string $needle, array &$result): void
    {
        foreach ($dir->listChildren() as $child) {
            if (stripos($child->getName(), $needle) !== false) {
                $result[] = $child;
            }
            if ($child instanceof DirectoryNode) {
                $this->searchRecursive($child, $needle, $result);
            }
        }
    }
}
