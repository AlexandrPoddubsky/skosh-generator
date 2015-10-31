<?php namespace Skosh\Console;

use Skosh\Builder;
use Skosh\Event;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BuildCommand extends Command
{
    /**
     * Skosh builder
     *
     * @var \Skosh\Builder
     */
    protected $builder;

    /**
     * Path to the target directory.
     *
     * @var string
     */
    private $target;

    protected function configure()
    {
        $this
            ->setName('build')
            ->setDescription('Renders the web site')
            ->addOption('env', 'e', InputOption::VALUE_OPTIONAL, 'Which environment to build for.', 'local')
            ->addOption('part', 'p', InputOption::VALUE_OPTIONAL, 'Which part of the site to build pages, assets, or all.', 'all');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Get application instance
        $app = $this->getApplication();

        // Initialize builder
        $this->builder = new Builder($output, $app);

        // Get arguments
        $env  = $app->getEnvironment();
        $part = $input->getOption('part');

        $isProduction = ($env === 'production');

        // Set system paths
        $this->target = $app->getTarget();

        // Announce production build
        if ($isProduction) {
            $output->writeln("<info>Building production version...</info>");
        }

        // Remove all built files
        if ($isProduction || in_array($part, array('all'))) {
            $output->writeln("<comment>Cleaning target...</comment>");
            $this->builder->cleanTarget();
        }

        // Create server configuration
        if ($isProduction || in_array($part, array('all', 'config'))) {
            $output->writeln("<comment>Creating server configuration...</comment>");
            $this->builder->createServerConfig();
        }

        // Copy static files
        if ($isProduction || in_array($part, array('all', 'static'))) {
            $output->writeln("<comment>Copying statics...</comment>");
            $this->builder->copyStaticFiles();
        }

        // Build assets
        if ($isProduction || in_array($part, array('all', 'assets')))
        {
            $output->writeln("<comment>Building assets (gulp)...</comment>\n");
            $output->writeln(shell_exec("gulp --target={$this->target} --env={$env}"));

            // Fire event
            Event::fire('assets.built');
        }

        // Build pages
        if ($isProduction || in_array($part, array('all', 'pages')))
        {
            $output->writeln("<comment>Building pages...</comment>");
            $this->builder->build();
        }

        $output->writeln("<info>Build complete!</info>");
    }
}