<?php

namespace Emanci\Deployer;

use Emanci\Deployer\Agents\SvnAgent;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

define('SVN_BASEPATH', '/codes/foobar/project_name');
define('IGNORE_FILE', 'update_ignore.txt');
define('SCM_AGENT', 'svn');

class Deployer extends Command
{
    /**
     * @var array
     */
    protected $agents = [
        'svn' => SvnAgent::class,
    ];

    protected function configure()
    {
        $this
        ->setName('deploy')
        ->setDescription('Deployment the project code.')
        ->setHelp('This command help you to deploy the project code.')
        ->addArgument('env', InputArgument::REQUIRED, 'Input the env name.')
        ->addArgument('password', InputArgument::REQUIRED, 'Forget the password?')
        ->addArgument('action', InputArgument::REQUIRED, 'Input the action name.')
        ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'limit.')
        ->addOption('days', null, InputOption::VALUE_REQUIRED, 'days.')
        ->addOption('changing', null, InputOption::VALUE_REQUIRED, 'changing.')
        ->addOption('test', null, InputOption::VALUE_REQUIRED, 'test.');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $env = $input->getArgument('env');
        $password = $input->getArgument('password');
        $action = $input->getArgument('action');
        $test = intval($input->getOption('test'));

        $changing = boolval($input->getOption('changing'));
        $limit = intval($input->getOption('limit'));
        $days = intval($input->getOption('days'));

        $agent = $this->getAgent();
        $hasMore = $agent->moreLogs($limit, $days);

        if ($hasMore) {
            $output->writeln('May be more limit.');

            return;
        }

        $files = $changing ? $agent->onChangingFiles() : $agent->onChangedFiles($limit);
        $fileCount = count($files);

        if (empty($files)) {
            $output->writeln('No file changed.');

            return;
        }

        $ignoreFile = dirname(__DIR__).'/'.IGNORE_FILE;
        $ignoreItems = $this->getIgnoreItems($ignoreFile);

        $progress = new ProgressBar($output, $fileCount);
        $progress->setFormat('Start upload...');
        $progress->start();

        foreach ($files as $key => $filename) {
            $progress->setFormat(' %current%/%max% -- %message%');

            if ($this->shouldIgnore($ignoreItems, $filename)) {
                $progress->setMessage('*** ignore file *** '.$filename."\n");
                $progress->advance();
                continue;
            }

            if (!$test) {
                // upload the logic
            }
            $progress->setMessage('*** upload file *** '.$filename."\n");
            $progress->advance();
        }

        $progress->setFormat('Upload completed.');
        $progress->finish();
    }

    /**
     * @param string $agent
     *
     * @throws InvalidArgumentException
     *
     * @return mixed
     */
    protected function getAgent($agent = SCM_AGENT)
    {
        if (isset($this->agents[$agent])) {
            $agentClass = $this->agents[$agent];

            return new $agentClass();
        }

        throw new InvalidArgumentException("$agent not supported.");
    }

    /**
     * @param string $filename
     *
     * @return array
     */
    protected function getIgnoreItems($filename)
    {
        $ignoreItems = [];

        if (file_exists($filename)) {
            $ignoreItems = explode("\r\n", file_get_contents($filename));
        }

        return $ignoreItems;
    }

    /**
     * @param array  $ignoreItems
     * @param string $path
     *
     * @return bool
     */
    protected function shouldIgnore(array $ignoreItems, $path)
    {
        foreach ($ignoreItems as $item) {
            if ($item && preg_match($item, $path)) {
                return true;
            }
        }

        return false;
    }
}
