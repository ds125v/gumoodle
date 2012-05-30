// Javascript functions for course format

M.course = M.course || {};

M.course.format = M.course.format || {};

/**
 * Get sections config for this format
 *
 * The section structure is:
 * <ul class="weeks">
 *  <li class="section">...</li>
 *  <li class="section">...</li>
 *   ...
 * </ul>
 *
 * @return {object} section list configuration
 */
M.course.format.get_config = function() {
    return {
        container_node : 'ul',
        container_class : 'weeks',
        section_node : 'li',
        section_class : 'section'
    };
}

/**
 * Swap section
 *
 * @param {YUI} Y YUI3 instance
 * @param {string} node1 node to swap to
 * @param {string} node2 node to swap with
 * @return {NodeList} section list
 */
M.course.format.swap_sections = function(Y, node1, node2) {
    var CSS = {
        COURSECONTENT : 'course-content',
        LEFT : 'left',
        SECTIONADDMENUS : 'section_add_menus',
        WEEKDATES: 'sectionname'
    };

    var sectionlist = Y.Node.all('.'+CSS.COURSECONTENT+' '+M.course.format.get_section_selector(Y));
    // Swap left block
    sectionlist.item(node1).one('.'+CSS.LEFT).swap(sectionlist.item(node2).one('.'+CSS.LEFT));
    // Swap menus
    sectionlist.item(node1).one('.'+CSS.SECTIONADDMENUS).swap(sectionlist.item(node2).one('.'+CSS.SECTIONADDMENUS));
    // Swap week dates
    sectionlist.item(node1).one('.'+CSS.WEEKDATES).swap(sectionlist.item(node2).one('.'+CSS.WEEKDATES));
}
