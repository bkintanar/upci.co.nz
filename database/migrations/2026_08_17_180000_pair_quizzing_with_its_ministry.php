<?php

use App\Models\MenuItem;
use Illuminate\Database\Migrations\Migration;

/**
 * SBQ belongs with Youth, JBQ with Children's.
 *
 * The first pass put all four items in one flat list ordered Youth,
 * Children's, SBQ, JBQ — so both quizzing links sat at the bottom, associated
 * with neither ministry. Senior Bible Quizzing is a youth programme and Junior
 * Bible Quizzing a children's one, which the flat order lost.
 *
 * Ordered as pairs rather than nested: the navbar renders exactly two levels
 * (`item.children`, no grandchildren), so a third level would not display at
 * all. Interleaving is what actually reads as "SBQ under Youth" in the menu
 * the site has, and the labels carry the full programme name so the pairing is
 * legible rather than implied by position alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        $parent = MenuItem::where('location', 'header')
            ->where('label', "Youth & Children's")
            ->whereNull('parent_id')
            ->first();

        if (! $parent) {
            return;
        }

        $order = [
            '/departments/youth' => ['sort' => 10, 'label' => 'Youth Ministry'],
            '/youth-and-childrens/sbq' => ['sort' => 20, 'label' => 'SBQ — Senior Bible Quizzing'],
            '/departments/childrens' => ['sort' => 30, 'label' => "Children's Ministry"],
            '/youth-and-childrens/jbq' => ['sort' => 40, 'label' => 'JBQ — Junior Bible Quizzing'],
        ];

        foreach ($order as $url => $spec) {
            MenuItem::where('parent_id', $parent->id)
                ->where('url', $url)
                ->update(['sort_order' => $spec['sort'], 'label' => $spec['label']]);
        }
    }

    public function down(): void
    {
        $parent = MenuItem::where('location', 'header')
            ->where('label', "Youth & Children's")
            ->whereNull('parent_id')
            ->first();

        if (! $parent) {
            return;
        }

        foreach ([
            '/departments/youth' => ['sort' => 10, 'label' => 'Youth Ministry'],
            '/departments/childrens' => ['sort' => 20, 'label' => "Children's Ministry"],
            '/youth-and-childrens/sbq' => ['sort' => 30, 'label' => 'SBQ'],
            '/youth-and-childrens/jbq' => ['sort' => 40, 'label' => 'JBQ'],
        ] as $url => $spec) {
            MenuItem::where('parent_id', $parent->id)
                ->where('url', $url)
                ->update(['sort_order' => $spec['sort'], 'label' => $spec['label']]);
        }
    }
};
