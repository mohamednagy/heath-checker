<?php
namespace Nagy\HealthChecker\Tests;

use Illuminate\Support\Facades\Config;
use Nagy\HealthChecker\Checkers\Expression;
use Nagy\HealthChecker\Checkers\ProcessCount;
use Nagy\HealthChecker\Checkers\ServerAvailability;
use Nagy\HealthChecker\CheckService;
use Nagy\HealthChecker\HealthChecker;
use Nagy\HealthChecker\HealthCheckRunner;
use Nagy\HealthChecker\Result;

class CheckerTest extends TestCase
{
    /** @var CheckService */
    private $checkService;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testProcessCountChecker()
    {
        Config::set('health-check.checkers', [
            'php' => [
                "class" => ProcessCount::class,
                "options" => ['processName' => 'php', 'min' => 1]
            ]
        ]);

        $this->checkService = $this->app->make(HealthCheckRunner::class);

        $result = $this->checkService->run(new HealthChecker('php', config('health-check.checkers.php')));
        $this->assertEquals(Result::SUCCESS_STATUS, $result->toArray()['status']);


        Config::set('health-check.checkers', [
            'php' => [
                "class" => ProcessCount::class,
                "options" => ['processName' => 'notExistingProcess', 'min' => 1]
            ]
        ]);

        $result = $this->checkService->run(new HealthChecker('php', config('health-check.checkers.php')));
        $this->assertEquals(Result::ERROR_STATUS, $result->toArray()['status']);
    }


    public function testExpressionChecker()
    {
        Config::set('health-check.checkers', [
            'expression' => [
                "class" => Expression::class,
                'options' => [
                    'expression' => '1+1 == 2'
                ]
            ]
        ]);

        $this->checkService = $this->app->make(HealthCheckRunner::class);

        $result = $this->checkService->run(new HealthChecker('expression', config('health-check.checkers.expression')))->toArray();
        $this->assertEquals(Result::SUCCESS_STATUS, $result['status']);

        Config::set('health-check.checkers.expression.options.expression', '1+1 == 3');
        $result = $this->checkService->run(new HealthChecker('expression', config('health-check.checkers.expression')))->toArray();
        $this->assertEquals(Result::ERROR_STATUS, $result['status']);
    }

    public function testServerAvailabilityChecker()
    {
        Config::set('health-check.checkers', [
            'google' => [
                "class" => ServerAvailability::class,
                'options' => [
                    'host' => 'google.com'
                ]
            ]
        ]);

        $this->checkService = $this->app->make(HealthCheckRunner::class);

        $result = $this->checkService->run(new HealthChecker('google', config('health-check.checkers.google')))->toArray();
        $this->assertEquals(Result::SUCCESS_STATUS, $result['status']);

        Config::set('health-check.checkers.google.options.host', 'notExistingHost');
        $result = $this->checkService->run(new HealthChecker('google', config('health-check.checkers.google')))->toArray();
        $this->assertEquals(Result::ERROR_STATUS, $result['status']);
    }
}
