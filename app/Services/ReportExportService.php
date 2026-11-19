<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Renderer PDF/XLSX. ReportService tetap menjadi satu-satunya sumber data;
 * kelas ini hanya memformat, tidak menghitung ulang angka bisnis.
 */
final class ReportExportService
{
    public const PDF_MAX_ROWS = 1000;

    public const XLSX_MAX_ROWS = 10000;

    private const MONEY_METRICS = [
        'rental_subtotal', 'rental_gross', 'rental_refund', 'net_rental_revenue', 'deposit_collected',
        'gross_sales', 'discount_total', 'net_sales', 'refunded_sales', 'collected', 'required', 'held',
        'held_balance', 'deducted', 'refunded', 'forfeited', 'outstanding', 'total', 'average_order_value',
    ];

    public function __construct(private readonly ReportService $reports) {}

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<string, mixed>  $report
     */
    public function pdf(string $key, array $filters, array $report, string $preparedBy): Response
    {
        abort_if(count($report['table']) > self::PDF_MAX_ROWS, 422, 'Ekspor PDF dibatasi '.self::PDF_MAX_ROWS.' baris. Persempit periode atau gunakan Excel/CSV.');

        $landscape = in_array($key, ['rental', 'sales', 'payments', 'deposits', 'charges', 'inventory'], true);

        return Pdf::loadView('admin.reports.pdf', [
            'title' => $this->label($key),
            'filters' => $filters,
            'report' => $report,
            'preparedBy' => $preparedBy,
            'currencyKeys' => $this->currencyKeys($key),
            'moneyMetrics' => self::MONEY_METRICS,
        ])->setPaper('a4', $landscape ? 'landscape' : 'portrait')
            ->setOption('isRemoteEnabled', false)
            ->download($this->filename($key, $filters, 'pdf'));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<string, mixed>  $report
     */
    public function xlsx(string $key, array $filters, array $report): StreamedResponse
    {
        abort_if(count($report['table']) > self::XLSX_MAX_ROWS, 422, 'Ekspor Excel dibatasi '.self::XLSX_MAX_ROWS.' baris. Persempit periode atau gunakan CSV.');

        $spreadsheet = new Spreadsheet;

        if ($key === 'summary') {
            $this->buildSummaryWorkbook($spreadsheet, $report['metrics']);
        } else {
            $this->buildDetailSheet($spreadsheet->getActiveSheet(), $key, $filters, $report['table']);
        }

        $spreadsheet->setActiveSheetIndex(0);
        $filename = $this->filename($key, $filters, 'xlsx');

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    /**
     * @param  array<string, array<string, mixed>>  $groups
     */
    private function buildSummaryWorkbook(Spreadsheet $spreadsheet, array $groups): void
    {
        $overview = $spreadsheet->getActiveSheet();
        $overview->setTitle('Ringkasan');
        $this->brandHeader($overview, 'Ringkasan Laporan', ['Grup', 'Metrik', 'Nilai']);
        $row = 5;
        foreach ($groups as $group => $metrics) {
            foreach ($metrics as $metric => $value) {
                if (is_array($value) || is_bool($value) || $value === null) {
                    continue;
                }
                $overview->setCellValueExplicit($this->cell(1, $row), $this->groupLabel($group), DataType::TYPE_STRING);
                $overview->setCellValueExplicit($this->cell(2, $row), str_replace('_', ' ', $metric), DataType::TYPE_STRING);
                $this->writeMetric($overview, 3, $row, $metric, $value);
                $row++;
            }
        }
        $this->finalizeSheet($overview, 3, $row - 1);

        foreach ($groups as $group => $metrics) {
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle(mb_substr($this->groupLabel($group), 0, 31));
            $this->brandHeader($sheet, $this->groupLabel($group), ['Metrik', 'Nilai']);
            $row = 5;
            foreach ($metrics as $metric => $value) {
                if (is_array($value) || is_bool($value) || $value === null) {
                    continue;
                }
                $sheet->setCellValueExplicit($this->cell(1, $row), str_replace('_', ' ', $metric), DataType::TYPE_STRING);
                $this->writeMetric($sheet, 2, $row, $metric, $value);
                $row++;
            }
            $this->finalizeSheet($sheet, 2, $row - 1);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function buildDetailSheet(Worksheet $sheet, string $key, array $filters, array $rows): void
    {
        $sheet->setTitle(mb_substr($this->label($key), 0, 31));

        $headers = $this->reports->csvHeaders($key);
        $this->brandHeader($sheet, $this->label($key).' — '.$filters['start_date'].' sd '.$filters['end_date'], $headers);

        $row = 5;
        foreach ($rows as $record) {
            $values = array_values($record);
            $keys = array_keys($record);
            foreach ($values as $index => $value) {
                $column = $index + 1;
                $field = $keys[$index] ?? '';
                $cell = $sheet->getCell($this->cell($column, $row));
                if (in_array($field, $this->currencyKeys($key), true) && is_numeric($value)) {
                    $cell->setValueExplicit((int) $value, DataType::TYPE_NUMERIC);
                    $cell->getStyle()->getNumberFormat()->setFormatCode('"Rp"#,##0');
                } else {
                    $cell->setValueExplicit($this->safeString($value), DataType::TYPE_STRING);
                }
            }
            $row++;
        }

        $this->finalizeSheet($sheet, max(1, count($headers)), max(4, $row - 1), autoFilterFromRow: 5);
        $sheet->freezePane('A6');
        $sheet->getPageSetup()->setOrientation(in_array($key, ['rental', 'sales', 'payments', 'deposits', 'charges', 'inventory'], true) ? 'landscape' : 'portrait');
    }

    /**
     * @param  array<int, string>  $headers
     */
    private function brandHeader(Worksheet $sheet, string $subtitle, array $headers): void
    {
        $sheet->setCellValue('A1', 'GEARORA — OUTDOOR SUPPLY');
        $sheet->setCellValue('A2', mb_strtoupper($subtitle));
        $sheet->setCellValue('A3', 'Gear Up. Explore More. · '.now(config('app.timezone'))->format('d M Y H:i').' WIB');
        $last = Coordinate::stringFromColumnIndex(max(1, count($headers)));
        $sheet->mergeCells("A1:{$last}1")->mergeCells("A2:{$last}2")->mergeCells("A3:{$last}3");
        $sheet->getStyle('A1:A3')->getFont()->setBold(true)->setColor(new Color('FFFFFFFF'));
        $sheet->getStyle('A1:A3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF08213F');

        foreach ($headers as $index => $header) {
            $sheet->setCellValue($this->cell($index + 1, 4), $header);
        }
        $sheet->getStyle("A4:{$last}4")->getFont()->setBold(true)->setColor(new Color('FFFFFFFF'));
        $sheet->getStyle("A4:{$last}4")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF183D32');
    }

    private function finalizeSheet(Worksheet $sheet, int $lastColumn, int $lastRow, ?int $autoFilterFromRow = null): void
    {
        $last = Coordinate::stringFromColumnIndex(max(1, $lastColumn));
        if ($autoFilterFromRow !== null && $lastRow >= $autoFilterFromRow) {
            $sheet->setAutoFilter("A{$autoFilterFromRow}:{$last}{$lastRow}");
        }
        for ($column = 1; $column <= $lastColumn; $column++) {
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(true);
        }
    }

    private function writeMetric(Worksheet $sheet, int $column, int $row, string $metric, mixed $value): void
    {
        $cell = $sheet->getCell($this->cell($column, $row));
        if (in_array($metric, self::MONEY_METRICS, true) && is_numeric($value)) {
            $cell->setValueExplicit((int) $value, DataType::TYPE_NUMERIC);
            $cell->getStyle()->getNumberFormat()->setFormatCode('"Rp"#,##0');

            return;
        }

        if (is_numeric($value)) {
            $cell->setValueExplicit($value + 0, DataType::TYPE_NUMERIC);

            return;
        }

        $cell->setValueExplicit($this->safeString($value), DataType::TYPE_STRING);
    }

    /** @param array<string, mixed> $filters */
    private function filename(string $key, array $filters, string $extension): string
    {
        return "gearora-{$key}-{$filters['start_date']}_{$filters['end_date']}.{$extension}";
    }

    private function label(string $key): string
    {
        return [
            'summary' => 'Ringkasan Laporan', 'rental' => 'Laporan Rental', 'sales' => 'Laporan Penjualan Retail',
            'payments' => 'Laporan Pembayaran', 'deposits' => 'Laporan Deposit', 'charges' => 'Laporan Biaya Tambahan',
            'inventory' => 'Laporan Inventaris', 'operations' => 'Laporan Operasional Rental',
        ][$key] ?? 'Laporan';
    }

    private function groupLabel(string $group): string
    {
        return [
            'rental' => 'Rental', 'sales' => 'Penjualan Retail', 'payments' => 'Pembayaran',
            'deposits' => 'Deposit', 'charges' => 'Biaya Tambahan', 'inventory' => 'Inventaris', 'operations' => 'Operasional',
        ][$group] ?? ucfirst($group);
    }

    /** @return array<int, string> */
    private function currencyKeys(string $key): array
    {
        return match ($key) {
            'rental' => ['subtotal', 'deposit_total', 'total', 'rental_paid'],
            'sales' => ['subtotal', 'discount_total', 'total'],
            'payments' => ['amount'],
            'deposits' => ['required', 'collected', 'deducted', 'refunded', 'forfeited', 'balance'],
            'charges' => ['amount', 'outstanding'],
            default => [],
        };
    }

    private function cell(int $column, int $row): string
    {
        return Coordinate::stringFromColumnIndex($column).$row;
    }

    private function safeString(mixed $value): string
    {
        $value = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'{$value}" : $value;
    }
}
