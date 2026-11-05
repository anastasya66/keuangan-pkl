<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FinancialTransaction\CancelFinancialTransactionRequest;
use App\Http\Requests\FinancialTransaction\StoreFinancialTransactionRequest;
use App\Http\Requests\FinancialTransaction\UpdateFinancialTransactionRequest;
use App\Http\Resources\FinancialTransactionResource;
use App\Models\FinancialTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class FinancialTransactionController extends Controller
{
    /**
     * Display a listing of financial transactions with filters, search, and financial summary.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', FinancialTransaction::class);

        $query = FinancialTransaction::query()->with(['category', 'creator', 'updater', 'canceller']);

        // Scope by route context (if called from /incomes or /expenses)
        if ($request->routeIs('*incomes*') || $request->is('api/v1/incomes*') || $request->is('api/incomes*')) {
            $query->where('transaction_type', 'income');
        } elseif ($request->routeIs('*expenses*') || $request->is('api/v1/expenses*') || $request->is('api/expenses*')) {
            $query->where('transaction_type', 'expense');
        } elseif ($type = $request->input('transaction_type')) {
            $query->where('transaction_type', $type);
        }

        // Filter by category
        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        // Filter by status (completed / cancelled)
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Filter by recording user (created_by)
        if ($createdBy = $request->input('created_by')) {
            $query->where('created_by', $createdBy);
        }

        // Filter by date range
        if ($startDate = $request->input('start_date')) {
            $query->whereDate('transaction_date', '>=', $startDate);
        }
        if ($endDate = $request->input('end_date')) {
            $query->whereDate('transaction_date', '<=', $endDate);
        }

        // Search by code or description
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('transaction_code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Calculate summary for active (completed) transactions based on current filter conditions
        $summaryQuery = clone $query;
        $totalIncome = (float) (clone $summaryQuery)->where('transaction_type', 'income')->where('status', 'completed')->sum('amount');
        $totalExpense = (float) (clone $summaryQuery)->where('transaction_type', 'expense')->where('status', 'completed')->sum('amount');
        $netBalance = $totalIncome - $totalExpense;

        $perPage = (int) $request->input('per_page', 10);
        $transactions = $query->orderByDesc('transaction_date')->orderByDesc('id')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar transaksi keuangan berhasil diambil',
            'data' => FinancialTransactionResource::collection($transactions),
            'summary' => [
                'total_income' => number_format($totalIncome, 2, '.', ''),
                'total_expense' => number_format($totalExpense, 2, '.', ''),
                'net_balance' => number_format($netBalance, 2, '.', ''),
                'total_income_numeric' => $totalIncome,
                'total_expense_numeric' => $totalExpense,
                'net_balance_numeric' => $netBalance,
            ],
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created financial transaction using DB transaction.
     */
    public function store(StoreFinancialTransactionRequest $request): JsonResponse
    {
        $this->authorize('create', FinancialTransaction::class);

        $transaction = DB::transaction(function () use ($request) {
            $validated = $request->validated();

            // Auto-generate unique transaction code if not provided
            $transactionCode = $validated['transaction_code']
                ?? FinancialTransaction::generateTransactionCode($validated['transaction_date'] ?? null);

            return FinancialTransaction::create([
                'transaction_code' => $transactionCode,
                'transaction_type' => $validated['transaction_type'],
                'category_id' => $validated['category_id'],
                'amount' => $validated['amount'],
                'transaction_date' => $validated['transaction_date'],
                'description' => $validated['description'] ?? null,
                'created_by' => $request->user()->id,
                'status' => $validated['status'] ?? 'completed',
            ]);
        });

        $transaction->load(['category', 'creator']);

        $typeName = $transaction->transaction_type === 'income' ? 'Pemasukan' : 'Pengeluaran';

        return response()->json([
            'success' => true,
            'message' => "Transaksi {$typeName} berhasil ditambahkan",
            'data' => new FinancialTransactionResource($transaction),
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified financial transaction.
     */
    public function show(FinancialTransaction $transaction): JsonResponse
    {
        $this->authorize('view', $transaction);

        $transaction->load(['category', 'creator', 'updater', 'canceller']);

        return response()->json([
            'success' => true,
            'message' => 'Detail transaksi berhasil diambil',
            'data' => new FinancialTransactionResource($transaction),
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified financial transaction.
     */
    public function update(UpdateFinancialTransactionRequest $request, FinancialTransaction $transaction): JsonResponse
    {
        $this->authorize('update', $transaction);

        if ($transaction->isCancelled()) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi yang sudah dibatalkan tidak dapat diubah.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::transaction(function () use ($request, $transaction) {
            $validated = $request->validated();
            $validated['updated_by'] = $request->user()->id;

            $transaction->update($validated);
        });

        $transaction->load(['category', 'creator', 'updater', 'canceller']);

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil diperbarui',
            'data' => new FinancialTransactionResource($transaction),
        ], Response::HTTP_OK);
    }

    /**
     * Cancel the financial transaction.
     */
    public function cancel(CancelFinancialTransactionRequest $request, FinancialTransaction $transaction): JsonResponse
    {
        $this->authorize('cancel', $transaction);

        if ($transaction->isCancelled()) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi ini sudah dibatalkan sebelumnya.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::transaction(function () use ($request, $transaction) {
            $transaction->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => $request->user()->id,
                'cancellation_reason' => $request->input('cancellation_reason'),
            ]);
        });

        $transaction->load(['category', 'creator', 'canceller']);

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil dibatalkan',
            'data' => new FinancialTransactionResource($transaction),
        ], Response::HTTP_OK);
    }

    /**
     * Remove the financial transaction from storage using Soft Delete.
     */
    public function destroy(FinancialTransaction $transaction): JsonResponse
    {
        $this->authorize('delete', $transaction);

        $transaction->delete();

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil dihapus (soft delete).',
        ], Response::HTTP_OK);
    }
}
