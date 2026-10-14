<?php

use App\Models\User;
use Livewire\Livewire;
use App\Enums\UserRole;
use App\Enums\AccessLevel;
use App\Models\Department;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use App\Models\DepartmentAnnouncement;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use App\Filament\Resources\Departments\Pages\EditDepartment;
use App\Filament\Resources\Departments\RelationManagers\AnnouncementsRelationManager;

/*
 * One optional photo on a department announcement (owner request,
 * 2026-10-15). Before this, authors pasted Facebook links and embeds into the
 * body because there was nowhere to put the image.
 */
beforeEach(function () {
    Storage::fake('public');
    Filament::setCurrentPanel('admin');

    $this->actingAs(User::create([
        'name' => 'N', 'email' => 'announcement-photo@x', 'password' => 'x',
        'role' => UserRole::ADMINISTRATOR, 'access_level' => AccessLevel::NATIONAL,
    ]));

    $this->department = Department::firstOrCreate(['slug' => 'mens'], ['name' => "National Men's Department"]);
});

function announcementPhotoManager(): \Livewire\Features\SupportTesting\Testable
{
    return Livewire::test(AnnouncementsRelationManager::class, [
        'ownerRecord' => test()->department,
        'pageClass' => EditDepartment::class,
    ]);
}

function announcementWithPhoto(array $attrs = []): DepartmentAnnouncement
{
    return DepartmentAnnouncement::create(array_merge([
        'department_id' => test()->department->id,
        'title' => 'Men\'s Rally',
        'content' => 'Body',
        'published_at' => now()->subDay(),
        'is_published' => true,
    ], $attrs));
}

test('announcements have nullable photo and description columns', function () {
    expect(Schema::hasColumns('department_announcements', ['image_path', 'image_alt']))->toBeTrue();

    $plain = announcementWithPhoto();

    expect($plain->fresh()->image_path)->toBeNull()
        ->and($plain->fresh()->image_alt)->toBeNull();
});

test('an admin can attach a photo with a description', function () {
    announcementPhotoManager()
        ->callTableAction('create', data: [
            'title' => 'Junior Bible Quizzing',
            'image_path' => UploadedFile::fake()->image('flyer.jpg', 800, 1131),
            'image_alt' => 'Flyer: Junior Bible Quizzing, 14 March, Hamilton',
        ])
        ->assertHasNoTableActionErrors();

    $announcement = DepartmentAnnouncement::where('title', 'Junior Bible Quizzing')->sole();

    expect($announcement->image_path)->toStartWith('announcements/')
        ->and($announcement->image_alt)->toBe('Flyer: Junior Bible Quizzing, 14 March, Hamilton');

    Storage::disk('public')->assertExists($announcement->image_path);
});

test('a photo without a description is refused in plain words', function () {
    announcementPhotoManager()
        ->callTableAction('create', data: [
            'title' => 'No description',
            'image_path' => UploadedFile::fake()->image('flyer.jpg'),
        ])
        ->assertHasTableActionErrors(['image_alt' => 'Describe the photo for people who cannot see it.']);

    expect(DepartmentAnnouncement::where('title', 'No description')->exists())->toBeFalse();
});

test('an svg is refused as an announcement photo', function () {
    announcementPhotoManager()
        ->callTableAction('create', data: [
            'title' => 'Vector',
            'image_path' => UploadedFile::fake()->createWithContent('x.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>'),
            'image_alt' => 'A drawing',
        ])
        ->assertHasTableActionErrors(['image_path']);

    expect(DepartmentAnnouncement::where('title', 'Vector')->exists())->toBeFalse();
});

test('an announcement without a photo still saves as before', function () {
    announcementPhotoManager()
        ->callTableAction('create', data: ['title' => 'Text only', 'content' => 'Just words.'])
        ->assertHasNoTableActionErrors();

    expect(DepartmentAnnouncement::where('title', 'Text only')->sole()->image_path)->toBeNull();
});

test('the announcements table shows a photo column', function () {
    announcementWithPhoto(['image_path' => 'announcements/a.jpg', 'image_alt' => 'A']);

    announcementPhotoManager()->assertTableColumnExists('image_path');
});

test('the department page gets the photo and its description', function () {
    announcementWithPhoto(['image_path' => 'announcements/rally.jpg', 'image_alt' => 'Men at the rally']);

    $this->getJson('/api/departments/mens')
        ->assertOk()
        ->assertJsonPath('data.announcements.0.image_path', 'announcements/rally.jpg')
        ->assertJsonPath('data.announcements.0.image_alt', 'Men at the rally');
});

test('an announcement without a photo sends both keys as null', function () {
    announcementWithPhoto();

    $announcement = $this->getJson('/api/departments/mens')->json('data.announcements.0');

    expect($announcement)->toHaveKeys(['image_path', 'image_alt'])
        ->and($announcement['image_path'])->toBeNull()
        ->and($announcement['image_alt'])->toBeNull();
});

test('a description saved without a photo never reaches the page', function () {
    announcementWithPhoto(['image_alt' => 'Stray words']);

    expect($this->getJson('/api/departments/mens')->json('data.announcements.0.image_alt'))->toBeNull();
});

test('the page gets the photo\'s pixel size, so a flyer and a wide photo can be laid out differently', function () {
    Storage::disk('public')->putFileAs('announcements', UploadedFile::fake()->image('flyer.jpg', 800, 1131), 'flyer.jpg');
    announcementWithPhoto(['image_path' => 'announcements/flyer.jpg', 'image_alt' => 'Flyer']);

    $this->getJson('/api/departments/mens')
        ->assertJsonPath('data.announcements.0.image_width', 800)
        ->assertJsonPath('data.announcements.0.image_height', 1131);
});

test('the size is null without a photo, or when the file is missing', function () {
    announcementWithPhoto(['title' => 'No photo', 'sort_order' => 0]);
    announcementWithPhoto(['title' => 'Missing file', 'image_path' => 'announcements/gone.jpg', 'image_alt' => 'x', 'sort_order' => 1]);

    $announcements = collect($this->getJson('/api/departments/mens')->json('data.announcements'))->keyBy('title');

    expect($announcements['No photo'])->toMatchArray(['image_width' => null, 'image_height' => null])
        ->and($announcements['Missing file'])->toMatchArray(['image_width' => null, 'image_height' => null]);
});
