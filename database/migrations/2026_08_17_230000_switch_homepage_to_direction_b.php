<?php

use Illuminate\Database\Migrations\Migration;

/**
 * NEUTRALISED. This migration switched the homepage to Direction B's block
 * sequence. The user reviewed the result and rejected it, so the homepage was
 * rolled back to its original content.
 *
 * The rollback was not enough, and that is the lesson worth recording here.
 * `migrate:rollback` removes the migration's ROW, which leaves the file in the
 * "not yet run" state — so the next `php artisan migrate` for any unrelated
 * work re-applied it, and the rejected homepage went live again silently. It
 * survived several iterations that way before a full-site sweep noticed the
 * homepage had no h1.
 *
 * Rolling back a migration undoes the DATA. It does not withdraw the
 * INSTRUCTION. A migration whose change has been rejected has to be emptied,
 * not merely rolled back.
 *
 * The body is kept in git history (see 981a379) and the block sequence it wrote
 * is preserved as three reviewable directions under
 * .claude/design/upci-redesign/design-demos/home-r2/. Nothing is lost by this
 * file doing nothing.
 *
 * The homepage's own content now lives where it should: in the CMS, editable,
 * and archived at the `home-previous` page.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Deliberately empty. See the class comment.
    }

    public function down(): void
    {
        // Deliberately empty.
    }
};
