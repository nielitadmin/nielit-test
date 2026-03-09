<?php
/**
 * File name: UserImportController.php
 * Last modified: 19/07/21, 12:55 AM
 * Author: NearCraft - https://codecanyon.net/user/nearcraft
 * Copyright (c) 2021
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportUsersRequest;
use App\Imports\QuestionsImport;
use App\Imports\UsersImport;
use App\Models\DifficultyLevel;
use App\Models\QuestionType;
use App\Models\Skill;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserImportController extends Controller
{
    public function __construct()
    {
        $this->middleware(['role:admin']);
    }

    /**
     * Import users page
     *
     * @return \Inertia\Response
     */
    public function initiateImport()
    {
        return Inertia::render('Admin/ImportUsers', [
            'userRoles' => [
                ['code' => 'admin', 'name' => 'Admin'],
                ['code' => 'instructor', 'name' => 'Instructor'],
                ['code' => 'student', 'name' => 'Student'],
                ['code' => 'guest', 'name' => 'Guest'],
            ],
            'sampleFileUrl' => url('assets/sample-users-upload.xlsx')
        ]);
    }

    /**
     * Import users from excel sheet
     *
     * @param ImportUsersRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function import(ImportUsersRequest $request)
    {
        if(config('qwiktest.demo_mode')) {
            return redirect()->back()->with('errorMessage', 'Demo Mode! Files can\'t be imported.');
        }

        $file = $request->file('file')->store('imports');

        try {
            $import = new UsersImport();
            $import->import($file);
        } catch (\Exception $exception) {
            return redirect()->back()->with('errorMessage', 'Oops! Upload Failed. '.$exception->getMessage().' Please check all rows are entered accurately.');
        }

        return redirect()->route('users.index')
            ->with('successMessage', $import->getRowCount().' users were imported successfully.');
    }
}
