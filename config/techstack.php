<?php

// Tiers: 1 = Primary, 2 = Other production experience, 3 = Earlier experience.

return [
    'categories' => [
        'Backend & APIs' => [
            ['name' => 'Laravel', 'image' => 'img/logos/laravel.svg', 'tier' => 1],
            ['name' => 'PHP', 'image' => 'img/logos/php.svg', 'tier' => 1],
            ['name' => 'Livewire', 'image' => 'img/logos/livewire.svg', 'tier' => 1],
            ['name' => 'REST APIs', 'image' => 'img/logos/rest-api.svg', 'tier' => 1],
            ['name' => 'NestJS', 'tier' => 2],
            ['name' => 'Node.js', 'tier' => 2],
            ['name' => 'Symfony', 'tier' => 2],
            ['name' => 'MikroORM', 'tier' => 2],
            ['name' => 'WordPress', 'tier' => 3],
            ['name' => 'WooCommerce', 'tier' => 3],
            ['name' => 'Magento 2', 'tier' => 3],
        ],
        'Frontend' => [
            ['name' => 'Vue.js', 'image' => 'img/logos/vue.svg', 'tier' => 1],
            ['name' => 'JavaScript', 'image' => 'img/logos/javascript.svg', 'tier' => 1],
            ['name' => 'HTML', 'image' => 'img/logos/html5.svg', 'tier' => 1],
            ['name' => 'CSS', 'image' => 'img/logos/css.svg', 'tier' => 1],
            ['name' => 'Tailwind CSS', 'image' => 'img/logos/tailwindcss.svg', 'tier' => 1],
            ['name' => 'Bootstrap', 'image' => 'img/logos/bootstrap.svg', 'tier' => 1],
            ['name' => 'TypeScript', 'tier' => 2],
            ['name' => 'Vuetify', 'tier' => 2],
            ['name' => 'jQuery', 'tier' => 3],
            ['name' => 'React', 'tier' => 2]
        ],
        'Data & Messaging' => [
            ['name' => 'MySQL', 'image' => 'img/logos/mysql.svg', 'tier' => 1],
            ['name' => 'Redis', 'image' => 'img/logos/redis.svg', 'tier' => 1],
            ['name' => 'MongoDB', 'tier' => 2],
            ['name' => 'Apache Kafka', 'image' => 'img/logos/kafka.svg', 'tier' => 2],
            ['name' => 'PostgreSQL', 'tier' => 2],
        ],
        'Infrastructure & Observability' => [
            ['name' => 'AWS', 'image' => 'img/logos/aws.svg', 'tier' => 1],
            ['name' => 'Linux', 'image' => 'img/logos/linux.svg', 'tier' => 1],
            ['name' => 'Docker', 'image' => 'img/logos/docker.svg', 'tier' => 1],
            ['name' => 'NGINX', 'image' => 'img/logos/nginx.svg', 'tier' => 1],
            ['name' => 'DigitalOcean', 'image' => 'img/logos/digitalocean.svg', 'tier' => 1],
            ['name' => 'Kubernetes', 'image' => 'img/logos/kubernetes.svg', 'tier' => 2],
            ['name' => 'Grafana', 'image' => 'img/logos/grafana.svg', 'tier' => 2],
            ['name' => 'Sentry', 'tier' => 2],
            ['name' => 'Apache', 'tier' => 2],
            ['name' => 'Tomcat', 'tier' => 3],
        ],
        'Testing & Tooling' => [
            ['name' => 'PHPUnit', 'image' => 'img/logos/phpunit.svg', 'tier' => 1],
            ['name' => 'Pest', 'image' => 'img/logos/pest.svg', 'tier' => 1],
            ['name' => 'Cypress', 'image' => 'img/logos/cypress.svg', 'tier' => 1],
            ['name' => 'Xdebug', 'image' => 'img/logos/xdebug.svg', 'tier' => 1],
            ['name' => 'Git', 'image' => 'img/logos/git.svg', 'tier' => 1],
            ['name' => 'Composer', 'image' => 'img/logos/composer.svg', 'tier' => 1],
            ['name' => 'Playwright', 'tier' => 2],
            ['name' => 'GitHub Actions', 'tier' => 2],
            ['name' => 'Selenium', 'tier' => 3],
        ],
    ],
    'exploring' => [
        ['name' => 'Python'],
        ['name' => 'Go'],
    ],
];
