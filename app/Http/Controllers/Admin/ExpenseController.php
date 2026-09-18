<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(): View
    {
        $expenses = Expense::query()
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        return view('admin.expenses.index', compact('expenses'));
    }
}
