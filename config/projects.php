<?php

return [
    'categories' => [
        'personal' => [
            [
                'name' => 'GoBNB',
                'description' => 'A toy project that imports and displays data from insideairbnb, supports server-side reverse-geocoding via geoapify, and finds listings by city, uses geohashes to bucket listings by city and pre-seeds the cities list on import, React SPA frontend',
                'tech' => ['Go', 'GORM', 'PostgreSQL', 'PostGIS', 'React'],
                'status' => 'WIP',
                'tone' => 'go',
                'icon' => [
                    'fa-solid fa-circle fa-stack-2x',
                    'fa-solid fa-person-walking-luggage fa-stack-1x fa-flip-horizontal fa-inverse'
                ],
                'featured' => true,
                'links' => [
                    ['url' => 'https://github.com/zack6849/go-bnb', 'label' => 'View Code', 'icon' => 'fab fa-github']
                ]
            ],
            [
                'name' => 'Amateur Radio Logbook Map',
                'description' => 'Part of zcraig.me. A queued Laravel job imports my QRZ logbook, parses ADIF records, enriches Parks on the Air contacts, and serves GeoJSON to a Vue and MapLibre map with band and mode filters.',
                'tech' => ['Laravel', 'PHP', 'Vue.js', 'MapLibre', 'QRZ API', 'Queues', 'PHPUnit'],
                'status' => 'Live',
                'tone' => 'radio',
                'icon' => [
                    'fa-solid fa-circle fa-stack-2x',
                    'fa-solid fa-map-location-dot fa-stack-1x fa-inverse',
                ],
                'featured' => true,
                'links' => [
                    ['url' => '/radio', 'label' => 'View the map', 'icon' => 'fas fa-location-dot'],
                    ['url' => 'https://github.com/zack6849/website-laravel', 'label' => 'View website repository', 'icon' => 'fab fa-github'],
                ],
            ],
            [
                'name' => 'Channel Points Prize Wheel',
                'description' => 'A Laravel and Livewire app I built for a friend that lets viewers redeem channel points for prize-wheel spins. It includes Twitch authentication, configurable prizes and odds, sounds and timed effects, and a live browser overlay driven by WebSockets.',
                'tech' => ['Laravel', 'Livewire', 'Reverb', 'WebSockets', 'Twitch API'],
                'tone' => 'prize',
                'icon' => [
                    'fa-solid fa-circle fa-stack-2x',
                    'fa-solid fa-gift fa-stack-1x fa-inverse',
                ],
                'featured' => true,
                'links' => [],
            ],
            [
                'name' => 'YAPI Development Environment',
                'description' => 'A reusable Docker-based Laravel development environment with NGINX, PHP, MariaDB, Redis, MailHog, Selenium, and optional Xdebug support.',
                'tech' => ['Docker', 'NGINX', 'MariaDB', 'Redis', 'Selenium', 'Xdebug'],
                'status' => 'Archived',
                'tone' => 'infrastructure',
                'icon' => [
                    'fa-solid fa-circle fa-stack-2x',
                    'fa-brands fa-docker fa-stack-1x fa-inverse',
                ],
                'links' => [],
            ],
        ],
        'professional' => [
            [
                'name' => 'Marketplace Platform Engineering',
                'description' => 'Backend-focused full-stack work on a mature marketplace platform serving web and mobile clients. I contribute across APIs, background processing, data modeling, and client-facing experiences, and have owned multi-quarter initiatives requiring cross-team coordination, backward compatibility, staged rollouts, and performance improvements.',
                'tech' => ['Laravel', 'PHP', 'Vue.js', 'REST APIs', 'AWS', 'Kubernetes'],
                'tone' => 'marketplace',
                'icon' => [
                    'fa-solid fa-circle fa-stack-2x',
                    'fa-solid fa-store fa-stack-1x fa-inverse',
                ],
                'featured' => true,
                'links' => [],
            ],
            [
                'name' => 'Monitoring and Alerting Platform',
                'description' => 'I built a standalone monitoring product as the sole engineer. It synchronized sensor, location, and account data from several systems, detected missing or stalled devices and unusual usage, and delivered dashboards, configurable alerts, daily reports, and third-party uploads.',
                'tech' => ['Laravel', 'Livewire', 'PHP', 'MySQL', 'Scheduled Jobs', 'REST APIs'],
                'tone' => 'monitoring',
                'icon' => [
                    'fa-solid fa-circle fa-stack-2x',
                    'fa-solid fa-bell fa-stack-1x fa-inverse',
                ],
                'featured' => true,
                'links' => [],
            ],
            [
                'name' => 'Customer Operations and Reporting Portal',
                'description' => 'Built the customer-facing side of a new operations portal from a rough product sketch, working directly with stakeholders to figure out what they actually needed. It included account management, approval workflows, reports, charts, exports, and integrations with third-party business systems.',
                'tech' => ['Laravel', 'Livewire', 'Vue.js', 'PHP', 'MySQL', 'REST APIs'],
                'tone' => 'reporting',
                'icon' => [
                    'fa-solid fa-circle fa-stack-2x',
                    'fa-solid fa-chart-simple fa-stack-1x fa-inverse',
                ],
                'links' => [],
            ],
            [
                'name' => 'Long-Lived PHP Application Modernization',
                'description' => 'Modernized a business-critical PHP application while it remained in active use. Upgraded it from PHP 5.6 to 7.2, addressed security issues, introduced Sentry, and improved its day-to-day stability and debuggability.',
                'tech' => ['PHP', 'MySQL', 'Sentry', 'REST APIs', 'SOAP APIs'],
                'tone' => 'modernization',
                'icon' => [
                    'fa-solid fa-circle fa-stack-2x',
                    'fa-solid fa-screwdriver-wrench fa-stack-1x fa-inverse',
                ],
                'links' => [],
            ],
            [
                'name' => 'E-Commerce Platform Development and Migration',
                'description' => 'As the only in-house web developer for a multi-store e-commerce company, I built custom Magento modules, maintained several storefronts, and wrote a data extractor to migrate an acquired Shopify store into Magento 2 when there was no usable native export.',
                'tech' => ['Magento 2', 'PHP', 'MySQL', 'JavaScript', 'REST APIs'],
                'tone' => 'commerce',
                'icon' => [
                    'fa-solid fa-circle fa-stack-2x',
                    'fa-solid fa-cart-shopping fa-stack-1x fa-inverse',
                ],
                'links' => [],
            ],
        ],
    ],
];
