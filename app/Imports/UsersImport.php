<?php

namespace App\Imports;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Rules\Password;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class UsersImport implements ToCollection, WithHeadingRow, WithBatchInserts, WithChunkReading, WithValidation
{
    use Importable;
    private int $rows = 0;
    private string $now;

    public function __construct()
    {
        $this->now = Carbon::now()->toDateTimeString();
    }

    /**
     * Import users from excel
     *
     * @param Collection $rows
     * @return void
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $row)
        {
            ++$this->rows;
            User::create([
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'user_name' => $row['user_name'],
                'email' => $row['email'],
                'email_verified_at' => $row['email_verified'] == 'yes' ? $this->now : null,
                'password' => Hash::make($row['password']),
            ])->assignRole($row['role']);
        }
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'user_name' => ['required', 'string', 'max:60', 'unique:users'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', new Password()],
            'role' => ['required'],
        ];
    }

    public function batchSize(): int
    {
        return 100;
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function getRowCount(): int
    {
        return $this->rows;
    }
}
