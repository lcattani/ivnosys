<?php

namespace App\Command;

use App\Controller\LawsuitsController;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class LawsuitsCommand extends Command
{
    protected static $defaultName = 'lawsuits';
    protected static $defaultDescription = 'We are in the era of "lawsuits", everyone wants to go to court with their lawyer Saul and try to get a lot of dollars as if they were raining over Manhattan.';

    protected function configure(): void
    {
        $this
        ->addArgument('Plaintiff signatures', InputArgument::REQUIRED, 'The Plaintiff signatures.')
        ->addArgument('Defendant signatures', InputArgument::REQUIRED, 'The Defendant signatures.')
        //->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $plaintiff = $input->getArgument('Plaintiff signatures');
        $defendant = $input->getArgument('Defendant signatures');

        // Parametros pasados
        $io->note("You passed\n Plaintiff signatures: {$plaintiff}\n Defendant signatures: {$defendant}");

        /*
        if ($input->getOption('option1')) {
            // ...
        }*/

        $controler = new LawsuitsController();
        $result = $controler->getPlainResult($plaintiff, $defendant);
        $io->info($result);

        $io->success('Pass --help to see your options.');

        return Command::SUCCESS;
    }
}
