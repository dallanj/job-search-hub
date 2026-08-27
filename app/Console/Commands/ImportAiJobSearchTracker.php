<?php

namespace App\Console\Commands;

use App\Actions\ImportAiJobSearchTrackerRow;
use App\Models\User;
use Illuminate\Console\Command;

class ImportAiJobSearchTracker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:ai-job-search-tracker
                            {path : Path to an ai-job-search job_search_tracker.csv}
                            {--user= : ID of the user these applications belong to, inferred when only one user exists}
                            {--dry-run : Report what would be imported without writing anything}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import applications from an ai-job-search job_search_tracker.csv';

    public function __construct(private readonly ImportAiJobSearchTrackerRow $importRow)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $path = (string) $this->argument('path');

        if (! is_file($path) || ! is_readable($path)) {
            $this->error("Cannot read file: {$path}.");

            return self::FAILURE;
        }

        $user = $this->resolveUser();

        if (! $user instanceof User) {
            return self::FAILURE;
        }

        $rows = $this->readCsv($path);

        if ($rows === []) {
            $this->warn('No data rows found in the CSV.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');

        $this->info(sprintf(
            '%s %d row(s) for %s.',
            $dryRun ? 'Previewing' : 'Importing',
            count($rows),
            $user->email,
        ));

        $counts = ['created' => 0, 'updated' => 0, 'previewed' => 0, 'skipped' => 0];

        foreach ($rows as $index => $row) {
            $result = $this->importRow->handle($user, $row, $dryRun);
            $counts[$result['outcome']]++;
            $line = $index + 2;

            if ($result['outcome'] === 'skipped') {
                $this->warn("Line {$line}: {$result['message']}, skipped.");

                continue;
            }

            $this->line("Line {$line}: {$result['message']}");
        }

        $this->info($dryRun
            ? sprintf('Dry run complete: %d row(s) previewed, %d skipped. Nothing was written.', $counts['previewed'], $counts['skipped'])
            : sprintf('Import complete: %d created, %d updated, %d skipped.', $counts['created'], $counts['updated'], $counts['skipped']));

        return self::SUCCESS;
    }

    private function resolveUser(): ?User
    {
        $id = $this->option('user');

        if ($id !== null) {
            $user = User::find($id);

            if (! $user instanceof User) {
                $this->error("No user with id {$id}.");

                return null;
            }

            return $user;
        }

        $count = User::query()->count();

        if ($count === 1) {
            return User::query()->firstOrFail();
        }

        $this->error($count === 0
            ? 'No users exist yet, create one first.'
            : "Multiple users exist ({$count}), pass --user=<id> to pick one.");

        return null;
    }

    /**
     * @return list<array<string, string>>
     */
    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return [];
        }

        $header = fgetcsv($handle, escape: '');

        if ($header === false) {
            fclose($handle);

            return [];
        }

        $header = array_map(
            static fn ($column): string => mb_strtolower(trim((string) $column, " \t\n\r\0\x0B\u{FEFF}")),
            $header,
        );
        $columnCount = count($header);
        $rows = [];

        while (($line = fgetcsv($handle, escape: '')) !== false) {
            if ($line === [null] || array_filter($line, static fn ($value): bool => trim((string) $value) !== '') === []) {
                continue;
            }

            $line = array_pad(array_slice($line, 0, $columnCount), $columnCount, '');
            $rows[] = array_map(static fn ($value): string => (string) $value, array_combine($header, $line));
        }

        fclose($handle);

        return $rows;
    }
}
