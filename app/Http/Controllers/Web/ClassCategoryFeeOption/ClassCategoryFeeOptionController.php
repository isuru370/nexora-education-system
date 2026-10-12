<?php

namespace App\Http\Controllers\Web\ClassCategoryFeeOption;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ClassCategoryFeeOption\StoreClassCategoryFeeOptionRequest;
use App\Http\Requests\Web\ClassCategoryFeeOption\UpdateClassCategoryFeeOptionRequest;
use App\Models\ClassCategoryFee;
use App\Services\Web\ClassCategoryFeeOption\ClassCategoryFeeOptionService;
use Illuminate\Validation\ValidationException;
use Throwable;
use Illuminate\Support\Facades\Log;

class ClassCategoryFeeOptionController extends Controller
{
    public function __construct(
        private ClassCategoryFeeOptionService $classCategoryFeeOptionService
    ) {}

    public function index($classCategoryFeeId)
    {
        $classCategoryFee = ClassCategoryFee::with([
            'category',
            'studentClass',
        ])->findOrFail($classCategoryFeeId);

        $feeOptions = $this->classCategoryFeeOptionService
            ->getOptionsByCategoryFee($classCategoryFeeId);

        return view(
            'class_category_fee_options.index',
            compact('classCategoryFee', 'feeOptions')
        );
    }

    public function create($classCategoryFeeId)
    {
        $classCategoryFee = ClassCategoryFee::with([
            'category',
            'studentClass',
        ])->findOrFail($classCategoryFeeId);

        return view(
            'class_category_fee_options.create',
            compact('classCategoryFee')
        );
    }

    public function store(
        StoreClassCategoryFeeOptionRequest $request,
        $classCategoryFeeId
    ) {
        try {
            $data = $request->validated();

            $this->classCategoryFeeOptionService->create([
                'class_category_fee_id' => $classCategoryFeeId,
                'label' => $data['label'],
                'fee' => $data['fee'],
                'is_default' => (bool) ($data['is_default'] ?? false),
                'is_active' => (bool) ($data['is_active'] ?? true),
                'note' => $data['note'] ?? null,
            ]);

            return redirect()
                ->route('class-category-fee-options.index', [
                    'classCategoryFeeId' => $classCategoryFeeId,
                ])
                ->with('success', 'Fee option created successfully.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (Throwable $e) {
            Log::error('Failed to create class category fee option.', [
                'class_category_fee_id' => $classCategoryFeeId,
                'exception' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Unable to create fee option.');
        }
    }

    public function edit($id)
    {
        $feeOption = $this->classCategoryFeeOptionService->find($id);
        $classCategoryFee = $feeOption->classCategoryFee;

        return view(
            'class_category_fee_options.edit',
            compact('feeOption', 'classCategoryFee')
        );
    }

    public function update(
        UpdateClassCategoryFeeOptionRequest $request,
        $id
    ) {
        try {
            $feeOption = $this->classCategoryFeeOptionService->find($id);
            $classCategoryFeeId = $feeOption->class_category_fee_id;

            $data = $request->validated();

            $this->classCategoryFeeOptionService->update($id, [
                'label' => $data['label'],
                'fee' => $data['fee'],
                'is_default' => (bool) ($data['is_default'] ?? false),
                'is_active' => (bool) ($data['is_active'] ?? true),
                'note' => $data['note'] ?? null,
            ]);

            return redirect()
                ->route('class-category-fee-options.index', [
                    'classCategoryFeeId' => $classCategoryFeeId,
                ])
                ->with('success', 'Fee option updated successfully.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (Throwable $e) {
            Log::error('Failed to update class category fee option.', [
                'fee_option_id' => $id,
                'exception' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Unable to update fee option.');
        }
    }

    public function destroy($id)
    {
        try {
            $feeOption = $this->classCategoryFeeOptionService->find($id);
            $classCategoryFeeId = $feeOption->class_category_fee_id;

            $this->classCategoryFeeOptionService->delete($id);

            return redirect()
                ->route('class-category-fee-options.index', [
                    'classCategoryFeeId' => $classCategoryFeeId,
                ])
                ->with('success', 'Fee option deleted successfully.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        } catch (Throwable $e) {
            Log::error('Failed to delete class category fee option.', [
                'fee_option_id' => $id,
                'exception' => $e->getMessage(),
            ]);

            return back()->with('error', 'Unable to delete fee option.');
        }
    }

    public function restore($id)
    {
        try {
            $feeOption = $this->classCategoryFeeOptionService->restore($id);

            return redirect()
                ->route('class-category-fee-options.index', [
                    'classCategoryFeeId' => $feeOption->class_category_fee_id,
                ])
                ->with('success', 'Fee option restored successfully.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        } catch (Throwable $e) {
            Log::error('Failed to restore class category fee option.', [
                'fee_option_id' => $id,
                'exception' => $e->getMessage(),
            ]);

            return back()->with('error', 'Unable to restore fee option.');
        }
    }

    public function setDefault($id)
    {
        return $this->performAction(
            $id,
            'setDefault',
            'Default fee option updated successfully.'
        );
    }

    public function activate($id)
    {
        return $this->performAction(
            $id,
            'activate',
            'Fee option activated successfully.'
        );
    }

    public function deactivate($id)
    {
        return $this->performAction(
            $id,
            'deactivate',
            'Fee option deactivated successfully.'
        );
    }

    private function performAction($id, $method, $successMessage)
    {
        try {
            $feeOption = $this->classCategoryFeeOptionService->{$method}($id);

            return redirect()
                ->route('class-category-fee-options.index', [
                    'classCategoryFeeId' => $feeOption->class_category_fee_id,
                ])
                ->with('success', $successMessage);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        } catch (Throwable $e) {
            Log::error("Class category fee option action failed: {$method}.", [
                'fee_option_id' => $id,
                'exception' => $e->getMessage(),
            ]);

            return back()->with('error', 'Unable to complete this action.');
        }
    }
}
