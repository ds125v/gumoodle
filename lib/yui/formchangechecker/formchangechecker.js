YUI.add('moodle-core-formchangechecker',
    function(Y) {
        // The CSS selectors we use
        var CSS = {
        };

        var FORMCHANGECHECKERNAME = 'core-formchangechecker';

        var FORMCHANGECHECKER = function() {
            FORMCHANGECHECKER.superclass.constructor.apply(this, arguments);
        }

        Y.extend(FORMCHANGECHECKER, Y.Base, {
                /**
                 * Initialize the module
                 */
                initializer : function(config) {
                    var formid = 'form#' + this.get('formid');

                    // Add change events to the form elements
                    Y.all(formid + ' input').once('change', M.core_formchangechecker.set_form_changed, this);
                    Y.all(formid + ' textarea').once('change', M.core_formchangechecker.set_form_changed, this);
                    Y.all(formid + ' select').once('change', M.core_formchangechecker.set_form_changed, this);

                    // Add a focus event to check for changes which are made without triggering a change event
                    Y.all(formid + ' input').on('focus', this.store_initial_value, this);
                    Y.all(formid + ' textarea').on('focus', this.store_initial_value, this);
                    Y.all(formid + ' select').on('focus', this.store_initial_value, this);

                    // We need any submit buttons on the form to set the submitted flag
                    Y.one(formid).on('submit', M.core_formchangechecker.set_form_submitted, this);

                    // YUI doesn't support onbeforeunload properly so we must use the DOM to set the onbeforeunload. As
                    // a result, the has_changed must stay in the DOM too
                    window.onbeforeunload = M.core_formchangechecker.report_form_dirty_state;
                },

                /**
                 * Store the initial value of the currently focussed element
                 *
                 * If an element has been focussed and changed but not yet blurred, the on change
                 * event won't be fired. We need to store it's initial value to compare it in the
                 * get_form_dirty_state function later.
                 */
                store_initial_value : function(e) {
                    if (e.target.hasClass('ignoredirty')) {
                        // Don't warn on elements with the ignoredirty class
                        return;
                    }
                    if (M.core_formchangechecker.get_form_dirty_state()) {
                        // Clear the store_initial_value listeners as the form is already dirty so
                        // we no longer need to call this function
                        var formid = 'form#' + this.get('formid');

                        Y.all(formid + ' input').detach('focus', this.store_initial_value, this);
                        Y.all(formid + ' textarea').detach('focus', this.store_initial_value, this);
                        Y.all(formid + ' select').detach('focus', this.store_initial_value, this);
                        return;
                    }

                    // Make a note of the current element so that it can be interrogated and
                    // compared in the get_form_dirty_state function
                    M.core_formchangechecker.stateinformation.focused_element = {
                        element : e.target,
                        initial_value : e.target.get('value')
                    }
                }
            },
            {
                NAME : FORMCHANGECHECKERNAME,
                ATTRS : {
                    formid : {
                        'value' : ''
                    }
                }
            }
        );

        M.core_formchangechecker = M.core_formchangechecker || {};

        // We might have multiple instances of the form change protector
        M.core_formchangechecker.instances = M.core_formchangechecker.instances || [];
        M.core_formchangechecker.init = function(config) {
            var formchangechecker = new FORMCHANGECHECKER(config);
            M.core_formchangechecker.instances.push(formchangechecker);
            return formchangechecker;
        }

        // Store state information
        M.core_formchangechecker.stateinformation = [];

        /**
         * Set the form changed state to true
         */
        M.core_formchangechecker.set_form_changed = function(e) {
            if (e && e.target && e.target.hasClass('ignoredirty')) {
                // Don't warn on elements with the ignoredirty class
                return;
            }
            M.core_formchangechecker.stateinformation.formchanged = 1;

            // Once the form has been marked as dirty, we no longer need to keep track of form elements
            // which haven't yet blurred
            delete M.core_formchangechecker.stateinformation.focused_element;
        }

        /**
         * Set the form submitted state to true
         */
        M.core_formchangechecker.set_form_submitted = function() {
            M.core_formchangechecker.stateinformation.formsubmitted = 1;
        }

        /**
         * Attempt to determine whether the form has been modified in any way and
         * is thus 'dirty'
         *
         * @return Integer 1 is the form is dirty; 0 if not
         */
        M.core_formchangechecker.get_form_dirty_state = function() {
            var state = M.core_formchangechecker.stateinformation;

            // If the form was submitted, then return a non-dirty state
            if (state.formsubmitted) {
                return 0;
            }

            // If any fields have been marked dirty, return a dirty state
            if (state.formchanged) {
                return 1;
            }

            // If a field has been focused and changed, but still has focus then the browser won't fire the
            // onChange event. We check for this eventuality here
            if (state.focused_element) {
                if (state.focused_element.element.get('value') != state.focused_element.initial_value) {
                    return 1;
                }
            }

            // Handle TinyMCE editor instances
            // We can't add a listener in the initializer as the editors may not have been created by that point
            // so we do so here instead
            if (typeof tinyMCE != 'undefined') {
                for (var editor in tinyMCE.editors) {
                    if (tinyMCE.editors[editor].isDirty()) {
                        return 1;
                    }
                }
            }

            // If we reached here, then the form hasn't met any of the dirty conditions
            return 0;
        };

        /**
         * Return a suitable message if changes have been made to a form
         */
        M.core_formchangechecker.report_form_dirty_state = function(e) {
            if (!M.core_formchangechecker.get_form_dirty_state()) {
                // the form is not dirty, so don't display any message
                return;
            }

            // This is the error message that we'll show to browsers which support it
            var warningmessage = M.util.get_string('changesmadereallygoaway', 'moodle');

            // Most browsers are happy with the returnValue being set on the event
            // But some browsers do not consistently pass the event
            if (e) {
                e.returnValue = warningmessage;
            }

            // But some require it to be returned instead
            return warningmessage;
        };
    },
    '@VERSION@', {
        requires : ['base']
    }
);
