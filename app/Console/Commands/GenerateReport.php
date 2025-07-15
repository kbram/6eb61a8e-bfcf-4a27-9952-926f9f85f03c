<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

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

        switch ($reportType) {
            case 'Diagnostic':
                $this->generateDiagnosticReport($studentId, $students, $assessments, $questions, $responses);
                break;
            case 'Progress':
                $this->generateProgressReport($studentId, $students, $assessments, $questions, $responses);
                break;
            case 'Feedback':
                $this->generateFeedbackReport($studentId, $students, $assessments, $questions, $responses);
                break;
        }
        return Command::SUCCESS;
    }

    private function generateDiagnosticReport($studentId, $students, $assessments, $questions, $responses)
    {
        $student = collect($students)->firstWhere('id', $studentId);
        if (!$student) {
            $this->error('Student not found.');
            return;
        }
        $studentResponses = collect($responses)
            ->where('student.id', $studentId)
            ->whereNotNull('completed')
            ->sortByDesc(function($r) {
                return \DateTime::createFromFormat('d/m/Y H:i:s', $r['completed']);
            });
        $latestResponse = $studentResponses->first();
        if (!$latestResponse) {
            $this->error('No completed assessments found for this student.');
            return;
        }
        $assessment = collect($assessments)->firstWhere('id', $latestResponse['assessmentId']);
        $assessmentName = $assessment ? $assessment['name'] : 'Assessment';
        $completedDate = \DateTime::createFromFormat('d/m/Y H:i:s', $latestResponse['completed']);
        $dateStr = $completedDate ? $completedDate->format('jS F Y h:i A') : $latestResponse['completed'];
        $totalQuestions = count($latestResponse['responses']);
        $correctCount = 0;
        $strandStats = [];
        foreach ($latestResponse['responses'] as $resp) {
            $question = collect($questions)->firstWhere('id', $resp['questionId']);
            if (!$question) continue;
            $strand = $question['strand'];
            $isCorrect = $resp['response'] === $question['config']['key'];
            if (!isset($strandStats[$strand])) {
                $strandStats[$strand] = ['correct' => 0, 'total' => 0];
            }
            $strandStats[$strand]['total']++;
            if ($isCorrect) {
                $strandStats[$strand]['correct']++;
                $correctCount++;
            }
        }
        $this->line("{$student['firstName']} {$student['lastName']} recently completed {$assessmentName} assessment on {$dateStr}");
        $this->line("He got {$correctCount} questions right out of {$totalQuestions}. Details by strand given below:");
        foreach ($strandStats as $strand => $stat) {
            $this->line("{$strand}: {$stat['correct']} out of {$stat['total']} correct");
        }
    }

    private function generateProgressReport($studentId, $students, $assessments, $questions, $responses)
    {
        $student = collect($students)->firstWhere('id', $studentId);
        if (!$student) {
            $this->error('Student not found.');
            return;
        }
        $studentResponses = collect($responses)
            ->where('student.id', $studentId)
            ->whereNotNull('completed')
            ->sortBy(function($r) {
                return \DateTime::createFromFormat('d/m/Y H:i:s', $r['completed']);
            });
        if ($studentResponses->isEmpty()) {
            $this->error('No completed assessments found for this student.');
            return;
        }
        $assessment = collect($assessments)->firstWhere('id', $studentResponses->first()['assessmentId']);
        $assessmentName = $assessment ? $assessment['name'] : 'Assessment';
        $this->line("{$student['firstName']} {$student['lastName']} has completed {$assessmentName} assessment {$studentResponses->count()} times in total. Date and raw score given below:");
        $scores = [];
        foreach ($studentResponses as $resp) {
            $completedDate = \DateTime::createFromFormat('d/m/Y H:i:s', $resp['completed']);
            $dateStr = $completedDate ? $completedDate->format('jS F Y') : $resp['completed'];
            $rawScore = isset($resp['results']['rawScore']) ? $resp['results']['rawScore'] : 0;
            $totalQuestions = count($resp['responses']);
            $scores[] = $rawScore;
            $this->line("Date: {$dateStr}, Raw Score: {$rawScore} out of {$totalQuestions}");
        }
        if (count($scores) > 1) {
            $diff = $scores[count($scores)-1] - $scores[0];
            $this->line("\n{$student['firstName']} {$student['lastName']} got {$diff} more correct in the recent completed assessment than the oldest");
        }
    }

    private function generateFeedbackReport($studentId, $students, $assessments, $questions, $responses)
    {
        $student = collect($students)->firstWhere('id', $studentId);
        if (!$student) {
            $this->error('Student not found.');
            return;
        }
        $studentResponses = collect($responses)
            ->where('student.id', $studentId)
            ->whereNotNull('completed')
            ->sortByDesc(function($r) {
                return \DateTime::createFromFormat('d/m/Y H:i:s', $r['completed']);
            });
        $latestResponse = $studentResponses->first();
        if (!$latestResponse) {
            $this->error('No completed assessments found for this student.');
            return;
        }
        $assessment = collect($assessments)->firstWhere('id', $latestResponse['assessmentId']);
        $assessmentName = $assessment ? $assessment['name'] : 'Assessment';
        $completedDate = \DateTime::createFromFormat('d/m/Y H:i:s', $latestResponse['completed']);
        $dateStr = $completedDate ? $completedDate->format('jS F Y h:i A') : $latestResponse['completed'];
        $totalQuestions = count($latestResponse['responses']);
        $correctCount = 0;
        $wrongAnswers = [];
        foreach ($latestResponse['responses'] as $resp) {
            $question = collect($questions)->firstWhere('id', $resp['questionId']);
            if (!$question) continue;
            $isCorrect = $resp['response'] === $question['config']['key'];
            if ($isCorrect) {
                $correctCount++;
            } else {
                $option = collect($question['config']['options'])->firstWhere('id', $resp['response']);
                $rightOption = collect($question['config']['options'])->firstWhere('id', $question['config']['key']);
                $wrongAnswers[] = [
                    'stem' => $question['stem'],
                    'yourLabel' => $option ? $option['label'] : $resp['response'],
                    'yourValue' => $option ? $option['value'] : '',
                    'rightLabel' => $rightOption ? $rightOption['label'] : $question['config']['key'],
                    'rightValue' => $rightOption ? $rightOption['value'] : '',
                    'hint' => $question['config']['hint']
                ];
            }
        }
        $this->line("{$student['firstName']} {$student['lastName']} recently completed {$assessmentName} assessment on {$dateStr}");
        $this->line("He got {$correctCount} questions right out of {$totalQuestions}. Feedback for wrong answers given below\n");
        foreach ($wrongAnswers as $wrong) {
            $this->line("Question: {$wrong['stem']}");
            $this->line("Your answer: {$wrong['yourLabel']} with value {$wrong['yourValue']}");
            $this->line("Right answer: {$wrong['rightLabel']} with value {$wrong['rightValue']}");
            $this->line("Hint: {$wrong['hint']}\n");
        }
    }
}
