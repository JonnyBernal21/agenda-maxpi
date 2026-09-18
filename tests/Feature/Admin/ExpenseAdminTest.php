<?php

namespace Tests\Feature\Admin;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_or_manage_expenses(): void
    {
        $expense = Expense::factory()->create();

        $this->get(route('admin.expenses.index'))
            ->assertRedirect(route('login'));

        $this->post(route('admin.expenses.store'), $this->payload([
            'concept' => 'Gasto nuevo',
        ]))->assertRedirect(route('login'));

        $this->put(route('admin.expenses.update', $expense), $this->payload([
            'concept' => 'Gasto editado',
        ]))->assertRedirect(route('login'));

        $this->delete(route('admin.expenses.destroy', $expense))
            ->assertRedirect(route('login'));

        $this->assertNotSoftDeleted($expense);
        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'concept' => $expense->concept,
        ]);
    }

    public function test_admin_can_view_the_expenses_index(): void
    {
        $this->actingAs(User::factory()->create());
        Expense::factory()->create([
            'concept' => 'Carga de gasolina',
            'category' => Expense::CATEGORY_COMBUSTIBLE,
            'amount' => 1850,
        ]);

        $this->get(route('admin.expenses.index'))
            ->assertOk()
            ->assertSee('Gastos')
            ->assertSee('Agregar gasto')
            ->assertSee('Carga de gasolina')
            ->assertSee('1 gastos en total');
    }

    public function test_admin_can_store_an_expense(): void
    {
        $this->actingAs(User::factory()->create());

        $this->from(route('admin.expenses.index'))
            ->post(route('admin.expenses.store'), $this->payload())
            ->assertRedirect(route('admin.expenses.index'));

        $this->assertDatabaseHas('expenses', [
            'concept' => 'Cambio de aceite',
            'category' => Expense::CATEGORY_MANTENIMIENTO,
            'amount' => 2400.00,
            'payment_method' => Expense::PAYMENT_TRANSFER,
        ]);
    }

    public function test_store_requires_expense_fields(): void
    {
        $this->actingAs(User::factory()->create());

        $this->from(route('admin.expenses.index'))
            ->post(route('admin.expenses.store'), [])
            ->assertRedirect(route('admin.expenses.index'))
            ->assertSessionHasErrors(['date', 'concept', 'category', 'amount', 'payment_method']);
    }

    public function test_store_rejects_an_invalid_category(): void
    {
        $this->actingAs(User::factory()->create());

        $this->from(route('admin.expenses.index'))
            ->post(route('admin.expenses.store'), $this->payload([
                'category' => 'invalida',
            ]))
            ->assertRedirect(route('admin.expenses.index'))
            ->assertSessionHasErrors(['category']);
    }

    public function test_admin_can_update_an_expense(): void
    {
        $this->actingAs(User::factory()->create());
        $expense = Expense::factory()->create([
            'concept' => 'Carga de gasolina',
            'category' => Expense::CATEGORY_COMBUSTIBLE,
            'amount' => 1850,
        ]);

        $this->from(route('admin.expenses.index'))
            ->put(route('admin.expenses.update', $expense), $this->payload([
                'concept' => 'Carga de gasolina premium',
                'amount' => 2100,
            ]))
            ->assertRedirect(route('admin.expenses.index'));

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'concept' => 'Carga de gasolina premium',
            'amount' => 2100.00,
        ]);
    }

    public function test_admin_can_soft_delete_an_expense(): void
    {
        $this->actingAs(User::factory()->create());
        $expense = Expense::factory()->create([
            'concept' => 'Renta de oficina',
        ]);

        $this->from(route('admin.expenses.index'))
            ->delete(route('admin.expenses.destroy', $expense))
            ->assertRedirect(route('admin.expenses.index'));

        $this->assertSoftDeleted('expenses', ['id' => $expense->id]);
        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'concept' => 'Renta de oficina',
        ]);
        $this->assertNull(Expense::query()->find($expense->id));
        $this->get(route('admin.expenses.index'))
            ->assertOk()
            ->assertSee('0 gastos en total')
            ->assertDontSee('>Renta de oficina</span>', false);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'date' => '2026-09-10',
            'concept' => 'Cambio de aceite',
            'category' => Expense::CATEGORY_MANTENIMIENTO,
            'amount' => 2400,
            'payment_method' => Expense::PAYMENT_TRANSFER,
            'notes' => 'Servicio en taller de confianza.',
        ], $overrides);
    }
}
