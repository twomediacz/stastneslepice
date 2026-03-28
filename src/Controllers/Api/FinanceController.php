<?php

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Models\Expense;
use App\Models\EggTransaction;
use App\Models\FeedPurchase;
use App\Models\EggRecord;
use App\Models\Setting;

class FinanceController extends Controller
{
    // --- Ostatní náklady ---

    public function expenses(): void
    {
        Auth::requireAuthApi();

        $group = $_GET['group'] ?? '';
        $months = (int) ($_GET['months'] ?? 12);

        if ($group === 'month') {
            $this->json([
                'records' => Expense::getMonthlyTotal($months),
                'grouped' => 'month',
            ]);
        } elseif ($group === 'category') {
            $this->json([
                'records' => Expense::getTotalByCategory($months),
                'grouped' => 'category',
            ]);
        } else {
            $this->json([
                'records' => Expense::getAll(),
                'total' => Expense::getTotalAmount($months),
            ]);
        }
    }

    public function storeExpense(): void
    {
        Auth::requireAuthApi();
        $data = $this->getPostData();

        $expenseDate = trim($data['expense_date'] ?? '');
        if ($expenseDate === '') {
            $this->jsonError('Datum je povinné.');
        }

        $category = trim($data['category'] ?? 'other');
        $validCategories = ['bedding', 'vet', 'equipment', 'other'];
        if (!in_array($category, $validCategories)) {
            $this->jsonError('Neplatná kategorie.');
        }

        $amount = (float) ($data['amount'] ?? 0);
        if ($amount <= 0) {
            $this->jsonError('Částka musí být větší než 0.');
        }

        $note = trim($data['note'] ?? '');

        $id = Expense::insert([
            'expense_date' => $expenseDate,
            'category' => $category,
            'amount' => $amount,
            'note' => $note ?: null,
        ]);

        $this->json([
            'success' => true,
            'record' => Expense::findById($id),
        ]);
    }

    public function updateExpense(): void
    {
        Auth::requireAuthApi();
        $data = $this->getPostData();

        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            $this->jsonError('Neplatné ID.');
        }

        $expenseDate = trim($data['expense_date'] ?? '');
        if ($expenseDate === '') {
            $this->jsonError('Datum je povinné.');
        }

        $category = trim($data['category'] ?? 'other');
        $validCategories = ['bedding', 'vet', 'equipment', 'other'];
        if (!in_array($category, $validCategories)) {
            $this->jsonError('Neplatná kategorie.');
        }

        $amount = (float) ($data['amount'] ?? 0);
        if ($amount <= 0) {
            $this->jsonError('Částka musí být větší než 0.');
        }

        $note = trim($data['note'] ?? '');

        Expense::update($id, [
            'expense_date' => $expenseDate,
            'category' => $category,
            'amount' => $amount,
            'note' => $note ?: null,
        ]);

        $this->json([
            'success' => true,
            'record' => Expense::findById($id),
        ]);
    }

    public function deleteExpense(): void
    {
        Auth::requireAuthApi();
        $data = $this->getPostData();
        $id = (int) ($data['id'] ?? 0);

        if ($id <= 0) {
            $this->jsonError('Neplatné ID.');
        }

        Expense::delete($id);
        $this->json(['success' => true]);
    }

    // --- Prodej / darování vajec ---

    public function eggTransactions(): void
    {
        Auth::requireAuthApi();

        $group = $_GET['group'] ?? '';
        $months = (int) ($_GET['months'] ?? 12);

        if ($group === 'month') {
            $this->json([
                'records' => EggTransaction::getMonthlyRevenue($months),
                'grouped' => 'month',
            ]);
        } else {
            $this->json([
                'records' => EggTransaction::getAll(),
                'totalRevenue' => EggTransaction::getTotalRevenue($months),
                'totalSold' => EggTransaction::getTotalSold($months),
                'totalGifted' => EggTransaction::getTotalGifted($months),
            ]);
        }
    }

    public function storeEggTransaction(): void
    {
        Auth::requireAuthApi();
        $data = $this->getPostData();

        $transactionDate = trim($data['transaction_date'] ?? '');
        if ($transactionDate === '') {
            $this->jsonError('Datum je povinné.');
        }

        $type = trim($data['type'] ?? 'sale');
        if (!in_array($type, ['sale', 'gift'])) {
            $this->jsonError('Neplatný typ transakce.');
        }

        $quantity = (int) ($data['quantity'] ?? 0);
        if ($quantity <= 0) {
            $this->jsonError('Počet musí být větší než 0.');
        }

        $priceTotal = (float) ($data['price_total'] ?? 0);
        if ($type === 'sale' && $priceTotal < 0) {
            $this->jsonError('Cena nemůže být záporná.');
        }

        $recipient = trim($data['recipient'] ?? '');
        $note = trim($data['note'] ?? '');

        $id = EggTransaction::insert([
            'transaction_date' => $transactionDate,
            'type' => $type,
            'quantity' => $quantity,
            'price_total' => $type === 'gift' ? 0 : $priceTotal,
            'recipient' => $recipient ?: null,
            'note' => $note ?: null,
        ]);

        $this->json([
            'success' => true,
            'record' => EggTransaction::findById($id),
        ]);
    }

    public function updateEggTransaction(): void
    {
        Auth::requireAuthApi();
        $data = $this->getPostData();

        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            $this->jsonError('Neplatné ID.');
        }

        $transactionDate = trim($data['transaction_date'] ?? '');
        if ($transactionDate === '') {
            $this->jsonError('Datum je povinné.');
        }

        $type = trim($data['type'] ?? 'sale');
        if (!in_array($type, ['sale', 'gift'])) {
            $this->jsonError('Neplatný typ transakce.');
        }

        $quantity = (int) ($data['quantity'] ?? 0);
        if ($quantity <= 0) {
            $this->jsonError('Počet musí být větší než 0.');
        }

        $priceTotal = (float) ($data['price_total'] ?? 0);
        if ($type === 'sale' && $priceTotal < 0) {
            $this->jsonError('Cena nemůže být záporná.');
        }

        $recipient = trim($data['recipient'] ?? '');
        $note = trim($data['note'] ?? '');

        EggTransaction::update($id, [
            'transaction_date' => $transactionDate,
            'type' => $type,
            'quantity' => $quantity,
            'price_total' => $type === 'gift' ? 0 : $priceTotal,
            'recipient' => $recipient ?: null,
            'note' => $note ?: null,
        ]);

        $this->json([
            'success' => true,
            'record' => EggTransaction::findById($id),
        ]);
    }

    public function deleteEggTransaction(): void
    {
        Auth::requireAuthApi();
        $data = $this->getPostData();
        $id = (int) ($data['id'] ?? 0);

        if ($id <= 0) {
            $this->jsonError('Neplatné ID.');
        }

        EggTransaction::delete($id);
        $this->json(['success' => true]);
    }

    // --- Cena vejce v obchodě ---

    public function updateEggMarketPrice(): void
    {
        Auth::requireAuthApi();
        $data = $this->getPostData();

        $price = (float) ($data['egg_market_price'] ?? 0);
        if ($price <= 0) {
            $this->jsonError('Cena musí být větší než 0.');
        }

        Setting::set('egg_market_price', (string) $price);

        $this->json([
            'success' => true,
            'egg_market_price' => $price,
        ]);
    }

    // --- Souhrnná data ---

    public function summary(): void
    {
        Auth::requireAuthApi();

        $months = (int) ($_GET['months'] ?? 12);

        $feedMonthly = FeedPurchase::getMonthlySpending($months);
        $expenseMonthly = Expense::getMonthlyTotal($months);
        $revenueMonthly = EggTransaction::getMonthlyRevenue($months);
        $expensesByCategory = Expense::getTotalByCategory($months);
        $feedTotal = FeedPurchase::getTotalSpent($months);

        $this->json([
            'feedMonthly' => $feedMonthly,
            'expenseMonthly' => $expenseMonthly,
            'revenueMonthly' => $revenueMonthly,
            'expensesByCategory' => $expensesByCategory,
            'feedTotal' => $feedTotal,
        ]);
    }
}
