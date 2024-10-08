<?php

declare(strict_types=1);

namespace Syntatis\Tests\Projects\Howdy;

use PHPUnit\Framework\TestCase;
use Syntatis\Codex\Companion\Projects\Howdy\ProjectFiles;
use Syntatis\Tests\WithTemporaryFiles;

use function count;

class ProjectFilesTest extends TestCase
{
	use WithTemporaryFiles;

	public function setUp(): void
	{
		parent::setUp();

		self::setUpTemporaryPath();
	}

	public function tearDown(): void
	{
		parent::tearDown();

		self::tearDownTemporaryPath();
	}

	public function testIteratedFiles(): void
	{
		self::createTempFiles([
			'composer.json',
			'src/index.js',
			'foo.php',
			'bar/hello-world.php',
			'package.json',
			'phpcs.xml.dist',
			// Should be ignored
			'.editorconfig',
			'.eslintrc.json',
			'.gitignore',
			'LICENSE',
			'composer.lock',
			'dist-autoload/autoload.php',
			'dist/index.js',
			'node_modules/react/main.js',
			'package-lock.json',
			'vendor/autoload.php',
		]);

		$projectFiles = new ProjectFiles(self::$tempDir);

		self::assertSame(6, count($projectFiles));
		foreach ($projectFiles as $projectFile) {
			self::assertNotContains(
				$projectFile->getRealPath(),
				[
					self::$tempDir . '/.editorconfig',
					self::$tempDir . '/.eslintrc.json',
					self::$tempDir . '/.gitignore',
					self::$tempDir . '/LICENSE',
					self::$tempDir . '/composer.lock',
					self::$tempDir . '/dist-autoload/autoload.php',
					self::$tempDir . '/dist/index.js',
					self::$tempDir . '/node_modules/react/main.js',
					self::$tempDir . '/package-lock.json',
					self::$tempDir . '/vendor/autoload.php',
				],
			);
		}
	}

	private static function createTempFiles(array $files): void
	{
		foreach ($files as $file) {
			self::$filesystem->dumpFile(self::$tempDir . '/' . $file, '');
		}
	}
}
