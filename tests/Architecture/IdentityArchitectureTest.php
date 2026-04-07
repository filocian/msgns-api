<?php

declare(strict_types=1);

describe('Identity module architecture', function () {
	it('does not import Illuminate classes inside Identity Domain', function () {
		$directory = new RecursiveDirectoryIterator(__DIR__ . '/../../src/Identity/Domain');
		$iterator = new RecursiveIteratorIterator($directory);

		foreach ($iterator as $file) {
			if (! $file->isFile() || $file->getExtension() !== 'php') {
				continue;
			}

			$content = file_get_contents($file->getPathname());

			expect($content)->not->toContain(
				'use Illuminate\\',
				"File {$file->getPathname()} must not import Illuminate classes inside Identity Domain"
			);
		}
	});

	it('does not import App classes inside Identity Domain', function () {
		$directory = new RecursiveDirectoryIterator(__DIR__ . '/../../src/Identity/Domain');
		$iterator = new RecursiveIteratorIterator($directory);

		foreach ($iterator as $file) {
			if (! $file->isFile() || $file->getExtension() !== 'php') {
				continue;
			}

			$content = file_get_contents($file->getPathname());

			expect($content)->not->toContain(
				'use App\\',
				"File {$file->getPathname()} must not import App classes inside Identity Domain"
			);
		}
	});

	it('does not import Illuminate classes inside Identity Application', function () {
		$directory = new RecursiveDirectoryIterator(__DIR__ . '/../../src/Identity/Application');
		$iterator = new RecursiveIteratorIterator($directory);

		foreach ($iterator as $file) {
			if (! $file->isFile() || $file->getExtension() !== 'php') {
				continue;
			}

			$content = file_get_contents($file->getPathname());

			expect($content)->not->toContain(
				'use Illuminate\\',
				"File {$file->getPathname()} must not import Illuminate classes inside Identity Application"
			);
		}
	});

	// ─── Application layer — no App\* ────────────────────────────────────────

	it('does not import App classes inside Identity Application', function () {
		$directory = new RecursiveDirectoryIterator(__DIR__ . '/../../src/Identity/Application');
		$iterator = new RecursiveIteratorIterator($directory);

		foreach ($iterator as $file) {
			if (! $file->isFile() || $file->getExtension() !== 'php') {
				continue;
			}

			$content = file_get_contents($file->getPathname());
			assert(is_string($content));

			expect($content)->not->toContain(
				'use App\\',
				"File {$file->getPathname()} must not import App classes inside Identity Application"
			);
		}
	});

	// ─── Domain layer — no Application\* (dependency inversion) ─────────────

	it('does not import Application classes inside Identity Domain', function () {
		$directory = new RecursiveDirectoryIterator(__DIR__ . '/../../src/Identity/Domain');
		$iterator = new RecursiveIteratorIterator($directory);

		foreach ($iterator as $file) {
			if (! $file->isFile() || $file->getExtension() !== 'php') {
				continue;
			}

			$content = file_get_contents($file->getPathname());
			assert(is_string($content));

			expect($content)->not->toContain(
				'use Src\\Identity\\Application\\',
				"File {$file->getPathname()} must not import Application classes inside Identity Domain"
			);
		}
	});

	// ─── Route ownership: all identity routes point to Src\ controllers ──────

	it('routes file contains no App\\Http\\Controllers\\Identity imports', function () {
		$routesContent = file_get_contents(__DIR__ . '/../../routes/api/identity.php');
		assert(is_string($routesContent));

		expect($routesContent)->not->toContain('use App\\Http\\Controllers\\Identity\\');
	});

	// ─── V2 controller contract tests ───────────────────────────────────────

	$v2Controllers = [
		'SignUpController',
		'LoginController',
		'GoogleLoginController',
		'RequestVerificationController',
		'VerifyEmailController',
		'ConfirmEmailChangeController',
		'RequestPasswordResetController',
		'ResetPasswordController',
		'GetCurrentUserController',
		'UpdateMyProfileController',
		'ChangeMyPasswordController',
		'RequestEmailChangeController',
		'CancelPendingEmailChangeController',
		'LogoutController',
		'AdminListUsersController',
		'AdminExportUsersController',
		'AdminGetUserController',
		'AdminUpdateUserController',
		'AdminSetPasswordController',
		'AdminSetEmailVerifiedController',
		'AdminDeactivateUserController',
		'AdminActivateUserController',
		'AdminBulkVerifyEmailController',
		'AdminBulkChangeEmailController',
		'AdminBulkActivationController',
		'AdminBulkAssignRolesController',
		'AdminBulkPasswordResetController',
		'AdminListRolesController',
		'AdminCreateRoleController',
		'AdminUpdateRoleController',
		'AdminDeleteRoleController',
		'AdminAssignRoleToUserController',
		'AdminRemoveRoleFromUserController',
		'AdminListPermissionsController',
		'StartImpersonationController',
		'StopImpersonationController',
	];

	foreach ($v2Controllers as $controller) {
		it("does not import forbidden App classes inside {$controller}", function () use ($controller) {
			$controllerContent = file_get_contents(
				__DIR__ . "/../../src/Identity/Infrastructure/Http/Controllers/{$controller}.php"
			);
			assert(is_string($controllerContent));

			// App\Http\Contracts\Controller is the ONLY allowed App\ import
			preg_match_all('/^use (App\\\\[^;]+);$/m', $controllerContent, $matches);

			$allowedImports = ['App\\Http\\Contracts\\Controller'];
			$forbidden = array_diff($matches[1], $allowedImports);

			expect($forbidden)->toBeEmpty(
				"Controller {$controller} has forbidden App\\ imports: " . implode(', ', $forbidden)
			);
		});
	}
});
