<?php

namespace App\Http\Controllers\Web\TemporaryCard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\TemporaryCard\ChangeTemporaryCardStatusRequest;
use App\Http\Requests\Web\TemporaryCard\DownloadTemporaryCardPdfRequest;
use App\Http\Requests\Web\TemporaryCard\PreviewTemporaryCardRequest;
use App\Http\Requests\Web\TemporaryCard\StoreTemporaryCardRequest;
use App\Services\Web\TemporaryCard\TemporaryCardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class TemporaryIDCardController extends Controller
{
    public function __construct(
        private TemporaryCardService $temporaryCardService
    ) {}

    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    /**
     * Show list of temporary ID cards.
     */
    public function index(Request $request)
    {
        // ✅ Validated search + status (index has no FormRequest)
        $search = $request->input('search');
        $status = $request->input('status');

        $data = $this->temporaryCardService->getIndexData(
            $search,
            $status
        );

        return view('temporary_id_cards.index', $data);
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE
    |--------------------------------------------------------------------------
    */

    /**
     * Show create form.
     */
    public function create()
    {
        return view('temporary_id_cards.create');
    }

    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    */

    /**
     * Save temporary ID cards to DB.
     */
    public function store(StoreTemporaryCardRequest $request)
    {
        $validated = $request->validated();

        try {

            $this->temporaryCardService->storeCards(
                $validated['start'],
                $validated['end']
            );

            return redirect()
                ->route('temporary_id_cards.index')
                ->with('success', 'Temporary ID cards saved successfully.');

        } catch (RuntimeException $e) {

            return back()
                ->with('error', $e->getMessage())
                ->withInput();

        } catch (\Throwable $e) {

            Log::error('Temporary card store failed.', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
                'user_id' => auth()->id(),
            ]);

            return back()
                ->with('error', 'Failed to save temporary ID cards.')
                ->withInput();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PREVIEW
    |--------------------------------------------------------------------------
    */

    /**
     * Show preview page (empty state).
     */
    public function preview()
    {
        return view('temporary_id_cards.preview', [
            'codes' => collect(),
            'start' => '',
            'end'   => '',
        ]);
    }

    /**
     * Generate preview for a TMP range.
     */
    public function generatePreview(PreviewTemporaryCardRequest $request)
    {
        $validated = $request->validated();

        try {

            $data = $this->temporaryCardService->getPreviewData(
                $validated['start'],
                $validated['end']
            );

            return view('temporary_id_cards.preview', $data);

        } catch (RuntimeException $e) {

            return back()->with('error', $e->getMessage());

        } catch (\Throwable $e) {

            Log::error('Temporary card preview failed.', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'user_id' => auth()->id(),
            ]);

            return back()->with('error', 'Failed to generate preview.');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE STATUS
    |--------------------------------------------------------------------------
    */

    /**
     * Show update status page.
     */
    public function updateStatusPage()
    {
        return view('temporary_id_cards.update-status', [
            'updatedCards'   => collect(),
            'start'          => '',
            'end'            => '',
            'selectedStatus' => 'downloaded',
        ]);
    }

    /**
     * Change card statuses.
     */
    public function changeStatus(ChangeTemporaryCardStatusRequest $request)
    {
        $validated = $request->validated();

        try {

            $data = $this->temporaryCardService->changeStatus(
                $validated['start'],
                $validated['end'] ?? null,
                $validated['status']
            );

            return view('temporary_id_cards.update-status', $data)
                ->with('success', $data['message']);

        } catch (RuntimeException $e) {

            return back()->with('error', $e->getMessage());

        } catch (\Throwable $e) {

            Log::error('Temporary card status update failed.', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'user_id' => auth()->id(),
            ]);

            return back()->with('error', 'Failed to update card statuses.');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DOWNLOAD PDF
    |--------------------------------------------------------------------------
    */

    /**
     * Download PDF for a range of temporary ID cards.
     */
    public function downloadPdf(DownloadTemporaryCardPdfRequest $request)
    {
        $validated = $request->validated();

        try {

            $pdf = $this->temporaryCardService->generateRangePdf(
                $validated['start'],
                $validated['end']
            );

            return $pdf->download('temporary-id-cards.pdf');

        } catch (RuntimeException $e) {

            return back()->with('error', $e->getMessage());

        } catch (\Throwable $e) {

            Log::error('Temporary card PDF download failed.', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'user_id' => auth()->id(),
            ]);

            return back()->with('error', 'Failed to download PDF.');
        }
    }

    /**
     * Download issued temporary ID cards report PDF.
     */
    public function downloadIdCardPdf()
    {
        try {

            $pdf = $this->temporaryCardService->generateIssuedCardsPdf();

            return $pdf->download(
                'temporary-id-cards-' . now()->format('Y-m-d') . '.pdf'
            );

        } catch (RuntimeException $e) {

            return back()->with('error', $e->getMessage());

        } catch (\Throwable $e) {

            Log::error('Issued temporary ID cards PDF download failed.', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
                'user_id' => auth()->id(),
            ]);

            return back()->with('error', 'Failed to download PDF: ' . $e->getMessage());
        }
    }
}