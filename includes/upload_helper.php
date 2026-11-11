
<?php

function savePortfolioUpload(array $file, string $type): ?string
{
    // No new file selected
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    // Check upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException(
            'File upload failed. Please select the file again.'
        );
    }

    // Supported upload types and size limits
    $allowedTypes = [
        'image' => [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ],
        'pdf' => [
            'application/pdf' => 'pdf'
        ]
    ];

    if (!isset($allowedTypes[$type])) {
        throw new RuntimeException('Invalid upload category.');
    }

    // Maximum file sizes
    $maxSize = $type === 'image'
        ? 5 * 1024 * 1024
        : 10 * 1024 * 1024;

    if (
        !isset($file['size']) ||
        $file['size'] <= 0 ||
        $file['size'] > $maxSize
    ) {
        throw new RuntimeException(
            $type === 'image'
                ? 'Images must be smaller than 5 MB.'
                : 'PDF documents must be smaller than 10 MB.'
        );
    }

    // Confirm the uploaded temporary file
    if (
        empty($file['tmp_name']) ||
        !is_uploaded_file($file['tmp_name'])
    ) {
        throw new RuntimeException('Invalid uploaded file.');
    }

    // Detect the actual file type using its contents
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!isset($allowedTypes[$type][$mimeType])) {
        throw new RuntimeException(
            $type === 'image'
                ? 'Only JPG, PNG and WebP images are allowed.'
                : 'Only PDF documents are allowed.'
        );
    }

    // Additional image validation
    if ($type === 'image') {

        $imageInfo = getimagesize($file['tmp_name']);

        if (
            $imageInfo === false ||
            $imageInfo['mime'] !== $mimeType
        ) {
            throw new RuntimeException('Invalid image file.');
        }

    }

    // Additional PDF validation
    if ($type === 'pdf') {

        $handle = fopen($file['tmp_name'], 'rb');

        if ($handle === false) {
            throw new RuntimeException('Unable to read the PDF file.');
        }

        $header = fread($handle, 5);

        fclose($handle);

        if ($header !== '%PDF-') {
            throw new RuntimeException('Invalid PDF document.');
        }

    }

    // Select the destination folder
    $folder = $type === 'image' ? 'images' : 'documents';

    $destinationDirectory =
        dirname(__DIR__) . '/uploads/' . $folder;

    // Create the folder if it does not exist
    if (
        !is_dir($destinationDirectory) &&
        !mkdir($destinationDirectory, 0755, true) &&
        !is_dir($destinationDirectory)
    ) {
        throw new RuntimeException(
            'Unable to prepare the upload directory.'
        );
    }

    // Generate a random filename
    $extension = $allowedTypes[$type][$mimeType];

    $filename = bin2hex(random_bytes(16)) . '.' . $extension;

    $destination = $destinationDirectory . '/' . $filename;

    // Move the file to its destination
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException(
            'Unable to save the uploaded file.'
        );
    }

    // Return the relative path to store in MySQL
    return 'uploads/' . $folder . '/' . $filename;
}
