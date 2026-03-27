<?php

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Models\BeddingChange;
use App\Models\Repair;
use App\Models\Setting;

class MaintenanceController extends Controller
{
    // --- Výměna podestýlky ---

    public function beddingIndex(): void
    {
        Auth::requireAuthApi();
        $this->json(['records' => BeddingChange::getAll()]);
    }

    public function beddingStore(): void
    {
        Auth::requireAuthApi();
        $data = $this->getPostData();

        $changedAt = trim($data['changed_at'] ?? '');
        $note = trim($data['note'] ?? '');

        if ($changedAt === '') {
            $this->jsonError('Datum je povinné.');
        }

        $id = BeddingChange::insert([
            'changed_at' => $changedAt,
            'note' => $note ?: null,
        ]);

        $this->json([
            'success' => true,
            'record' => BeddingChange::findById($id),
        ]);
    }

    public function beddingUpdate(): void
    {
        Auth::requireAuthApi();
        $data = $this->getPostData();

        $id = (int) ($data['id'] ?? 0);
        $changedAt = trim($data['changed_at'] ?? '');
        $note = trim($data['note'] ?? '');

        if ($id <= 0) {
            $this->jsonError('Neplatné ID.');
        }
        if ($changedAt === '') {
            $this->jsonError('Datum je povinné.');
        }

        BeddingChange::update($id, [
            'changed_at' => $changedAt,
            'note' => $note ?: null,
        ]);

        $this->json([
            'success' => true,
            'record' => BeddingChange::findById($id),
        ]);
    }

    public function beddingDestroy(): void
    {
        Auth::requireAuthApi();
        $data = $this->getPostData();
        $id = (int) ($data['id'] ?? 0);

        if ($id <= 0) {
            $this->jsonError('Neplatné ID.');
        }

        BeddingChange::delete($id);
        $this->json(['success' => true]);
    }

    // --- Interval podestýlky ---

    public function beddingInterval(): void
    {
        Auth::requireAuthApi();
        $data = $this->getPostData();

        $days = (int) ($data['interval_days'] ?? 0);
        if ($days < 1) {
            $this->jsonError('Interval musí být alespoň 1 den.');
        }

        Setting::set('bedding_interval_days', (string) $days);

        $latest = BeddingChange::getLatest();
        $lastDate = $latest ? $latest['changed_at'] : null;
        $nextDate = $lastDate ? date('Y-m-d', strtotime($lastDate . " +{$days} days")) : null;

        $this->json([
            'success' => true,
            'interval_days' => $days,
            'last_change' => $lastDate,
            'next_change' => $nextDate,
        ]);
    }

    public function beddingQuickLog(): void
    {
        Auth::requireAuthApi();
        $data = $this->getPostData();
        $note = trim($data['note'] ?? '');

        $id = BeddingChange::insert([
            'changed_at' => date('Y-m-d H:i:s'),
            'note' => $note ?: null,
        ]);

        $intervalDays = (int) (Setting::get('bedding_interval_days') ?? 14);
        $record = BeddingChange::findById($id);
        $nextDate = date('Y-m-d', strtotime($record['changed_at'] . " +{$intervalDays} days"));

        $this->json([
            'success' => true,
            'record' => $record,
            'interval_days' => $intervalDays,
            'next_change' => $nextDate,
        ]);
    }

    // --- Opravy ---

    public function repairIndex(): void
    {
        Auth::requireAuthApi();
        $this->json(['records' => Repair::getAll()]);
    }

    public function repairStore(): void
    {
        Auth::requireAuthApi();
        $data = $this->getPostData();

        $repairedAt = trim($data['repaired_at'] ?? '');
        $note = trim($data['note'] ?? '');

        if ($repairedAt === '') {
            $this->jsonError('Datum je povinné.');
        }

        $id = Repair::insert([
            'repaired_at' => $repairedAt,
            'note' => $note ?: null,
        ]);

        $this->json([
            'success' => true,
            'record' => Repair::findById($id),
        ]);
    }

    public function repairUpdate(): void
    {
        Auth::requireAuthApi();
        $data = $this->getPostData();

        $id = (int) ($data['id'] ?? 0);
        $repairedAt = trim($data['repaired_at'] ?? '');
        $note = trim($data['note'] ?? '');

        if ($id <= 0) {
            $this->jsonError('Neplatné ID.');
        }
        if ($repairedAt === '') {
            $this->jsonError('Datum je povinné.');
        }

        Repair::update($id, [
            'repaired_at' => $repairedAt,
            'note' => $note ?: null,
        ]);

        $this->json([
            'success' => true,
            'record' => Repair::findById($id),
        ]);
    }

    public function repairDestroy(): void
    {
        Auth::requireAuthApi();
        $data = $this->getPostData();
        $id = (int) ($data['id'] ?? 0);

        if ($id <= 0) {
            $this->jsonError('Neplatné ID.');
        }

        Repair::delete($id);
        $this->json(['success' => true]);
    }
}
