<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCard;
use App\Models\ExpenseItem;
use App\Models\ExpenseSummary;
use App\Models\RiderRemit;
use App\Services\ExpenseRiderFuelSyncService;
use App\Services\ExpenseSummaryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        if (! auth()->user()->can('order-list')) {
            return redirect()->route('home')->withErrors(__('message.demo_permission_denied'));
        }

        $monthParam = trim((string) $request->get('month', ''));
        try {
            $month = $monthParam !== ''
                ? Carbon::createFromFormat('Y-m', $monthParam, 'Asia/Yangon')->startOfMonth()
                : now('Asia/Yangon')->startOfMonth();
        } catch (\Throwable $e) {
            $month = now('Asia/Yangon')->startOfMonth();
        }

        $fromInput = trim((string) $request->get('from_date', ''));
        $toInput = trim((string) $request->get('to_date', ''));
        $subject = trim((string) $request->get('subject', ''));

        $from = $month->copy()->startOfMonth()->toDateString();
        $to = $month->copy()->endOfMonth()->toDateString();

        if ($fromInput !== '') {
            $from = $this->parseExpenseFilterDate($fromInput) ?? $from;
        }
        if ($toInput !== '') {
            $to = $this->parseExpenseFilterDate($toInput) ?? $to;
        }
        if ($to < $from) {
            $to = $from;
        }

        app(ExpenseRiderFuelSyncService::class)->syncDateRange($from, $to, auth()->id());

        $cardsQuery = ExpenseCard::query()
            ->with('items')
            ->whereBetween('expense_date', [$from, $to])
            ->orderBy('expense_date')
            ->orderBy('id');

        if ($subject !== '') {
            $cardsQuery->whereHas('items', function ($q) use ($subject) {
                $q->where('subject', 'like', '%'.$subject.'%');
            });
        }

        $cards = $cardsQuery->get();

        $cards = $cards->map(function (ExpenseCard $card) use ($subject) {
            $allItems = $card->items;
            if ($subject !== '') {
                $filtered = $allItems->filter(function (ExpenseItem $item) use ($subject) {
                    return mb_stripos((string) $item->subject, $subject) !== false;
                })->values();
                $card->setAttribute('display_items', $filtered);
                $card->setAttribute('display_total', round((float) $filtered->sum('amount'), 2));
            } else {
                $card->setAttribute('display_items', $allItems);
                $card->setAttribute('display_total', (float) $card->total_amount);
            }

            return $card;
        });

        if ($subject !== '') {
            $cards = $cards->filter(function (ExpenseCard $card) {
                return collect($card->display_items)->isNotEmpty();
            })->values();
        }

        $monthTotal = round((float) $cards->sum(fn (ExpenseCard $card) => (float) ($card->display_total ?? $card->total_amount)), 2);

        $generatedCardIds = ExpenseSummary::query()
            ->whereIn('expense_card_id', $cards->pluck('id')->filter()->all())
            ->pluck('expense_card_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $pageTitle = __('message.expenses_title');
        $assets = [];
        $canEdit = auth()->user()->can('order-edit');
        $prevMonth = $month->copy()->subMonth()->format('Y-m');
        $nextMonth = $month->copy()->addMonth()->format('Y-m');
        $monthLabel = $month->format('M Y');
        $monthValue = $month->format('Y-m');
        $today = now('Asia/Yangon')->toDateString();
        $filterFrom = Carbon::parse($from)->format('d-m-Y');
        $filterTo = Carbon::parse($to)->format('d-m-Y');
        $filterSubject = $subject;

        return view('order.expenses', compact(
            'pageTitle',
            'assets',
            'cards',
            'monthTotal',
            'canEdit',
            'prevMonth',
            'nextMonth',
            'monthLabel',
            'monthValue',
            'today',
            'filterFrom',
            'filterTo',
            'filterSubject',
            'generatedCardIds'
        ));
    }

    private function parseExpenseFilterDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        foreach (['d-m-Y', 'Y-m-d', 'd/m/Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value, 'Asia/Yangon')->toDateString();
            } catch (\Throwable $e) {
                // try next
            }
        }
        try {
            return Carbon::parse($value, 'Asia/Yangon')->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function riderFuelTotal(Request $request)
    {
        if (! auth()->user()->can('order-list')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        try {
            $day = Carbon::parse((string) $request->get('date', now('Asia/Yangon')->toDateString()))
                ->toDateString();
        } catch (\Throwable $e) {
            $day = now('Asia/Yangon')->toDateString();
        }

        $total = round((float) RiderRemit::query()
            ->whereDate('remit_date', $day)
            ->sum('fuel_amount'), 2);

        return response()->json([
            'date' => $day,
            'subject' => app(ExpenseRiderFuelSyncService::class)->subject(),
            'amount' => $total,
        ]);
    }

    public function store(Request $request)
    {
        if (! auth()->user()->can('order-edit')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        $data = $this->validatedPayload($request);

        try {
            $card = DB::transaction(function () use ($data) {
                $existing = ExpenseCard::query()
                    ->whereDate('expense_date', $data['expense_date'])
                    ->first();
                if ($existing) {
                    throw new \RuntimeException(__('message.expenses_date_exists'));
                }

                $card = ExpenseCard::query()->create([
                    'expense_date' => $data['expense_date'],
                    'total_amount' => 0,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);

                $this->syncItems($card, $data['items']);
                app(ExpenseRiderFuelSyncService::class)->syncDate($data['expense_date'], auth()->id());
                $card->recalculateTotal();

                return $card->fresh('items');
            });
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => __('message.expenses_saved'),
            'card' => $this->cardPayload($card),
        ]);
    }

    public function update(Request $request, int $id)
    {
        if (! auth()->user()->can('order-edit')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        $data = $this->validatedPayload($request);
        $card = ExpenseCard::query()->findOrFail($id);

        try {
            $card = DB::transaction(function () use ($card, $data) {
                $conflict = ExpenseCard::query()
                    ->whereDate('expense_date', $data['expense_date'])
                    ->where('id', '!=', $card->id)
                    ->exists();
                if ($conflict) {
                    throw new \RuntimeException(__('message.expenses_date_exists'));
                }

                $card->fill([
                    'expense_date' => $data['expense_date'],
                    'updated_by' => auth()->id(),
                ])->save();

                // Manual rows from form; Rider ဆီဖိုး is re-synced from remits below.
                ExpenseItem::query()->where('expense_card_id', $card->id)->delete();
                $this->syncItems($card, $data['items']);
                app(ExpenseRiderFuelSyncService::class)->syncDate($data['expense_date'], auth()->id());
                $card->recalculateTotal();

                return $card->fresh('items');
            });
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => __('message.expenses_saved'),
            'card' => $this->cardPayload($card),
        ]);
    }

    public function destroy(int $id)
    {
        if (! auth()->user()->can('order-edit')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        $card = ExpenseCard::query()->findOrFail($id);
        DB::transaction(function () use ($card) {
            ExpenseSummary::query()->where('expense_card_id', $card->id)->delete();
            $card->delete();
        });

        return response()->json([
            'message' => __('message.expenses_deleted'),
        ]);
    }

    public function generate(int $id, ExpenseSummaryService $summaryService)
    {
        if (! auth()->user()->can('order-edit')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        $card = ExpenseCard::query()->with('items')->findOrFail($id);
        $summary = $summaryService->generateFromCard($card, auth()->id());

        return response()->json([
            'message' => __('message.expense_summary_generated'),
            'summary' => [
                'id' => $summary->id,
                'summary_date' => $summary->summary_date?->format('Y-m-d'),
                'income' => (float) $summary->income,
                'expense' => (float) $summary->expense,
                'ako_given' => (float) $summary->ako_given,
            ],
            'summary_url' => route('order.expense-summary', [
                'month' => $summary->summary_date?->format('Y-m'),
            ]),
        ]);
    }

    private function validatedPayload(Request $request): array
    {
        $data = $request->validate([
            'expense_date' => 'required|date',
            'items' => 'nullable|array',
            'items.*.subject' => 'nullable|string|max:255',
            'items.*.amount' => 'nullable|numeric|min:0',
            'items.*.existing_image' => 'nullable|string|max:500',
            'items.*.image' => 'nullable|image|max:5120',
        ]);

        $items = [];
        $fuelSync = app(ExpenseRiderFuelSyncService::class);
        foreach (($data['items'] ?? []) as $index => $row) {
            $subject = trim((string) ($row['subject'] ?? ''));
            $amount = round((float) ($row['amount'] ?? 0), 2);
            if ($subject === '') {
                continue;
            }
            // Rider ဆီဖိုး is controlled by Day-by-Day Rider remit — ignore form edits.
            if ($fuelSync->isRiderFuelSubject($subject)) {
                continue;
            }

            $image = trim((string) ($row['existing_image'] ?? ''));
            $uploaded = $request->file("items.$index.image");
            if ($uploaded instanceof UploadedFile) {
                $image = $this->storeItemImage($uploaded);
            } elseif ($image === '' || $image === ExpenseItem::DEMO_IMAGE) {
                $image = null;
            }

            $items[] = [
                'subject' => $subject,
                'amount' => $amount,
                'image' => $image,
            ];
        }

        // Allow saving a card that only has auto Rider fuel (items may be empty here).
        $data['items'] = $items;
        $data['expense_date'] = Carbon::parse($data['expense_date'])->toDateString();

        return $data;
    }

    private function storeItemImage(UploadedFile $file): string
    {
        $dir = 'expenses/items';
        Storage::disk('public')->makeDirectory($dir);

        return $file->store($dir, 'public');
    }

    private function syncItems(ExpenseCard $card, array $items): void
    {
        foreach ($items as $index => $row) {
            ExpenseItem::query()->create([
                'expense_card_id' => $card->id,
                'subject' => $row['subject'],
                'amount' => $row['amount'],
                'image' => $row['image'] ?? null,
                'sort_order' => $index + 1,
                'source' => null,
            ]);
        }
    }

    private function cardPayload(ExpenseCard $card): array
    {
        return [
            'id' => $card->id,
            'expense_date' => $card->expense_date?->format('Y-m-d'),
            'expense_date_label' => $card->expense_date?->format('d/m/y'),
            'total_amount' => (float) $card->total_amount,
            'items' => $card->items->map(static fn (ExpenseItem $item) => [
                'id' => $item->id,
                'subject' => $item->subject,
                'amount' => (float) $item->amount,
                'image' => $item->image,
                'image_url' => $item->imageUrl(),
                'source' => $item->source,
                'locked' => $item->source === ExpenseItem::SOURCE_RIDER_FUEL,
            ])->values()->all(),
        ];
    }
}
