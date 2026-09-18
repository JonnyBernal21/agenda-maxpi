<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        Expense::query()->create($validated);

        return redirect()
            ->to(URL::previous() ?: route('admin.expenses.index'))
            ->with('success', 'Gasto registrado correctamente.');
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $expense->update($validated);

        return redirect()
            ->to(URL::previous() ?: route('admin.expenses.index'))
            ->with('success', "Se actualizó el gasto {$expense->concept}.");
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $label = $expense->concept;
        $expense->delete();

        return redirect()
            ->to(URL::previous() ?: route('admin.expenses.index'))
            ->with('success', "Se eliminó {$label} de la lista.");
    }

    /**
     * @return array<string, list<mixed>>
     */
    private function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'concept' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(array_keys(Expense::CATEGORIES))],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'payment_method' => ['required', Rule::in(array_keys(Expense::PAYMENT_METHODS))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
