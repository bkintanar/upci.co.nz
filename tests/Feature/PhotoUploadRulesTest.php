<?php

use App\Filament\Resources\Shared\PhotoUpload;

/*
 * One definition of a photo upload (plan: announcement-photo-and-department-
 * gallery). These are the rules every photo field in the admin inherits, so a
 * phone photo that uploads in one place uploads in all of them.
 */

test('a photo upload goes to the public disk, publicly visible', function () {
    $field = PhotoUpload::make('image_path', 'gallery');

    expect($field->getDiskName())->toBe('public')
        ->and($field->getVisibility())->toBe('public')
        ->and($field->getDirectory())->toBe('gallery');
});

test('a photo upload accepts jpeg, png and webp, never svg', function () {
    expect(PhotoUpload::make('image_path', 'gallery')->getAcceptedFileTypes())
        ->toBe(['image/jpeg', 'image/png', 'image/webp'])
        ->not->toContain('image/svg+xml')
        ->not->toContain('image/*');
});

test('a photo upload takes 10 MB and shrinks it to fit 2000px without cropping', function () {
    $field = PhotoUpload::make('image_path', 'gallery');

    expect($field->getMaxSize())->toBe(10240)
        ->and($field->getAutomaticallyResizeImagesMode())->toBe('contain')
        ->and($field->getAutomaticallyResizeImagesWidth())->toBe('2000')
        ->and($field->getAutomaticallyResizeImagesHeight())->toBe('2000');
});

test('every photo field in the admin uses the shared definition', function (string $path) {
    $source = file_get_contents(app_path($path));

    expect($source)->toContain('PhotoUpload::make(')
        ->and($source)->not->toContain("FileUpload::make('image_path')")
        ->and($source)->not->toContain('FileUpload::make("image_path")');
})->with([
    'announcements' => 'Filament/Resources/Departments/RelationManagers/AnnouncementsRelationManager.php',
    'gallery relation manager' => 'Filament/Resources/Shared/GalleryItemsRelationManager.php',
    'standalone gallery form' => 'Filament/Resources/GalleryItems/Schemas/GalleryItemForm.php',
]);
