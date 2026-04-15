<?php

namespace App\Console\Commands;

use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class BackfillStudentUuids extends Command
{
    protected $signature   = 'students:backfill-uuids {--school= : Limit to a specific school_id}';
    protected $description = 'Generate UUIDs for any students that are missing one';

    public function handle(): int
    {
        $query = Student::whereNull('uuid');

        if ($schoolId = $this->option('school')) {
            $query->where('school_id', $schoolId);
        }

        $count = $query->count();

        if ($count === 0) {
            $this->info('All students already have UUIDs.');
            return 0;
        }

        $this->info("Backfilling UUIDs for {$count} student(s)...");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $query->chunkById(200, function ($students) use ($bar) {
            foreach ($students as $student) {
                $student->timestamps = false;
                $student->uuid = (string) Str::uuid();
                $student->save();
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info('Done.');

        return 0;
    }
}
