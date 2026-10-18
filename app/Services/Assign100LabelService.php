<?php

namespace App\Services;

use App\Models\DispatchOrderItem;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class Assign100LabelService
{
    public const QR_PREFIX = 'PDS1';

    /** @var array{company: string, hotline: string, logo_url: string}|null */
    protected ?array $sharedMeta = null;

    public function payload(DispatchOrderItem $item): string
    {
        return self::QR_PREFIX.':'.(int) $item->id.':'.trim((string) ($item->code ?? ''));
    }

    /**
     * @return array{item_id: ?int, code: ?string}|null
     */
    public static function parse(string $raw): ?array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        if (preg_match('/PDS1[:\/|](\d+)[:\/|]([A-Za-z0-9\-]+)/i', $raw, $m)) {
            return [
                'item_id' => (int) $m[1],
                'code' => trim($m[2]),
            ];
        }

        if (preg_match('#(?:assign100|assign-100)[/:](\d+)(?:[/:]([A-Za-z0-9\-]+))?#i', $raw, $m)) {
            return [
                'item_id' => (int) $m[1],
                'code' => isset($m[2]) && $m[2] !== '' ? trim($m[2]) : null,
            ];
        }

        if (preg_match('/^[A-Za-z0-9\-]{6,24}$/', $raw)) {
            return [
                'item_id' => null,
                'code' => $raw,
            ];
        }

        return null;
    }

    public function qrSvg(string $payload, int $size = 132): string
    {
        return (string) QrCode::format('svg')->size($size)->margin(0)->errorCorrection('M')->generate($payload);
    }

    /**
     * @param  iterable<int, DispatchOrderItem>  $items
     * @return Collection<int, object>
     */
    public function buildMany(iterable $items): Collection
    {
        $this->sharedMeta = $this->sharedLabelMeta();

        return collect($items)->map(fn (DispatchOrderItem $item) => $this->build($item))->values();
    }

    /**
     * @return object{
     *     item: DispatchOrderItem,
     *     payload: string,
     *     qr_svg: string,
     *     printed_at: string,
     *     company: string,
     *     hotline: string,
     *     create_date: string,
     *     code: string,
     *     sender: string,
     *     sender_phone: string,
     *     sender_address: string,
     *     receiver: string,
     *     receiver_phone: string,
     *     township: string,
     *     receiver_address: string,
     *     item_value: string,
     *     deli_amount: string,
     *     customer_to_get: string
     * }
     */
    public function build(DispatchOrderItem $item): object
    {
        $meta = $this->sharedMeta ?? $this->sharedLabelMeta();
        $order = $item->relationLoaded('order') ? $item->order : $item->order()->with(['client.city'])->first();
        $payload = $this->payload($item);
        $osName = resolveDispatchOsName($order);
        $city = trim((string) (optional(optional(optional($order)->client)->city)->name ?? ''));
        $senderDisplay = $osName;
        if ($osName !== '-' && $city !== '') {
            $senderDisplay .= ' ('.$city.')';
        }

        $township = trim((string) ($item->township ?? ''));
        if ($township === '') {
            $township = DispatchOrderItem::deliveryCityLabel($item->delivery_city);
        }

        $itemValue = (float) ($item->item_value ?? 0);
        $deli = (float) ($item->deli_amount ?? 0);
        $custGet = (float) ($item->cust_get ?? 0);
        if ($custGet <= 0 && ($itemValue > 0 || $deli > 0)) {
            $amounts = DispatchOrderItem::computeAmounts(
                $itemValue,
                $deli,
                (float) ($item->advance_paid ?? 0),
                (float) ($item->os_paid ?? 0),
                (string) ($item->credit_to ?? 'customer')
            );
            $custGet = (float) ($amounts['cust_get'] ?? 0);
        }

        $create = $item->received_date
            ?: ($item->created_at ?: optional($order)->created_at);
        $createDate = $create
            ? Carbon::parse((string) $create, 'Asia/Yangon')->timezone('Asia/Yangon')->format('d-m-Y')
            : now('Asia/Yangon')->format('d-m-Y');

        return (object) [
            'item' => $item,
            'payload' => $payload,
            'qr_svg' => $this->qrSvg($payload, 148),
            'printed_at' => now('Asia/Yangon')->format('d M Y · g:i A'),
            'company' => $meta['company'],
            'hotline' => $meta['hotline'],
            'logo_url' => $meta['logo_url'],
            'create_date' => $createDate,
            'code' => trim((string) ($item->code ?? '')) ?: (string) $item->id,
            'sender' => $senderDisplay !== '' ? $senderDisplay : '-',
            'sender_name' => $osName !== '' ? $osName : '-',
            'sender_city' => $city,
            'sender_phone' => resolveDispatchOsPhone($order instanceof Order ? $order : null),
            'sender_address' => resolveDispatchOsAddress($order instanceof Order ? $order : null),
            'receiver' => trim((string) ($item->customer_name ?? '')) ?: '-',
            'receiver_phone' => trim((string) ($item->customer_phone ?? '')) ?: '-',
            'township' => $township !== '' && $township !== '-' ? $township : '-',
            'receiver_address' => trim((string) ($item->customer_address ?? '')) ?: '-',
            'item_value' => number_format($itemValue),
            'deli_amount' => formatDispatchDeliAmount($item, $deli),
            'customer_to_get' => number_format($custGet),
        ];
    }

    /**
     * @return array{company: string, hotline: string, logo_url: string}
     */
    protected function sharedLabelMeta(): array
    {
        $info = slipCompanyInfo();
        $company = trim((string) ($info['name'] ?? ''));
        if ($company === '') {
            $company = 'Point Delivery';
        }

        return [
            'company' => $company,
            'hotline' => (string) ($info['hotline'] ?? companyHotlinePhone()),
            'logo_url' => $this->labelLogoUrl(),
        ];
    }

    /**
     * Prefer a tiny cached PNG URL (browser-cacheable) over a base64 data URI.
     */
    protected function labelLogoUrl(): string
    {
        $cached = $this->ensureCachedLogoFile();
        if ($cached !== null) {
            // Relative URL so local / production hosts both hit the same origin as the print iframe.
            return '/images/'.$cached.'?v='.filemtime(public_path('images/'.$cached));
        }

        return $this->labelLogoDataUri();
    }

    protected function ensureCachedLogoFile(): ?string
    {
        $source = public_path('images/point-logo-circle.png');
        $cachedName = 'airway-bill-logo.png';
        $cachedPath = public_path('images/'.$cachedName);

        if (! is_file($source)) {
            return is_file($cachedPath) ? $cachedName : null;
        }

        $needsRebuild = ! is_file($cachedPath) || filemtime($cachedPath) < filemtime($source);
        if (! $needsRebuild) {
            return $cachedName;
        }

        if (! function_exists('imagecreatefrompng')) {
            return null;
        }

        $src = @imagecreatefrompng($source);
        if ($src === false) {
            return null;
        }

        $tw = 128;
        $th = 128;
        $dst = imagecreatetruecolor($tw, $th);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefilledrectangle($dst, 0, 0, $tw, $th, $transparent);
        imagealphablending($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $tw, $th, imagesx($src), imagesy($src));
        imagesavealpha($dst, true);
        $ok = @imagepng($dst, $cachedPath, 6);
        imagedestroy($src);
        imagedestroy($dst);

        return ($ok && is_file($cachedPath)) ? $cachedName : null;
    }

    protected function labelLogoDataUri(): string
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        try {
            $file = $this->ensureCachedLogoFile();
            $path = $file ? public_path('images/'.$file) : public_path('images/point-logo-circle.png');
            if (is_file($path)) {
                $bytes = file_get_contents($path);
                if ($bytes !== false && $bytes !== '') {
                    return $cached = 'data:image/png;base64,'.base64_encode($bytes);
                }
            }

            $invoice = \App\Models\Setting::query()
                ->where('type', 'order_invoice')
                ->where('key', 'company_logo')
                ->first();
            $remote = (string) (($invoice ? getSingleMedia($invoice, 'company_logo') : null)
                ?: getSingleMedia(appSettingData('get'), 'site_logo', null)
                ?: '');

            return $cached = $remote;
        } catch (\Throwable $e) {
            return $cached = '';
        }
    }
}
