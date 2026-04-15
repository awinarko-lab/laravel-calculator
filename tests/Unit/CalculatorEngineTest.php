<?php

use App\Services\CalculatorEngine;

it('evaluates simple addition', function () {
    $engine = new CalculatorEngine;
    expect($engine->evaluate('2+3'))->toBe('5');
});

it('evaluates simple subtraction', function () {
    $engine = new CalculatorEngine;
    expect($engine->evaluate('10-4'))->toBe('6');
});

it('evaluates simple multiplication', function () {
    $engine = new CalculatorEngine;
    expect($engine->evaluate('3*7'))->toBe('21');
});

it('evaluates simple division', function () {
    $engine = new CalculatorEngine;
    expect($engine->evaluate('20/4'))->toBe('5');
});

it('respects operator precedence: multiplication before addition', function () {
    $engine = new CalculatorEngine;
    expect($engine->evaluate('2+3*4'))->toBe('14');
});

it('respects operator precedence: division before subtraction', function () {
    $engine = new CalculatorEngine;
    expect($engine->evaluate('10-6/2'))->toBe('7');
});

it('handles chained operations', function () {
    $engine = new CalculatorEngine;
    expect($engine->evaluate('1+2+3+4'))->toBe('10');
});

it('handles mixed operations', function () {
    $engine = new CalculatorEngine;
    expect($engine->evaluate('2*3+4*5'))->toBe('26');
});

it('handles decimal numbers', function () {
    $engine = new CalculatorEngine;
    expect($engine->evaluate('1.5+2.5'))->toBe('4');
});

it('handles decimal results', function () {
    $engine = new CalculatorEngine;
    expect($engine->evaluate('10/3'))->toBe('3.3333333333');
});

it('handles leading negative number', function () {
    $engine = new CalculatorEngine;
    expect($engine->evaluate('-5+3'))->toBe('-2');
});

it('returns null for division by zero', function () {
    $engine = new CalculatorEngine;
    expect($engine->evaluate('5/0'))->toBeNull();
});

it('returns null for empty expression', function () {
    $engine = new CalculatorEngine;
    expect($engine->evaluate(''))->toBeNull();
});

it('returns null for expression ending with operator', function () {
    $engine = new CalculatorEngine;
    expect($engine->evaluate('5+'))->toBeNull();
});

it('formats whole numbers without decimals', function () {
    $engine = new CalculatorEngine;
    expect($engine->evaluate('4*5'))->toBe('20');
});

it('evaluates expression with parentheses', function () {
    $engine = new CalculatorEngine;
    expect($engine->evaluate('(2+3)*4'))->toBe('20');
});

it('evaluates expression with parentheses at end', function () {
    $engine = new CalculatorEngine;
    expect($engine->evaluate('2*(3+4)'))->toBe('14');
});

it('evaluates expression with nested parentheses', function () {
    $engine = new CalculatorEngine;
    expect($engine->evaluate('((1+2)*(3+4))'))->toBe('21');
});

it('evaluates expression with multiple parentheses groups', function () {
    $engine = new CalculatorEngine;
    expect($engine->evaluate('(2+3)+(4+5)'))->toBe('14');
});

it('evaluates expression with nested operations in parentheses', function () {
    $engine = new CalculatorEngine;
    expect($engine->evaluate('(2+3*4)*2'))->toBe('28');
});

it('evaluates expression with division in parentheses', function () {
    $engine = new CalculatorEngine;
    expect($engine->evaluate('(10/2)+3'))->toBe('8');
});

it('evaluates expression with parentheses overriding precedence', function () {
    $engine = new CalculatorEngine;
    expect($engine->evaluate('(2+3)*4'))->toBe('20');
});

it('evaluates expression with deeply nested parentheses', function () {
    $engine = new CalculatorEngine;
    expect($engine->evaluate('(((1+1)))'))->toBe('2');
});

it('evaluates expression with decimal numbers in parentheses', function () {
    $engine = new CalculatorEngine;
    expect($engine->evaluate('(1.5+2.5)*2'))->toBe('8');
});

it('evaluates expression with negative number in parentheses', function () {
    $engine = new CalculatorEngine;
    expect($engine->evaluate('(-5+3)*2'))->toBe('-4');
});

it('returns null for mismatched opening parenthesis', function () {
    $engine = new CalculatorEngine;
    expect($engine->evaluate('(2+3*4'))->toBeNull();
});

it('returns null for mismatched closing parenthesis', function () {
    $engine = new CalculatorEngine;
    expect($engine->evaluate('2+3)*4'))->toBeNull();
});

it('returns null for expression ending with opening parenthesis', function () {
    $engine = new CalculatorEngine;
    expect($engine->evaluate('2+('))->toBeNull();
});
