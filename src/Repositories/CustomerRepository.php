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
        $account = new Account($connection);

        // If customer is already matched to an Exact Account
        $exactId = $this->getExactIdFromCustomer($order->customer);
        if ($exactId) {
            // Update the existing Exact Account
            $account->ID = $exactId;
        }

        if($order->contact_firstname) {
            $name = $order->contact_firstname . ' ' . $order->contact_lastname;
        } else {
            $name = $order->customer->firstname . ' ' . $order->customer->firstname;
        }

        if($order->invoice_address && $order->invoice_housenumber) {
            $addressLine = $order->invoice_address . ' ' . $order->invoice_housenumber;
        } else {
            $addressLine = $order->customer->invoice_address . ' ' . $order->customer->invoice_housenumber;
        }

        $account->Name = $name;
        $account->InvoicingMethod = 2;
        $account->Email = $order->email ?? $order->customer->email;
        $account->Phone = $order->mobile ?? $order->phone ?? $order->customer->mobile ?? $order->customer->telephone;
        $account->Status = 'C';
        $account->AddressLine1 = $addressLine;
        $account->City = $order->invoice_city ?? $order->customer->invoice_city;
        $account->Postcode = $order->invoice_postalcode ?? $order->customer->invoice_postalcode;
        $account->Country = $order->country_code ?? $order->customer->invoice_country_code;
//        $account->SalesVATCode = $this->getVATCode($order);

        $account->save();

        return $account;
    }

    public function getVATCode(Order $order) {
//        $order->vatnumber
    }
}
