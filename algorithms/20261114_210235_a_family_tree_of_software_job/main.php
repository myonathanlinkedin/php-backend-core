<?php
declare(strict_types=1);
require_once __DIR__ . '/core.php';

// Enable strict assertions
ini_set('assert.exception', '1');

// Helper for deep equality of arrays
function assertEquals(mixed $expected, mixed $actual, string $message = ''): void
{
    if ($expected !== $actual) {
        $msg = $message !== '' ? $message . ': ' : '';
        $msg .= 'Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true);
        throw new AssertionError($msg);
    }
}

// Build the family tree
$tree = new JobTitleTree('Software Professional');
assert($tree->getRoot()->getTitle() === 'Software Professional');

// Add titles
assert($tree->add('Software Professional', 'Software Engineer') === true);
assert($tree->add('Software Engineer', 'Senior Software Engineer') === true);
assert($tree->add('Senior Software Engineer', 'Lead Engineer') === true);
assert($tree->add('Lead Engineer', 'Software Architect') === true);
assert($tree->add('Software Engineer', 'Frontend Engineer') === true);
assert($tree->add('Software Engineer', 'Backend Engineer') === true);
assert($tree->add('Software Engineer', 'Fullstack Engineer') === true);
assert($tree->add('Software Engineer', 'Intern') === true);

// Duplicate addition should fail
assert($tree->add('Software Engineer', 'Frontend Engineer') === false);
assert($tree->add('Nonexistent', 'Ghost Engineer') === false);

// Test find
$node = $tree->find('Lead Engineer');
assert($node !== null && $node->getTitle() === 'Lead Engineer');

// Ancestors test (order: immediate parent first)
$ancestors = $tree->getAncestors('Software Architect');
assertEquals(['Lead Engineer', 'Senior Software Engineer', 'Software Engineer', 'Software Professional'], $ancestors, 'Ancestors of Software Architect');

// Descendants test
$descendants = $tree->getDescendants('Software Engineer');
$expectedDesc = [
    'Senior Software Engineer',
    'Lead Engineer',
    'Software Architect',
    'Frontend Engineer',
    'Backend Engineer',
    'Fullstack Engineer',
    'Intern'
];
assertEquals($expectedDesc, $descendants, 'Descendants of Software Engineer');

// toArray structure test
$arrayTree = $tree->toArray();
$expectedArray = [
    'title' => 'Software Professional',
    'children' => [
        [
            'title' => 'Software Engineer',
            'children' => [
                [
                    'title' => 'Senior Software Engineer',
                    'children' => [
                        [
                            'title' => 'Lead Engineer',
                            'children' => [
                                ['title' => 'Software Architect']
                            ]
                        ]
                    ]
                ],
                ['title' => 'Frontend Engineer'],
                ['title' => 'Backend Engineer'],
                ['title' => 'Fullstack Engineer'],
                ['title' => 'Intern']
            ]
        ]
    ]
];
assertEquals($expectedArray, $arrayTree, 'Tree array representation');

// Edge case: ancestors of root should be empty
assertEquals([], $tree->getAncestors('Software Professional'), 'Ancestors of root');

// Edge case: descendants of leaf should be empty
assertEquals([], $tree->getDescendants('Intern'), 'Descendants of leaf');

// All tests passed
echo "All unit tests passed.\n";
