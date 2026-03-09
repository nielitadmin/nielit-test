<?php
/**
 * File name: ExamSearchTransformer.php
 * Last modified: 18/06/21, 2:45 PM
 * Author: NearCraft - https://codecanyon.net/user/nearcraft
 * Copyright (c) 2021
 */

namespace App\Transformers\Admin;

use App\Models\Exam;
use League\Fractal\TransformerAbstract;

class ExamSearchTransformer extends TransformerAbstract
{
    /**
     * A Fractal transformer.
     *
     * @param Exam $exam
     * @return array
     */
    public function transform(Exam $exam)
    {
        return [
            'id' => $exam->id,
            'title' => $exam->title
        ];
    }
}
