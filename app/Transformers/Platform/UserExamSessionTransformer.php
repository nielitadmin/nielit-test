<?php
/**
 * File name: UserExamSessionTransformer.php
 * Last modified: 16/07/21, 11:36 PM
 * Author: NearCraft - https://codecanyon.net/user/nearcraft
 * Copyright (c) 2021
 */

namespace App\Transformers\Platform;

use App\Models\ExamSession;
use League\Fractal\TransformerAbstract;

class UserExamSessionTransformer extends TransformerAbstract
{
    /**
     * A Fractal transformer.
     *
     * @param ExamSession $session
     * @return array
     */
    public function transform(ExamSession $session)
    {
        return [
            'id' => $session->code,
            'slug' => $session->exam->slug,
            'name' => $session->exam->title,
            'accuracy' => $session->results->accuracy,
            'percentage' => $session->results->percentage.'%',
            'score' => $session->results->score.'/'.$session->results->total_marks,
            'status' => $session->results->pass_or_fail,
            'completed' => $session->completed_at->toFormattedDateString()
        ];
    }
}
