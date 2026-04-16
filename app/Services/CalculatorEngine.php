<?php

namespace App\Services;

class CalculatorEngine
{
    public function evaluate(string $expression): ?string
    {
        $expression = str_replace(' ', '', $expression);

        if ($expression === '') {
            return null;
        }

        $tokens = $this->tokenize($expression);

        if ($tokens === null || count($tokens) === 0) {
            return null;
        }

        $lastChar = substr($expression, -1);
        if (in_array($lastChar, ['+', '-', '*', '/'])) {
            return null;
        }

        $tokens = $this->evaluateMultiplicationAndDivision($tokens);

        if ($tokens === null) {
            return null;
        }

        $result = $this->evaluateAdditionAndSubtraction($tokens);

        if ($result === null) {
            return null;
        }

        return $this->formatResult($result);
    }

    private function tokenize(string $expression): ?array
    {
        $tokens = preg_split('/([+\-*\/])/', $expression, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        if (empty($tokens)) {
            return null;
        }

        // Handle leading negative number
        if ($tokens[0] === '-') {
            if (count($tokens) < 2) {
                return null;
            }
            array_shift($tokens);
            $tokens[0] = '-'.$tokens[0];
        }

        return $tokens;
    }

    private function evaluateMultiplicationAndDivision(array $tokens): ?array
    {
        $i = 0;
        while ($i < count($tokens)) {
            if (isset($tokens[$i]) && ($tokens[$i] === '*' || $tokens[$i] === '/')) {
                $left = (float) $tokens[$i - 1];
                $right = (float) $tokens[$i + 1];

                if ($tokens[$i] === '/' && $right == 0) {
                    return null;
                }

                $result = $tokens[$i] === '*' ? $left * $right : $left / $right;
                array_splice($tokens, $i - 1, 3, [(string) $result]);
                $i--;
            }
            $i++;
        }

        return $tokens;
    }

    private function evaluateAdditionAndSubtraction(array $tokens): ?float
    {
        if (empty($tokens)) {
            return null;
        }

        $result = (float) $tokens[0];

        for ($i = 1; $i < count($tokens); $i += 2) {
            if (! isset($tokens[$i + 1])) {
                return null;
            }

            $operator = $tokens[$i];
            $operand = (float) $tokens[$i + 1];

            if ($operator === '+') {
                $result += $operand;
            } elseif ($operator === '-') {
                $result -= $operand;
            }
        }

        return $result;
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
