<?php
/**
 * File name: ExamTypeFilters.php
 * Last modified: 17/03/22, 2:05 PM
 * Author: NearCraft - https://codecanyon.net/user/nearcraft
 * Copyright (c) 2022
 */

namespace App\Filters;

class ExamTypeFilters extends QueryFilter
{
    /*
    |--------------------------------------------------------------------------
    | DEFINE ALL COLUMN FILTERS BELOW
    |--------------------------------------------------------------------------
    */

    public function name($query)
    {
        return $this->builder->where('name', 'like', '%'.$query.'%');
    }

    public function code($query)
    {
        return $this->builder->where('code', 'like', '%'.$query.'%');
    }

    public function status($query = null)
    {
        return $this->builder->where('is_active', $query);
    }
}
