<?php

namespace Emanci\Deployer\Agents;

class SvnAgent
{
    public function onChangingFiles()
    {
        $output = $this->status();

        return array_filter($output, function ($line) {
            if (preg_match('/([MA])[\s\t]+(\S+)/', $line, $match)) {
                return str_replace('\\', '/', '/'.$match[2]);
            }
        });
    }

    protected function status()
    {
        exec('svn status -q', $output);

        return $output;
    }

    public function onChangedFiles($limit)
    {
        $logs = $this->logs($limit);

        return array_filter($logs, function ($line) {
            if (preg_match('/([MA])\s(\S+)/', $line, $match)) {
                $filename = $match[2];
                if (starts_with($filename, SVN_BASEPATH)) {
                    return substr($filename, strlen(SVN_BASEPATH));
                }
            }
        });
    }

    public function moreLogs($limit, $days)
    {
        $logs = $this->logs($limit);
        $hasMore = true;

        if ($days > 0) {
            $limitTimestamp = get_today_morning_timestamp() - ($days - 1) * 3600 * 24;
            foreach ($logs as $line) {
                if (preg_match('/\d{4}\-\d{2}\-\d{2}\s\d{2}\:\d{2}\:\d{2}/i', $line, $match)) {
                    $lineTimestamp = strtotime($match[0]);
                    if ($lineTimestamp <= $limitTimestamp) {
                        $hasMore = false;
                        break;
                    }
                }
            }
        }

        return $hasMore;
    }

    protected function logs($limit)
    {
        exec('svn log --limit '.$limit.' -v', $logs);

        return $logs;
    }
}
