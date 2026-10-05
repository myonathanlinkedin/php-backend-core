<?php
declare(strict_types=1);

require_once __DIR__ . '/core.php';

// Simple assertion helper
function assertTrue(bool $condition, string $message = ''): void
{
    assert($condition, $message);
}

// Unit Tests
$fs = new FileSystem();

// Test root path
assertTrue($fs->getRoot()->getPath() === '/', 'Root path should be "/"');

// Add directories
$fs->addDirectory('/documents');
$fs->addDirectory('/pictures');
assertTrue($fs->resolve('/documents') instanceof DirectoryNode, 'Directory /documents should exist');
assertTrue($fs->resolve('/pictures') instanceof DirectoryNode, 'Directory /pictures should exist');

// Add nested directory and file
$fs->addDirectory('/documents/reports');
$file = $fs->addFile('/documents/reports/summary.txt', 'Quarterly report content');
assertTrue($file->getContent() === 'Quarterly report content', 'File content mismatch');
assertTrue($fs->resolve('/documents/reports/summary.txt') === $file, 'Resolved file should match');

// List children
$docsChildren = $fs->resolve('/documents')->listChildren();
assertTrue(count($docsChildren) === 1 && $docsChildren[0]->getName() === 'reports', 'Documents should have one child named reports');

// Move file
$fs->move('/documents/reports/summary.txt', '/pictures/summary.txt');
assertTrue($fs->resolve('/pictures/summary.txt') instanceof FileNode, 'File should be moved to /pictures');
assertTrue($fs->resolve('/documents/reports')->listChildren() === [], 'Source directory should be empty after move');

// Remove directory
$fs->remove('/documents/reports');
try {
    $fs->resolve('/documents/reports');
    assertTrue(false, 'Directory should have been removed');
} catch (FileSystemException $e) {
    assertTrue(true);
}

// Search test
$fs->addFile('/pictures/vacation1.jpg');
$fs->addFile('/pictures/vacation2.jpg');
$fs->addFile('/documents/notes.txt');
$searchResults = $fs->search('vacation');
assertTrue(count($searchResults) === 2, 'Search should return two vacation files');

// Benchmark simple operations (microseconds)
$iterations = 1000;
$start = microtime(true);
for ($i = 0; $i < $iterations; ++$i) {
    $fs->addFile("/tmp/file{$i}.txt", "data");
    $fs->remove("/tmp/file{$i}.txt");
}
$duration = microtime(true) - $start;
printf("Benchmark: %d add/remove cycles in %.4f seconds (%.2f µs per operation)\n",
    $iterations, $duration, ($duration / $iterations) * 1_000_000);

// End of tests
echo "All tests passed.\n";
