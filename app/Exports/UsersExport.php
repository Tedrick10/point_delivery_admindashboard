<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Events\AfterSheet;

class UsersExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithEvents
{
    protected $user_data;
    protected $request;
    protected $counter = 1;
    protected $columns;

    public function __construct($user_data, $request)
    {
        $this->user_data = $user_data;
        $this->request   = $request;
        $this->columns   = $request->input('columns', []); 
    }

    public function collection()
    {
        return $this->user_data;
    }

    public function map($user): array
    {
        foreach ($this->columns as $col) {
            switch ($col) {
                case 'no':
                    $row[] = $this->counter++;
                    break;
                case 'id':
                    $row[] = $user->id ?? '-';
                    break;
                case 'name':
                    $row[] = $user->name ?? '-';
                    break;
                case 'email':
                    $row[] = ($user->email ?? '-');
                    break;
                case 'username':
                    $row[] = $user->username ?? '-';
                    break;
                case 'address':
                    $row[] = $user->address ?? '-';
                    break;
                case 'contact_number':
                    $row[] = $user->contact_number ?? '-';
                    break;
                case 'country':
                    $row[] = $user->country->name ?? '-';
                    break;
                case 'city':
                    $row[] = $user->city->name ?? '-';
                    break;
               case 'status':
                    $row[] = $user->status == 1 ? 'Active' : 'Inactive';
                    break;
                    break;
                case 'app_version':
                    $row[] = $user->app_version ?? '-';
                    break;
                case 'app_source':
                    $row[] = $user->app_source ?? '-';
                    break;
                case 'referral_code':
                    $row[] = $user->referral_code ?? '-';
                    break;
                case 'created_at':
                    $row[] = $user->created_at ? $user->created_at->format('Y-m-d') : '-';
                    break;
            }
        }

        return $row;
    }

    public function headings($exportType = 'excel'): array
    {
        $fromDate = $this->request->input('from_date');
        $toDate   = $this->request->input('to_date');

        $date = ($fromDate && $toDate)
            ? 'From Date: ' . ($fromDate ?: '-') . ' To Date ' . ($toDate ?: '-')
            : ($fromDate ? 'From Date: ' . $fromDate : ($toDate ? 'To Date: ' . $toDate : null));

        $labels = [];
        foreach ($this->columns as $col) {
            switch ($col) {
                case 'no': $labels[] = __('message.no'); break;
                case 'id': $labels[] = __('message.id'); break;
                case 'name': $labels[] = __('message.name'); break;
                case 'email': $labels[] = __('message.email'); break;
                case 'username': $labels[] = __('message.username'); break;
                case 'address': $labels[] = __('message.address'); break;
                case 'contact_number': $labels[] = __('message.contact_number'); break;
                case 'country': $labels[] = __('message.country'); break;
                case 'city': $labels[] = __('message.city'); break;
                case 'status': $labels[] = __('message.status'); break;
                case 'app_version': $labels[] = __('message.app_version'); break;
                case 'app_source': $labels[] = __('message.app_source'); break;
                case 'referral_code': $labels[] = __('message.referral_code'); break;
                case 'created_at': $labels[] = __('message.created_at'); break;
            }
        }

        if ($exportType === 'excel') {
            $first = $this->user_data->first();
            if ($first && $first->user_type === 'client') {
                return [
                    ['Users Report' . ($date ? ' : ' . $date : '')],
                    [''],
                    $labels,
                ];
            }else{
                return [
                    ['Deliveryman Report' . ($date ? ' : ' . $date : '')],
                    [''],
                    $labels,
                ];
            }
        }

        return $labels;
    }

    public function styles(Worksheet $sheet)
    {
        $highestColumn = $sheet->getHighestColumn();
        $sheet->mergeCells('A1:' . $highestColumn . '1');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:' . $highestColumn . '1')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                foreach (range('A', $event->sheet->getDelegate()->getHighestColumn()) as $col) {
                    $event->sheet->getDelegate()->getColumnDimension($col)->setAutoSize(true);
                }
                $event->sheet->getDelegate()->getStyle('A:Z')
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            },
        ];
    }
}
