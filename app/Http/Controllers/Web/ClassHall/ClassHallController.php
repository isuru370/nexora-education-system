<?php

namespace App\Http\Controllers\Web\ClassHall;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ClassHall\StoreClassHallRequest;
use App\Http\Requests\Web\ClassHall\UpdateClassHallRequest;
use App\Models\ClassHall;
use App\Services\Web\ClassHall\ClassHallService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ClassHallController extends Controller
{
    protected ClassHallService $classHallService;

    public function __construct(ClassHallService $classHallService)
    {
        $this->classHallService = $classHallService;
    }

    public function index(Request $request)
    {
        $data = $this->classHallService->getIndexData($request);

        return view('class_halls.index', $data);
    }

    public function create()
    {
        $data = $this->classHallService->getCreateData();

        return view('class_halls.create', $data);
    }

    public function store(StoreClassHallRequest $request)
    {
        $this->classHallService->store($request->validated());

        return redirect()
            ->route('class-halls.index')
            ->with('success', 'Class hall created successfully.');
    }

    public function show(ClassHall $classHall)
    {
        $classHall = $this->classHallService->getDetails($classHall);

        return view('class_halls.show', compact('classHall'));
    }

    public function edit(ClassHall $classHall)
    {
        $classHall = $this->classHallService->getDetails($classHall);
        $data = $this->classHallService->getCreateData();

        return view('class_halls.edit', array_merge(
            $data,
            compact('classHall')
        ));
    }

    public function update(
        UpdateClassHallRequest $request,
        ClassHall $classHall
    ) {
        $this->classHallService->update(
            $classHall,
            $request->validated()
        );

        return redirect()
            ->route('class-halls.index')
            ->with('success', 'Class hall updated successfully.');
    }

    public function destroy(ClassHall $classHall)
    {
        try {
            $this->classHallService->delete($classHall);

            return redirect()
                ->route('class-halls.index')
                ->with('success', 'Class hall deleted successfully.');
        } catch (ValidationException $e) {
            return back()->with(
                'error',
                collect($e->errors())->flatten()->first()
            );
        }
    }

    public function toggleActive(ClassHall $classHall)
    {
        try {
            $this->classHallService->toggleActive($classHall);

            return back()->with('success', 'Hall status updated.');
        } catch (ValidationException $e) {
            return back()->with(
                'error',
                collect($e->errors())->flatten()->first()
            );
        }
    }
}