# CMS System Documentation

## Overview

The CMS (Content Management System) has been successfully implemented for managing frontend page content through the admin panel. This system allows you to create flexible, block-based pages that are separate from the existing Vue.js frontend pages.

## Features

### Admin Panel Features

-   **Block-Based Editor**: Create pages using flexible content blocks
-   **Multiple Block Types**:

    -   **Hero Section**: Eye-catching headers with customizable backgrounds and styles
    -   **Text Block**: Rich text with markdown support
    -   **Image Block**: Images with captions and alt text
    -   **Two Column Layout**: Side-by-side content sections
    -   **Call to Action**: Promotional sections with buttons
    -   **Card Grid**: Display multiple cards in a grid layout
    -   **Embed Code**: Add YouTube videos, Google Maps, or other embeds

-   **SEO Features**:

    -   Custom meta descriptions
    -   Customizable slugs
    -   Publishing controls (draft/published)
    -   Sort ordering

-   **User-Friendly Interface**:
    -   Auto-generate slugs from titles
    -   Live preview links
    -   Drag-and-drop block reordering
    -   Image upload and management
    -   Markdown editor with toolbar

## URL Structure

-   **CMS Pages**: All CMS-managed pages are accessible at `/cms/{slug}`
-   **Existing Pages**: Your original Vue.js pages remain at their original URLs:
    -   `/` - Home
    -   `/about` - About
    -   `/find-church` - Church Locator
    -   etc.

## Getting Started

### Accessing the Admin Panel

1. Navigate to `/admin` and log in
2. Look for **"CMS Pages"** under the **"Content"** navigation group
3. Click to view all pages

### Creating a New Page

1. Click the **"Create"** button
2. Fill in the page details:

    - **Title**: The page title (slug auto-generates from this)
    - **Slug**: URL-friendly identifier (e.g., `my-page` for `/cms/my-page`)
    - **Meta Description**: SEO description (150-160 characters recommended)
    - **Published**: Toggle to control visibility
    - **Sort Order**: Number for ordering pages (lower appears first)

3. Add content blocks:

    - Click **"Add item"** to choose a block type
    - Fill in the block fields
    - Reorder blocks by dragging
    - Delete blocks using the delete icon

4. Click **"Create"** to save

### Viewing Pages

-   Click the slug in the table to open the page in a new tab
-   Or visit `/cms/{your-slug}` directly
-   Use the **"View Page"** action on published pages

## Sample Pages

Two sample pages have been created for you:

-   `/cms/welcome` - Welcome to Our CMS
-   `/cms/about-cms` - About Our CMS System

## API Endpoints

The CMS exposes two API endpoints:

-   `GET /api/pages` - List all published pages
-   `GET /api/pages/{slug}` - Get a specific page by slug

## Database

The `pages` table stores all CMS content:

-   `title` - Page title
-   `slug` - URL slug (unique)
-   `meta_description` - SEO meta description
-   `content` - JSON array of content blocks
-   `is_published` - Boolean visibility flag
-   `sort_order` - Integer for sorting

## Technical Stack

-   **Backend**: Laravel 11 + Filament v4
-   **Frontend**: Vue.js 3 + Vue Router
-   **Markdown**: Marked.js for rendering
-   **Styling**: Tailwind CSS

## Migration Plan

When you're ready to migrate existing pages to the CMS:

1. Create equivalent pages in the CMS admin
2. Test the CMS versions at `/cms/{slug}`
3. Update routes in `routes/web.php` to point to CMS pages
4. Update Vue router to load CMS content instead of static Vue components
5. Archive or remove old Vue components

## Tips

-   Use the **sort_order** field to control the order of pages in listings
-   Add descriptive **meta descriptions** for better SEO
-   Use the **draft mode** (unpublished) to work on pages before making them live
-   Images are stored in `storage/app/public/page-images`
-   Run `php artisan storage:link` if images don't display

## Support

If you need to add new block types or customize the CMS, edit:

-   `app/Filament/Resources/Pages/Schemas/PageForm.php` - Admin form builder
-   `resources/js/views/CmsPage.vue` - Frontend renderer
