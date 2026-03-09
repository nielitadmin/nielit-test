<?php
/**
 * File name: ExamTypeTransformer.php
 * Last modified: 25/05/22, 5:10 PM
 * Author: NearCraft - https://codecanyon.net/user/nearcraft
 * Copyright (c) 2022
 */


namespace App\Transformers\Platform;

use App\Models\ExamType;
use Illuminate\Support\Str;
use League\Fractal\TransformerAbstract;

class ExamTypeTransformer extends TransformerAbstract
{
    public function transform(ExamType $examType)
    {
        return [
            'slug' => $examType->slug,
            'code' => $examType->code,
            'name' => Str::plural($examType->name),
            'color' => '#'.$examType->color,
            'image' => $examType->image_path,
        ];
    }
}


