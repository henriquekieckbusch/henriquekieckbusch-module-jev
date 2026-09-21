<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Console\Command;

use HenriqueKieckbusch\Jev\Model\Analyzer;
use HenriqueKieckbusch\Jev\Model\Handler\Pool;
use HenriqueKieckbusch\Jev\Model\Question\Locator;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * bin/magento jev:analyze <type> <id>... [--force]
 *
 * Runs a Jev analysis from the command line, e.g. to backfill existing entities.
 */
class AnalyzeCommand extends Command
{
    private const ARG_TYPE = 'type';
    private const ARG_IDS = 'ids';
    private const OPT_FORCE = 'force';

    /**
     * @param Analyzer $analyzer
     * @param Pool $pool
     * @param Locator $locator
     * @param State $state
     */
    public function __construct(
        private readonly Analyzer $analyzer,
        private readonly Pool $pool,
        private readonly Locator $locator,
        private readonly State $state
    ) {
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this->setName('jev:analyze')
            ->setDescription('Ask Jev about one or more entities and store the answers')
            ->addArgument(
                self::ARG_TYPE,
                InputArgument::REQUIRED,
                'Entity type: ' . implode(', ', array_keys($this->pool->getAll()))
            )
            ->addArgument(self::ARG_IDS, InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'Entity id(s)')
            ->addOption(
                self::OPT_FORCE,
                'f',
                InputOption::VALUE_NONE,
                'Call the API even when the context is unchanged'
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return (int)$this->state->emulateAreaCode(
            Area::AREA_ADMINHTML,
            fn (): int => $this->analyzeAll($input, $output)
        );
    }

    /**
     * Analyze every entity id given on the command line.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    private function analyzeAll(InputInterface $input, OutputInterface $output): int
    {
        $type = (string)$input->getArgument(self::ARG_TYPE);
        $force = (bool)$input->getOption(self::OPT_FORCE);
        $exitCode = Cli::RETURN_SUCCESS;
        foreach ($input->getArgument(self::ARG_IDS) as $id) {
            $exitCode = max($exitCode, $this->analyzeOne($type, (int)$id, $force, $output));
        }
        return $exitCode;
    }

    /**
     * Analyze one entity and print the result, returning the CLI exit code.
     *
     * @param string $type
     * @param int $id
     * @param bool $force
     * @param OutputInterface $output
     * @return int
     */
    private function analyzeOne(string $type, int $id, bool $force, OutputInterface $output): int
    {
        try {
            $result = $this->analyzer->analyze($type, $id, $force);
        } catch (\Throwable $e) {
            $output->writeln(sprintf('<error>%s #%d: %s</error>', $type, $id, $e->getMessage()));
            return Cli::RETURN_FAILURE;
        }
        $analysis = $result->getAnalysis();
        $output->writeln(sprintf(
            '<info>%s #%d: %s (%s)</info>',
            $type,
            $id,
            $result->isSkipped() ? 'unchanged, skipped' : 'analyzed',
            $analysis->getData('model') ?: 'no model'
        ));
        foreach ($analysis->getAnswers() as $code => $answer) {
            $question = $this->locator->get($type, (string)$code);
            $output->writeln(sprintf(
                '  %-32s %-28s %3d%%',
                $question ? $question->getLabel() : $code,
                $answer['choice'],
                (int)round(($answer['confidence'] ?? 0) * 100)
            ));
        }
        return Cli::RETURN_SUCCESS;
    }
}
