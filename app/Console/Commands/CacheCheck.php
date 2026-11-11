<?php

namespace App\Console\Commands;

use App\Support\CacheHealth;
use Illuminate\Console\Command;

/**
 * php artisan cache:check — same checks as Admin → Settings → Cache (App\Support\CacheHealth).
 * Exit code 1 when something is wrong (e.g. Redis set in config/cache.php but not usable), so deploy scripts notice.
 */
class CacheCheck extends Command
{
    protected $signature = 'cache:check';

    protected $description = 'Show which cache driver is set and in use, and test it (Redis: connection, server figures)';

    public function handle(): int
    {
        $r = CacheHealth::report();

        $this->line('Set in config/cache.php : <info>'.$r['requested'].'</info>');
        $this->line('Driver in use           : <info>'.$r['label'].'</info>');
        if ($r['fallback']) {
            $this->error('Set to '.$r['requested'].', but this PHP cannot use it — the file cache is used instead.');
            if ($r['fix']) {
                $this->line('  '.$r['fix']);
            }
        }

        if ($x = $r['redis']) {
            $this->line("Redis                   : {$x['host']}:{$x['port']}, database {$x['database']}, client {$r['client']}");
            $this->line("Key prefix              : {$x['key_prefix']}");
            if (! $x['ok']) {
                $this->error('Cannot connect to Redis: '.$x['error']);
                $this->line('  Is Redis running?  sudo systemctl status redis-server   Check host / port / password in config/cache.php.');
            } else {
                $this->line("Ping                    : <info>{$x['ping_ms']} ms</info>   (Redis {$x['version']}, up {$x['uptime']})");
                $this->line("Keys in this database   : {$x['keys']}   Memory: {$x['memory']} (max: {$x['max_memory']})");
                $this->line('Hit rate                : '.($x['hit_rate'] !== null ? $x['hit_rate'].'%' : '—')." ({$x['hits']} hits, {$x['misses']} misses since Redis started)");
            }
        }
        if ($f = $r['file']) {
            $this->line("File cache              : {$f['path']}");
            $this->line('Files / size            : '.number_format($f['files']).($f['more'] ? '+' : '')." / {$f['size']}".($f['writable'] ? '' : '  <error>NOT WRITABLE</error>'));
        }

        $t = $r['test'];
        $t['ok']
            ? $this->line("Write/read test         : <info>OK</info> ({$t['ms']} ms)")
            : $this->error('Write/read test FAILED: '.$t['message']);

        return $r['ok'] ? self::SUCCESS : self::FAILURE;
    }
}
