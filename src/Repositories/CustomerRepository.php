<?php

namespace Daalder\Exact\Repositories;

use Picqer\Financials\Exact\Account;
use Picqer\Financials\Exact\Connection;
use Pionect\Daalder\Models\Customer\Customer;
use Pionect\Daalder\Models\Order\Order;

class CustomerRepository extends \Pionect\Daalder\Models\Customer\Repositories\CustomerRepository
{
    // TODO: change return type to int|null on PHP 8
    public function getExactIdFromCustomer(Customer $customer) {
        return $customer->exact_id;
    }

    public function setExactIdIfNotExists(Customer $customer, string $exactId) : void {
        if(is_null($this->getExactIdFromCustomer($customer))) {
            $customer->exact_id = $exactId;

            Customer::withoutSyncingToSearch(function () use ($customer) {
                $customer->save();
            });
        }
    }

    /**
     * @param Order $order
     * @param Connection $connection
     * @return Account
     * @description Updates or creates an Exact customer based on customer values of an order
     */
    public function updateOrCreateExactCustomerFromOrder(Connection $connection, Order $order) : Account
    {
        $exactId = $this->getExactIdFromCustomer($order->customer);

        // If no exact ID found
        if(!$exactId){
            // Try matching based on customer email
            $item = new Account($connection);
            /* @var Account $match */
            $match = $item->filter("Email eq '".$order->email."'",'','ID');

            // If match was found
            if(isset($match[0])) {
                // Get ID from match
                $exactId = $match[0]->ID;
                // Save reference
                $this->setExactIdIfNotExists($order->customer, $exactId);
            }
        }

        $account = new Account($connection);

        if ($exactId) {
            $account->ID = $exactId;
        }

        $account->Name = $order->contact_firstname . ' ' . $order->contact_lastname;
        $account->InvoicingMethod = 2;
        $account->Email = $order->email;
        $account->Phone = $order->mobile ?? $order->phone;
        $account->Status = 'C';
        $account->AddressLine1 = $order->invoice_address . ' ' . $order->invoice_housenumber;
        $account->City = $order->invoice_city;
        $account->Postcode = $order->invoice_postalcode;
//        $account->AddressLine2 = $order->email;
//        $account->AddressLine3 = $order->mobile ?? $order->phone;
        $account->Country = $order->country_code;
//        $account->SalesVATCode = $this->getVATCode($order);

//        try {
//            if (VatCalculator::isValidVATNumber($order->vatnumber)) {
//                $account->VATNumber = $order->vatnumber;
//            }
//        } catch (VATCheckUnavailableException $e) {
//            // vat api unavailable
//        }

        $account->save();

        return $account;
    }

    public function getVATCode(Order $order) {
//        $order->vatnumber
    }
}
