<?php

use App\Models\Calculation;
use Livewire\Livewire;

it('renders the calculator page', function () {
    $this->get('/')->assertStatus(200);
});

it('displays initial state', function () {
    Livewire::test('calculator')
        ->assertSee('0')
        ->assertSee('No calculations yet');
});

it('can press digits to build expression', function () {
    Livewire::test('calculator')
        ->call('press', '1')
        ->call('press', '2')
        ->call('press', '3')
        ->assertSet('expression', '123');
});

it('can press operators', function () {
    Livewire::test('calculator')
        ->call('press', '5')
        ->call('press', '+')
        ->call('press', '3')
        ->assertSet('expression', '5+3');
});

it('prevents starting with + * /', function () {
    Livewire::test('calculator')
        ->call('press', '+')
        ->assertSet('expression', '');
});

it('allows starting with minus for negative', function () {
    Livewire::test('calculator')
        ->call('press', '-')
        ->assertSet('expression', '-');
});

it('replaces double operators with the new one', function () {
    Livewire::test('calculator')
        ->call('press', '5')
        ->call('press', '+')
        ->call('press', '-')
        ->assertSet('expression', '5-');
});

it('prevents double decimals in same number', function () {
    Livewire::test('calculator')
        ->call('press', '1')
        ->call('press', '.')
        ->call('press', '5')
        ->call('press', '.')
        ->assertSet('expression', '1.5');
});

it('evaluates expression on equals', function () {
    Livewire::test('calculator')
        ->call('press', '2')
        ->call('press', '+')
        ->call('press', '3')
        ->call('equals')
        ->assertSet('result', '5')
        ->assertSet('hasResult', true);
});

it('shows Error on division by zero', function () {
    Livewire::test('calculator')
        ->call('press', '5')
        ->call('press', '/')
        ->call('press', '0')
        ->call('equals')
        ->assertSet('result', 'Error');
});

it('saves calculation to database on equals', function () {
    Livewire::test('calculator')
        ->call('press', '2')
        ->call('press', '+')
        ->call('press', '3')
        ->call('equals');

    expect(Calculation::count())->toBe(1);
    expect(Calculation::first()->expression)->toBe('2 + 3');
    expect(Calculation::first()->result)->toBe('5');
});

it('does not save to database on error', function () {
    Livewire::test('calculator')
        ->call('press', '5')
        ->call('press', '/')
        ->call('press', '0')
        ->call('equals');

    expect(Calculation::count())->toBe(0);
});

it('does nothing on equals with empty expression', function () {
    Livewire::test('calculator')
        ->call('equals')
        ->assertSet('result', null)
        ->assertSet('hasResult', false);
});

it('clears the expression', function () {
    Livewire::test('calculator')
        ->call('press', '5')
        ->call('press', '+')
        ->call('clear')
        ->assertSet('expression', '')
        ->assertSet('result', null)
        ->assertSet('hasResult', false);
});

it('removes last character on backspace', function () {
    Livewire::test('calculator')
        ->call('press', '1')
        ->call('press', '2')
        ->call('press', '3')
        ->call('backspace')
        ->assertSet('expression', '12');
});

it('clears everything on backspace after result', function () {
    Livewire::test('calculator')
        ->call('press', '5')
        ->call('equals')
        ->call('backspace')
        ->assertSet('expression', '')
        ->assertSet('result', null);
});

it('chains from result when pressing operator', function () {
    Livewire::test('calculator')
        ->call('press', '5')
        ->call('press', '+')
        ->call('press', '3')
        ->call('equals')
        ->assertSet('result', '8')
        ->call('press', '+')
        ->assertSet('expression', '8+')
        ->assertSet('hasResult', false);
});

it('starts fresh when pressing digit after result', function () {
    Livewire::test('calculator')
        ->call('press', '5')
        ->call('equals')
        ->call('press', '3')
        ->assertSet('expression', '3')
        ->assertSet('hasResult', false);
});

it('loads result from history', function () {
    $calc = Calculation::create([
        'expression' => '2 + 3',
        'result' => '5',
    ]);

    Livewire::test('calculator')
        ->call('loadFromHistory', $calc->id)
        ->assertSet('expression', '5')
        ->assertSet('hasResult', false);
});

it('clears all history', function () {
    Calculation::create(['expression' => '1 + 1', 'result' => '2']);
    Calculation::create(['expression' => '2 + 2', 'result' => '4']);

    expect(Calculation::count())->toBe(2);

    Livewire::test('calculator')
        ->call('clearHistory');

    expect(Calculation::count())->toBe(0);
});

it('can press percentage operator', function () {
    Livewire::test('calculator')
        ->call('press', '5')
        ->call('press', '0')
        ->call('press', '%')
        ->assertSet('expression', '50%');
});

it('prevents starting with percentage operator', function () {
    Livewire::test('calculator')
        ->call('press', '%')
        ->assertSet('expression', '');
});

it('replaces percentage operator when pressed after another operator', function () {
    Livewire::test('calculator')
        ->call('press', '5')
        ->call('press', '+')
        ->call('press', '%')
        ->assertSet('expression', '5%');
});

it('evaluates percentage calculation', function () {
    Livewire::test('calculator')
        ->call('press', '1')
        ->call('press', '0')
        ->call('press', '0')
        ->call('press', '*')
        ->call('press', '1')
        ->call('press', '5')
        ->call('press', '%')
        ->call('equals')
        ->assertSet('result', '15');
});

it('evaluates percentage addition correctly', function () {
    Livewire::test('calculator')
        ->call('press', '2')
        ->call('press', '0')
        ->call('press', '0')
        ->call('press', '+')
        ->call('press', '1')
        ->call('press', '0')
        ->call('press', '%')
        ->call('equals')
        ->assertSet('result', '220');
});
