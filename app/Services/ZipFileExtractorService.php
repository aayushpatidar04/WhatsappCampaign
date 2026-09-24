<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class ZipFileExtractorService
{
    /**
     * Extract a zip archive and map files to phone numbers.
     *
     * Expected zip structure: flat files named like 919876543210.pdf or 9876543210.pdf
     * Returns: ['919876543210' => ['path' => '...', 'filename' => '...', 'mime' => 'application/pdf', 'size' => 12345]]
     */
    public function extractAndMap(string $zipPath, string $tempDir = null): array
    {
        $tempDir = $tempDir ?: sys_get_temp_dir() . '/wa_campaign_' . uniqid();
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0700, true);
        }

        $zip = new \ZipArchive();
        $result = $zip->open($zipPath);
        if ($result !== true) {
            throw new \RuntimeException("Unable to open zip file (error code: {$result})");
        }

        $zip->extractTo($tempDir);
        $zip->close();

        $files = [];
        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'mp4', 'doc', 'docx'];
        $allowedMimes = [
            'pdf'  => 'application/pdf',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'mp4'  => 'video/mp4',
            'doc'  => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($tempDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $filename = $file->getFilename();
                $ext = strtolower($file->getExtension());
                if (!in_array($ext, $allowedExtensions, true)) {
                    continue;
                }

                // Extract number from filename (strip extension)
                $number = pathinfo($filename, PATHINFO_FILENAME);
                $normalized = preg_replace('/[^0-9]/', '', $number);

                if (strlen($normalized) < 7) {
                    continue;
                }

                $files[$normalized] = [
                    'path'            => $file->getRealPath(),
                    'filename'        => $filename,
                    'normalized'      => $normalized,
                    'mime_type'       => $allowedMimes[$ext] ?? 'application/octet-stream',
                    'size_bytes'      => $file->getSize(),
                    'temp_dir'        => $tempDir,
                ];
            }
        }

        return $files;
    }

    /**
     * Move a file from temp to permanent storage and return the stored path.
     */
    public function storeFile(string $sourcePath, string $campaignId, string $phoneNumber, string $originalFilename): string
    {
        $relativeDir = "campaign-files/{$campaignId}";
        $safeFilename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalFilename);
        $storedFilename = "{$phoneNumber}_{$safeFilename}";

        $fullPath = Storage::disk('local')->path($relativeDir . '/' . $storedFilename);
        if (!is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0750, true);
        }

        copy($sourcePath, $fullPath);

        return $relativeDir . '/' . $storedFilename;
    }

    /**
     * Delete temporary directory.
     */
    public function cleanup(string $tempDir): void
    {
        if (is_dir($tempDir)) {
            $this->deleteRecursive($tempDir);
        }
    }

    private function deleteRecursive(string $dir): void
    {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = "{$dir}/{$file}";
            if (is_dir($path)) {
                $this->deleteRecursive($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
