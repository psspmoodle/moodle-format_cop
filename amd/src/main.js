import Config from 'core/config';

const TAGS = document.querySelectorAll('.tag_list li a');

/**
 * Is this page a forum page?
 * @returns {boolean}
 */
function isForum() {
    return document.querySelector('body').classList.contains('path-mod-forum');
}

/**
 * Get course ID from body class.
 * @returns {string}
 */
function getCourseId() {
    return document.querySelector('body').className.match(/course-(\d+)/)[1];
}

/**
 *
 * @param {Element} el
 */
function replaceHref(el) {
    let tagName = el.innerHTML.trim().match(/\w+/)[0] ?? '';
    switch (tagName) {
        case 'resource':
            el.href = Config.wwwroot + '/course/format/cop/posts.php?view=resource&id=' + getCourseId();
            el.title = "See a list of all posts tagged 'resource'";
            break;
        case 'event':
            el.href = Config.wwwroot + '/calendar/view.php?course=' + getCourseId();
            el.title = "Visit the Community of Practice events calendar";
            break;
        default:
            el.removeAttribute('href');
    }
}

export const init = ()=> {
    if (isForum() && TAGS) {
        Array.from(TAGS, el => replaceHref(el));
    }
};