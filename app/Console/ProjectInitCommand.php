<?php

declare(strict_types=1);

namespace Syntatis\Codex\Companion\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Syntatis\Codex\Companion\Console\ProjectInitCommand\Howdy;
use Syntatis\Codex\Companion\Traits\RunOnComposerEvent;

class ProjectInitCommand extends BaseCommand
{
	use RunOnComposerEvent;

	protected function configure(): void
	{
		$this->setName('project:init');
		$this->setDescription('Initialize a project');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$style = new SymfonyStyle($input, $output);
		$projectName = $this->codex->getProjectName();

		switch ($projectName) {
			case 'syntatis/howdy':
				return (new Howdy($this->codex))->execute($style);

			default:
				$style->warning('Unsupported project: "' . $projectName . '".');

				return 0;
		}
	}
}
