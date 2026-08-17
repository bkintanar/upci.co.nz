<?php

use App\Models\MenuItem;
use Illuminate\Database\Migrations\Migration;

/**
 * SBQ becomes a child of Youth Ministry, JBQ a child of Children's Ministry.
 *
 * The previous pass interleaved all four as siblings, because the navbar
 * rendered only two levels. That was a workaround for a rendering limit rather
 * than the right structure, and it still read as one flat list. The API now
 * formats children recursively and the navbar renders a third level, so the
 * hierarchy can be what it should have been: each quizzing programme sits
 * inside the ministry that runs it.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->reparent([
            '/youth-and-childrens/sbq' => '/departments/youth',
            '/youth-and-childrens/jbq' => '/departments/childrens',
        ]);
    }

    /**
     * Returns them to siblings of their ministries under the section parent.
     */
    public function down(): void
    {
        $section = MenuItem::where('location', 'header')
            ->where('label', "Youth & Children's")
            ->whereNull('parent_id')
            ->first();

        if (! $section) {
            return;
        }

        MenuItem::where('location', 'header')
            ->whereIn('url', ['/youth-and-childrens/sbq', '/youth-and-childrens/jbq'])
            ->update(['parent_id' => $section->id]);
    }

    /**
     * @param  array<string, string>  $childToParentUrl
     */
    private function reparent(array $childToParentUrl): void
    {
        foreach ($childToParentUrl as $childUrl => $parentUrl) {
            $parent = MenuItem::where('location', 'header')->where('url', $parentUrl)->first();
            $child = MenuItem::where('location', 'header')->where('url', $childUrl)->first();

            if (! $parent || ! $child) {
                continue;
            }

            $child->update(['parent_id' => $parent->id, 'sort_order' => 10]);
        }
    }
};
