<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Team;
use App\Models\User;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Gumawa ng Teams
        $teamJeremiah = Team::create([
            'team_name' => 'Team Jeremiah',
            'quota' => 500000.00,
        ]);

        $teamPsalms = Team::create([
            'team_name' => 'Team Psalms',
            'quota' => 400000.00,
        ]);

        // 2. Gumawa ng Users (Admin, Superadmin, at Regular Users)
        $superadmin = User::create([
            'name' => 'System Super Administrator',
            'email' => 'superadmin@gmail.com',
            'password' => Hash::make('password123'),
            'role' => 'superadmin',
            'team_id' => null, // Superadmins might not be bound to a team
        ]);

        $admin = User::create([
            'name' => 'System Administrator',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'team_id' => $teamJeremiah->id,
        ]);

        $user1 = User::create([
            'name' => 'John Agent',
            'email' => 'user1@gmail.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'team_id' => $teamJeremiah->id,
        ]);

        $user2 = User::create([
            'name' => 'Sarah Agent',
            'email' => 'user2@gmail.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'team_id' => $teamPsalms->id,
        ]);

        // 3. Gumawa ng Sample Clients
        $client1 = Client::create([
            'client_code' => 'CLI-001',
            'user_id' => $user1->id,
            'client_name' => 'Acme Corporation Phils',
            'contact_person' => 'Robert Tan',
            'position' => 'Procurement Manager',
            'contact_number' => '09171234567',
            'client_address' => 'Ayala Avenue, Makati City',
            'tin_number' => '123-456-789-000',
            'business_style' => 'IT Services & Solutions',
            'client_status' => 'Approved',
        ]);

        $client2 = Client::create([
            'client_code' => 'CLI-002',
            'user_id' => $user2->id,
            'client_name' => 'Global Logistics Inc.',
            'contact_person' => 'Elena Cruz',
            'position' => 'Operations Director',
            'contact_number' => '09189876543',
            'client_address' => 'Cebu IT Park, Cebu City',
            'tin_number' => '987-654-321-000',
            'business_style' => 'Freight & Logistics',
            'client_status' => 'Approved',
        ]);

        // 4. Gumawa ng Sample Invoices at Invoice Items
        // Invoice 1 (Under User 1 & Team Jeremiah)
        $subtotal1 = 50000.00;
        $taxAmount1 = 6000.00; // 12% VAT
        $totalAmount1 = 56000.00;

        $invoice1 = Invoice::create([
            'invoice_number' => '1001',
            'client_id' => $client1->id,
            'team_id' => $teamJeremiah->id,
            'user_id' => $user1->id,
            'issue_date' => Carbon::now()->subDays(5),
            'due_date' => Carbon::now()->addDays(25),
            'service_period_start' => Carbon::now()->subDays(30),
            'service_period_end' => Carbon::now(),
            'payment_terms' => '30',
            'tax_type' => 'vat',
            'delivery_address' => 'Ayala Avenue, Makati City',
            'delivery_contact_person' => 'Robert Tan',
            'delivery_position' => 'Procurement Manager',
            'delivery_phone' => '09171234567',
            'subtotal' => $subtotal1,
            'tax_amount' => $taxAmount1,
            'discount_amount' => 0.00,
            'total_amount' => $totalAmount1,
            'notes' => 'Please deposit to BPI Account #1234-5678-90',
            'status' => 'Pending Approval',
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice1->id,
            'item_name' => 'Cloud Architecture Setup & Migration Services',
            'quantity' => 1,
            'price' => 50000.00,
            'tax' => 6000.00,
            'discount' => 0.00,
            'total' => 50000.00,
        ]);

        // Invoice 2 (Under User 2 & Team Psalms)
        $subtotal2 = 35000.00;
        $taxAmount2 = 0.00; // Non-VAT
        $totalAmount2 = 35000.00;

        $invoice2 = Invoice::create([
            'invoice_number' => '1002',
            'client_id' => $client2->id,
            'team_id' => $teamPsalms->id,
            'user_id' => $user2->id,
            'issue_date' => Carbon::now()->subDays(2),
            'due_date' => Carbon::now()->addDays(13),
            'service_period_start' => Carbon::now()->subDays(15),
            'service_period_end' => Carbon::now(),
            'payment_terms' => '15',
            'tax_type' => 'non-vat',
            'delivery_address' => 'Cebu IT Park, Cebu City',
            'delivery_contact_person' => 'Elena Cruz',
            'delivery_position' => 'Operations Director',
            'delivery_phone' => '09189876543',
            'subtotal' => $subtotal2,
            'tax_amount' => $taxAmount2,
            'discount_amount' => 0.00,
            'total_amount' => $totalAmount2,
            'notes' => 'GCash / Bank Transfer accepted.',
            'status' => 'Approved',
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice2->id,
            'item_name' => 'Monthly Software Maintenance & Support Fee',
            'quantity' => 1,
            'price' => 35000.00,
            'tax' => 0.00,
            'discount' => 0.00,
            'total' => 35000.00,
        ]);
    }
}