<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Filters\QuizTypeFilters;
use App\Http\Requests\Admin\StoreQuizTypeRequest;
use App\Http\Requests\Admin\UpdateQuizTypeRequest;
use App\Models\QuizType;
use App\Transformers\Admin\QuizTypeTransformer;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class QuizTypeCrudController extends Controller
{
    public function __construct()
    {
        $this->middleware(['role:admin|instructor'])->except('search');
    }

    /**
     * List all Quiz Types
     *
     * @param QuizTypeFilters $filters
     * @return \Inertia\Response
     */
    public function index(QuizTypeFilters $filters)
    {
        return Inertia::render('Admin/QuizTypes', [
            'quizTypes' => function () use($filters) {
                return fractal(QuizType::filter($filters)
                    ->paginate(request()->perPage != null ? request()->perPage : 10),
                    new QuizTypeTransformer())->toArray();
            },
        ]);
    }

    /**
     * Store a QuizType
     *
     * @param StoreQuizTypeRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreQuizTypeRequest $request)
    {
        QuizType::create($request->validated());
        return redirect()->back()->with('successMessage', 'Quiz Type was successfully added!');
    }

    /**
     * Show a QuizType
     *
     * @param $id
     * @return array
     */
    public function show($id)
    {
        return fractal(QuizType::find($id), new QuizTypeTransformer())->toArray();
    }

    /**
     * Edit a QuizType
     *
     * @param $id
     * @return QuizType|QuizType[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|null
     */
    public function edit($id)
    {
        return QuizType::find($id);
    }

    /**
     * Update a QuizType
     *
     * @param UpdateQuizTypeRequest $request
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateQuizTypeRequest $request, $id)
    {
        $quizType = QuizType::find($id);
        $quizType->update($request->validated());
        return redirect()->back()->with('successMessage', 'Quiz Type was successfully updated!');
    }

    /**
     * Delete a QuizType
     *
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        try {
            $quizType = QuizType::withCount(['quizzes'])->find($id);

            if(!$quizType->canSecureDelete('quizzes')) {
                $associations = implode(", ", array_filter([
                    $quizType->quizzes_count > 0 ? "{$quizType->quizzes_count} quizzes" : ""
                ]));
                return redirect()->back()
                    ->with('errorMessage',
                        "Unable to delete quiz type as it is associated with {$associations}. Remove all associations and try again!"
                    );
            }

            DB::transaction(function () use ($quizType) {
                $quizType->secureDelete('quizzes');
            });
        }
        catch (\Illuminate\Database\QueryException $e){
            return redirect()->back()->with('errorMessage', 'Unable to Delete Quiz Type . Remove all associations and Try again!');
        }

        return redirect()->back()->with('successMessage', 'Quiz Type was successfully deleted!');
    }


}
