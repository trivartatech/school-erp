<?php

namespace App\Exports;

use App\Models\Payroll;
use App\Models\Staff;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PayrollExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected int $schoolId;
    protected int $month;
    protected int $year;

    private static array $monthNames = [
        '', 'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December',
    ];

    public function __construct(int $schoolId, int $month, int $year)
    {
        $this->schoolId = $schoolId;
        $this->month    = $month;
        $this->year     = $year;
    }

    public function collection()
    {
        return Payroll::where('school_id', $this->schoolId)
            ->where('month', $this->month)
            ->where('year',  $this->year)
            ->with(['staff.user', 'staff.designation', 'staff.department'])
            ->orderBy('id')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Employee ID',
            'Name',
            'Designation',
            'Department',
            'Basic Pay',
            'Allowances',
            'Gross Pay',
            'Deductions',
            'LWP Days',
            'LWP Deduction',
            'Net Salary',
            'Status',
            'Payment Date',
            'Payment Mode',
        ];
    }

    public function map($payroll): array
    {
        $staff = $payroll->staff;
        $gross = (float) $payroll->basic_pay + (float) $payroll->allowances;

        return [
            $staff->employee_id ?? '',
            $staff->user->name ?? '',
            $staff->designation->name ?? '',
            $staff->department->name ?? '',
            number_format((float) $payroll->basic_pay, 2, '.', ''),
            number_format((float) $payroll->allowances, 2, '.', ''),
            number_format($gross, 2, '.', ''),
            number_format((float) $payroll->deductions, 2, '.', ''),
            $payroll->unpaid_leave_days ?? 0,
            number_format((float) ($payroll->unpaid_leave_deduction ?? 0), 2, '.', ''),
            number_format((float) $payroll->net_salary, 2, '.', ''),
            ucfirst($payroll->status),
            $payroll->payment_date ?? '',
            $payroll->payment_mode ? ucfirst(str_replace('_', ' ', $payroll->payment_mode)) : '',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
