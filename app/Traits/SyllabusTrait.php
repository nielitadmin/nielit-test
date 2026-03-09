<?php
/**
 * File name: SyllabusTrait.php
 * Last modified: 02/02/22, 4:08 PM
 * Author: NearCraft - https://codecanyon.net/user/nearcraft
 * Copyright (c) 2022
 */

namespace App\Traits;

use App\Models\SubCategory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Cookie;

trait SyllabusTrait
{
    /**
     * Get user selected syllabus
     *
     */
    public function selectedSyllabus()
    {
        try {
            return SubCategory::findOrFail(Cookie::get('category_id'));
        } catch (ModelNotFoundException $exception) {
            return redirect()->route('change_syllabus')->send();
        }
    }
}
