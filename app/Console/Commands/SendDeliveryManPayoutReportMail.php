<?php

namespace App\Console\Commands;

use App\Models\DeliveryManPayoutReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\UploadedFile;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Mail\SelfBillingStatementMail;
use Illuminate\Support\Facades\Log;
use App\Models\OrderMail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


class SendDeliveryManPayoutReportMail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-driver-payout-report-mail';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send email for unsent delivery man payout reports';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Log::info('=== Driver Payout Mail Cron STARTED ===');

        $reports = DeliveryManPayoutReport::where('is_mail_sent', 0)->get();

        Log::info('Total reports found', [
            'count' => $reports->count()
        ]);

        foreach ($reports as $report) {

            Log::info('Processing payout report', [
                'report_id' => $report->id,
                'delivery_man_id' => $report->delivery_man_id
            ]);

            DB::beginTransaction();

            try {

                $weekStart = Carbon::parse($report->week_start_date);
                $weekEnd   = Carbon::parse($report->week_end_date);
                $paymentDate = $weekEnd->copy()->next(Carbon::WEDNESDAY);
                $collectedTips = ($report->total_fare ?? 0) + ($report->driver_tips ?? 0);
                $html = SettingData('billing_statement');

                $html = str_replace(
                    [
                        '{{AUTO-GENERATED}}',

                        '{{Driver Full Name}}',
                        '{{Driver Address}}',

                        '{{DD/MM/YYYY}}',
                        '{{DD/MM/YYYY}}',
                        '{{DD/MM/YYYY}}',

                        '{{GrossFares}}',
                        '{{Extras}}',
                        '{{GrossTotal}}',
                        '{{Commission}}',
                        '{{TotalDeductions}}',
                        '{{NetPay}}',
                    ],
                    [
                        'SB-' . str_pad($report->id, 6, '0', STR_PAD_LEFT),

                        $report->driver->name ?? '-',
                        $report->driver->address ?? '-',
                        // $report->userDetail->driver_licence_number ?? '-',
                        $weekStart->format('M j, Y'),
                        $weekEnd->format('M j, Y'),
                        $paymentDate->format('M j, Y'),
                        number_format($report->total_fare, 2),
                        number_format($report->driver_tips, 2),
                        number_format($collectedTips, 2),
                        number_format($report->total_commission, 2),
                        number_format($report->total_commission, 2),
                        number_format($report->payout_amount, 2),
                    ],
                    $html
                );

                $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');

                $fileName = 'Driver_Payout_Report_' . $report->id . '.pdf';
                $tempDir  = storage_path('app/temp');

                if (!is_dir($tempDir)) {
                    mkdir($tempDir, 0755, true);
                    Log::info('Temp directory created', ['path' => $tempDir]);
                }

                $tempPath = $tempDir . '/' . $fileName;
                file_put_contents($tempPath, $pdf->output());

                $uploadedFile = new UploadedFile( $tempPath, $fileName, 'application/pdf', null, true );

                uploadMediaFile($report, $uploadedFile,'delivery_man_payout_report_document');
                $pdfUrl = getSingleMedia( $report, 'delivery_man_payout_report_document', null );

                if (!$pdfUrl || !$report->driver?->email) {
                    throw new \Exception('Driver email or PDF missing');
                }

                 $mailTemplate = OrderMail::where('type','self_billing_statement')->first();

                if (!$mailTemplate) {
                    Log::error('Self billing mail template not found');
                    return;
                }

                $mailContent = str_replace(
                    [
                        '{{ deliveryman name }}',
                        '{{ Week Start Date }}',
                        '{{ Week End Date }}',
                        '{{ Payout Amount }}',
                        '{{ Payment Method }}',
                        '{{ Payment Date }}',
                    ],
                    [
                        $report->driver->display_name,
                        $weekStart,
                        $weekEnd,
                        number_format($report->payout_amount, 2),
                        $report->payment_method,
                        $report->paid_at,

                    ],
                    $mailTemplate->description
                );

                Mail::to($report->driver->email)->send(new SelfBillingStatementMail( $mailContent, $pdfUrl ));
                $report->update([ 'is_mail_sent' => 1 ]);

                DB::commit();

                $this->info("Mail sent to {$report->driver->email}");

            } catch (\Exception $e) {

                DB::rollBack();

                Log::error('Payout mail cron FAILED', [
                    'report_id' => $report->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                $this->error(
                    "Failed report ID {$report->id}: " . $e->getMessage()
                );
            }
        }

        Log::info('=== Driver Payout Mail Cron COMPLETED ===');

        return Command::SUCCESS;
    }
}
