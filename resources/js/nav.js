/**
 * Mobile hamburger toggle for the site nav.
 *
 * This is plain JS (not part of the Vue app) on purpose: the nav is present
 * on every page, but the Vue bundle is only loaded on pages that opt into it
 * (`<x-base-page vue="true">`) — driving the nav off Vue's root instance meant
 * it silently stopped working on any page that didn't load vue.js.
 */
document.addEventListener('DOMContentLoaded', () => {
    const navToggle = document.querySelector('[data-nav-toggle]');
    const navContent = document.querySelector('[data-nav-content]');

    const closeNav = () => navContent?.classList.add('hidden');
    const closeDropdown = (dropdown, returnFocus = false) => {
        const toggle = dropdown.querySelector('[data-dropdown-toggle]');
        const menu = dropdown.querySelector('[data-dropdown-menu]');

        if (!toggle || !menu) {
            return;
        }

        toggle.setAttribute('aria-expanded', 'false');
        menu.hidden = true;

        if (returnFocus) {
            toggle.focus();
        }
    };

    if (navToggle && navContent) {
        navToggle.addEventListener('click', () => {
            navContent.classList.toggle('hidden');
        });

        navContent.querySelectorAll('a, button[type="submit"]').forEach((el) => {
            el.addEventListener('click', closeNav);
        });
    }

    document.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        const dropdown = event.target.closest('[data-dropdown]');
        const toggle = event.target.closest('[data-dropdown-toggle]');

        if (toggle && dropdown) {
            const wasOpen = toggle.getAttribute('aria-expanded') === 'true';

            document.querySelectorAll('[data-dropdown]').forEach((item) => {
                if (item !== dropdown) {
                    closeDropdown(item);
                }
            });

            const menu = dropdown.querySelector('[data-dropdown-menu]');
            toggle.setAttribute('aria-expanded', String(!wasOpen));
            if (menu) {
                menu.hidden = wasOpen;
            }
            return;
        }

        if (dropdown && event.target.closest('[data-dropdown-menu] a, [data-dropdown-menu] button')) {
            closeDropdown(dropdown);
            return;
        }

        document.querySelectorAll('[data-dropdown]').forEach((item) => {
            if (!item.contains(event.target)) {
                closeDropdown(item);
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        document.querySelectorAll('[data-dropdown]').forEach((dropdown) => {
            const toggle = dropdown.querySelector('[data-dropdown-toggle]');
            const isOpen = toggle?.getAttribute('aria-expanded') === 'true';
            closeDropdown(dropdown, isOpen);
        });
    });
});
