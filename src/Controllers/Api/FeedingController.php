<?php

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Models\FeedType;
use App\Models\FeedingRecord;
use App\Models\FeedPurchase;

class FeedingController extends Controller
{
    // --- Typy krmiva ---

    public function types(): void
    {
        Auth::requireAuthApi();
        $this->json(['types' => FeedType::getAll()]);
    }

    public function storeType(): void
    {
        Auth::requireAuthApi();
        $data = $this->getPostData();

        $name = trim($data['name'] ?? '');
        if ($name === '') {
            $this->jsonError('Název krmiva je povinný.');
        }

        $pricePerKg = (float) ($data['price_per_kg'] ?? 0);
        if ($pricePerKg < 0) {
            $this->jsonError('Cena nemůže být záporná.');
        }

        $palatability = isset($data['palatability']) && $data['palatability'] !== ''
            ? (int) $data['palatability']
            : null;
        if ($palatability !== null && ($palatability < 1 || $palatability > 5)) {
            $this->jsonError('Chutnost musí být 1–5.');
        }

        $note = trim($data['note'] ?? '');

        $id = FeedType::insert([
            'name' => $name,
            'price_per_kg' => $pricePerKg,
            'palatability' => $palatability,
            'note' => $note ?: null,
        ]);

        $this->json([
            'success' => true,
            'type' => FeedType::findById($id),
        ]);
    }

    public function updateType(): void
    {
        Auth::requireAuthApi();
        $data = $this->getPostData();

        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            $this->jsonError('Neplatné ID.');
        }

        $name = trim($data['name'] ?? '');
        if ($name === '') {
            $this->jsonError('Název krmiva je povinný.');
        }

        $pricePerKg = (float) ($data['price_per_kg'] ?? 0);
        if ($pricePerKg < 0) {
            $this->jsonError('Cena nemůže být záporná.');
        }

        $palatability = isset($data['palatability']) && $data['palatability'] !== ''
            ? (int) $data['palatability']
            : null;
        if ($palatability !== null && ($palatability < 1 || $palatability > 5)) {
            $this->jsonError('Chutnost musí být 1–5.');
        }

        $note = trim($data['note'] ?? '');
        $isActive = isset($data['is_active']) ? (int) $data['is_active'] : 1;

        FeedType::update($id, [
            'name' => $name,
            'price_per_kg' => $pricePerKg,
            'palatability' => $palatability,
            'note' => $note ?: null,
            'is_active' => $isActive,
        ]);

        $this->json([
            'success' => true,
            'type' => FeedType::findById($id),
        ]);
    }

    public function deleteType(): void
    {
        Auth::requireAuthApi();
        $data = $this->getPostData();
        $id = (int) ($data['id'] ?? 0);

        if ($id <= 0) {
            $this->jsonError('Neplatné ID.');
        }

        FeedType::delete($id);
        $this->json(['success' => true]);
    }

    // --- Záznamy krmení ---

    public function records(): void
    {
        Auth::requireAuthApi();

        $group = $_GET['group'] ?? '';
        $days = (int) ($_GET['days'] ?? 30);
        $months = (int) ($_GET['months'] ?? 12);
        $weeks = (int) ($_GET['weeks'] ?? 12);

        if ($group === 'day') {
            $this->json(['records' => FeedingRecord::getDailyConsumption($days), 'grouped' => 'day']);
        } elseif ($group === 'week') {
            $this->json(['records' => FeedingRecord::getWeeklyConsumption($weeks), 'grouped' => 'week']);
        } elseif ($group === 'month') {
            $this->json(['records' => FeedingRecord::getMonthlyConsumption($months), 'grouped' => 'month']);
        } elseif ($group === 'by_type') {
            $this->json(['records' => FeedingRecord::getConsumptionByType($days), 'grouped' => 'by_type']);
        } elseif ($group === 'daily_by_type') {
            $this->json(['records' => FeedingRecord::getDailyConsumptionByType($days), 'grouped' => 'daily_by_type']);
        } elseif ($group === 'monthly_by_type') {
            $this->json(['records' => FeedingRecord::getMonthlyConsumptionByType($months), 'grouped' => 'monthly_by_type']);
        } else {
            $this->json([
                'records' => FeedingRecord::getRecent($days),
                'totalKg' => FeedingRecord::getTotalKg($days),
                'totalCost' => FeedingRecord::getTotalCost($days),
                'dailyAvg' => FeedingRecord::getDailyAverage($days),
            ]);
        }
    }

    public function storeRecord(): void
    {
        Auth::requireAuthApi();
        $data = $this->getPostData();

        $feedTypeId = (int) ($data['feed_type_id'] ?? 0);
        if ($feedTypeId <= 0) {
            $this->jsonError('Vyberte typ krmiva.');
        }

        $recordDate = trim($data['record_date'] ?? '');
        if ($recordDate === '') {
            $this->jsonError('Datum je povinné.');
        }

        $amountKg = (float) ($data['amount_kg'] ?? 0);
        if ($amountKg <= 0) {
            $this->jsonError('Množství musí být větší než 0.');
        }

        $note = trim($data['note'] ?? '');

        $id = FeedingRecord::insert([
            'feed_type_id' => $feedTypeId,
            'record_date' => $recordDate,
            'amount_kg' => $amountKg,
            'note' => $note ?: null,
        ]);

        $record = FeedingRecord::findById($id);
        $type = FeedType::findById($feedTypeId);
        $record['feed_type_name'] = $type['name'] ?? '';
        $record['price_per_kg'] = $type['price_per_kg'] ?? 0;

        $this->json([
            'success' => true,
            'record' => $record,
            'totalKg' => FeedingRecord::getTotalKg(30),
            'totalCost' => FeedingRecord::getTotalCost(30),
            'dailyAvg' => FeedingRecord::getDailyAverage(30),
        ]);
    }

    public function updateRecord(): void
    {
        Auth::requireAuthApi();
        $data = $this->getPostData();

        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            $this->jsonError('Neplatné ID.');
        }

        $feedTypeId = (int) ($data['feed_type_id'] ?? 0);
        if ($feedTypeId <= 0) {
            $this->jsonError('Vyberte typ krmiva.');
        }

        $recordDate = trim($data['record_date'] ?? '');
        if ($recordDate === '') {
            $this->jsonError('Datum je povinné.');
        }

        $amountKg = (float) ($data['amount_kg'] ?? 0);
        if ($amountKg <= 0) {
            $this->jsonError('Množství musí být větší než 0.');
        }

        $note = trim($data['note'] ?? '');

        FeedingRecord::update($id, [
            'feed_type_id' => $feedTypeId,
            'record_date' => $recordDate,
            'amount_kg' => $amountKg,
            'note' => $note ?: null,
        ]);

        $record = FeedingRecord::findById($id);
        $type = FeedType::findById($feedTypeId);
        $record['feed_type_name'] = $type['name'] ?? '';
        $record['price_per_kg'] = $type['price_per_kg'] ?? 0;

        $this->json([
            'success' => true,
            'record' => $record,
            'totalKg' => FeedingRecord::getTotalKg(30),
            'totalCost' => FeedingRecord::getTotalCost(30),
            'dailyAvg' => FeedingRecord::getDailyAverage(30),
        ]);
    }

    public function deleteRecord(): void
    {
        Auth::requireAuthApi();
        $data = $this->getPostData();
        $id = (int) ($data['id'] ?? 0);

        if ($id <= 0) {
            $this->jsonError('Neplatné ID.');
        }

        FeedingRecord::delete($id);
        $this->json([
            'success' => true,
            'totalKg' => FeedingRecord::getTotalKg(30),
            'totalCost' => FeedingRecord::getTotalCost(30),
            'dailyAvg' => FeedingRecord::getDailyAverage(30),
        ]);
    }

    // --- Nákupy krmiva ---

    public function purchases(): void
    {
        Auth::requireAuthApi();

        $group = $_GET['group'] ?? '';
        $months = (int) ($_GET['months'] ?? 12);

        if ($group === 'month') {
            $this->json([
                'records' => FeedPurchase::getMonthlySpending($months),
                'grouped' => 'month',
            ]);
        } else {
            $this->json([
                'records' => FeedPurchase::getAll(),
                'totalSpent' => FeedPurchase::getTotalSpent($months),
            ]);
        }
    }

    public function storePurchase(): void
    {
        Auth::requireAuthApi();
        $data = $this->getPostData();

        $feedTypeId = (int) ($data['feed_type_id'] ?? 0);
        if ($feedTypeId <= 0) {
            $this->jsonError('Vyberte typ krmiva.');
        }

        $purchasedAt = trim($data['purchased_at'] ?? '');
        if ($purchasedAt === '') {
            $this->jsonError('Datum je povinné.');
        }

        $quantityKg = (float) ($data['quantity_kg'] ?? 0);
        if ($quantityKg <= 0) {
            $this->jsonError('Množství musí být větší než 0.');
        }

        $totalPrice = (float) ($data['total_price'] ?? 0);
        if ($totalPrice < 0) {
            $this->jsonError('Cena nemůže být záporná.');
        }

        $note = trim($data['note'] ?? '');

        $id = FeedPurchase::insert([
            'feed_type_id' => $feedTypeId,
            'purchased_at' => $purchasedAt,
            'quantity_kg' => $quantityKg,
            'total_price' => $totalPrice,
            'note' => $note ?: null,
        ]);

        $purchase = FeedPurchase::findById($id);
        $type = FeedType::findById($feedTypeId);
        $purchase['feed_type_name'] = $type['name'] ?? '';

        $this->json([
            'success' => true,
            'purchase' => $purchase,
        ]);
    }

    public function deletePurchase(): void
    {
        Auth::requireAuthApi();
        $data = $this->getPostData();
        $id = (int) ($data['id'] ?? 0);

        if ($id <= 0) {
            $this->jsonError('Neplatné ID.');
        }

        FeedPurchase::delete($id);
        $this->json(['success' => true]);
    }

    // --- Statistiky ---

    public function stats(): void
    {
        Auth::requireAuthApi();

        $group = $_GET['group'] ?? '';
        $months = (int) ($_GET['months'] ?? 12);

        if ($group === 'month') {
            $consumption = FeedingRecord::getMonthlyConsumption($months);
            $spending = FeedPurchase::getMonthlySpending($months);
            $this->json([
                'consumption' => $consumption,
                'spending' => $spending,
            ]);
        } else {
            $this->json([
                'totalKg' => FeedingRecord::getTotalKg(30),
                'totalCost' => FeedingRecord::getTotalCost(30),
                'dailyAvg' => FeedingRecord::getDailyAverage(30),
                'totalSpent' => FeedPurchase::getTotalSpent($months),
            ]);
        }
    }
}
