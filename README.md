# Assessment Report Generator

This application provides a command-line tool for generating student assessment reports using Laravel. The report logic is refactored into a dedicated service for maintainability and testability.

## Features

-   Generate diagnostic, progress, and feedback reports for students based on assessment data.
-   Report logic is encapsulated in `App\Services\ReportService`.
-   Automated tests for main report features using PHPUnit.

## Usage

### Without Docker

Run the report generator via artisan:

```
php artisan generate:report
```

### With Docker Compose

Build containers (first time or after changes):

```
docker-compose build
```

Run the Laravel application:

```
docker-compose up app
```

Run the report generator command:

```
docker-compose run app php artisan generate:report
```

## Testing

Automated tests are provided in `tests/Unit/ReportServiceTest.php`.

### Without Docker

Run tests with:

```
php artisan test --filter=ReportServiceTest
```

or

```
vendor/bin/phpunit tests/Unit/ReportServiceTest.php
```

### With Docker Compose

Run tests with:

```
docker-compose run test
```

### Sample Test Cases

-   Error if student not found
-   Error if no completed assessments
-   Output summary for valid diagnostic report
-   Output summary for progress report
-   Output feedback for wrong answers

## Technologies Used

-   Laravel (Console Commands, Service classes)
-   PHPUnit (Unit testing)
-   Docker, Docker Compose

## Project Structure

-   `app/Console/Commands/GenerateReport.php`: Console command for report generation
-   `app/Services/ReportService.php`: Service class for report logic
-   `tests/Unit/ReportServiceTest.php`: Automated tests for report features
-   `Dockerfile`, `docker-compose.yml`: Docker setup for app and tests

---

For further customization or extension, add more report types or expand the test coverage as needed.
