<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $table = 'pages';

    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'is_active',
        'is_homepage',
        'meta_title',
        'meta_description',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_homepage' => 'boolean',
    ];

    public static function getDefaultPages(): array
    {
        return [
            [
                'title' => 'Home',
                'slug' => 'home',
                'content' => '<h1>Welcome to our Store</h1><p>Discover quality products at great prices.</p>',
                'excerpt' => 'Homepage content',
                'is_active' => true,
                'is_homepage' => true,
                'meta_title' => 'Home - Our Store',
                'meta_description' => 'Welcome to our online store',
                'sort_order' => 1,
            ],
            [
                'title' => 'About Us',
                'slug' => 'about',
                'content' => '<h1>About Us</h1><p>Learn more about our company.</p>',
                'excerpt' => 'About our company',
                'is_active' => true,
                'is_homepage' => false,
                'meta_title' => 'About Us',
                'meta_description' => 'Learn more about our company',
                'sort_order' => 2,
            ],
            [
                'title' => 'Contact',
                'slug' => 'contact',
                'content' => '<h1>Contact Us</h1><p>Get in touch with us.</p>',
                'excerpt' => 'Contact information',
                'is_active' => true,
                'is_homepage' => false,
                'meta_title' => 'Contact Us',
                'meta_description' => 'Contact our team',
                'sort_order' => 3,
            ],
        ];
    }
}