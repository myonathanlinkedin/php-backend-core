<?php
declare(strict_types=1);

class MojoToken {
    public string $type;
    public string $value;
    public int $line;
    public int $col;

    public function __construct(string $type, string $value, int $line, int $col) {
        $this->type = $type;
        $this->value = $value;
        $this->line = $line;
        $this->col = $col;
    }
}

class MojoLexer {
    private string $source;
    private int $pos;
    private int $line;
    private int $col;

    public function __construct(string $source) {
        $this->source = $source;
        $this->pos = 0;
        $this->line = 1;
        $this->col = 1;
    }

    public function tokenize(): array {
        $tokens = [];
        while ($this->pos < strlen($this->source)) {
            $this->skipWhitespace();
            if ($this->pos >= strlen($this->source)) break;

            $ch = $this->source[$this->pos];
            $startLine = $this->line;
            $startCol = $this->col;

            if ($ch === '#') {
                $this->skipComment();
                continue;
            }

            if (ctype_alpha($ch) || $ch === '_') {
                $tokens[] = $this->lexIdentifier($startLine, $startCol);
            } elseif (ctype_digit($ch)) {
                $tokens[] = $this->lexNumber($startLine, $startCol);
            } elseif ($ch === '"' || $ch === "'") {
                $tokens[] = $this->lexString($ch, $startLine, $startCol);
            } elseif ($ch === '(') {
                $tokens[] = new MojoToken('LPAREN', '(', $startLine, $startCol);
                $this->advance();
            } elseif ($ch === ')') {
                $tokens[] = new MojoToken('RPAREN', ')', $startLine, $startCol);
                $this->advance();
            } elseif ($ch === '{') {
                $tokens[] = new MojoToken('LBRACE', '{', $startLine, $startCol);
                $this->advance();
            } elseif ($ch === '}') {
                $tokens[] = new MojoToken('RBRACE', '}', $startLine, $startCol);
                $this->advance();
            } elseif ($ch === ',') {
                $tokens[] = new MojoToken('COMMA', ',', $startLine, $startCol);
                $this->advance();
            } elseif ($ch === '=') {
                $tokens[] = new MojoToken('ASSIGN', '=', $startLine, $startCol);
                $this->advance();
            } elseif ($ch === '+') {
                $tokens[] = new MojoToken('PLUS', '+', $startLine, $startCol);
                $this->advance();
            } elseif ($ch === '-') {
                $tokens[] = new MojoToken('MINUS', '-', $startLine, $startCol);
                $this->advance();
            } elseif ($ch === '*') {
                $tokens[] = new MojoToken('STAR', '*', $startLine, $startCol);
                $this->advance();
            } elseif ($ch === '/') {
                $tokens[] = new MojoToken('SLASH', '/', $startLine, $startCol);
                $this->advance();
            } else {
                throw new \RuntimeException("Unexpected character '$ch' at line $startLine, col $startCol");
            }
        }
        $tokens[] = new MojoToken('EOF', '', $this->line, $this->col);
        return $tokens;
    }

    private function skipWhitespace(): void {
        while ($this->pos < strlen($this->source) && in_array($this->source[$this->pos], [' ', "\t", "\r"])) {
            $this->advance();
        }
        while ($this->pos < strlen($this->source) && $this->source[$this->pos] === "\n") {
            $this->advance();
        }
    }

    private function skipComment(): void {
        while ($this->pos < strlen($this->source) && $this->source[$this->pos] !== "\n") {
            $this->advance();
        }
    }

    private function lexIdentifier(int $line, int $col): MojoToken {
        $start = $this->pos;
        while ($this->pos < strlen($this->source) && (ctype_alnum($this->source[$this->pos]) || $this->source[$this->pos] === '_')) {
            $this->advance();
        }
        $value = substr($this->source, $start, $this->pos - $start);
        $type = 'IDENT';
        if ($value === 'fn') $type = 'FN';
        elseif ($value === 'let') $type = 'LET';
        elseif ($value === 'return') $type = 'RETURN';
        elseif ($value === 'if') $type = 'IF';
        elseif ($value === 'else') $type = 'ELSE';
        elseif ($value === 'true') $type = 'TRUE';
        elseif ($value === 'false') $type = 'FALSE';
        return new MojoToken($type, $value, $line, $col);
    }

    private function lexNumber(int $line, int $col): MojoToken {
        $start = $this->pos;
        while ($this->pos < strlen($this->source) && ctype_digit($this->source[$this->pos])) {
            $this->advance();
        }
        $value = substr($this->source, $start, $this->pos - $start);
        return new MojoToken('INT', $value, $line, $col);
    }

    private function lexString(string $quote, int $line, int $col): MojoToken {
        $this->advance();
        $start = $this->pos;
        while ($this->pos < strlen($this->source) && $this->source[$this->pos] !== $quote) {
            $this->advance();
        }
        if ($this->pos >= strlen($this->source)) {
            throw new \RuntimeException("Unterminated string at line $line");
        }
        $value = substr($this->source, $start, $this->pos - $start);
        $this->advance();
        return new MojoToken('STRING', $value, $line, $col);
    }

    private function advance(): void {
        $this->pos++;
        if ($this->pos > 0 && $this->source[$this->pos - 1] === "\n") {
            $this->line++;
            $this->col = 1;
        } else {
            $this->col++;
        }
    }
}

class MojoParser {
    private array $tokens;
    private int $pos;

    public function __construct(array $tokens) {
        $this->tokens = $tokens;
        $this->pos = 0;
    }

    public function parse(): array {
        $functions = [];
        while (!$this->isAtEnd() && $this->current()->type === 'FN') {
            $functions[] = $this->parseFunction();
        }
        return $functions;
    }

    private function parseFunction(): array {
        $this->consume('FN');
        $name = $this->consume('IDENT')->value;
        $this->consume('LPAREN');
        $params = [];
        if (!$this->check('RPAREN')) {
            $params[] = $this->consume('IDENT')->value;
            while ($this->check('COMMA')) {
                $this->consume('COMMA');
                $params[] = $this->consume('IDENT')->value;
            }
        }
        $this->consume('RPAREN');
        $this->consume('LBRACE');
        $body = [];
        while (!$this->check('RBRACE')) {
            $body[] = $this->parseStatement();
        }
        $this->consume('RBRACE');
        return ['name' => $name, 'params' => $params, 'body' => $body];
    }

    private function parseStatement(): array {
        if ($this->check('LET')) {
            $this->consume('LET');
            $name = $this->consume('IDENT')->value;
            $this->consume('ASSIGN');
            $value = $this->parseExpression();
            return ['type' => 'let', 'name' => $name, 'value' => $value];
        } elseif ($this->check('RETURN')) {
            $this->consume('RETURN');
            $value = $this->parseExpression();
            return ['type' => 'return', 'value' => $value];
        } else {
            $name = $this->consume('IDENT')->value;
            $this->consume('ASSIGN');
            $value = $this->parseExpression();
            return ['type' => 'assign', 'name' => $name, 'value' => $value];
        }
    }

    private function parseExpression(): array {
        $left = $this->parseTerm();
        while ($this->check('PLUS') || $this->check('MINUS')) {
            $op = $this->current()->value;
            $this->advance();
            $right = $this->parseTerm();
            $left = ['type' => 'binop', 'op' => $op, 'left' => $left, 'right' => $right];
        }
        return $left;
    }

    private function parseTerm(): array {
        $left = $this->parseFactor();
        while ($this->check('STAR') || $this->check('SLASH')) {
            $op = $this->current()->value;
            $this->advance();
            $right = $this->parseFactor();
            $left = ['type' => 'binop', 'op' => $op, 'left' => $left, 'right' => $right];
        }
        return $left;
    }

    private function parseFactor(): array {
        $token = $this->current();
        if ($token->type === 'INT') {
            $this->advance();
            return ['type' => 'int', 'value' => (int)$token->value];
        } elseif ($token->type === 'STRING') {
            $this->advance();
            return ['type' => 'string', 'value' => $token->value];
        } elseif ($token->type === 'TRUE' || $token->type === 'FALSE') {
            $this->advance();
            return ['type' => 'bool', 'value' => $token->type === 'TRUE'];
        } elseif ($token->type === 'IDENT') {
            $this->advance();
            return ['type' => 'var', 'name' => $token->value];
        } elseif ($token->type === 'LPAREN') {
            $this->advance();
            $expr = $this->parseExpression();
            $this->consume('RPAREN');
            return $expr;
        }
        throw new \RuntimeException("Unexpected token '{$token->value}' in expression");
    }

    private function current(): MojoToken {
        return $this->tokens[$this->pos];
    }

    private function check(string $type): bool {
        return !$this->isAtEnd() && $this->current()->type === $type;
    }

    private function consume(string $type): MojoToken {
        if (!$this->check($type)) {
            throw new \RuntimeException("Expected '$type' but got '{$this->current()->type}'");
        }
        $token = $this->current();
        $this->advance();
        return $token;
    }

    private function advance(): void {
        $this->pos++;
    }

    private function isAtEnd(): bool {
        return $this->pos >= count($this->tokens) || $this->current()->type === 'EOF';
    }
}
