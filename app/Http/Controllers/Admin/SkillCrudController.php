<?php
/**
 * File name: SkillCrudController.php
 * Last modified: 19/07/21, 12:55 AM
 * Author: NearCraft - https://codecanyon.net/user/nearcraft
 * Copyright (c) 2021
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\Skill;
use App\Transformers\Admin\SkillSearchTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Filters\SkillFilters;
use App\Http\Requests\Admin\StoreSkillRequest;
use App\Http\Requests\Admin\UpdateSkillRequest;
use App\Transformers\Admin\SkillTransformer;

class SkillCrudController extends Controller
{
    public function __construct()
    {
        $this->middleware(['role:admin|instructor'])->except('search');
    }

    /**
     * List all skills
     *
     * @param SkillFilters $filters
     * @return \Inertia\Response
     */
    public function index(SkillFilters $filters)
    {
        return Inertia::render('Admin/Skills', [
            'sections' => Section::select(['name', 'id'])->get(),
            'skills' => function () use($filters) {
                return fractal(Skill::filter($filters)->with('section:id,name')
                    ->orderBy('name')->paginate(request()->perPage != null ? request()->perPage : 10),
                    new SkillTransformer())->toArray();
            },
        ]);
    }

    /**
     * Search skills api endpoint
     *
     * @param Request $request
     * @param SkillFilters $filters
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request, SkillFilters $filters)
    {
        $query = $request->get('query');
        return response()->json([
            'skills' => fractal(Skill::select(['id', 'name', 'section_id'])
                ->filter($filters)
                ->with('section:id,name')
                ->where('name', 'like', '%'.$query.'%')->limit(20)
                ->get(), new SkillSearchTransformer())
                ->toArray()['data']
        ]);
    }

    /**
     * Store a skill
     *
     * @param StoreSkillRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreSkillRequest $request)
    {
        Skill::create($request->validated());
        return redirect()->back()->with('successMessage', 'Skill was successfully added!');
    }

    /**
     * Show a skill
     *
     * @param $id
     * @return array
     */
    public function show($id)
    {
        $skill = Skill::find($id);
        return fractal($skill, new SkillTransformer())->toArray();
    }

    /**
     * Edit a skill
     *
     * @param $id
     * @return Skill|Skill[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|null
     */
    public function edit($id)
    {
        return Skill::find($id);
    }

    /**
     * Update a skill
     *
     * @param UpdateSkillRequest $request
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateSkillRequest $request, $id)
    {
        $skill = Skill::find($id);
        $skill->update($request->validated());
        return redirect()->back()->with('successMessage', 'Skill was successfully updated!');
    }

    /**
     * Delete a skill
     *
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        try {
            $skill = Skill::withCount(['topics', 'questions', 'practiceSets', 'lessons', 'videos'])->find($id);

            if(!$skill->canSecureDelete('topics', 'questions', 'practiceSets', 'lessons', 'videos')) {
                $associations = implode(", ", array_filter([
                    $skill->topics_count > 0 ? "{$skill->topics_count} topics" : "",
                    $skill->questions_count > 0 ? "{$skill->questions_count} questions" : "",
                    $skill->lessons_count > 0 ? "{$skill->lessons_count} lessons" : "",
                    $skill->videos_count > 0 ? "{$skill->videos_count} videos" : "",
                    $skill->practice_sets_count > 0 ? "{$skill->questions_count} practice sets" : ""
                ]));
                return redirect()->back()
                    ->with('errorMessage',
                        "Unable to delete skill as it is associated with {$associations}. Remove all associations and try again!"
                    );
            }

            DB::transaction(function () use ($skill) {
                $skill->practiceLessons()->detach();
                $skill->practiceVideos()->detach();
                $skill->secureDelete('topics', 'questions', 'practiceSets', 'lessons', 'videos');
            });
        }
        catch (\Illuminate\Database\QueryException $e) {
            return redirect()->back()->with('errorMessage', 'Unable to Delete Skill . Remove all associations and Try again!');
        }
        return redirect()->back()->with('successMessage', 'Skill was successfully deleted!');
    }
}
