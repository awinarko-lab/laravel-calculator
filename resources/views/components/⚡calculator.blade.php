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
            // BUG: loads result instead of expression
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

<div class="flex flex-col lg:flex-row gap-6 w-full max-w-3xl" x-data="{ hasFocus: false }" @keydown.window="hasFocus = true" @click.away="hasFocus = false" tabindex="0"
    @keydown.="if ($event.key >= '0' && $event.key <= '9') { $event.preventDefault(); $wire.press($event.key); }"
    @keydown."+="$wire.press('+')"
    @keydown.-="$wire.press('-')"
    @keydown.*="$wire.press('*')"
    @keydown./="$event.preventDefault(); $wire.press('/')"
    @keydown.enter="$wire.equals()"
    @keydown.="if ($event.key === '=') { $event.preventDefault(); $wire.equals(); }"
    @keydown.escape="$wire.clear()"
    @keydown.backspace="$wire.backspace()"
    @keydown..="if ($event.key === '.') { $event.preventDefault(); $wire.press('.'); }">
    {{-- Calculator --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden w-full max-w-sm" :class="{ 'ring-4 ring-blue-500': hasFocus }">
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
