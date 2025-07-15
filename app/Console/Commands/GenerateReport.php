<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ReportService;

class GenerateReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate assessment reports for a student';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $studentId = $this->ask('Please enter the Student ID');
        $reportType = $this->choice('Report to generate', ['Diagnostic', 'Progress', 'Feedback'], 0);

        $dataPath = base_path('storage/app/data/');
        $students = json_decode(file_get_contents($dataPath . 'students.json'), true);
        $assessments = json_decode(file_get_contents($dataPath . 'assessments.json'), true);
        $questions = json_decode(file_get_contents($dataPath . 'questions.json'), true);
        $responses = json_decode(file_get_contents($dataPath . 'student-responses.json'), true);

        $reportService = new ReportService();
        switch ($reportType) {
            case 'Diagnostic':
                $reportService->generateDiagnosticReport($studentId, $students, $assessments, $questions, $responses, $this);
                break;
            case 'Progress':
                $reportService->generateProgressReport($studentId, $students, $assessments, $questions, $responses, $this);
                break;
            case 'Feedback':
                $reportService->generateFeedbackReport($studentId, $students, $assessments, $questions, $responses, $this);
                break;
            default:
                $this->error('Invalid report type selected.');
                return Command::FAILURE;
        }
        return Command::SUCCESS;
    }
}
