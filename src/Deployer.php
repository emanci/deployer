<?php

namespace Emanci\Deployer;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

define('SVN_BASEPATH', '/codes/foobar/project_name');
define('IGNORE_FILE', 'update_ignore.txt');

class Deployer extends Command
{
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

        $hasMore = $this->checkMoreLogs($limit, $days);

        if ($hasMore) {
            $output->writeln('May be more limit');

            return;
        }

        $files = $changing ? $this->getChangingFiles() : $this->getChangedFiles($limit);
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
                // upload
            }
            $progress->setMessage('*** upload file *** '.$filename."\n");
            $progress->advance();
        }

        $progress->setFormat('Upload completed.');
        $progress->finish();
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

    /**
     * @return array
     */
    protected function getChangingFiles()
    {
        exec('svn status -q', $output);

        return array_filter($output, function ($line) {
            if (preg_match('/([MA])[\s\t]+(\S+)/', $line, $match)) {
                return str_replace('\\', '/', '/'.$match[2]);
            }
        });
    }

    /**
     * @param int $limit
     * @param int $days
     *
     * @return bool
     */
    protected function checkMoreLogs($limit, $days)
    {
        exec('svn log --limit '.$limit.' -v', $logs);
        $hasMore = false;

        if ($days > 0) {
            $limitTimestamp = $this->getTodayTimestamp() - ($days - 1) * 3600 * 24;
            foreach ($logs as $line) {
                if (preg_match('/\d{4}\-\d{2}\-\d{2}\s\d{2}\:\d{2}\:\d{2}/i', $line, $match)) {
                    $lineTimestamp = strtotime($match[0]);
                    if ($lineTimestamp <= $limitTimestamp) {
                        $hasMore = true;
                        break;
                    }
                }
            }
        }

        return $hasMore;
    }

    /**
     * @param int $limit
     *
     * @return array
     */
    protected function getChangedFiles($limit)
    {
        exec('svn log --limit '.$limit.' -v', $logs);

        return array_filter($logs, function ($line) {
            if (preg_match('/([MA])\s(\S+)/', $line, $match)) {
                $filename = $match[2];
                if ($this->startsWith($filename, SVN_BASEPATH)) {
                    return substr($filename, strlen(SVN_BASEPATH));
                }
            }
        });
    }

    /**
     * @return int
     */
    protected function getTodayTimestamp()
    {
        $date = date('Y-m-d', time());
        list($year, $month, $day) = explode('-', $date);

        return mktime(0, 0, 0, $month, $day, $year);
    }

    /**
     * @param string $str
     * @param string $start
     *
     * @return bool
     */
    protected function startsWith($str, $start)
    {
        return strpos($str, $start) === 0;
    }
}
