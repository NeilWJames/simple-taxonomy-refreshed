# Filters

The following filters are available for this plug-in:

* staxo_auto_content_types

    Modifies the selection list of auto-extract options

* staxo_can_edit_callbacks

    Decides whether the current user may change the callback fields (update_count_callback, rest_controller_class, meta box callbacks).
    
    Default: users with unfiltered_html (super admins on multisite). Receives the default, the user and the taxonomy name (empty for a new taxonomy).

* staxo_check_merge

    Modifies the data being written to wp_options table

* staxo_object_types

    Modifies the selection list of public post types (used to assign taxonomies to)

* staxo_prepare_args

    Modifies the parameters passed to register_taxonomy

* staxo_taxo_import_convert_select

    Modifies the default taxonomy selectors (for displaying which taxonomy to import/convert) 

* staxo_term_count_statuses

    Filter to manage additional post_statuses for Terms Control entered on the screen. 

* staxo_widget_title

    Modifies the widget title

* staxo_widget_tag_cloud_args

    Modifies the arguments sent to the tag cloud widget

* staxo_widget_tag_list_args

    Modifies the list get_terms arguments.
