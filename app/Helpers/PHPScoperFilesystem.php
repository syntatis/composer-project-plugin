<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Helpers;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Syntatis\Codex\Companion\Codex;
use Syntatis\Utils\Val;

use function array_filter;
use function array_map;
use function in_array;
use function is_array;
use function is_string;
use function json_encode;
use function md5;
use function time;
use function trim;

use const ARRAY_FILTER_USE_BOTH;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;

class PHPScoperFilesystem
{
	private Filesystem $filesystem;

	private Codex $codex;

	private string $hash;

	private string $outputPath;

	public function __construct(Codex $codex)
	{
		$this->codex = $codex;
		$this->hash = md5((string) time());
		$this->filesystem = new Filesystem();

		$outputPath = $this->codex->getConfig('scoper.output-path');

		if (! is_string($outputPath)) {
			return;
		}

		$this->outputPath = $outputPath;
	}

	public function getHash(): string
	{
		return $this->hash;
	}

	public function getOutputPath(?string $path = null): string
	{
		$outputPath = $this->outputPath;

		if ($path !== null) {
			$outputPath .= $path;
		}

		return $outputPath;
	}

	public function getBuildPath(?string $path = null): string
	{
		$buildPath = $this->outputPath . '-build-' . $this->hash;

		if ($path !== null) {
			$buildPath .= $path;
		}

		return $buildPath;
	}

	public function getBinPath(): string
	{
		return $this->codex->getProjectPath('/vendor/bin/php-scoper');
	}

	public function getConfigPath(): string
	{
		return $this->codex->getProjectPath('/scoper.inc.php');
	}

	public function dumpComposerFile(): void
	{
		$data = array_filter(
			[
				'autoload' => $this->getAutoload('autoload'),
				'autoload-dev' => $this->getAutoload('autoload-dev'),
				'require' => $this->codex->getComposer('require'),
				'require-dev' => $this->getInstallDev(),
			],
			static fn ($value): bool => ! Val::isBlank($value),
		);

		$this->filesystem->dumpFile(
			$this->getBuildPath('/composer.json'),
			json_encode(
				$data,
				JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
			),
		);
	}

	/**
	 * Remove all the directories created. It includes the temporary directory
	 * and the output directory.
	 */
	public function removeAll(): void
	{
		$this->filesystem->remove($this->getOutputPath());
		$this->filesystem->remove($this->getBuildPath());
	}

	/**
	 * Remove only the temporary directory created.
	 */
	public function removeBuildPath(): void
	{
		$this->filesystem->remove($this->getBuildPath());
	}

	/**
	 * Handles the list of package to add in "require-dev" config in Composer
	 * file. It should only include the packages that are explicitly added
	 * in the `scoper.install-dev` configuration.
	 *
	 * @return array<string>
	 */
	private function getInstallDev(): array
	{
		$requireDev = $this->codex->getComposer('require-dev');

		if (! is_array($requireDev) || Val::isBlank($requireDev)) {
			return [];
		}

		$includes = $this->codex->getConfig('scoper.install-dev');

		if (! is_array($includes) || Val::isBlank($includes)) {
			return [];
		}

		return array_filter(
			$requireDev,
			static fn ($value, $key): bool => in_array($key, $includes, true),
			ARRAY_FILTER_USE_BOTH,
		);
	}

	/** @return array<string,array<string,string|array<string>>>|null */
	private function getAutoload(string $key): ?array
	{
		$mapper = function ($paths) {
			if (is_string($paths)) {
				return Path::makeRelative(
					$this->codex->getProjectPath('/' . trim($paths, '/')),
					$this->getBuildPath(),
				);
			}

			if (is_array($paths)) {
				return array_map(function (string $path) {
					return Path::makeRelative(
						$this->codex->getProjectPath('/' . trim($path, '/')),
						$this->getBuildPath(),
					);
				}, $paths);
			}

			return $paths;
		};

		$autoloads = $this->codex->getComposer($key);

		if (is_array($autoloads) && ! Val::isBlank($autoloads)) {
			foreach ($autoloads as $std => $autoload) {
				$autoloads[$std] = array_map($mapper, $autoload);
			}

			return $autoloads;
		}

		return null;
	}
}
