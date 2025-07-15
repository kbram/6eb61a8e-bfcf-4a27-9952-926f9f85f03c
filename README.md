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

## Continuous Integration (CI)

This project uses GitHub Actions for CI. On every push or pull request to `main`, tests are run automatically using the workflow defined in `.github/workflows/ci.yml`.

To require tests to pass before merging:

1.  Go to your repository's **Settings > Branches**.
2.  Add a branch protection rule for `main`.
3.  Enable **Require status checks to pass before merging** and select the `CI` workflow.
4.  Optionally, enable **Require branches to be up to date before merging**.

## Technologies Used

-   Laravel (Console Commands, Service classes)
-   PHPUnit (Unit testing)
-   Docker, Docker Compose
-   GitHub Actions (Continuous Integration)

## Project Structure

-   `app/Console/Commands/GenerateReport.php`: Console command for report generation
-   `app/Services/ReportService.php`: Service class for report logic
-   `tests/Unit/ReportServiceTest.php`: Automated tests for report features
-   `Dockerfile`, `docker-compose.yml`: Docker setup for app and tests
-   `.github/workflows/ci.yml`: GitHub Actions workflow for CI

---

For further customization or extension, add more report types or expand the test coverage as needed.
