import "./icons";
import "./nav";
import "./contact-anchor";
import RelativeUTCTime from "./support/RelativeUTCTime";

window.RelativeUTCTime = RelativeUTCTime;

const hydrateRelativeUTCTimes = () => RelativeUTCTime.hydrate();
const dismissNotification = (message) => {
    if (! message || message.dataset.notificationDismissing === 'true') {
        return;
    }

    message.dataset.notificationDismissing = 'true';
    message.classList.add('notification-message-dismissing');

    window.setTimeout(() => {
        const stack = message.closest('.notification-stack');

        message.remove();

        if (stack && stack.querySelectorAll('[data-notification-message]').length === 0) {
            stack.remove();
        }
    }, 200);
};

const hydrateDismissibleNotifications = () => {
    document.querySelectorAll('[data-notification-message]').forEach((message) => {
        if (message.dataset.notificationInitialized === 'true') {
            return;
        }

        message.dataset.notificationInitialized = 'true';

        message.querySelector('[data-notification-dismiss]')?.addEventListener('click', () => {
            dismissNotification(message);
        });

        const dismissAfter = Number(message.dataset.notificationDismissAfter || 0);

        if (Number.isFinite(dismissAfter) && dismissAfter > 0) {
            window.setTimeout(() => dismissNotification(message), dismissAfter);
        }
    });
};
const hydratePageEnhancements = () => {
    hydrateRelativeUTCTimes();
    hydrateDismissibleNotifications();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', hydratePageEnhancements, { once: true });
} else {
    hydratePageEnhancements();
}

document.addEventListener('livewire:navigated', hydratePageEnhancements);
