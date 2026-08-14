<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Evidence upload handler.
 *
 * Files are stored OUTSIDE the web root (storage/uploads) under randomised
 * names and are only ever served back through an authorised controller action,
 * so a malicious upload can never be executed by the web server.
 */
final class Uploader
{
    /**
     * @return array{stored:array<int,array<string,mixed>>,errors:array<int,string>}
     */
    public static function handle(string $inputName, string $entityType, int $entityId): array
    {
        $stored = [];
        $errors = [];

        if (empty($_FILES[$inputName]['name'][0]) && empty($_FILES[$inputName]['name'])) {
            return ['stored' => $stored, 'errors' => $errors];
        }

        $files    = self::normalise($_FILES[$inputName]);
        $maxBytes = (int)App::config('uploads.max_bytes');
        $maxFiles = (int)App::config('uploads.max_files');
        $allowed  = (array)App::config('uploads.allowed_mimes');

        if (count($files) > $maxFiles) {
            $errors[] = "You may upload at most {$maxFiles} files.";
            $files    = array_slice($files, 0, $maxFiles);
        }

        $dir = App::config('app.uploads') . '/' . $entityType . '/' . date('Y/m');
        if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
            return ['stored' => [], 'errors' => ['Upload directory is not writable.']];
        }

        foreach ($files as $file) {
            if ($file['error'] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = self::errorText($file['name'], $file['error']);
                continue;
            }
            if ($file['size'] <= 0 || $file['size'] > $maxBytes) {
                $errors[] = sprintf('%s exceeds the %d MB limit.', $file['name'], (int)($maxBytes / 1048576));
                continue;
            }
            if (!is_uploaded_file($file['tmp_name'])) {
                $errors[] = $file['name'] . ' could not be verified as an upload.';
                continue;
            }

            // Trust the sniffed type, never the client-supplied one.
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: '';
            if (!isset($allowed[$mime])) {
                $errors[] = sprintf('%s has an unsupported file type (%s).', $file['name'], $mime ?: 'unknown');
                continue;
            }

            $ext    = self::extensionFor($mime);
            $name   = bin2hex(random_bytes(16)) . '.' . $ext;
            $target = $dir . '/' . $name;

            if (!move_uploaded_file($file['tmp_name'], $target)) {
                $errors[] = 'Failed to store ' . $file['name'] . '.';
                continue;
            }
            @chmod($target, 0644);

            $relative = $entityType . '/' . date('Y/m') . '/' . $name;
            $id = Database::instance()->insert('attachments', [
                'entity_type'   => $entityType,
                'entity_id'     => $entityId,
                'original_name' => mb_substr(self::sanitiseName($file['name']), 0, 255),
                'stored_name'   => $relative,
                'mime_type'     => $mime,
                'kind'          => $allowed[$mime],
                'size_bytes'    => (int)$file['size'],
                'checksum'      => hash_file('sha256', $target),
                'uploaded_by'   => Auth::id(),
            ]);

            $stored[] = ['id' => $id, 'name' => $file['name'], 'kind' => $allowed[$mime]];
            Audit::log('evidence.uploaded', $entityType, $entityId, 'Uploaded ' . $file['name']);
        }

        return ['stored' => $stored, 'errors' => $errors];
    }

    /** Streams an attachment to an already-authorised user. */
    public static function stream(array $attachment): never
    {
        $path = App::config('app.uploads') . '/' . $attachment['stored_name'];
        if (!is_file($path)) {
            http_response_code(404);
            echo 'File not found.';
            exit;
        }
        header('Content-Type: ' . $attachment['mime_type']);
        header('Content-Length: ' . (string)filesize($path));
        header('Content-Disposition: inline; filename="' . str_replace('"', '', $attachment['original_name']) . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=0, no-store');
        readfile($path);
        exit;
    }

    private static function normalise(array $input): array
    {
        if (!is_array($input['name'])) {
            return [$input];
        }
        $out = [];
        foreach ($input['name'] as $i => $name) {
            $out[] = [
                'name'     => (string)$name,
                'type'     => (string)($input['type'][$i] ?? ''),
                'tmp_name' => (string)($input['tmp_name'][$i] ?? ''),
                'error'    => (int)($input['error'][$i] ?? UPLOAD_ERR_NO_FILE),
                'size'     => (int)($input['size'][$i] ?? 0),
            ];
        }
        return $out;
    }

    private static function extensionFor(string $mime): string
    {
        return match ($mime) {
            'image/jpeg'      => 'jpg',
            'image/png'       => 'png',
            'image/webp'      => 'webp',
            'image/gif'       => 'gif',
            'video/mp4'       => 'mp4',
            'video/quicktime' => 'mov',
            'audio/mpeg'      => 'mp3',
            'audio/wav'       => 'wav',
            'audio/mp4'       => 'm4a',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            default           => 'bin',
        };
    }

    private static function sanitiseName(string $name): string
    {
        $name = preg_replace('/[^\w .\-()]+/u', '_', basename($name)) ?? 'file';
        return trim($name) === '' ? 'file' : $name;
    }

    private static function errorText(string $name, int $code): string
    {
        return $name . ': ' . match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'file is too large.',
            UPLOAD_ERR_PARTIAL    => 'upload was interrupted.',
            UPLOAD_ERR_NO_TMP_DIR => 'server temporary directory is missing.',
            UPLOAD_ERR_CANT_WRITE => 'server could not write the file.',
            default               => 'upload failed.',
        };
    }
}
