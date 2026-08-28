<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class UploadedDocument
{
    /**
     * Reject PHP/server upload failures and files that are not a real JPG/PNG/WEBP/PDF.
     *
     * @param  array<string, string>  $fields  input name => label
     */
    public static function assertValid(Request $request, array $fields): void
    {
        if (self::postTooLarge($request)) {
            throw ValidationException::withMessages([
                array_key_first($fields) => 'El archivo es demasiado grande para el servidor. Comprime la imagen a menos de 5 MB (JPG o PNG) e intenta de nuevo.',
            ]);
        }

        $errors = [];

        foreach ($fields as $field => $label) {
            $file = $request->file($field);

            if ($file instanceof UploadedFile && ! $file->isValid()) {
                $errors[$field] = self::errorMessage($file->getError(), $label);

                continue;
            }

            if (! $file instanceof UploadedFile) {
                continue;
            }

            if (! self::contentLooksAllowed($file)) {
                $errors[$field] = "{$label} no es un JPG, PNG, WEBP o PDF válido. Si la foto es de iPhone, guárdala o expórtala como JPG e inténtalo de nuevo.";
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    public static function postTooLarge(Request $request): bool
    {
        $max = self::iniBytes((string) ini_get('post_max_size'));
        $length = (int) $request->header('Content-Length', 0);

        if ($max <= 0 || $length <= 0) {
            return false;
        }

        return $length > $max;
    }

    public static function errorMessage(int $code, string $label): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => "{$label} es demasiado grande. Usa JPG o PNG de máximo 5 MB (documentos, 8 MB).",
            UPLOAD_ERR_PARTIAL => "{$label} se subió incompleta. Intenta de nuevo.",
            UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE => "El servidor no pudo guardar {$label}. Revisa la carpeta de uploads.",
            default => "No se pudo subir {$label}. Usa JPG, PNG o WEBP de máximo 5 MB.",
        };
    }

    private static function contentLooksAllowed(UploadedFile $file): bool
    {
        $mime = (string) ($file->getMimeType() ?: $file->getClientMimeType());
        $extension = strtolower($file->getClientOriginalExtension() ?: '');

        if ($mime === 'application/pdf' || $extension === 'pdf') {
            return true;
        }

        $path = $file->getRealPath();

        if (! $path) {
            return false;
        }

        $info = @getimagesize($path);

        if ($info !== false) {
            return in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true);
        }

        return in_array($mime, [
            'image/jpeg',
            'image/jpg',
            'image/pjpeg',
            'image/png',
            'image/x-png',
            'image/webp',
        ], true);
    }

    private static function iniBytes(string $value): int
    {
        $value = trim($value);

        if ($value === '' || $value === '0') {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $number = (int) $value;

        return match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => (int) $value,
        };
    }
}
