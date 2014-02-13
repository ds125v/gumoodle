YUI.add('moodle-core-dragdrop', function(Y) {
    var MOVEICON = {
        pix: "i/move_2d",
        largepix: "i/dragdrop",
        component: 'moodle',
        cssclass: 'moodle-core-dragdrop-draghandle'
    };

   /*
    * General DRAGDROP class, this should not be used directly,
    * it is supposed to be extended by your class
    */
    var DRAGDROP = function() {
        DRAGDROP.superclass.constructor.apply(this, arguments);
    };

    Y.extend(DRAGDROP, Y.Base, {
        goingup : null,
        absgoingup : null,
        samenodeclass : null,
        parentnodeclass : null,
        groups : [],
        lastdroptarget : null,
        initializer : function() {
            // Listen for all drag:start events
            Y.DD.DDM.on('drag:start', this.global_drag_start, this);
            // Listen for all drag:end events
            Y.DD.DDM.on('drag:end', this.global_drag_end, this);
            // Listen for all drag:drag events
            Y.DD.DDM.on('drag:drag', this.global_drag_drag, this);
            // Listen for all drop:over events
            Y.DD.DDM.on('drop:over', this.global_drop_over, this);
            // Listen for all drop:hit events
            Y.DD.DDM.on('drop:hit', this.global_drop_hit, this);
            // Listen for all drop:miss events
            Y.DD.DDM.on('drag:dropmiss', this.global_drag_dropmiss, this);

            Y.one(Y.config.doc.body).delegate('key', this.global_keydown, 'down:32, enter, esc', '.' + MOVEICON.cssclass, this);
            Y.one(Y.config.doc.body).delegate('click', this.global_keydown, '.' + MOVEICON.cssclass , this);
        },

        get_drag_handle: function(title, classname, iconclass, large) {
            var iconname = MOVEICON.pix;
            if (large) {
                iconname = MOVEICON.largepix;
            }
            var dragicon = Y.Node.create('<img />')
                .setStyle('cursor', 'move')
                .setAttrs({
                    'src' : M.util.image_url(iconname, MOVEICON.component),
                    'alt' : title
                });
            if (iconclass) {
                dragicon.addClass(iconclass);
            }

            var dragelement = Y.Node.create('<span></span>')
                .addClass(classname)
                .setAttribute('title', title)
                .setAttribute('tabIndex', 0)
                .setAttribute('data-draggroups', this.groups)
                .setAttribute('aria-grabbed', 'false')
                .setAttribute('role', 'button');
            dragelement.appendChild(dragicon);
            dragelement.addClass(MOVEICON.cssclass);

            return dragelement;
        },

        lock_drag_handle: function(drag, classname) {
            drag.removeHandle('.'+classname);
        },

        unlock_drag_handle: function(drag, classname) {
            drag.addHandle('.'+classname);
        },

        ajax_failure: function(response) {
            var e = {
                name : response.status+' '+response.statusText,
                message : response.responseText
            };
            return new M.core.exception(e);
        },

        in_group: function(target) {
            var ret = false;
            Y.each(this.groups, function(v, k) {
                if (target._groups[v]) {
                    ret = true;
                }
            }, this);
            return ret;
        },
        /*
         * Drag-dropping related functions
         */
        global_drag_start : function(e) {
            // Get our drag object
            var drag = e.target;
            // Check that drag object belongs to correct group
            if (!this.in_group(drag)) {
                return;
            }
            // Set some general styles here
            drag.get('node').setStyle('opacity', '.25');
            drag.get('dragNode').setStyles({
                opacity: '.75',
                borderColor: drag.get('node').getStyle('borderColor'),
                backgroundColor: drag.get('node').getStyle('backgroundColor')
            });
            drag.get('dragNode').empty();
            this.drag_start(e);
        },

        global_drag_end : function(e) {
            var drag = e.target;
            // Check that drag object belongs to correct group
            if (!this.in_group(drag)) {
                return;
            }
            //Put our general styles back
            drag.get('node').setStyles({
                visibility: '',
                opacity: '1'
            });
            this.drag_end(e);
        },

        global_drag_drag : function(e) {
            var drag = e.target,
                info = e.info;

            // Check that drag object belongs to correct group
            if (!this.in_group(drag)) {
                return;
            }

            // Note, we test both < and > situations here. We don't want to
            // effect a change in direction if the user is only moving side
            // to side with no Y position change.

            // Detect changes in the position relative to the start point.
            if (info.start[1] < info.xy[1]) {
                // We are going up if our final position is higher than our start position.
                this.absgoingup = true;

            } else if (info.start[1] > info.xy[1]) {
                // Otherwise we're going down.
                this.absgoingup = false;
            }

            // Detect changes in the position relative to the last movement.
            if (info.delta[1] < 0) {
                // We are going up if our final position is higher than our start position.
                this.goingup = true;

            } else if (info.delta[1] > 0) {
                // Otherwise we're going down.
                this.goingup = false;
            }

            this.drag_drag(e);
        },

        global_drop_over : function(e) {
            // Check that drop object belong to correct group.
            if (!e.drop || !e.drop.inGroup(this.groups)) {
                return;
            }

            // Get a reference to our drag and drop nodes.
            var drag = e.drag.get('node'),
                drop = e.drop.get('node');

            // Save last drop target for the case of missed target processing.
            this.lastdroptarget = e.drop;

            // Are we dropping within the same parent node?
            if (drop.hasClass(this.samenodeclass)) {
                var where;

                if (this.goingup) {
                    where = "before";
                } else {
                    where = "after";
                }

                // Add the node contents so that it's moved, otherwise only the drag handle is moved.
                drop.insert(drag, where);
            } else if ((drop.hasClass(this.parentnodeclass) || drop.test('[data-droptarget="1"]')) && !drop.contains(drag)) {
                // We are dropping on parent node and it is empty
                if (this.goingup) {
                    drop.append(drag);
                } else {
                    drop.prepend(drag);
                }
            }
            this.drop_over(e);
        },

        global_drag_dropmiss : function(e) {
            // drag:dropmiss does not have e.drag and e.drop properties
            // we substitute them for the ease of use. For e.drop we use,
            // this.lastdroptarget (ghost node we use for indicating where to drop)
            e.drag = e.target;
            e.drop = this.lastdroptarget;
            // Check that drag object belongs to correct group
            if (!this.in_group(e.drag)) {
                return;
            }
            // Check that drop object belong to correct group
            if (!e.drop || !e.drop.inGroup(this.groups)) {
                return;
            }
            this.drag_dropmiss(e);
        },

        global_drop_hit : function(e) {
            // Check that drop object belong to correct group
            if (!e.drop || !e.drop.inGroup(this.groups)) {
                return;
            }
            this.drop_hit(e);
        },

        /**
         * This is used to build the text for the heading of the keyboard
         * drag drop menu and the text for the nodes in the list.
         * @method find_element_text
         * @param {Node} n The node to start searching for a valid text node.
         * @returns {string} The text of the first text-like child node of n.
         */
        find_element_text : function(n) {
            // The valid node types to get text from.
            var nodes = n.all('h2, h3, h4, h5, span, p, div.no-overflow, div.dimmed_text');
            var text = '';

            nodes.each(function () {
                if (text == '') {
                    if (Y.Lang.trim(this.get('text')) != '') {
                        text = this.get('text');
                    }
                }
            });

            if (text != '') {
                return text;
            }
            return M.util.get_string('emptydragdropregion', 'moodle');
        },

        /**
         * This is used to initiate a keyboard version of a drag and drop.
         * A dialog will open listing all the valid drop targets that can be selected
         * using tab, tab, tab, enter.
         * @method global_start_keyboard_drag
         * @param {Event} e The keydown / click event on the grab handle.
         * @param {Node} dragcontainer The resolved draggable node (an ancestor of the drag handle).
         * @param {Node} draghandle The node that triggered this action.
         */
        global_start_keyboard_drag : function(e, draghandle, dragcontainer) {
            M.core.dragdrop.keydragcontainer = dragcontainer;
            M.core.dragdrop.keydraghandle = draghandle;

            // Indicate to a screenreader the node that is selected for drag and drop.
            dragcontainer.setAttribute('aria-grabbed', 'true');
            // Get the name of the thing to move.
            var nodetitle = this.find_element_text(dragcontainer);
            var dialogtitle = M.util.get_string('movecontent', 'moodle', nodetitle);

            // Build the list of drop targets.
            var droplist = Y.Node.create('<ul></ul>');
            droplist.addClass('dragdrop-keyboard-drag');
            var listitem;
            var listitemtext;

            // Search for possible drop targets.
            var droptargets = Y.all('.' + this.samenodeclass + ', .' + this.parentnodeclass);

            droptargets.each(function (node) {
                var validdrop = false, labelroot = node;
                if (node.drop && node.drop.inGroup(this.groups) && node.drop.get('node') != dragcontainer) {
                    // This is a drag and drop target with the same class as the grabbed node.
                    validdrop = true;
                } else {
                    var elementgroups = node.getAttribute('data-draggroups').split(' ');
                    var i, j;
                    for (i = 0; i < elementgroups.length; i++) {
                        for (j = 0; j < this.groups.length; j++) {
                            if (elementgroups[i] == this.groups[j]) {
                                // This is a parent node of the grabbed node (used for dropping in empty sections).
                                validdrop = true;
                                // This node will have no text - so we get the first valid text from the parent.
                                labelroot = node.get('parentNode');
                                break;
                            }
                        }
                        if (validdrop) {
                            break;
                        }
                    }
                }

                if (validdrop) {
                    // It is a valid drop target - create a list item for it.
                    listitem = Y.Node.create('<li></li>');
                    listlink = Y.Node.create('<a></a>');
                    nodetitle = this.find_element_text(labelroot);

                    listitemtext = M.util.get_string('tocontent', 'moodle', nodetitle);
                    listlink.setContent(listitemtext);

                    // Add a data attribute so we can get the real drop target.
                    listlink.setAttribute('data-drop-target', node.get('id'));
                    // Notify the screen reader this is a valid drop target.
                    listlink.setAttribute('aria-dropeffect', 'move');
                    // Allow tabbing to the link.
                    listlink.setAttribute('tabindex', '0');

                    // Set the event listeners for enter, space or click.
                    listlink.on('click', this.global_keyboard_drop, this);
                    listlink.on('key', this.global_keyboard_drop, 'down:enter,32', this);

                    // Add to the list or drop targets.
                    listitem.append(listlink);
                    droplist.append(listitem);
                }
            }, this);

            // Create the dialog for the interaction.
            M.core.dragdrop.dropui = new M.core.dialogue({
                headerContent : dialogtitle,
                bodyContent : droplist,
                draggable : true,
                visible : true,
                centered : true
            });

            // Focus the first drop target.
            if (droplist.one('a')) {
                droplist.one('a').focus();
            }
        },

        /**
         * This is used as a simulated drag/drop event in order to prevent any
         * subtle bugs from creating a real instance of a drag drop event. This means
         * there are no state changes in the Y.DD.DDM and any undefined functions
         * will trigger an obvious and fatal error.
         * The end result is that we call all our drag/drop handlers but do not bubble the
         * event to anyone else.
         *
         * The functions/properties implemented in the wrapper are:
         * e.target
         * e.drag
         * e.drop
         * e.drag.get('node')
         * e.drop.get('node')
         * e.drag.addHandle()
         * e.drag.removeHandle()
         *
         * @class simulated_drag_drop_event
         * @param {Node} dragnode The drag container node
         * @param {Node} dropnode The node to initiate the drop on
         */
        simulated_drag_drop_event : function(dragnode, dropnode) {

            // Subclass for wrapping both drag and drop.
            var dragdropwrapper = function(node) {
                this.node = node;
            }

            // Method e.drag.get() - get the node.
            dragdropwrapper.prototype.get = function(param) {
                if (param == 'node' || param == 'dragNode' || param == 'dropNode') {
                    return this.node;
                }
                return null;
            };

            // Method e.drag.inGroup() - we have already run the group checks before triggering the event.
            dragdropwrapper.prototype.inGroup = function() {
                return true;
            };

            // Method e.drag.addHandle() - we don't want to run this.
            dragdropwrapper.prototype.addHandle = function() {};
            // Method e.drag.removeHandle() - we don't want to run this.
            dragdropwrapper.prototype.removeHandle = function() {};

            // Create instances of the dragdropwrapper.
            this.drop = new dragdropwrapper(dropnode);
            this.drag = new dragdropwrapper(dragnode);
            this.target = this.drop;
        },

        /**
         * This is used to complete a keyboard version of a drag and drop.
         * A drop event will be simulated based on the drag and drop nodes.
         * @method global_keyboard_drop
         * @param {Event} e The keydown / click event on the proxy drop node.
         */
        global_keyboard_drop : function(e) {
            // The drag node was saved.
            var dragcontainer = M.core.dragdrop.keydragcontainer;
            dragcontainer.setAttribute('aria-grabbed', 'false');
            // The real drop node is stored in an attribute of the proxy.
            var droptarget = Y.one('#' + e.target.getAttribute('data-drop-target'));

            // Close the dialog.
            M.core.dragdrop.dropui.hide();
            // Cancel the event.
            e.preventDefault();
            // Convert to drag drop events.
            var dragevent = new this.simulated_drag_drop_event(dragcontainer, dragcontainer);
            var dropevent = new this.simulated_drag_drop_event(dragcontainer, droptarget);
            // Simulate the full sequence.
            this.drag_start(dragevent);
            this.global_drop_over(dropevent);
            this.global_drop_hit(dropevent);
            M.core.dragdrop.keydraghandle.focus();
        },

        /**
         * This is used to cancel a keyboard version of a drag and drop.
         *
         * @method global_cancel_keyboard_drag
         */
        global_cancel_keyboard_drag : function() {
            if (M.core.dragdrop.keydragcontainer) {
                M.core.dragdrop.keydragcontainer.setAttribute('aria-grabbed', 'false');
                M.core.dragdrop.keydraghandle.focus();
                M.core.dragdrop.keydragcontainer = null;
            }
        },

        /**
         * Process key events on the drag handles.
         * @method global_keydown
         * @param {Event} e The keydown / click event on the drag handle.
         */
        global_keydown : function(e) {
            var draghandle = e.target.ancestor('.' + MOVEICON.cssclass, true),
                dragcontainer,
                draggroups;

            if (draghandle === null) {
                // The element clicked did not have a a draghandle in it's lineage.
                return;
            }

            if (e.keyCode === 27 ) {
                // Escape to cancel from anywhere.
                this.global_cancel_keyboard_drag();
                e.preventDefault();
                return;
            }

            // Only process events on a drag handle.
            if (!draghandle.hasClass(MOVEICON.cssclass)) {
                return;
            }

            // Do nothing if not space or enter.
            if (e.keyCode !== 13 && e.keyCode !== 32 && e.type !== 'click') {
                return;
            }

            // Check the drag groups to see if we are the handler for this node.
            draggroups = draghandle.getAttribute('data-draggroups').split(' ');
            var i, j, validgroup = false;

            for (i = 0; i < draggroups.length; i++) {
                for (j = 0; j < this.groups.length; j++) {
                    if (draggroups[i] === this.groups[j]) {
                        validgroup = true;
                        break;
                    }
                }
                if (validgroup) {
                    break;
                }
            }
            if (!validgroup) {
                return;
            }

            // Valid event - start the keyboard drag.
            dragcontainer = draghandle.ancestor('.yui3-dd-drop');
            this.global_start_keyboard_drag(e, draghandle, dragcontainer);

            e.preventDefault();
        },

        /*
         * Abstract functions definitions
         */
        drag_start : function(e) {},
        drag_end : function(e) {},
        drag_drag : function(e) {},
        drag_dropmiss : function(e) {},
        drop_over : function(e) {},
        drop_hit : function(e) {}
    }, {
        NAME : 'dragdrop',
        ATTRS : {}
    });

M.core = M.core || {};
M.core.dragdrop = DRAGDROP;

}, '@VERSION@', {requires:['base', 'node', 'io', 'dom', 'dd', 'event-key', 'event-focus', 'moodle-core-notification']});
