<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\UploadImage;

final class UploadListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(UploadImage $event): string
    {
        if ($event->files === null) {
            // Single file upload
            $event->model->clearMediaCollection($event->collectionName);
            $singleFile = request()->file($event->collectionName);

            return $this->handleSingleFileUpload($event->model, $event->collectionName, $singleFile);
        } else {
            // Multiple file upload
            return $this->handleMultipleFileUploads($event->model, $event->collectionName, $event->files);
        }
    }

    private function handleSingleFileUpload($model, $collectionName, $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $imgName = $collectionName.'-'.date('Y.m.d').rand(1111, 9999);
        $model->addMediaFromRequest($collectionName)
            ->usingName($imgName)
            ->usingFileName($imgName.'.'.$extension)
            ->toMediaCollection($collectionName);

        return $imgName;
    }

    private function handleMultipleFileUploads($model, $collectionName, $files): string
    {
        $uploadedImages = [];
        foreach ($files as $photo) {
            $extension = $photo->getClientOriginalExtension();
            $imgName = $collectionName.'-'.date('Y.m.d').random_int(1111, 9999);
            $model->addMedia($photo)
                ->usingName($imgName)
                ->usingFileName($imgName.'.'.$extension)
                ->toMediaCollection($collectionName);
            $uploadedImages[] = $imgName;
        }

        // Return comma-separated list of uploaded images
        return implode(',', $uploadedImages);
    }
}
