<?php

namespace ResqueExamples\Basic;

use Resque\Interfaces\Job;

class BasicJob implements Job
{
    public function perform(array $arguments): void
    {
        echo "foo";
        file_put_contents(__DIR__ . '/basic_test', var_export($arguments, true) . "\n", FILE_APPEND);
    }
}
