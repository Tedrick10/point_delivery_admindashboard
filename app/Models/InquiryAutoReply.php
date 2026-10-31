<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class InquiryAutoReply extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'label',
        'reply_message',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function registerMediaCollections(): void
    {
        // Multiple photos sent with the bot reply when user taps the chip.
        $this->addMediaCollection('reply_images');
        // Legacy single collection (kept for existing rows).
        $this->addMediaCollection('reply_image')->singleFile();
    }

    public function replyImageUrls(): array
    {
        $urls = $this->getMedia('reply_images')
            ->map(fn ($media) => $media->getFullUrl())
            ->filter()
            ->values()
            ->all();

        if ($urls === [] && getMediaFileExit($this, 'reply_image')) {
            $legacy = getSingleMedia($this, 'reply_image', null);
            if ($legacy) {
                $urls[] = $legacy;
            }
        }

        return $urls;
    }

    public function replyImageUrl(): ?string
    {
        $urls = $this->replyImageUrls();

        return $urls[0] ?? null;
    }

    public function replyMediaItems()
    {
        $items = $this->getMedia('reply_images');
        if ($items->isEmpty()) {
            $legacy = $this->getFirstMedia('reply_image');
            if ($legacy) {
                return collect([$legacy]);
            }
        }

        return $items;
    }
}
