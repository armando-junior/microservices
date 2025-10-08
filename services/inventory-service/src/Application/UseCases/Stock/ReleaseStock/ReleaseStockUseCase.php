<?php

declare(strict_types=1);

namespace Src\Application\UseCases\Stock\ReleaseStock;

use Illuminate\Support\Facades\DB;

/**
 * Release Stock Use Case
 * 
 * Libera estoque reservado quando um pedido é cancelado.
 */
final class ReleaseStockUseCase
{
    public function execute(array $data): void
    {
        $orderId = $data['order_id'];

        // Busca reservas pendentes do pedido
        $reservations = DB::table('stock_reservations')
            ->where('order_id', $orderId)
            ->where('status', 'pending')
            ->get();

        if ($reservations->isEmpty()) {
            // Não há reservas pendentes (pode já ter sido liberado ou commitado)
            return;
        }

        // Marca reservas como liberadas
        DB::table('stock_reservations')
            ->where('order_id', $orderId)
            ->where('status', 'pending')
            ->update([
                'status' => 'released',
                'released_at' => now(),
                'updated_at' => now(),
            ]);

        // TODO: Publicar evento inventory.stock.released
    }
}

