/**
 * YUI Module for block_eventsengine
 *
 * @package   block_eventsengine
 * @category  event
 * @copyright 2017 onwards Brent Boghosian <brentboghosian@alumni.uwaterloo.ca>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

YUI.add('moodle-block_eventsengine-engineactionselect', function(Y) {

    /**
     * The module name
     * @property MODULEBASENAME
     * @type {String}
     * @default "core-engineactionselect"
     */
    var MODULEBASENAME = 'core-engineactionselect';

    /**
     * This method calls the base class constructor
     * @method MODULEBASE
     */
    var MODULEBASE = function() {
        MODULEBASE.superclass.constructor.apply(this, arguments);
    };

    /**
     * @class M.block_eventsengine.engineactionselect
     */
    Y.extend(MODULEBASE, Y.Base, {
        /**
         * @property availableengines
         * @type array
         * @default []
         */
        availableengines: [],

        /**
         * @property availableactions
         * @type array
         * @default []
         */
        availableactions: [],

        /**
         * Initialize the customfieldform module
         * @param object args function arguments
         */
        initializer : function(args) {
            this.availableengines = args.availableengines;
            this.availableactions = args.availableactions;

            Y.on('change', this.update_selectors, '#id_context_selector', this);
        },

        /**
         * update menu options
         */
        update_selectors: function() {
            var contextselector = document.getElementById("id_context_selector");
            var engineselector = document.getElementById("id_engine_selector");
            var actionselector = document.getElementById("id_action_selector");
            if (contextselector && engineselector && actionselector) {
                var i;
                var context = contextselector.options[contextselector.selectedIndex].value;
                // Y.log(context);
                // Clear current engine selections.
                for (i in engineselector.options) {
                    engineselector.options.remove(i);
                }
                // Clear current action selections.
                for (i in actionselector.options) {
                    actionselector.options.remove(i);
                }
                if (typeof this.availableengines[context] != 'undefined' && typeof this.availableactions[context] != 'undefined') {
                    var engine, action, elem;
                    // Update engine selections.
                    for (engine in this.availableengines[context]) {
                        elem = new Option(this.availableengines[context][engine], engine);
                        engineselector.options.add(elem);
                    }
                    // Update action selections.
                    for (action in this.availableactions[context]) {
                        elem = new Option(this.availableactions[context][action], action);
                        actionselector.options.add(elem);
                    }
                    // Check for and add wildcard 'any' actions.
                    if (typeof this.availableactions['any'] != 'undefined') {
                        for (action in this.availableactions['any']) {
                            elem = new Option(this.availableactions['any'][action], action);
                            actionselector.options.add(elem);
                        }
                    }
                }
            }
        }
    },
    {
        NAME : MODULEBASENAME,
        ATTRS : {}
    }
    );

    // Ensure that M.block_eventsengine exusts and is initialized correctly
    M.block_eventsengine = M.block_eventsengine || {};

    /**
     * Entry point for engineactionselect form module
     * @param array availableengines
     * @param array availableactions
     * @return object the engineactionselect object
     */
    M.block_eventsengine.init_engineactionselect = function(availableengines, availableactions) {
        args = {
            availableengines: availableengines,
            availableactions: availableactions
        };
        // Y.log(args);
        return new MODULEBASE(args);
    }

}, '@VERSION@', { requires : ['base', 'event', 'node'] }
);
