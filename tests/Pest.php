<?php

declare(strict_types=1);

use Database\Seeders\RolePermissionsSeeder;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class)->in('Feature');
uses(TestCase::class)->in('Unit');

/*
|--------------------------------------------------------------------------
| Legacy test convention
|--------------------------------------------------------------------------
|
| Default CI runs `composer test`, which excludes only the explicit `legacy`
| group. Every legacy-only test must therefore (1) live in the inventory at
| `tests/Support/LegacyTestInventory.php` and (2) mark its cases with
| `->group('legacy')` in the test source file.
|
*/

beforeEach(function (): void {
	/** @var TestCase $this */
	if (str_contains((string) $this::class, 'Feature')) {
		$this->seed(RolePermissionsSeeder::class);
	}
});

//uses(
//	Tests\TestCase::class,
//// Illuminate\Foundation\Testing\RefreshDatabase::class,
//)->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/



/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/
