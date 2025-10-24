<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a sample welcome page
        Page::create([
            'title' => 'Welcome to Our CMS',
            'slug' => 'welcome',
            'meta_description' => 'Welcome to the UPCI New Zealand Content Management System. Create beautiful, flexible pages with our block-based editor.',
            'is_published' => true,
            'sort_order' => 1,
            'content' => [
                [
                    'type' => 'hero',
                    'data' => [
                        'heading' => 'Welcome to Our CMS',
                        'subheading' => 'Create beautiful, flexible pages with our powerful content management system',
                        'style' => 'gradient-blue',
                    ],
                ],
                [
                    'type' => 'text',
                    'data' => [
                        'heading' => 'About This CMS',
                        'content' => "This is a flexible content management system built with **Filament** and **Laravel**. You can create pages with various content blocks including:\n\n- Hero sections with customizable backgrounds\n- Text blocks with markdown support\n- Images with captions\n- Two-column layouts\n- Call-to-action sections\n- Card grids\n- Embed codes for videos, maps, and more\n\nAll pages created here are accessible at `/cms/[page-slug]` URLs.",
                    ],
                ],
                [
                    'type' => 'cards',
                    'data' => [
                        'heading' => 'Key Features',
                        'items' => [
                            [
                                'type' => 'card',
                                'data' => [
                                    'title' => 'Block-Based Editor',
                                    'description' => 'Build pages using flexible content blocks. Add, remove, and reorder blocks to create the perfect layout.',
                                ],
                            ],
                            [
                                'type' => 'card',
                                'data' => [
                                    'title' => 'Markdown Support',
                                    'description' => 'Write content using Markdown for easy formatting. Add headings, lists, links, and more with simple syntax.',
                                ],
                            ],
                            [
                                'type' => 'card',
                                'data' => [
                                    'title' => 'SEO Friendly',
                                    'description' => 'Set meta descriptions, customize slugs, and control page visibility to optimize for search engines.',
                                ],
                            ],
                            [
                                'type' => 'card',
                                'data' => [
                                    'title' => 'Image Management',
                                    'description' => 'Upload and manage images directly in the editor. Add alt text and captions for accessibility.',
                                ],
                            ],
                            [
                                'type' => 'card',
                                'data' => [
                                    'title' => 'Flexible Layouts',
                                    'description' => 'Choose from multiple layout options including single column, two-column, and card grids.',
                                ],
                            ],
                            [
                                'type' => 'card',
                                'data' => [
                                    'title' => 'Easy to Use',
                                    'description' => 'Intuitive admin interface built with Filament makes creating and managing pages a breeze.',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'type' => 'cta',
                    'data' => [
                        'heading' => 'Ready to Create Your First Page?',
                        'text' => 'Head over to the admin panel and start building beautiful pages with our content management system.',
                        'button_text' => 'Go to Admin Panel',
                        'button_url' => '/admin/pages',
                        'style' => 'blue',
                    ],
                ],
            ],
        ]);

        // Create a sample about page
        Page::create([
            'title' => 'About Our CMS System',
            'slug' => 'about-cms',
            'meta_description' => 'Learn about the powerful content management system powering UPCI New Zealand.',
            'is_published' => true,
            'sort_order' => 2,
            'content' => [
                [
                    'type' => 'hero',
                    'data' => [
                        'heading' => 'About Our CMS',
                        'subheading' => 'Built with modern technologies for maximum flexibility',
                        'style' => 'gradient-indigo',
                    ],
                ],
                [
                    'type' => 'two_column',
                    'data' => [
                        'left_content' => "## Technology Stack\n\nOur CMS is built using:\n\n- **Laravel 11** - The PHP framework for web artisans\n- **Filament v4** - Beautiful admin panels for Laravel\n- **Vue.js 3** - Progressive JavaScript framework\n- **Tailwind CSS** - Utility-first CSS framework",
                        'right_content' => "## Features\n\nKey features include:\n\n- Drag-and-drop content blocks\n- Real-time preview\n- Image upload and management\n- Markdown editor\n- SEO optimization tools\n- Publishing controls",
                    ],
                ],
                [
                    'type' => 'text',
                    'data' => [
                        'heading' => 'Getting Started',
                        'content' => "To create a new page:\n\n1. Log in to the admin panel at `/admin`\n2. Navigate to **Pages** in the sidebar\n3. Click **Create Page**\n4. Fill in the page details (title, slug, meta description)\n5. Add content blocks using the builder\n6. Preview and publish your page\n\nYour page will be accessible at `/cms/your-page-slug`.",
                    ],
                ],
            ],
        ]);
    }
}
