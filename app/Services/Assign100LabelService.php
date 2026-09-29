<?php

namespace App\Services;

use App\Models\DispatchOrderItem;
use App\Models\Order;
use Carbon\Carbon;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class Assign100LabelService
{
    public const QR_PREFIX = 'PDS1';

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
        return (string) QrCode::size($size)->margin(0)->errorCorrection('M')->generate($payload);
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
     *     remark: string
     * }
     */
    public function build(DispatchOrderItem $item): object
    {
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
        $remark = trim((string) ($item->remark ?? ''));
        if ($remark === '' && ($itemValue + $deli) > 0) {
            $remark = 'COD '.number_format($itemValue + $deli);
        }

        $create = $item->received_date
            ?: ($item->created_at ?: optional($order)->created_at);
        $createDate = $create
            ? Carbon::parse((string) $create, 'Asia/Yangon')->timezone('Asia/Yangon')->format('d-m-Y')
            : now('Asia/Yangon')->format('d-m-Y');

        $company = trim((string) (SettingData('order_invoice', 'company_name') ?: config('app.name') ?: 'Point Delivery'));
        $hotline = trim((string) (SettingData('order_invoice', 'company_contact_number') ?: ''));
        $logoUrl = $this->labelLogoDataUri();

        return (object) [
            'item' => $item,
            'payload' => $payload,
            'qr_svg' => $this->qrSvg($payload, 148),
            'printed_at' => now('Asia/Yangon')->format('d M Y · g:i A'),
            'company' => $company !== '' ? $company : 'Point Delivery',
            'hotline' => $hotline,
            'logo_url' => $logoUrl,
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
            'deli_amount' => number_format($deli),
            'remark' => $remark !== '' ? $remark : '-',
        ];
    }

    protected function labelLogoDataUri(): string
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        try {
            $cleanLogo = public_path('images/point-logo-circle.png');
            if (is_file($cleanLogo) && function_exists('imagecreatefrompng')) {
                $src = @imagecreatefrompng($cleanLogo);
                if ($src !== false) {
                    $tw = 128;
                    $th = 128;
                    $dst = imagecreatetruecolor($tw, $th);
                    imagealphablending($dst, false);
                    imagesavealpha($dst, true);
                    $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
                    imagefilledrectangle($dst, 0, 0, $tw, $th, $transparent);
                    imagealphablending($dst, true);
                    imagecopyresampled(
                        $dst,
                        $src,
                        0,
                        0,
                        0,
                        0,
                        $tw,
                        $th,
                        imagesx($src),
                        imagesy($src)
                    );
                    ob_start();
                    imagesavealpha($dst, true);
                    imagepng($dst, null, 6);
                    $bytes = ob_get_clean();
                    imagedestroy($src);
                    imagedestroy($dst);
                    if (is_string($bytes) && $bytes !== '') {
                        return $cached = 'data:image/png;base64,'.base64_encode($bytes);
                    }
                }
            }

            if (is_file($cleanLogo)) {
                $bytes = file_get_contents($cleanLogo);
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
