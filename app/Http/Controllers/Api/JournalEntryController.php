<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\ReverseJournalEntryRequest;
use App\Http\Requests\Accounting\StoreJournalEntryRequest;
use App\Http\Requests\Accounting\UpdateJournalEntryRequest;
use App\Http\Resources\JournalEntryResource;
use App\Models\JournalEntry;
use App\Services\JournalPostingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JournalEntryController extends Controller
{
    public function __construct(
        protected JournalPostingService $journalPostingService
    ) {}

    /**
     * Tampilkan daftar jurnal umum.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', JournalEntry::class);

        $query = JournalEntry::with(['creator', 'poster', 'details.account']);

        // Filter status
        if ($request->filled('status') && $request->query('status') !== 'all') {
            $query->where('status', $request->query('status'));
        }

        // Filter rentang tanggal
        if ($request->filled('start_date')) {
            $query->where('journal_date', '>=', $request->query('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->where('journal_date', '<=', $request->query('end_date'));
        }

        // Filter tipe sumber
        if ($request->filled('source_type')) {
            $query->where('source_type', $request->query('source_type'));
        }

        // Pencarian nomor jurnal atau referensi
        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('journal_number', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $query->orderByDesc('journal_date')->orderByDesc('id');

        $perPage = (int) $request->query('per_page', 15);
        $journals = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar jurnal berhasil diambil.',
            'data' => JournalEntryResource::collection($journals),
            'meta' => [
                'current_page' => $journals->currentPage(),
                'last_page' => $journals->lastPage(),
                'per_page' => $journals->perPage(),
                'total' => $journals->total(),
            ],
        ]);
    }

    /**
     * Buat jurnal umum baru (status draft).
     */
    public function store(StoreJournalEntryRequest $request): JsonResponse
    {
        $journal = $this->journalPostingService->createDraft(
            $request->validated(),
            $request->user()->id
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Jurnal umum berhasil dibuat dengan status draft.',
            'data' => new JournalEntryResource($journal),
        ], 201);
    }

    /**
     * Tampilkan detail jurnal umum.
     */
    public function show(JournalEntry $journal): JsonResponse
    {
        $this->authorize('view', $journal);

        $journal->loadMissing(['details.account', 'creator', 'poster', 'reverser', 'reversalJournal']);

        return response()->json([
            'status' => 'success',
            'message' => 'Detail jurnal berhasil diambil.',
            'data' => new JournalEntryResource($journal),
        ]);
    }

    /**
     * Perbarui jurnal umum berstatus draft.
     */
    public function update(UpdateJournalEntryRequest $request, JournalEntry $journal): JsonResponse
    {
        $updated = $this->journalPostingService->updateDraft(
            $journal,
            $request->validated(),
            $request->user()->id
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Jurnal umum draft berhasil diperbarui.',
            'data' => new JournalEntryResource($updated),
        ]);
    }

    /**
     * Hapus jurnal umum draft.
     */
    public function destroy(JournalEntry $journal): JsonResponse
    {
        $this->authorize('delete', $journal);

        $this->journalPostingService->deleteDraft($journal);

        return response()->json([
            'status' => 'success',
            'message' => 'Jurnal draft berhasil dihapus.',
        ]);
    }

    /**
     * Posting jurnal draft ke buku besar (Admin only).
     */
    public function post(Request $request, JournalEntry $journal): JsonResponse
    {
        $this->authorize('post', $journal);

        $posted = $this->journalPostingService->postJournal($journal, $request->user()->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Jurnal berhasil diposting ke buku besar.',
            'data' => new JournalEntryResource($posted),
        ]);
    }

    /**
     * Reversal jurnal yang sudah diposting (Admin only).
     */
    public function reverse(ReverseJournalEntryRequest $request, JournalEntry $journal): JsonResponse
    {
        $reversalJournal = $this->journalPostingService->reverseJournal(
            $journal,
            $request->user()->id,
            $request->validated('reason')
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Jurnal berhasil dibatalkan melalui reversal.',
            'data' => [
                'original_journal' => new JournalEntryResource($journal->fresh(['details.account', 'reverser'])),
                'reversal_journal' => new JournalEntryResource($reversalJournal),
            ],
        ]);
    }
}
