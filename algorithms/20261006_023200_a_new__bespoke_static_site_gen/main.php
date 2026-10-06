<?php
declare(strict_types=1);

require_once __DIR__ . '/core.php';

function runTests(): void
{
    $tmp = sys_get_temp_dir();
    $src = $tmp . '/ssg_src_' . uniqid();
    $out = $tmp . '/ssg_out_' . uniqid();

    FileSystemHelper::ensureDir($src);
    FileSystemHelper::ensureDir($out);

    // Layout with placeholder
    $layout = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>{{title}}</title></head>
<body>
{{content}}
</body>
</html>
HTML;
    FileSystemHelper::writeFile($src . '/layout.html', $layout);

    // Sample markdown file
    $md = <<<MD
# Hello World

This is a **bold** statement and an *italic* remark.

Visit [OpenAI](https://openai.com).
MD;
    FileSystemHelper::writeFile($src . '/index.md', $md);

    // Generate site
    $engine = new TemplateEngine($layout);
    $parser = new MarkdownParser();
    $generator = new SiteGenerator($engine, $parser);
    $generator->generate($src, $out, 'layout.html');

    $generatedPath = $out . '/index.html';
    assert(is_file($generatedPath), 'Generated HTML file should exist');

    $output = FileSystemHelper::readFile($generatedPath);
    assert(str_contains($output, '<h1>Hello World</h1>'), 'Heading should be converted');
    assert(str_contains($output, '<strong>bold</strong>'), 'Bold should be converted');
    assert(str_contains($output, '<em>italic</em>'), 'Italic should be converted');
    assert(str_contains($output, '<a href="https://openai.com">OpenAI</a>'), 'Link should be converted');

    // Clean up
    // Note: In real tests you would recursively delete; omitted for brevity.
}

function benchmark(): void
{
    $tmp = sys_get_temp_dir();
    $src = $tmp . '/ssg_bench_src_' . uniqid();
    $out = $tmp . '/ssg_bench_out_' . uniqid();

    FileSystemHelper::ensureDir($src);
    FileSystemHelper::ensureDir($out);

    $layout = '<html><body>{{content}}</body></html>';
    FileSystemHelper::writeFile($src . '/layout.html', $layout);

    // Create 100 markdown files
    for ($i = 0; $i < 100; $i++) {
        $md = "# Page $i\n\nSample content $i.";
        FileSystemHelper::writeFile($src . "/page{$i}.md", $md);
    }

    $engine = new TemplateEngine($layout);
    $parser = new MarkdownParser();
    $generator = new SiteGenerator($engine, $parser);

    $start = microtime(true);
    $generator->generate($src, $out, 'layout.html');
    $elapsed = microtime(true) - $start;

    echo "Generated 100 pages in " . number_format($elapsed, 4) . " seconds\n";
}

// Execute when run from CLI
if (php_sapi_name() === 'cli') {
    runTests();
    benchmark();
}
