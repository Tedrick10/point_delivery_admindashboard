<?php

namespace App\Http\Controllers;

use App\Models\CustomerSupport;
use App\Models\SupportChathistory;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ChannelChatInboxController extends Controller
{
    public function inquiry(Request $request)
    {
        return $this->inbox($request, 'Inquiry', __('message.inquiry_chat'));
    }

    public function complaint(Request $request)
    {
        if (! isSuperAdmin(auth()->user())) {
            return redirect()->back()->withErrors(__('message.demo_permission_denied'));
        }

        return $this->inbox($request, 'Complaint', __('message.complaint_chat'));
    }

    public function inquiryLive(Request $request)
    {
        return $this->liveJson('Inquiry');
    }

    public function complaintLive(Request $request)
    {
        if (! isSuperAdmin(auth()->user())) {
            return response()->json(['ok' => false, 'message' => __('message.demo_permission_denied')], 403);
        }

        return $this->liveJson('Complaint');
    }

    protected function inbox(Request $request, string $supportType, string $pageTitle)
    {
        $payload = $this->buildInboxPayload($supportType);

        return view('channel-chat.inbox', [
            'pageTitle' => $pageTitle,
            'assets' => [],
            'supports' => $payload['supports'],
            'tabCounts' => $payload['tab_counts'],
            'channel' => $supportType,
            'inboxVersion' => $payload['version'],
        ]);
    }

    protected function liveJson(string $supportType)
    {
        $payload = $this->buildInboxPayload($supportType);
        $html = view('channel-chat.partials.inbox-threads', [
            'supports' => $payload['supports'],
            'channel' => $supportType,
        ])->render();

        $badgeCount = $supportType === 'Complaint'
            ? (function_exists('unansweredComplaintChatCount') ? unansweredComplaintChatCount() : $payload['tab_counts']['unanswered'])
            : (function_exists('unansweredInquiryChatCount') ? unansweredInquiryChatCount() : $payload['tab_counts']['unanswered']);

        return response()->json([
            'ok' => true,
            'version' => $payload['version'],
            'tab_counts' => $payload['tab_counts'],
            'badge_count' => (int) $badgeCount,
            'html' => $html,
        ]);
    }

    /**
     * @return array{supports: Collection, tab_counts: array{unread:int,unanswered:int,answered:int}, version: string}
     */
    protected function buildInboxPayload(string $supportType): array
    {
        $supports = CustomerSupport::query()
            ->where('support_type', $supportType)
            ->with(['user'])
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();

        $ids = $supports->pluck('id')->all();
        $lastIds = SupportChathistory::query()
            ->selectRaw('MAX(id) as id')
            ->whereIn('support_id', $ids)
            ->groupBy('support_id')
            ->pluck('id');

        $lastMessages = SupportChathistory::query()
            ->with('user:id,name,user_type')
            ->whereIn('id', $lastIds)
            ->get()
            ->keyBy('support_id');

        $maxChatId = 0;
        foreach ($supports as $row) {
            $last = $lastMessages->get($row->id);
            $senderType = (string) optional($last?->user)->user_type;
            $isClientSide = in_array($senderType, ['client', 'delivery_man'], true);
            $preview = trim((string) ($last?->message ?? ''));
            if ($preview === '' && $last && getMediaFileExit($last, 'chat_image')) {
                $preview = '[Image]';
            }
            if ($preview === '' && $last && getMediaFileExit($last, 'chat_media')) {
                $preview = '[Media]';
            }

            $row->last_chat_message = $preview !== '' ? $preview : '—';
            $row->last_chat_at = $last?->datetime ?? $last?->created_at;
            $row->last_chat_sender_type = $senderType;
            $row->last_chat_sender_label = $isClientSide
                ? (trim((string) optional($row->user)->name) ?: 'User')
                : (trim((string) optional($last?->user)->name) ?: 'Admin');
            $row->needs_reply = $isClientSide;

            $lastAt = optional($row->last_chat_at);
            $readAt = optional($row->staff_read_at);
            $row->is_unread = $isClientSide && (
                $row->staff_read_at === null
                || ($lastAt && $readAt && $lastAt->gt($readAt))
                || ($lastAt && ! $row->staff_read_at)
            );

            $maxChatId = max($maxChatId, (int) ($last?->id ?? 0));
        }

        $supports = $supports
            ->sort(function ($a, $b) {
                $aNeed = ! empty($a->needs_reply) ? 1 : 0;
                $bNeed = ! empty($b->needs_reply) ? 1 : 0;
                if ($aNeed !== $bNeed) {
                    return $bNeed <=> $aNeed;
                }
                $aTime = optional($a->last_chat_at)->timestamp ?? 0;
                $bTime = optional($b->last_chat_at)->timestamp ?? 0;

                return $bTime <=> $aTime;
            })
            ->values();

        $tabCounts = [
            'unread' => $supports->filter(fn ($i) => ! empty($i->is_unread))->count(),
            'unanswered' => $supports->filter(fn ($i) => ! empty($i->needs_reply))->count(),
            'answered' => $supports->filter(fn ($i) => empty($i->needs_reply))->count(),
        ];

        $version = implode(':', [
            $supportType,
            $supports->count(),
            $maxChatId,
            $tabCounts['unread'],
            $tabCounts['unanswered'],
            $tabCounts['answered'],
            optional($supports->max('updated_at'))->timestamp ?? 0,
        ]);

        return [
            'supports' => $supports,
            'tab_counts' => $tabCounts,
            'version' => $version,
        ];
    }
}
