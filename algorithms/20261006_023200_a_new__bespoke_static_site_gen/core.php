<?php
declare(strict_types=1);

final class FileSystemHelper
{
    public static function ensureDir(string $path): void
    {
        if (!is_dir($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
            throw new RuntimeException("Failed to create directory: $path");
        }
    }

    public static function writeFile(string $path, string $content): void
    {
        self::ensureDir(dirname($path));
        if (file_put_contents($path, $content) === false) {
            throw new RuntimeException("Failed to write file: $path");
        }
    }

    public static function readFile(string $path): string
    {
        $content = @file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException("Failed to read file: $path");
        }
        return $content;
    }

    /**
     * @return array<int, string>
     */
    public static function listFiles(string $dir, string $extension = ''): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $fileInfo) {
            /** @var SplFileInfo $fileInfo */
            if ($extension && $fileInfo->getExtension() !== $extension) {
                continue;
            }
            $files[] = $fileInfo->getRealPath();
        }
        return $files;
    }
}

final class MarkdownParser
{
    public function parse(string $markdown): string
    {
        $html = htmlspecialchars($markdown, ENT_NOQUOTES, 'UTF-8');

        // Headings
        $html = preg_replace_callback('/^(#{1,6})\s*(.+)$/m', function ($m) {
            $level = strlen($m[1]);
            $text = trim($m[2]);
            return "<h{$level}>{$text}</h{$level}>";
        }, $html);

        // Bold **text**
        $html = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $html);

        // Italic *text*
        $html = preg_replace('/\*(.+?)\*/s', '<em>$1</em>', $html);

        // Links [text](url)
        $html = preg_replace('/\[(.+?)\]\((.+?)\)/s', '<a href="$2">$1</a>', $html);

        // Paragraphs
        $lines = explode("\n", $html);
        $wrapped = [];
        foreach ($lines as $line) {
            $trim = trim($line);
            if ($trim === '' || preg_match('/^<h[1-6]>/i', $trim)) {
                $wrapped[] = $trim;
                continue;
            }
            $wrapped[] = "<p>{$trim}</p>";
        }
        return implode("\n", $wrapped);
    }
}

final class TemplateEngine
{
    private string $layout;

    public function __construct(string $layoutContent)
    {
        $this->layout = $layoutContent;
    }

    public function render(string $content, array $variables = []): string
    {
        $rendered = str_replace('{{content}}', $content, $this->layout);
        foreach ($variables as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $rendered = str_replace($placeholder, (string)$value, $rendered);
        }
        return $rendered;
    }
}

final class SiteGenerator
{
    private TemplateEngine $engine;
    private MarkdownParser $parser;

    public function __construct(TemplateEngine $engine, MarkdownParser $parser)
    {
        $this->engine = $engine;
        $this->parser = $parser;
    }

    public function generate(string $sourceDir, string $outputDir, string $layoutFile): void
    {
        $layoutPath = rtrim($sourceDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $layoutFile;
        $layoutContent = FileSystemHelper::readFile($layoutPath);
        $this->engine = new TemplateEngine($layoutContent);

        $mdFiles = FileSystemHelper::listFiles($sourceDir, 'md');
        foreach ($mdFiles as $mdPath) {
            $relative = substr($mdPath, strlen(rtrim($sourceDir, DIRECTORY_SEPARATOR)) + 1);
            $outputPath = rtrim($outputDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR .
                preg_replace('/\.md$/i', '.html', $relative);
            $markdown = FileSystemHelper::readFile($mdPath);
            $htmlBody = $this->parser->parse($markdown);
            $finalHtml = $this->engine->render($htmlBody);
            FileSystemHelper::writeFile($outputPath, $finalHtml);
        }
    }
}
