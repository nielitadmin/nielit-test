<?php
/**
 * File name: ExamScheduleDetailTransformer.php
 * Last modified: 18/07/21, 12:04 AM
 * Author: NearCraft - https://codecanyon.net/user/nearcraft
 * Copyright (c) 2021
 */

namespace App\Transformers\Platform;

use App\Models\ExamSchedule;
use League\Fractal\TransformerAbstract;

class ExamScheduleDetailTransformer extends TransformerAbstract
{
    /**
     * A Fractal transformer.
     *
     * @param ExamSchedule $schedule
     * @return array
     */
    public function transform(ExamSchedule $schedule)
    {
        return [
            'code' => $schedule->code,
            'scheduleType' => ucfirst($schedule->schedule_type),
            'starts_at' => $schedule->starts_at_formatted,
            'ends_at' => $schedule->ends_at_formatted,
            'timezone' => $schedule->timezone,
            'status' => $schedule->status
        ];
    }
}
