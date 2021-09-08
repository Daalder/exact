<?php

namespace Daalder\Exact\Repositories;

use Daalder\Exact\Jobs\PushProductToExact;
use Picqer\Financials\Exact\Account;
use Picqer\Financials\Exact\Address;
use Picqer\Financials\Exact\Connection;
use Picqer\Financials\Exact\Item;
use Picqer\Financials\Exact\SalesOrder;
use Pionect\Daalder\Models\Order\Order;
use Pionect\Daalder\Models\Order\Orderrow;
use Pionect\Daalder\Models\Order\Repositories\OrderRowRepository;
use Pionect\Daalder\Models\VatRate\Repositories\VatRateRepository;
use Pionect\Daalder\Models\VatRate\VatRate;
use Pionect\Daalder\Services\Product\ProductPriceFetcher;

class OrderRepository extends \Pionect\Daalder\Models\Order\Repositories\OrderRepository
{
    /**
     * @var ProductRepository $productRepository
     */
    private $productRepository;

    /**
     * @var VatRateRepository $vatRateRepository
     */
    private $vatRateRepository;

    public function __construct(
        Order $order,
        OrderRowRepository $orderRowRepository,
        ProductPriceFetcher $productPriceFetcher,
        ProductRepository $productRepository,
        VatRateRepository $vatRateRepository
    ) {
        parent::__construct($order, $orderRowRepository, $productPriceFetcher);
        $this->productRepository = $productRepository;
        $this->vatRateRepository = $vatRateRepository;
    }

    // TODO: change return type to int|null on PHP 8
    public function getExactIdFromOrder(Order $order) {
        return $order->exact_id;
    }

    public function setExactIdIfNotExists(Order $order, string $exactId) : void {
        if(is_null($this->getExactIdFromOrder($order))) {
            $order->exact_id = $exactId;

            Order::withoutSyncingToSearch(function() use ($order) {
                $order->save();
            });
        }
    }

    public function createExactOrder(
        Connection $connection,
        Order $order,
        Account $account,
        Address $deliveryAddress = null
    ) : SalesOrder {
        $salesOrder = new SalesOrder($connection);
        $salesOrder->PaymentReference = $order->transaction_type;
        $salesOrder->OrderedBy        = $account->ID;
        $salesOrder->DeliverTo        = $account->ID;
        $salesOrder->InvoiceTo        = $account->ID;
        $salesOrder->DeliveryAddress  = $deliveryAddress->ID;
        $salesOrder->Remarks          = $order->comment;
        $salesOrder->YourRef          = $order->transaction_id;
        $salesOrder->Description      = '';//$this->getDescription($order);

        $salesOrderLines = [];

        /** @var Orderrow $orderrow */
        foreach($order->orderrows as $orderrow) {
            // Get Exact ID for Daalder product on orderrow
            $productExactID = null;
            // Match Daalder product to Exact Item based on sku
            $item = new Item($connection);
            $productExactID = $item->findId(trim($orderrow->sku));

            // Product with sku not found in Exact
            if(!$productExactID) {
                // Create the product in Exact
                PushProductToExact::dispatchNow($orderrow->product);

                // Fetch the newly created product from Exact
                $item = new Item($connection);
                $productExactID = $item->findId(trim($orderrow->sku));
            }

            // Get the Daalder VAT rate based on the orderrow VAT percentage
            $vatRate = VatRate::firstWhere('percentage', $orderrow->vat_rate);
            if(is_null($vatRate)) {
                // If the VAT rate with orderrow VAT percentage isn't found, default to preferred VAT rate
                $vatRate = $this->vatRateRepository->fetchPreferred();
            }

            // Create an Exact SalesOrderLine for this Daalder Orderrow
            $salesOrderLines[] = [
                'Item' => $productExactID,
                'UnitCode' => 'pc',
                'UnitPrice' => $orderrow->getPrice() / (1 + ($vatRate->percentage / 100)),
                'Quantity' => $orderrow->amount,
                'VATCode' => $vatRate->exact_code,
                'Description' => $this->getOrderRowDescription($orderrow)
            ];
        }

        // Save SalesOrderLines to Exact SalesOrder
        $salesOrder->SalesOrderLines = $salesOrderLines;
        $salesOrder->save();

        // Save Exact SalesOrder ID to Daalder Order
        $this->setExactIdIfNotExists($order, $salesOrder->OrderID);

        return $salesOrder;
    }

    private function getOrderRowDescription(Orderrow $orderrow) : string {
        // Collect productoptions of order row
        $productOptionsString = collect(json_decode($orderrow->productoptions))
            // Map the productoptions into strings with format 'label: value'
            ->map(function($productoptions) {
                return $productoptions->label . ': ' . $productoptions->value;
            })
            // Implode strings into a single comma-separated string
            ->implode(', ');

        // Return the combined orderrow name and formatted productoptions string as the orderrow description
        return trim($orderrow->name . '. ' . $productOptionsString);
    }

    public function createExactDeliveryAddressFromOrder(
        Connection $connection,
        Order $order,
        Account $account
    ) : Address {
        $address = new Address($connection);
        $address->Account = $account->ID;
        $address->AddressLine1 = $order->address . ' ' . $order->housenumber;
        $address->City = $order->city;
        $address->Postcode = $order->postalcode;
        $address->Phone = $order->mobile ?? $order->phone;
        $address->AddressLine2 = $order->email;
        $address->AddressLine3 = $order->mobile ?? $order->phone;
        $address->Country = $order->country_code;
        $address->Type = 4;
        $address->save();

        return $address;
    }

    public function createExactInvoiceAddressFromOrder(
        Connection $connection,
        Order $order,
        Account $account
    ) : Address {
        $address = new Address($connection);
        $address->Account = $account->ID;
        $address->AddressLine1 = $order->invoice_address . ' ' . $order->invoice_housenumber;
        $address->City = $order->invoice_city;
        $address->Postcode = $order->invoice_postalcode;
        $address->Phone = $order->mobile ?? $order->phone;
        $address->AddressLine2 = $order->email;
        $address->AddressLine3 = $order->mobile ?? $order->phone;
        $address->Country = $order->invoice_country_code;
        $address->Type = 3;
        $address->save();

        return $address;
    }
}
