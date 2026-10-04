import {dom, library} from '@fortawesome/fontawesome-svg-core';
import {
    faBell, faCartShopping, faChartSimple, faChevronDown, faCircle,
    faCircleCheck, faCircleInfo, faCircleXmark, faEnvelope, faFile,
    faGear, faGift, faLocationDot, faMapLocationDot, faPaperPlane,
    faPersonWalkingLuggage, faPlus, faScrewdriverWrench, faStore,
    faTowerCell, faTriangleExclamation, faUser, faXmark,
} from '@fortawesome/free-solid-svg-icons';
import {
    faDocker, faGithub, faLinkedin, faStackOverflow,
} from '@fortawesome/free-brands-svg-icons';

library.add(
    faBell, faCartShopping, faChartSimple, faChevronDown, faCircle,
    faCircleCheck, faCircleInfo, faCircleXmark, faEnvelope, faFile,
    faGear, faGift, faLocationDot, faMapLocationDot, faPaperPlane,
    faPersonWalkingLuggage, faPlus, faScrewdriverWrench, faStore,
    faTowerCell, faTriangleExclamation, faUser, faXmark,
    faDocker, faGithub, faLinkedin, faStackOverflow,
);

// Also converts icons inserted by Vue and Livewire, including stacked icons.
dom.watch();
