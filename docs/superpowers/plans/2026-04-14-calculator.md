# Calculator Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a functional web-based calculator with persistent history using Laravel 13, Livewire 4, and Tailwind CSS 4.

**Architecture:** Single Livewire SFC component handles calculator UI and logic. Expression evaluation is delegated to a dedicated `CalculatorEngine` service class. History persisted to SQLite via `Calculation` Eloquent model. No authentication.

**Tech Stack:** Laravel 13, Livewire 4 (SFC), Tailwind CSS 4, SQLite, Pest 4

---

## File Structure

| Action | File | Responsibility |
|--------|------|----------------|
| Create | `database/migrations/*_create_calculations_table.php` | Schema for calculation history |
| Create | `app/Models/Calculation.php` | Eloquent model for calculations |
| Create | `app/Services/CalculatorEngine.php` | Expression evaluation (no `eval()`) |
| Create | `resources/views/layouts/app.blade.php` | App layout with Livewire assets |
| Create | `resources/views/calculator.blade.php` | Page wrapper |
| Create | `resources/views/components/⚡calculator.blade.php` | Livewire SFC component (logic + template) |
| Modify | `routes/web.php` | Point `/` to calculator page |
| Create | `tests/Unit/CalculatorEngineTest.php` | Unit tests for expression evaluator |
| Create | `tests/Feature/CalculatorComponentTest.php` | Livewire component tests |

---

### Task 1: Create Migration and Model

**Files:**
- Create: `database/migrations/*_create_calculations_table.php`
- Create: `app/Models/Calculation.php`

- [ ] **Step 1: Create model and migration**

Run:
```bash
php artisan make:model Calculation -m --no-interaction
```

- [ ] **Step 2: Edit the migration file**

Find the newly created migration in `database/migrations/` and replace its contents:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calculations', function (Blueprint $table) {
            $table->id();
            $table->string('expression');
            $table->string('result');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calculations');
    }
};
```

- [ ] **Step 3: Edit the model**

Replace `app/Models/Calculation.php` with:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['expression', 'result'])]
class Calculation extends Model {}
```

- [ ] **Step 4: Run migration**

Run:
```bash
php artisan migrate --no-interaction
```

Expected: `Migration table created successfully` and the calculations table created.

- [ ] **Step 5: Commit**

```bash
git add app/Models/Calculation.php database/migrations/
git commit -m "feat: add Calculation model and migration"
```

---

### Task 2: CalculatorEngine Service (TDD)

**Files:**
- Create: `tests/Unit/CalculatorEngineTest.php`
- Create: `app/Services/CalculatorEngine.php`

- [ ] **Step 1: Write the failing unit tests**

Create `tests/Unit/CalculatorEngineTest.php`:

```php
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
```

- [ ] **Step 2: Run tests to verify they fail**

Run:
```bash
php artisan test --compact --filter=CalculatorEngine
```

Expected: FAIL — `Class "App\Services\CalculatorEngine" not found`

- [ ] **Step 3: Implement CalculatorEngine**

Create `app/Services/CalculatorEngine.php`:

```php
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

        // Validate: must alternate number-operator-number
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
            $tokens[0] = '-' . $tokens[0];
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
```

- [ ] **Step 4: Run tests to verify they pass**

Run:
```bash
php artisan test --compact --filter=CalculatorEngine
```

Expected: All 15 tests PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Services/CalculatorEngine.php tests/Unit/CalculatorEngineTest.php
git commit -m "feat: add CalculatorEngine expression evaluator with tests"
```

---

### Task 3: Create Layout and Route

**Files:**
- Create: `resources/views/layouts/app.blade.php`
- Create: `resources/views/calculator.blade.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Create the app layout**

Create `resources/views/layouts/app.blade.php`:

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Calculator</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-100 dark:bg-gray-900 min-h-screen">
    @yield('content')
    @livewireScripts
</body>
</html>
```

- [ ] **Step 2: Create the page view**

Create `resources/views/calculator.blade.php`:

```blade
@extends('layouts.app')

@section('content')
    <div class="min-h-screen flex items-center justify-center p-4">
        <livewire:calculator />
    </div>
@endsection
```

- [ ] **Step 3: Update the route**

Replace `routes/web.php` with:

```php
<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'calculator')->name('home');
```

- [ ] **Step 4: Commit**

```bash
git add resources/views/layouts/app.blade.php resources/views/calculator.blade.php routes/web.php
git commit -m "feat: add app layout, calculator page, and route"
```

---

### Task 4: Create Livewire Calculator Component

**Files:**
- Create: `resources/views/components/⚡calculator.blade.php`

- [ ] **Step 1: Generate the component**

Run:
```bash
php artisan make:livewire calculator --no-interaction
```

This creates `resources/views/components/⚡calculator.blade.php`.

- [ ] **Step 2: Write the full SFC component**

Replace the generated file with the complete component. This is a single-file component containing PHP logic and Blade template:

```blade
<?php

use App\Models\Calculation;
use App\Services\CalculatorEngine;
use Livewire\Component;

new class extends Component
{
    public string $expression = '';
    public ?string $result = null;
    public bool $hasResult = false;

    public function press(string $value): void
    {
        if ($this->hasResult) {
            if (in_array($value, ['+', '-', '*', '/'])) {
                $this->expression = $this->result . $value;
                $this->result = null;
                $this->hasResult = false;
            } else {
                $this->expression = $value;
                $this->result = null;
                $this->hasResult = false;
            }

            return;
        }

        // Prevent double operators — replace the last one
        if (in_array($value, ['+', '-', '*', '/'])) {
            $lastChar = substr($this->expression, -1);
            if (in_array($lastChar, ['+', '-', '*', '/'])) {
                $this->expression = substr($this->expression, 0, -1) . $value;

                return;
            }
        }

        // Prevent double decimal in current number segment
        if ($value === '.') {
            $segments = preg_split('/[+\-*\/]/', $this->expression);
            $lastSegment = end($segments);
            if (str_contains($lastSegment, '.')) {
                return;
            }
        }

        // Prevent starting with operator other than minus
        if ($this->expression === '' && in_array($value, ['+', '*', '/'])) {
            return;
        }

        $this->expression .= $value;
    }

    public function equals(): void
    {
        if ($this->expression === '' || $this->hasResult) {
            return;
        }

        $lastChar = substr($this->expression, -1);
        if (in_array($lastChar, ['+', '-', '*', '/'])) {
            return;
        }

        $engine = new CalculatorEngine;
        $evaluated = $engine->evaluate($this->expression);

        if ($evaluated === null) {
            $this->result = 'Error';
            $this->hasResult = true;

            return;
        }

        Calculation::create([
            'expression' => $this->formatExpression($this->expression),
            'result' => $evaluated,
        ]);

        $this->result = $evaluated;
        $this->hasResult = true;
    }

    public function clear(): void
    {
        $this->expression = '';
        $this->result = null;
        $this->hasResult = false;
    }

    public function backspace(): void
    {
        if ($this->hasResult) {
            $this->clear();

            return;
        }

        $this->expression = substr($this->expression, 0, -1);
    }

    public function loadFromHistory(int $id): void
    {
        $calculation = Calculation::find($id);

        if ($calculation) {
            $this->expression = $calculation->result;
            $this->result = null;
            $this->hasResult = false;
        }
    }

    public function clearHistory(): void
    {
        Calculation::truncate();
    }

    public function formatExpression(string $expression): string
    {
        return preg_replace('/([+\-*\/])/', ' $1 ', $expression);
    }

    public function with(): array
    {
        return [
            'calculations' => Calculation::latest()->take(20)->get(),
        ];
    }
};
?>

<div class="flex flex-col lg:flex-row gap-6 w-full max-w-3xl">
    {{-- Calculator --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden w-full max-w-sm">
        {{-- Display --}}
        <div class="bg-gray-800 dark:bg-gray-900 p-6">
            <div class="text-right text-sm text-gray-400 h-6 overflow-hidden">
                @if($hasResult)
                    {{ $this->formatExpression($expression) }} =
                @endif
            </div>
            <div class="text-right text-4xl font-bold text-white tracking-wide overflow-hidden">
                @if($hasResult)
                    {{ $result }}
                @else
                    {{ $expression ?: '0' }}
                @endif
            </div>
        </div>

        {{-- Buttons --}}
        <div class="grid grid-cols-4 gap-px bg-gray-200 dark:bg-gray-700 p-px">
            {{-- Row 1: C, backspace, /, * --}}
            <button wire:click="clear" class="bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-white text-xl font-medium p-4 hover:bg-gray-400 dark:hover:bg-gray-500 active:bg-gray-500 dark:active:bg-gray-400 transition-colors">C</button>
            <button wire:click="backspace" class="bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-white text-xl font-medium p-4 hover:bg-gray-400 dark:hover:bg-gray-500 active:bg-gray-500 dark:active:bg-gray-400 transition-colors">&larr;</button>
            <button wire:click="press('/')" class="bg-amber-500 dark:bg-amber-600 text-white text-xl font-medium p-4 hover:bg-amber-600 dark:hover:bg-amber-700 active:bg-amber-700 transition-colors">&divide;</button>
            <button wire:click="press('*')" class="bg-amber-500 dark:bg-amber-600 text-white text-xl font-medium p-4 hover:bg-amber-600 dark:hover:bg-amber-700 active:bg-amber-700 transition-colors">&times;</button>

            {{-- Row 2: 7, 8, 9, - --}}
            <button wire:click="press('7')" class="bg-white dark:bg-gray-800 text-gray-800 dark:text-white text-xl font-medium p-4 hover:bg-gray-100 dark:hover:bg-gray-700 active:bg-gray-200 dark:active:bg-gray-600 transition-colors">7</button>
            <button wire:click="press('8')" class="bg-white dark:bg-gray-800 text-gray-800 dark:text-white text-xl font-medium p-4 hover:bg-gray-100 dark:hover:bg-gray-700 active:bg-gray-200 dark:active:bg-gray-600 transition-colors">8</button>
            <button wire:click="press('9')" class="bg-white dark:bg-gray-800 text-gray-800 dark:text-white text-xl font-medium p-4 hover:bg-gray-100 dark:hover:bg-gray-700 active:bg-gray-200 dark:active:bg-gray-600 transition-colors">9</button>
            <button wire:click="press('-')" class="bg-amber-500 dark:bg-amber-600 text-white text-xl font-medium p-4 hover:bg-amber-600 dark:hover:bg-amber-700 active:bg-amber-700 transition-colors">&minus;</button>

            {{-- Row 3: 4, 5, 6, + --}}
            <button wire:click="press('4')" class="bg-white dark:bg-gray-800 text-gray-800 dark:text-white text-xl font-medium p-4 hover:bg-gray-100 dark:hover:bg-gray-700 active:bg-gray-200 dark:active:bg-gray-600 transition-colors">4</button>
            <button wire:click="press('5')" class="bg-white dark:bg-gray-800 text-gray-800 dark:text-white text-xl font-medium p-4 hover:bg-gray-100 dark:hover:bg-gray-700 active:bg-gray-200 dark:active:bg-gray-600 transition-colors">5</button>
            <button wire:click="press('6')" class="bg-white dark:bg-gray-800 text-gray-800 dark:text-white text-xl font-medium p-4 hover:bg-gray-100 dark:hover:bg-gray-700 active:bg-gray-200 dark:active:bg-gray-600 transition-colors">6</button>
            <button wire:click="press('+')" class="bg-amber-500 dark:bg-amber-600 text-white text-xl font-medium p-4 hover:bg-amber-600 dark:hover:bg-amber-700 active:bg-amber-700 transition-colors">+</button>

            {{-- Row 4: 1, 2, 3, = (rowspan 2) --}}
            <button wire:click="press('1')" class="bg-white dark:bg-gray-800 text-gray-800 dark:text-white text-xl font-medium p-4 hover:bg-gray-100 dark:hover:bg-gray-700 active:bg-gray-200 dark:active:bg-gray-600 transition-colors">1</button>
            <button wire:click="press('2')" class="bg-white dark:bg-gray-800 text-gray-800 dark:text-white text-xl font-medium p-4 hover:bg-gray-100 dark:hover:bg-gray-700 active:bg-gray-200 dark:active:bg-gray-600 transition-colors">2</button>
            <button wire:click="press('3')" class="bg-white dark:bg-gray-800 text-gray-800 dark:text-white text-xl font-medium p-4 hover:bg-gray-100 dark:hover:bg-gray-700 active:bg-gray-200 dark:active:bg-gray-600 transition-colors">3</button>
            <button wire:click="equals" class="bg-blue-600 dark:bg-blue-700 text-white text-xl font-medium p-4 row-span-2 hover:bg-blue-700 dark:hover:bg-blue-800 active:bg-blue-800 transition-colors flex items-center justify-center">=</button>

            {{-- Row 5: 0 (colspan 2), . --}}
            <button wire:click="press('0')" class="bg-white dark:bg-gray-800 text-gray-800 dark:text-white text-xl font-medium p-4 col-span-2 hover:bg-gray-100 dark:hover:bg-gray-700 active:bg-gray-200 dark:active:bg-gray-600 transition-colors">0</button>
            <button wire:click="press('.')" class="bg-white dark:bg-gray-800 text-gray-800 dark:text-white text-xl font-medium p-4 hover:bg-gray-100 dark:hover:bg-gray-700 active:bg-gray-200 dark:active:bg-gray-600 transition-colors">.</button>
        </div>
    </div>

    {{-- History --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg w-full max-w-sm overflow-hidden">
        <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white">History</h2>
            @if($calculations->isNotEmpty())
                <button wire:click="clearHistory" wire:confirm="Clear all history?" class="text-sm text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">Clear</button>
            @endif
        </div>
        <div class="divide-y divide-gray-100 dark:divide-gray-700 max-h-96 overflow-y-auto">
            @forelse($calculations as $calculation)
                <button wire:click="loadFromHistory({{ $calculation->id }})" class="w-full text-left p-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $calculation->expression }}</div>
                    <div class="text-lg font-semibold text-gray-800 dark:text-white">= {{ $calculation->result }}</div>
                </button>
            @empty
                <div class="p-4 text-sm text-gray-400 dark:text-gray-500 text-center">No calculations yet</div>
            @endforelse
        </div>
    </div>
</div>
```

- [ ] **Step 3: Build frontend assets**

Run:
```bash
npm run build
```

Expected: `build/manifest.json` created successfully.

- [ ] **Step 4: Commit**

```bash
git add resources/views/components/⚡calculator.blade.php
git commit -m "feat: add Livewire calculator component with UI"
```

---

### Task 5: Write Feature Tests (TDD)

**Files:**
- Create: `tests/Feature/CalculatorComponentTest.php`

- [ ] **Step 1: Enable RefreshDatabase for Feature tests**

Edit `tests/Pest.php` — uncomment the `->use(RefreshDatabase::class)` line:

```php
<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

function something()
{
    // ..
}
```

- [ ] **Step 2: Write feature tests**

Create `tests/Feature/CalculatorComponentTest.php`:

```php
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
```

- [ ] **Step 3: Run all tests**

Run:
```bash
php artisan test --compact
```

Expected: All tests pass (both unit and feature).

- [ ] **Step 4: Commit**

```bash
git add tests/Pest.php tests/Feature/CalculatorComponentTest.php
git commit -m "feat: add Livewire component feature tests"
```

---

### Task 6: Final Verification and Polish

**Files:**
- All modified PHP files

- [ ] **Step 1: Run Pint formatter**

Run:
```bash
vendor/bin/pint --dirty --format agent
```

Expected: Any formatting issues fixed.

- [ ] **Step 2: Run full test suite**

Run:
```bash
php artisan test --compact
```

Expected: All tests pass.

- [ ] **Step 3: Verify in browser**

Run:
```bash
php artisan serve --no-interaction &
npm run build
```

Then visit the URL shown (typically `http://127.0.0.1:8000`). Verify:
- Calculator renders with display showing "0"
- Pressing digits builds the expression
- Pressing operators works
- Pressing = evaluates and shows result
- History appears on the right
- Clicking a history item loads its result
- Clear and backspace work
- Responsive layout (history below on mobile, side-by-side on desktop)

- [ ] **Step 4: Final commit**

```bash
git add -A
git commit -m "chore: format code and verify all tests pass"
```
