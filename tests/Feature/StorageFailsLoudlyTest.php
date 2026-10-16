<?php

use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToWriteFile;

/*
 * A failed write must fail loudly (2026-10-15). With 'throw' => false a write
 * the web server could not make returned false quietly: Filament kept the
 * generated path, the record saved, and the file was never written. That lost
 * a department gallery photo and a department hero image on staging, because
 * artisan commands run as root had left root-owned upload folders the web
 * server (www-data) could not write into.
 */

test('the public and private disks report a failed write', function (string $disk) {
    expect(config("filesystems.disks.{$disk}.throw"))->toBeTrue();
})->with(['public', 'local']);

test('a write that cannot happen throws instead of returning false', function () {
    // A folder already sitting where the file should go makes the write fail
    // the way a permission problem does (Flysystem's UnableToWriteFile), even
    // for root, so this holds whoever runs the suite.
    $root = sys_get_temp_dir().'/storage-fails-loudly-'.uniqid();
    mkdir("{$root}/gallery/photo.jpg", 0777, true);

    $loud = Storage::build(array_merge(config('filesystems.disks.public'), ['root' => $root]));
    $quiet = Storage::build(array_merge(config('filesystems.disks.public'), ['root' => $root, 'throw' => false]));

    expect($quiet->put('gallery/photo.jpg', 'x'))->toBeFalse()
        ->and(fn () => $loud->put('gallery/photo.jpg', 'x'))->toThrow(UnableToWriteFile::class);

    rmdir("{$root}/gallery/photo.jpg");
    rmdir("{$root}/gallery");
    rmdir($root);
});
