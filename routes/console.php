<?php

use App\Support\DatabaseBackup;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('mswd:backup {--keep= : How many backups to keep}', function () {
    $keep = $this->option('keep');

    $this->comment('MSWD database backup starting...');

    $binary = DatabaseBackup::mysqlDumpBinary();

    if ($binary === null) {
        $this->error('mysqldump was not found. Set BACKUP_MYSQLDUMP in .env.');
        return 1;
    }

    $result = DatabaseBackup::create();

    if (empty($result['ok'])) {
        $this->error($result['error']);
        return 1;
    }

    $this->info(
        'Created ' . $result['file'] . ' (' .
        DatabaseBackup::humanSize((int) $result['size']) .
        ')'
    );

    $resolvedKeep = $keep !== null
        ? max(1, (int) $keep)
        : (int) DatabaseBackup::settings()['keep'];

    $removed = DatabaseBackup::prune($resolvedKeep);

    if ($removed) {
        $this->comment(
            'Pruned ' . count($removed) .
            ' older backup(s); keeping ' . $resolvedKeep . '.'
        );
    }

    return 0;
})->purpose('Create a timestamped SQL backup of the MSWD database and prune old ones');
