<?php

namespace App\Services;

class ReportService
{
    public function generateDiagnosticReport($studentId, $students, $assessments, $questions, $responses, $output)
    {
        $student = collect($students)->firstWhere('id', $studentId);
        if (!$student) {
            $output->error('Student not found.');
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
            $output->error('No completed assessments found for this student.');
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
        $output->line("{$student['firstName']} {$student['lastName']} recently completed {$assessmentName} assessment on {$dateStr}");
        $output->line("He got {$correctCount} questions right out of {$totalQuestions}. Details by strand given below:");
        foreach ($strandStats as $strand => $stat) {
            $output->line("{$strand}: {$stat['correct']} out of {$stat['total']} correct");
        }
    }

    public function generateProgressReport($studentId, $students, $assessments, $responses, $output)
    {
        $student = collect($students)->firstWhere('id', $studentId);
        if (!$student) {
            $output->error('Student not found.');
            return;
        }
        $studentResponses = collect($responses)
            ->where('student.id', $studentId)
            ->whereNotNull('completed')
            ->sortBy(function($r) {
                return \DateTime::createFromFormat('d/m/Y H:i:s', $r['completed']);
            });
        if ($studentResponses->isEmpty()) {
            $output->error('No completed assessments found for this student.');
            return;
        }
        $assessment = collect($assessments)->firstWhere('id', $studentResponses->first()['assessmentId']);
        $assessmentName = $assessment ? $assessment['name'] : 'Assessment';
        $output->line("{$student['firstName']} {$student['lastName']} has completed {$assessmentName} assessment {$studentResponses->count()} times in total. Date and raw score given below:");
        $scores = [];
        foreach ($studentResponses as $resp) {
            $completedDate = \DateTime::createFromFormat('d/m/Y H:i:s', $resp['completed']);
            $dateStr = $completedDate ? $completedDate->format('jS F Y') : $resp['completed'];
            $rawScore = isset($resp['results']['rawScore']) ? $resp['results']['rawScore'] : 0;
            $totalQuestions = count($resp['responses']);
            $scores[] = $rawScore;
            $output->line("Date: {$dateStr}, Raw Score: {$rawScore} out of {$totalQuestions}");
        }
        if (count($scores) > 1) {
            $diff = $scores[count($scores)-1] - $scores[0];
            $output->line("\n{$student['firstName']} {$student['lastName']} got {$diff} more correct in the recent completed assessment than the oldest");
        }
    }

    public function generateFeedbackReport($studentId, $students, $assessments, $questions, $responses, $output)
    {
        $student = collect($students)->firstWhere('id', $studentId);
        if (!$student) {
            $output->error('Student not found.');
            return;
        }

        $studentResponses = $this->getCompletedResponses($responses, $studentId);
        $latestResponse = $studentResponses->first();
        if (!$latestResponse) {
            $output->error('No completed assessments found for this student.');
            return;
        }

        $assessmentName = $this->getAssessmentName($assessments, $latestResponse['assessmentId']);
        $dateStr = $this->getFormattedDate($latestResponse['completed']);
        $totalQuestions = count($latestResponse['responses']);

        [$correctCount, $wrongAnswers] = $this->processResponses($latestResponse['responses'], $questions);

        $output->line("{$student['firstName']} {$student['lastName']} recently completed {$assessmentName} assessment on {$dateStr}");
        $output->line("He got {$correctCount} questions right out of {$totalQuestions}. Feedback for wrong answers given below\n");
        $this->outputWrongAnswers($wrongAnswers, $output);
    }

    private function getCompletedResponses($responses, $studentId)
    {
        return collect($responses)
            ->where('student.id', $studentId)
            ->whereNotNull('completed')
            ->sortByDesc(function($r) {
                return \DateTime::createFromFormat('d/m/Y H:i:s', $r['completed']);
            });
    }

    private function getAssessmentName($assessments, $assessmentId)
    {
        $assessment = collect($assessments)->firstWhere('id', $assessmentId);
        return $assessment ? $assessment['name'] : 'Assessment';
    }

    private function getFormattedDate($completed)
    {
        $completedDate = \DateTime::createFromFormat('d/m/Y H:i:s', $completed);
        return $completedDate ? $completedDate->format('jS F Y h:i A') : $completed;
    }

    private function processResponses($responses, $questions)
    {
        $correctCount = 0;
        $wrongAnswers = [];
        foreach ($responses as $resp) {
            $question = collect($questions)->firstWhere('id', $resp['questionId']);
            if (!$question) continue;
            if ($resp['response'] === $question['config']['key']) {
                $correctCount++;
            } else {
                $wrongAnswers[] = $this->getWrongAnswerDetails($resp, $question);
            }
        }
        return [$correctCount, $wrongAnswers];
    }

    private function getWrongAnswerDetails($resp, $question)
    {
        $option = collect($question['config']['options'])->firstWhere('id', $resp['response']);
        $rightOption = collect($question['config']['options'])->firstWhere('id', $question['config']['key']);
        return [
            'stem' => $question['stem'],
            'yourLabel' => $option ? $option['label'] : $resp['response'],
            'yourValue' => $option ? $option['value'] : '',
            'rightLabel' => $rightOption ? $rightOption['label'] : $question['config']['key'],
            'rightValue' => $rightOption ? $rightOption['value'] : '',
            'hint' => $question['config']['hint']
        ];
    }

    private function outputWrongAnswers($wrongAnswers, $output)
    {
        foreach ($wrongAnswers as $wrong) {
            $output->line("Question: {$wrong['stem']}");
            $output->line("Your answer: {$wrong['yourLabel']} with value {$wrong['yourValue']}");
            $output->line("Right answer: {$wrong['rightLabel']} with value {$wrong['rightValue']}");
            $output->line("Hint: {$wrong['hint']}\n");
        }
    }
}
