<?php

use App\Models\Page;
use App\Models\MenuItem;
use Illuminate\Database\Migrations\Migration;

/**
 * The quizzing URLs now follow their parent's path.
 *
 * They sat at /youth-and-childrens/sbq and /youth-and-childrens/jbq, a path
 * invented for a menu section that no longer exists as a URL — the section
 * parent links to "#". With SBQ nested under Youth Ministry and JBQ under
 * Children's Ministry, the address should say the same thing the menu does.
 *
 *   /departments/youth/sbq
 *   /departments/childrens/jbq
 *
 * Both the CMS page slug and the menu row move together; leaving either behind
 * breaks the link.
 */
return new class extends Migration
{
    private const MOVES = [
        'youth-and-childrens/sbq' => 'departments/youth/sbq',
        'youth-and-childrens/jbq' => 'departments/childrens/jbq',
    ];

    public function up(): void
    {
        $this->move(self::MOVES);
    }

    public function down(): void
    {
        $this->move(array_flip(self::MOVES));
    }

    /**
     * @param  array<string, string>  $moves  old slug => new slug
     */
    private function move(array $moves): void
    {
        foreach ($moves as $from => $to) {
            Page::where('slug', $from)->update(['slug' => $to]);

            MenuItem::where('url', '/'.$from)->update(['url' => '/'.$to]);
        }
    }
};
