# Calculator Application — Design Spec

## Overview

A web-based calculator built with Laravel 13, Livewire 4, Tailwind CSS 4, and SQLite. Functional but not over-engineered — designed as a demonstration project with room to grow.

## Architecture

**Single Livewire component** handles calculator UI and logic. History persisted to SQLite via an Eloquent model. No authentication.

## Data Model

### `calculations` table

| Column       | Type      | Description                  |
|--------------|-----------|------------------------------|
| `id`         | bigint    | Auto-increment primary key   |
| `expression` | string    | e.g. `"12 + 7"`             |
| `result`     | string    | e.g. `"19"` (string for display precision) |
| `created_at` | timestamp |                              |
| `updated_at` | timestamp |                              |

### `Calculation` model

- `$fillable = ['expression', 'result']`
- No user relationship (no auth)

## UI Layout

Single-page layout with two sections, responsive:

1. **Calculator (left/center)** — card component with:
   - Display area: current expression on top, result below
   - Button grid (4 columns): digits 0-9, operators (+, -, *, /), decimal, equals, clear (C), backspace
   - Phone-calculator button arrangement

2. **History (right / below on mobile)** — scrollable list:
   - Shows expression → result, most recent first
   - Click an entry to load its result into the calculator
   - "Clear history" button to delete all entries

Styled with Tailwind CSS 4.

## Calculator Behavior

- **Expression building:** Tap digits/operators to build an expression string displayed live
- **Evaluation:** Press `=` to evaluate. Result displays and the calculation is saved to the database
- **Error handling:** Division by zero shows "Error" on display (no crash, nothing saved to DB)
- **Decimal handling:** One decimal point per number segment (prevents `12.3.4`)
- **Clear (C):** Resets the entire expression
- **Backspace:** Removes the last character from the expression
- **Chaining:** After a result, pressing an operator chains from that result (e.g. result `19`, press `+` → starts `19+`)
- **History click:** Loads the result from a history entry into the display for continued calculation

## Component Structure

```
app/Livewire/Calculator.php    — Single Livewire component
resources/views/livewire/calculator.blade.php — Blade template
app/Models/Calculation.php     — Eloquent model
database/migrations/xxxx_create_calculations_table.php
```

## Testing

All tests use Pest 4 syntax:

- **Unit test:** Expression evaluation logic (parsing, division by zero, decimal handling, operator chaining)
- **Feature test:** Livewire component interactions (button presses update display, history saved on equals, history click loads result, clear/backspace work, history clear works)

## Future Extensibility

The design allows future additions without restructuring:
- Scientific functions (sqrt, %, parentheses) — add buttons and extend evaluation
- Keyboard support — add Alpine.js event listeners
- User authentication — add user_id foreign key to calculations
- API endpoints — add routes returning Calculation resources
