<?php
declare(strict_types=1);

namespace Zalas\PHPUnit\Globals\Tests;

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

/**
 * @env AVAILABLE_IN_SETUP=foo
 * @env APP_ENV=test
 * @server APP_DEBUG=0
 * @putenv APP_HOST=localhost
 */
class AnnotationExtensionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        self::assertArrayHasKey('AVAILABLE_IN_SETUP', $_ENV);
        self::assertSame('foo', $_ENV['AVAILABLE_IN_SETUP']);
    }

    /**
     * @env APP_ENV=test_foo
     * @server APP_DEBUG=1
     * @putenv APP_HOST=dev
     */
    public function test_it_reads_global_variables_from_method_annotations(): void
    {
        self::assertArraySubset(['APP_ENV' => 'test_foo'], $_ENV);
        self::assertArraySubset(['APP_DEBUG' => '1'], $_SERVER);
        self::assertArraySubset(['APP_HOST' => 'dev'], \getenv());
    }

    public function test_it_reads_global_variables_from_class_annotations(): void
    {
        self::assertArraySubset(['APP_ENV' => 'test'], $_ENV);
        self::assertArraySubset(['APP_DEBUG' => '0'], $_SERVER);
        self::assertArraySubset(['APP_HOST' => 'localhost'], \getenv());
    }

    /**
     * @env FOO=foo
     * @server BAR=bar
     * @putenv BAZ=baz
     */
    public function test_it_reads_additional_global_variables_from_methods(): void
    {
        self::assertArraySubset(['APP_ENV' => 'test'], $_ENV);
        self::assertArraySubset(['APP_DEBUG' => '0'], $_SERVER);
        self::assertArraySubset(['APP_HOST' => 'localhost'], \getenv());
        self::assertArraySubset(['FOO' => 'foo'], $_ENV);
        self::assertArraySubset(['BAR' => 'bar'], $_SERVER);
        self::assertArraySubset(['BAZ' => 'baz'], \getenv());
    }

    /**
     * @env APP_ENV=test_foo
     * @env APP_ENV=test_foo_bar
     * @server APP_DEBUG=1
     * @server APP_DEBUG=2
     * @putenv APP_HOST=host1
     * @putenv APP_HOST=host2
     */
    public function test_it_reads_the_latest_var_defined(): void
    {
        self::assertArraySubset(['APP_ENV' => 'test_foo_bar'], $_ENV);
        self::assertArraySubset(['APP_DEBUG' => '2'], $_SERVER);
        self::assertArraySubset(['APP_HOST' => 'host2'], \getenv());
    }

    /**
     * @env APP_ENV
     * @server APP_DEBUG
     * @putenv APP_HOST
     */
    public function test_it_reads_empty_vars(): void
    {
        self::assertArraySubset(['APP_ENV' => ''], $_ENV);
        self::assertArraySubset(['APP_DEBUG' => ''], $_SERVER);
        self::assertArraySubset(['APP_HOST' => ''], \getenv());
    }

    /**
     * @unset-env APP_ENV
     * @unset-server APP_DEBUG
     * @unset-getenv APP_HOST
     * @unset-env USER
     */
    public function test_it_unsets_vars(): void
    {
        $this->assertArrayNotHasKey('USER', $_ENV);
        $this->assertArrayNotHasKey('APP_ENV', $_ENV);
        $this->assertArrayNotHasKey('APP_DEBUG', $_SERVER);
        $this->assertArrayNotHasKey('APP_HOST', \getenv());
    }

    public function test_it_backups_the_state(): void
    {
        // this test is only here so the next one could verify the state is brought back

        $_ENV['FOO'] = 'env_foo';
        $_SERVER['BAR'] = 'server_bar';
        \putenv('FOO=putenv_foo');
        \putenv('USER=foobar');

        $this->assertArrayHasKey('FOO', $_ENV);
        $this->assertArrayHasKey('BAR', $_SERVER);
        $this->assertSame('putenv_foo', \getenv('FOO'));
        $this->assertSame('foobar', \getenv('USER'));
    }

    #[Depends('test_it_backups_the_state')]
    public function test_it_cleans_up_after_itself(): void
    {
        $this->assertArrayNotHasKey('FOO', $_ENV);
        $this->assertArrayNotHasKey('BAR', $_SERVER);
        $this->assertFalse(\getenv('FOO'), 'It removes environment variables initialised in a test.');
        $this->assertNotSame('foobar', \getenv('USER'), 'It restores environment variables changed in a test.');
        $this->assertNotFalse(\getenv('USER'), 'It restores environment variables changed in a test.');
    }

    /**
     * Provides a replacement for the assertion deprecated in PHPUnit 8 and removed in PHPUnit 9.
     * @param array $subset
     * @param array $array
     */
    public static function assertArraySubset($subset, $array, bool $checkForObjectIdentity = false, string $message = ''): void
    {
        self::assertSame($array, \array_replace_recursive($array, $subset));
    }
}
