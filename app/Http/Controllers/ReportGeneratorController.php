<?php

namespace App\Http\Controllers;

use App\Exports\ReportExport;
use App\Models\Borrowing;
use App\Models\ReportArchive;
use App\Services\ReportDataService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class ReportGeneratorController extends Controller
{
    /**
     * Display report archives listing.
     */
    public function index(): View
    {
        $archives = ReportArchive::orderBy('generated_at', 'desc')->paginate(12);

        return view('reports.index', compact('archives'));
    }

    /**
     * Generate a periodic report.
     *
     * Menghasilkan file PDF dan Excel sungguhan ke disk penyimpanan local
     * serta mencatat path-nya di database.
     */
    public function generateReport(Request $request, ReportDataService $reportService): RedirectResponse
    {
        $request->validate([
            'jenis_laporan' => 'required|string|in:bulanan,kuartalan,tahunan,custom',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
        ]);

        $start = Carbon::parse($request->tanggal_mulai)->startOfDay();
        $end = Carbon::parse($request->tanggal_selesai)->endOfDay();

        // 1. Ambil data terpadu dari ReportDataService
        $data = $reportService->buildReportData($request->jenis_laporan, $start, $end);

        // 2. Buat nama file unik untuk PDF & Excel
        $baseFileName = 'Report_'.ucfirst($request->jenis_laporan).'_'
            .$start->format('Ymd').'-'.$end->format('Ymd').'_'.time();

        $pdfFileName = $baseFileName.'.pdf';
        $pdfPath = 'reports/'.$pdfFileName;

        $excelFileName = $baseFileName.'.xlsx';
        $excelPath = 'reports/'.$excelFileName;

        // 3. Generate PDF dan simpan ke Storage
        $pdf = Pdf::loadView('reports.pdf.template', $data);
        Storage::put($pdfPath, $pdf->output());

        // 4. Generate Excel dan simpan ke Storage
        Excel::store(new ReportExport($data), $excelPath);

        // 5. Simpan record arsip ke database
        ReportArchive::create([
            'jenis' => $request->jenis_laporan,
            'periode_mulai' => $start->toDateString(),
            'periode_selesai' => $end->toDateString(),
            'total_nilai_aset' => $data['total_nilai_aset'],
            'total_kerugian' => $data['kerugian']['total_kerugian'] ?? 0,
            'file_excel_path' => $excelPath,
            'file_pdf_path' => $pdfPath,
            'generated_by' => auth()->id(),
            'generated_at' => now(),
        ]);

        return redirect()->route('reports.index')
            ->with('success', 'Laporan periodik baru berhasil digenerate format PDF & Excel.');
    }

    /**
     * Display a preview of the generated report.
     */
    public function show(string $id, ReportDataService $reportService): View
    {
        $archive = ReportArchive::findOrFail($id);

        $start = Carbon::parse($archive->periode_mulai);
        $end = Carbon::parse($archive->periode_selesai);

        // Rebuild report data for the given period to show it in preview
        $data = $reportService->buildReportData($archive->jenis->value, $start, $end);

        // Pass the archive along
        $data['archive'] = $archive;

        // Fetch borrowings in the period to show in the logs table
        $data['borrowings'] = Borrowing::with(['borrower', 'details.product'])
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('reports.show', $data);
    }

    /**
     * Download a stored report file.
     *
     * Menerima query string format (pdf / excel). Download sesuai format.
     */
    public function download(Request $request, string $id): mixed
    {
        $archive = ReportArchive::findOrFail($id);
        $format = $request->query('format', 'excel');

        $path = ($format === 'pdf') ? $archive->file_pdf_path : $archive->file_excel_path;

        if (! $path) {
            return redirect()->route('reports.index')
                ->with('error', "File format '{$format}' belum tersedia untuk laporan ini.");
        }

        if (! Storage::exists($path)) {
            return redirect()->route('reports.index')
                ->with('error', "File laporan tidak ditemukan di storage: {$path}");
        }

        return Storage::download($path, basename($path));
    }
}
