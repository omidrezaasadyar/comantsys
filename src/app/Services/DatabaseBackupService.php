<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;

class DatabaseBackupService
{
    protected function config(): array
    {
        $conn = config('database.default');

        return [
            'host'     => config("database.connections.{$conn}.host"),
            'port'     => config("database.connections.{$conn}.port"),
            'database' => config("database.connections.{$conn}.database"),
            'username' => config("database.connections.{$conn}.username"),
            'password' => config("database.connections.{$conn}.password"),
        ];
    }

    public function backupDir(): string
    {
        return storage_path('app/backups');
    }

    public function backup(): string
    {
        $cfg = $this->config();
        $file = $this->backupDir() . '/backup_' . date('Y-m-d_His') . '.dump';

        $result = Process::env(['PGPASSWORD' => $cfg['password']])
            ->timeout(600)
            ->run([
                'pg_dump',
                '-h', $cfg['host'],
                '-p', (string) $cfg['port'],
                '-U', $cfg['username'],
                '-d', $cfg['database'],
                '-F', 'c',
                '-f', $file,
            ]);

        if (! $result->successful()) {
            throw new \RuntimeException('Backup failed: ' . $result->errorOutput());
        }

        return $file;
    }
    // src/app/Services/DatabaseBackupService.php
// این متد را داخل کلاس، بعد از backup() اضافه کنید

    public function restore(string $file): void
    {
        if (! is_file($file)) {
            throw new \RuntimeException('Backup file not found: ' . $file);
        }

        // محافظت: قبل از ریستور، از وضعیت فعلی بکاپ بگیر
        $this->backup();

        $cfg = $this->config();

        $result = Process::env(['PGPASSWORD' => $cfg['password']])
            ->timeout(600)
            ->run([
                'pg_restore',
                '-h', $cfg['host'],
                '-p', (string) $cfg['port'],
                '-U', $cfg['username'],
                '-d', $cfg['database'],
                '--clean',
                '--if-exists',
                '--no-owner',
                $file,
            ]);

        if (! $result->successful()) {
            throw new \RuntimeException('Restore failed: ' . $result->errorOutput());
        }
    }
    public function listBackups(): array
    {
        $dir = $this->backupDir();

        if (! is_dir($dir)) {
            return [];
        }

        $files = glob($dir . '/*.dump') ?: [];

        // جدیدترین اول
        usort($files, fn ($a, $b) => filemtime($b) <=> filemtime($a));

        $list = [];
        foreach ($files as $f) {
            $name = basename($f);
            $size = round(filesize($f) / 1024, 1) . ' KB';
            $list[$name] = $name . '  —  ' . $size;
        }

        return $list;
    }

    public function pathForFilename(string $filename): string
    {
        // امنیت: فقط نام فایل، بدون مسیر، تا از پوشهٔ بکاپ خارج نشود
        $safe = basename($filename);
        $path = $this->backupDir() . '/' . $safe;

        if (! is_file($path)) {
            throw new \RuntimeException('Backup file not found: ' . $safe);
        }

        return $path;
    }
}
