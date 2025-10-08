<?php

declare(strict_types=1);

namespace Src\Application\UseCases\Stock\CommitReservation;

use Illuminate\Support\Facades\DB;
use Src\Domain\Repositories\StockRepositoryInterface;
use Src\Domain\ValueObjects\StockId;
use Src\Domain\ValueObjects\Quantity;

/**
 * Commit Stock Reservation Use Case
 * 
 * Confirma a reserva de estoque, decrementando o estoque definitivamente.
 * Chamado quando um pedido é confirmado/pago.
 */
final class CommitReservationUseCase
{
    public function __construct(
        private readonly StockRepositoryInterface $stockRepository
    ) {
    }

    public function execute(array $data): void
    {
        $orderId = $data['order_id'];

        // Busca reservas pendentes do pedido
        $reservations = DB::table('stock_reservations')
            ->where('order_id', $orderId)
            ->where('status', 'pending')
            ->get();

        if ($reservations->isEmpty()) {
            throw new \DomainException("No pending reservations found for order {$orderId}");
        }

        DB::beginTransaction();

        try {
            foreach ($reservations as $reservation) {
                // Decrementa o estoque definitivamente
                $stock = $this->stockRepository->findById(StockId::fromString($reservation->stock_id));
                
                if (!$stock) {
                    throw new \DomainException("Stock {$reservation->stock_id} not found");
                }

                // Decrementa quantidade
                $newQuantity = max(0, $stock->getQuantity()->value() - $reservation->quantity);
                $stock->setQuantity(Quantity::fromInt($newQuantity));
                
                $this->stockRepository->save($stock);

                // Marca reserva como commitada
                DB::table('stock_reservations')
                    ->where('id', $reservation->id)
                    ->update([
                        'status' => 'committed',
                        'committed_at' => now(),
                        'updated_at' => now(),
                    ]);
            }

            DB::commit();

            // TODO: Publicar evento inventory.stock.committed

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}

