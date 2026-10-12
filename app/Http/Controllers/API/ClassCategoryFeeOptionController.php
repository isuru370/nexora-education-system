<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\ClassCategoryFeeOptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ClassCategoryFeeOptionController extends Controller
{
    protected $feeOptionService;

    public function __construct(
        ClassCategoryFeeOptionService $feeOptionService
    ) {
        $this->feeOptionService = $feeOptionService;
    }

    /**
     * Get all active fee options for a category fee.
     */
    public function index(
        Request $request,
        $classCategoryFeeId
    ): JsonResponse {

        $options = $this->feeOptionService
            ->getActiveOptions($classCategoryFeeId);

        return response()->json([
            'success' => true,
            'data' => $options->map(function ($option) {
                return [
                    'id' => $option->id,
                    'class_category_fee_id' =>
                        $option->class_category_fee_id,
                    'label' => $option->label,
                    'fee' => (float) $option->fee,
                    'is_default' =>
                        (bool) $option->is_default,
                    'is_active' =>
                        (bool) $option->is_active,
                    'note' => $option->note,
                ];
            })->values(),
        ]);
    }

    /**
     * Get single option.
     */
    public function show($id): JsonResponse
    {
        $option = $this->feeOptionService->find($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $option->id,
                'class_category_fee_id' =>
                    $option->class_category_fee_id,
                'label' => $option->label,
                'fee' => (float) $option->fee,
                'is_default' =>
                    (bool) $option->is_default,
                'is_active' =>
                    (bool) $option->is_active,
                'note' => $option->note,
            ],
        ]);
    }

    /**
     * Create option.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'class_category_fee_id' => [
                'required',
                'integer',
                'exists:class_category_fees,id',
            ],

            'label' => [
                'required',
                'string',
                'max:150',
            ],

            'fee' => [
                'required',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],

            'is_default' => [
                'nullable',
                'boolean',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],

            'note' => [
                'nullable',
                'string',
            ],
        ]);

        $option = $this->feeOptionService->create(
            $validated
        );

        return response()->json([
            'success' => true,
            'message' => 'Fee option created successfully.',
            'data' => $option,
        ], 201);
    }

    /**
     * Update option.
     */
    public function update(
        Request $request,
        $id
    ): JsonResponse {

        $validated = $request->validate([
            'class_category_fee_id' => [
                'required',
                'integer',
                'exists:class_category_fees,id',
            ],

            'label' => [
                'required',
                'string',
                'max:150',
            ],

            'fee' => [
                'required',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],

            'is_default' => [
                'nullable',
                'boolean',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],

            'note' => [
                'nullable',
                'string',
            ],
        ]);

        $option = $this->feeOptionService->update(
            $id,
            $validated
        );

        return response()->json([
            'success' => true,
            'message' => 'Fee option updated successfully.',
            'data' => $option,
        ]);
    }

    /**
     * Delete option.
     */
    public function destroy($id): JsonResponse
    {
        try {

            $this->feeOptionService->delete($id);

            return response()->json([
                'success' => true,
                'message' => 'Fee option deleted successfully.',
            ]);

        } catch (ValidationException $e) {

            return response()->json([
                'success' => false,
                'message' => 'Fee option cannot be deleted.',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Set default option.
     */
    public function setDefault($id): JsonResponse
    {
        try {

            $option = $this->feeOptionService
                ->setDefault($id);

            return response()->json([
                'success' => true,
                'message' =>
                    'Default fee option updated successfully.',
                'data' => $option,
            ]);

        } catch (ValidationException $e) {

            return response()->json([
                'success' => false,
                'message' =>
                    'Unable to set default fee option.',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Activate option.
     */
    public function activate($id): JsonResponse
    {
        $option = $this->feeOptionService->activate($id);

        return response()->json([
            'success' => true,
            'message' => 'Fee option activated successfully.',
            'data' => $option,
        ]);
    }

    /**
     * Deactivate option.
     */
    public function deactivate($id): JsonResponse
    {
        try {

            $option = $this->feeOptionService
                ->deactivate($id);

            return response()->json([
                'success' => true,
                'message' =>
                    'Fee option deactivated successfully.',
                'data' => $option,
            ]);

        } catch (ValidationException $e) {

            return response()->json([
                'success' => false,
                'message' =>
                    'Unable to deactivate fee option.',
                'errors' => $e->errors(),
            ], 422);
        }
    }
}