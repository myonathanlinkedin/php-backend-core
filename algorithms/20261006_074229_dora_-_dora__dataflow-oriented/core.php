<?php
declare(strict_types=1);

namespace DORA;

/**
 * Base exception for DORA middleware.
 */
class DORAException extends \Exception {}

/**
 * Interface for a data processing node.
 */
interface DataNode
{
    public function getName(): string;
    public function process(array $input): array;
}

/**
 * Abstract implementation of DataNode providing name handling.
 */
abstract class AbstractNode implements DataNode
{
    protected string $name;

    public function __construct(string $name)
    {
        if ($name === '') {
            throw new DORAException('Node name cannot be empty');
        }
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    abstract public function process(array $input): array;
}

/**
 * Node that applies a transformation callable to each element.
 */
final class TransformNode extends AbstractNode
{
    /** @var callable(array): array */
    private $transform;

    /**
     * @param string $name
     * @param callable(array): array $transform Callable that receives an item and returns transformed item.
     */
    public function __construct(string $name, callable $transform)
    {
        parent::__construct($name);
        $this->transform = $transform;
    }

    public function process(array $input): array
    {
        $output = [];
        foreach ($input as $item) {
            $output[] = ($this->transform)($item);
        }
        return $output;
    }
}

/**
 * Node that filters items based on a predicate callable.
 */
final class FilterNode extends AbstractNode
{
    /** @var callable(mixed): bool */
    private $predicate;

    /**
     * @param string $name
     * @param callable(mixed): bool $predicate Callable that returns true to keep the item.
     */
    public function __construct(string $name, callable $predicate)
    {
        parent::__construct($name);
        $this->predicate = $predicate;
    }

    public function process(array $input): array
    {
        $output = [];
        foreach ($input as $item) {
            if (($this->predicate)($item)) {
                $output[] = $item;
            }
        }
        return $output;
    }
}

/**
 * Sequential pipeline of DataNode objects.
 */
final class Pipeline
{
    /** @var DataNode[] */
    private array $nodes = [];

    /**
     * Add a node to the pipeline.
     */
    public function addNode(DataNode $node): void
    {
        $this->nodes[] = $node;
    }

    /**
     * Execute the pipeline on a batch of data.
     *
     * @param array $data Input data batch.
     * @return array Processed data.
     */
    public function execute(array $data): array
    {
        $payload = $data;
        foreach ($this->nodes as $node) {
            $payload = $node->process($payload);
        }
        return $payload;
    }

    /**
     * Get a snapshot of node names for debugging.
     *
     * @return string[]
     */
    public function getNodeNames(): array
    {
        $names = [];
        foreach ($this->nodes as $node) {
            $names[] = $node->getName();
        }
        return $names;
    }
}

/**
 * Engine that streams data through a pipeline.
 */
final class DataflowEngine
{
    /**
     * Run the pipeline over an iterable source.
     *
     * @param Pipeline $pipeline
     * @param iterable $source Data source (e.g., Generator, array).
     * @param callable|null $sink Optional sink callable receiving each processed item.
     */
    public static function run(Pipeline $pipeline, iterable $source, ?callable $sink = null): void
    {
        foreach ($source as $chunk) {
            // Ensure each chunk is an array for batch processing.
            $batch = is_array($chunk) ? $chunk : [$chunk];
            $processed = $pipeline->execute($batch);
            if ($sink !== null) {
                foreach ($processed as $item) {
                    $sink($item);
                }
            }
        }
    }
}
