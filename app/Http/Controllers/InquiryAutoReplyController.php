<?php

namespace App\Http\Controllers;

use App\Models\InquiryAutoReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InquiryAutoReplyController extends Controller
{
    public function index()
    {
        $pageTitle = __('message.inquiry_auto_replies');
        $assets = [];
        $items = InquiryAutoReply::orderBy('sort_order')->orderBy('id')->get();

        return view('inquiry-auto-replies.index', compact('pageTitle', 'assets', 'items'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.label' => 'required|string|max:255',
            'items.*.reply_message' => 'nullable|string|max:5000',
            'items.*.sort_order' => 'nullable|integer|min:0',
            'items.*.is_active' => 'nullable',
            'items.*.reply_images' => 'nullable|array',
            'items.*.reply_images.*' => 'nullable|image|max:10240',
        ]);

        $rows = $request->input('items', []);
        $files = $request->file('items', []);

        DB::transaction(function () use ($rows, $files) {
            foreach ($rows as $index => $row) {
                $label = trim((string) ($row['label'] ?? ''));
                $message = trim((string) ($row['reply_message'] ?? ''));
                $images = $files[$index]['reply_images'] ?? null;
                if ($images && !is_array($images)) {
                    $images = [$images];
                }
                $images = array_values(array_filter($images ?? []));

                if ($label === '') {
                    continue;
                }
                if ($message === '' && $images === []) {
                    continue;
                }

                $item = InquiryAutoReply::create([
                    'label' => $label,
                    'reply_message' => $message !== '' ? $message : ' ',
                    'sort_order' => (int) ($row['sort_order'] ?? 0),
                    'is_active' => !empty($row['is_active']),
                ]);

                foreach ($images as $image) {
                    $item->addMedia($image)->toMediaCollection('reply_images');
                }
            }
        });

        return redirect()->route('inquiry-auto-replies.index')
            ->withSuccess(__('message.save_form', ['form' => __('message.inquiry_auto_replies')]));
    }

    public function update(Request $request, $id)
    {
        $item = InquiryAutoReply::findOrFail($id);
        $data = $request->validate([
            'label' => 'required|string|max:255',
            'reply_message' => 'nullable|string|max:5000',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'reply_images' => 'nullable|array',
            'reply_images.*' => 'nullable|image|max:10240',
            'remove_media_ids' => 'nullable|array',
            'remove_media_ids.*' => 'nullable|integer',
        ]);

        $message = trim((string) ($data['reply_message'] ?? ''));
        $newImages = $request->file('reply_images', []);
        if ($newImages && !is_array($newImages)) {
            $newImages = [$newImages];
        }
        $newImages = array_values(array_filter($newImages ?? []));

        $removeIds = array_map('intval', $request->input('remove_media_ids', []));

        $remainingCount = $item->replyMediaItems()
            ->reject(fn ($m) => in_array((int) $m->id, $removeIds, true))
            ->count();

        if ($message === '' && $newImages === [] && $remainingCount === 0) {
            return redirect()->back()
                ->withErrors(['reply_message' => 'Reply message or at least one image is required.'])
                ->withInput();
        }

        // Hidden 0 + checkbox 1 pattern: treat only explicit "1" as active.
        $isActive = in_array((string) $request->input('is_active', '0'), ['1', 'true', 'on'], true);

        $item->update([
            'label' => $data['label'],
            'reply_message' => $message !== '' ? $message : ' ',
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $isActive,
        ]);

        foreach ($removeIds as $mediaId) {
            $media = $item->media()->where('id', $mediaId)->first();
            if ($media) {
                $media->delete();
            }
        }

        foreach ($newImages as $image) {
            $item->addMedia($image)->toMediaCollection('reply_images');
        }

        return redirect()->route('inquiry-auto-replies.index')
            ->withSuccess(__('message.update_form', ['form' => __('message.inquiry_auto_replies')]));
    }

    public function destroy($id)
    {
        $item = InquiryAutoReply::findOrFail($id);
        $item->clearMediaCollection('reply_images');
        $item->clearMediaCollection('reply_image');
        $item->delete();

        return redirect()->route('inquiry-auto-replies.index')
            ->withSuccess(__('message.delete_form', ['form' => __('message.inquiry_auto_replies')]));
    }

    public function reorder(Request $request)
    {
        $data = $request->validate([
            'order' => 'required|array|min:1',
            'order.*' => 'integer|distinct|exists:inquiry_auto_replies,id',
        ]);

        DB::transaction(function () use ($data) {
            foreach (array_values($data['order']) as $index => $id) {
                InquiryAutoReply::whereKey((int) $id)->update(['sort_order' => $index]);
            }
        });

        return response()->json([
            'ok' => true,
            'message' => __('message.update_form', ['form' => __('message.inquiry_auto_replies')]),
        ]);
    }
}
