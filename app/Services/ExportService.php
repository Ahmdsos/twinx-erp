<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export Service
 * خدمة التصدير
 */
class ExportService
{
    /**
     * Export data to CSV
     */
    public function exportCsv(array $headers, Collection|array $data, string $filename): StreamedResponse
    {
        $filename = $this->sanitizeFilename($filename) . '.csv';

        return response()->streamDownload(function () use ($headers, $data) {
            $file = fopen('php://output', 'w');
            
            // UTF-8 BOM for Excel
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            
            // Headers
            fputcsv($file, $headers);
            
            // Data rows
            foreach ($data as $row) {
                fputcsv($file, is_array($row) ? $row : (array) $row);
            }
            
            fclose($file);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Export data to JSON
     */
    public function exportJson(array|Collection $data, string $filename): JsonResponse
    {
        $filename = $this->sanitizeFilename($filename) . '.json';

        return response()->json($data)
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Generate printable HTML
     */
    public function generatePrintHtml(string $view, array $data): string
    {
        return View::make($view, $data)->render();
    }

    /**
     * Export to Excel (CSV format for simplicity)
     */
    public function exportExcel(array $headers, Collection|array $data, string $filename): StreamedResponse
    {
        return $this->exportCsv($headers, $data, $filename);
    }

    /**
     * Format report data for export
     */
    public function formatReportData(Collection $data, array $columns): array
    {
        return $data->map(function ($row) use ($columns) {
            $formatted = [];
            foreach ($columns as $key => $label) {
                $formatted[$label] = $row->{$key} ?? ($row[$key] ?? '');
            }
            return $formatted;
        })->toArray();
    }

    /**
     * Sanitize filename
     */
    private function sanitizeFilename(string $filename): string
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
    }
}
