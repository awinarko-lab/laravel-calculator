<?php

namespace App\Services;

class CalculatorEngine
{
    private int $position = 0;

    private string $expression;

    public function evaluate(string $expression): ?string
    {
        $expression = str_replace(' ', '', $expression);

        if ($expression === '') {
            return null;
        }

        // Validate expression only contains valid characters
        if (! preg_match('/^[\d+\-*\/().]+$/', $expression)) {
            return null;
        }

        $lastChar = substr($expression, -1);
        if (in_array($lastChar, ['+', '-', '*', '/', '('])) {
            return null;
        }

        $this->expression = $expression;
        $this->position = 0;

        try {
            $result = $this->parseExpression();

            // Ensure we've consumed the entire expression
            if ($this->position < strlen($this->expression)) {
                return null;
            }

            if ($result === null || ! is_finite($result)) {
                return null;
            }

            return $this->formatResult($result);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function parseExpression(): ?float
    {
        $result = $this->parseTerm();

        while ($this->position < strlen($this->expression)) {
            $operator = $this->currentChar();

            if ($operator === '+') {
                $this->advance();
                $right = $this->parseTerm();
                if ($right === null) {
                    return null;
                }
                $result += $right;
            } elseif ($operator === '-') {
                $this->advance();
                $right = $this->parseTerm();
                if ($right === null) {
                    return null;
                }
                $result -= $right;
            } else {
                break;
            }
        }

        return $result;
    }

    private function parseTerm(): ?float
    {
        $result = $this->parseFactor();

        while ($this->position < strlen($this->expression)) {
            $operator = $this->currentChar();

            if ($operator === '*') {
                $this->advance();
                $right = $this->parseFactor();
                if ($right === null) {
                    return null;
                }
                $result *= $right;
            } elseif ($operator === '/') {
                $this->advance();
                $right = $this->parseFactor();
                if ($right === null) {
                    return null;
                }
                if ($right == 0) {
                    return null;
                }
                $result /= $right;
            } else {
                break;
            }
        }

        return $result;
    }

    private function parseFactor(): ?float
    {
        $char = $this->currentChar();

        // Handle parentheses
        if ($char === '(') {
            $this->advance();
            $result = $this->parseExpression();
            if ($this->currentChar() !== ')') {
                return null; // Mismatched parentheses
            }
            $this->advance();

            return $result;
        }

        // Handle unary minus/plus
        if ($char === '-' || $char === '+') {
            $this->advance();
            $factor = $this->parseFactor();
            if ($factor === null) {
                return null;
            }

            return $char === '-' ? -$factor : $factor;
        }

        return $this->parseNumber();
    }

    private function parseNumber(): ?float
    {
        $start = $this->position;

        while ($this->position < strlen($this->expression)) {
            $char = $this->currentChar();
            if (is_numeric($char) || $char === '.') {
                $this->advance();
            } else {
                break;
            }
        }

        if ($start === $this->position) {
            return null;
        }

        $numberStr = substr($this->expression, $start, $this->position - $start);

        if (! is_numeric($numberStr)) {
            return null;
        }

        return (float) $numberStr;
    }

    private function currentChar(): ?string
    {
        if ($this->position >= strlen($this->expression)) {
            return null;
        }

        return $this->expression[$this->position];
    }

    private function advance(): void
    {
        $this->position++;
    }

    private function formatResult(float $result): string
    {
        if (floor($result) == $result && abs($result) < PHP_INT_MAX) {
            return (string) (int) $result;
        }

        $formatted = rtrim(sprintf('%.10f', $result), '0');

        return rtrim($formatted, '.');
    }
}
