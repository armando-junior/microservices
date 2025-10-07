<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FinancialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Categories (Receitas e Despesas)
        $categories = [
            // Receitas
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Vendas de Produtos',
                'description' => 'Receitas provenientes de vendas de produtos',
                'type' => 'income',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Prestação de Serviços',
                'description' => 'Receitas de serviços prestados',
                'type' => 'income',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Despesas
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Fornecedores',
                'description' => 'Pagamentos a fornecedores',
                'type' => 'expense',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Salários',
                'description' => 'Folha de pagamento',
                'type' => 'expense',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Impostos',
                'description' => 'Impostos e taxas',
                'type' => 'expense',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Aluguel',
                'description' => 'Aluguel de imóveis',
                'type' => 'expense',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('categories')->insert($categories);

        // Suppliers (Fornecedores)
        $suppliers = [
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Fornecedor Alpha Ltda',
                'document' => '12345678000190',
                'email' => 'contato@alpha.com.br',
                'phone' => '11987654321',
                'address' => 'Rua Exemplo, 123 - São Paulo, SP',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Fornecedor Beta S/A',
                'document' => '98765432000100',
                'email' => 'vendas@beta.com.br',
                'phone' => '11976543210',
                'address' => 'Av. Principal, 456 - Rio de Janeiro, RJ',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Fornecedor Gamma ME',
                'document' => '11122233000144',
                'email' => 'contato@gamma.com.br',
                'phone' => '11965432109',
                'address' => 'Rua Comercial, 789 - Belo Horizonte, MG',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('suppliers')->insert($suppliers);

        // Accounts Payable (Contas a Pagar) - Exemplos
        $supplierCategory = DB::table('categories')
            ->where('name', 'Fornecedores')
            ->first();

        $firstSupplier = DB::table('suppliers')->first();

        if ($supplierCategory && $firstSupplier) {
            $accountsPayable = [
                [
                    'id' => Str::uuid()->toString(),
                    'supplier_id' => $firstSupplier->id,
                    'category_id' => $supplierCategory->id,
                    'description' => 'Compra de materiais - Pedido #001',
                    'amount_cents' => 150000, // R$ 1.500,00
                    'issue_date' => now()->subDays(10),
                    'due_date' => now()->addDays(20),
                    'status' => 'pending',
                    'paid_at' => null,
                    'payment_notes' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'id' => Str::uuid()->toString(),
                    'supplier_id' => $firstSupplier->id,
                    'category_id' => $supplierCategory->id,
                    'description' => 'Fornecimento de produtos - Nota Fiscal #12345',
                    'amount_cents' => 500000, // R$ 5.000,00
                    'issue_date' => now()->subDays(30),
                    'due_date' => now()->subDays(5),
                    'status' => 'overdue',
                    'paid_at' => null,
                    'payment_notes' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ];

            DB::table('accounts_payable')->insert($accountsPayable);
        }

        $this->command->info('✅ Financial Seeder executado com sucesso!');
        $this->command->info('   • ' . count($categories) . ' categorias criadas');
        $this->command->info('   • ' . count($suppliers) . ' fornecedores criados');
        $this->command->info('   • 2 contas a pagar de exemplo criadas');
    }
}


