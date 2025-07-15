<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\ReportService;

class OutputMock {
    public $lines = [];
    public $errors = [];
    public function line($msg) { $this->lines[] = $msg; }
    public function error($msg) { $this->errors[] = $msg; }
}

class ReportServiceTest extends TestCase
{
    private function getOutputMock()
    {
        return new OutputMock();
    }

    public function testDiagnosticReportStudentNotFound()
    {
        $service = new ReportService();
        $output = $this->getOutputMock();
        $service->generateDiagnosticReport('999', [], [], [], [], $output);
        $this->assertContains('Student not found.', $output->errors);
    }

    public function testDiagnosticReportNoCompletedAssessments()
    {
        $service = new ReportService();
        $output = $this->getOutputMock();
        $students = [['id' => '1', 'firstName' => 'John', 'lastName' => 'Doe']];
        $service->generateDiagnosticReport('1', $students, [], [], [], $output);
        $this->assertContains('No completed assessments found for this student.', $output->errors);
    }

    public function testDiagnosticReportOutputsSummary()
    {
        $service = new ReportService();
        $output = $this->getOutputMock();
        $students = [['id' => '1', 'firstName' => 'John', 'lastName' => 'Doe']];
        $assessments = [['id' => 'a1', 'name' => 'Math']];
        $questions = [['id' => 'q1', 'strand' => 'Algebra', 'config' => ['key' => 'A']]];
        $responses = [[
            'student' => ['id' => '1'],
            'assessmentId' => 'a1',
            'completed' => '01/01/2024 10:00:00',
            'responses' => [ ['questionId' => 'q1', 'response' => 'A'] ]
        ]];
        $service->generateDiagnosticReport('1', $students, $assessments, $questions, $responses, $output);
        $this->assertNotEmpty($output->lines);
        $this->assertStringContainsString('John Doe recently completed Math assessment on', $output->lines[0]);
    }

    public function testProgressReportStudentNotFound()
    {
        $service = new ReportService();
        $output = $this->getOutputMock();
        $service->generateProgressReport('999', [], [], [], $output);
        $this->assertContains('Student not found.', $output->errors);
    }

    public function testProgressReportNoCompletedAssessments()
    {
        $service = new ReportService();
        $output = $this->getOutputMock();
        $students = [['id' => '1', 'firstName' => 'John', 'lastName' => 'Doe']];
        $service->generateProgressReport('1', $students, [], [], $output);
        $this->assertContains('No completed assessments found for this student.', $output->errors);
    }

    public function testFeedbackReportStudentNotFound()
    {
        $service = new ReportService();
        $output = $this->getOutputMock();
        $service->generateFeedbackReport('999', [], [], [], [], $output);
        $this->assertContains('Student not found.', $output->errors);
    }
}
