<?php

declare(strict_types=1);

final class HS_Local_Image_Process_Runner
{
    /**
     * @param list<string> $command
     * @return array{status: string, exit_code: int, stdout: string, stderr: string}
     */
    public static function run(array $command, int $timeoutSeconds = 180): array
    {
        if ($command === [] || !is_executable($command[0])) {
            return self::result('failed_missing_binary', 127, '', 'Encoder binary is not executable.');
        }

        $pipes = [];
        $process = @proc_open(
            $command,
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            null,
            null,
            ['bypass_shell' => true]
        );

        if (!is_resource($process)) {
            return self::result('failed_start', 126, '', 'Unable to start encoder process.');
        }

        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $stdout = '';
        $stderr = '';
        $startedAt = hrtime(true);
        $timeoutNanoseconds = max(1, $timeoutSeconds) * 1_000_000_000;
        $timedOut = false;
        $lastStatus = ['exitcode' => -1, 'running' => true];

        while (true) {
            $stdout .= (string) stream_get_contents($pipes[1]);
            $stderr .= (string) stream_get_contents($pipes[2]);
            $lastStatus = proc_get_status($process);

            if (!$lastStatus['running']) {
                break;
            }

            if ((hrtime(true) - $startedAt) >= $timeoutNanoseconds) {
                $timedOut = true;
                proc_terminate($process, 15);
                usleep(200_000);

                $lastStatus = proc_get_status($process);
                if ($lastStatus['running']) {
                    proc_terminate($process, 9);
                }
                break;
            }

            usleep(50_000);
        }

        $stdout .= (string) stream_get_contents($pipes[1]);
        $stderr .= (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $closeCode = proc_close($process);
        $exitCode = (int) ($lastStatus['exitcode'] ?? -1);
        if ($exitCode < 0) {
            $exitCode = $closeCode;
        }

        if ($timedOut) {
            return self::result('failed_timeout', 124, $stdout, $stderr);
        }

        return self::result($exitCode === 0 ? 'success' : 'failed_exit', $exitCode, $stdout, $stderr);
    }

    /** @return array{status: string, exit_code: int, stdout: string, stderr: string} */
    private static function result(string $status, int $exitCode, string $stdout, string $stderr): array
    {
        return [
            'status' => $status,
            'exit_code' => $exitCode,
            'stdout' => substr($stdout, -4096),
            'stderr' => substr($stderr, -4096),
        ];
    }
}
