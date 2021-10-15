<?php

namespace Daalder\Exact\Jobs;

use Daalder\Exact\Repositories\CustomerRepository;
use Daalder\Exact\Repositories\OrderRepository;
use Daalder\Exact\Services\ConnectionFactory;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Picqer\Financials\Exact\Connection;
use Pionect\Daalder\Models\Order\Order;

class PushOrderToExact implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels, Batchable;

    /**
     * @var Order
     */
    protected $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function handle(
        OrderRepository $orderRepository,
        CustomerRepository $customerRepository
    ) {
        // Resolve Picqer Connection
        $connection = ConnectionFactory::getConnection();

        // If order hasn't been pushed yet
        if (
            is_null($orderRepository->getExactIdFromOrder($this->order))
        ) {
            // Create Exact Account (customer)
            $account = $customerRepository->updateOrCreateExactCustomerFromOrder($connection, $this->order);
            // Create Exact Address
            $deliveryAddress = $orderRepository->createExactDeliveryAddressFromOrder($connection, $this->order, $account);

            // If no invoice address is available
//            if (!$this->order->invoice_address) {
//                // Use delivery address as invoice address
//                $invoiceAddress = $orderRepository->createExactDeliveryAddressFromOrder($this->connection, $this->order, $account);
//            } else {
//                $invoiceAddress = $orderRepository->createExactInvoiceAddressFromOrder($this->connection, $this->order, $account);
//            }

            // Make order
            $exactOrder = $orderRepository->createExactOrder($connection, $this->order, $account, $deliveryAddress);

//            $this->pushDiscount($exactOrder);
        }
    }
}
