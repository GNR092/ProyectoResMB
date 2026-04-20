<?php

namespace App\Libraries;

class ImageProcessor
{
    /**
     * Processes an uploaded file. If it's a bitmap image, it compresses and resizes it.
     * Otherwise, it just moves the file to the target directory.
     *
     * @param \CodeIgniter\HTTP\Files\UploadedFile|null $file The uploaded file object.
     * @param string $targetDir The destination directory.
     * @param string $newNameWithoutExt The desired new name for the file, without extension.
     * @return string|null The full new file name (with extension) if successful, otherwise null.
     */
    public static function processAndSave(?\CodeIgniter\HTTP\Files\UploadedFile $file, string $targetDir, string $newNameWithoutExt): ?string
    {
        if (!$file) {
            log_message('error', '[ImageProcessor] El archivo es nulo.');
            return null;
        }

        if (!$file->isValid()) {
            log_message('error', sprintf(
                '[ImageProcessor] El archivo no es válido. Error PHP: %s (%d). Nombre original: %s',
                $file->getErrorString(),
                $file->getError(),
                $file->getName()
            ));
            return null;
        }

        if ($file->hasMoved()) {
            log_message('error', '[ImageProcessor] El archivo ya ha sido movido.');
            return null;
        }

        $extension = $file->getExtension();
        $newName = $newNameWithoutExt . '.' . $extension;
        $destinationPath = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $newName;

        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0777, true)) {
                log_message('error', "Failed to create folder: {$targetDir}");
                return null;
            }
        }

        $mime = $file->getMimeType();
        $isBitmapImage = strpos($mime, 'image/') === 0 && !in_array($mime, ['image/svg+xml']);

        if ($isBitmapImage) {
            // Temporarily increase memory to handle large images
            ini_set('memory_limit', '1024M');
            try {
                $imageService = \Config\Services::image();
                $imageService->withFile($file->getTempName());

                // Resize if wider than 1920px, maintaining aspect ratio
                if ($imageService->getProperties(true)['width'] > 1920) {
                    $imageService->resize(1920, 0, true);
                }

                // Save with 80% quality. CI4's save() handles JPG/PNG quality mapping.
                if (!$imageService->save($destinationPath, 80)) {
                    log_message('error', 'Image compression/saving failed for ' . $destinationPath . '. Errors: ' . print_r($imageService->getErrors(), true));
                    // Fallback to simple move if compression fails
                    if (!$file->move($targetDir, $newName, true)) {
                         log_message('error', 'Fallback file move failed for: ' . $newName);
                        return null;
                    }
                }
            } catch (\Throwable $e) {
                log_message('error', 'Image processing failed for ' . $newName . ': ' . $e->getMessage() . '. Falling back to moving file.');
                // Fallback to simple move on any processing error
                if (!$file->move($targetDir, $newName, true)) {
                    log_message('error', 'Fallback file move on exception failed for: ' . $newName);
                    return null;
                }
            } finally {
                // Restore original memory limit
                ini_restore('memory_limit');
            }
        } else {
            // Not a bitmap image (e.g., PDF, SVG, DOCX), just move the file
            if (!$file->move($targetDir, $newName, true)) {
                log_message('error', 'File move failed for: ' . $newName);
                return null;
            }
        }

        return $newName;
    }
}
