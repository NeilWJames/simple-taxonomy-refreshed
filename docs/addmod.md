# Add/Modify a Taxonomy

**Note. Defining a taxonomy uses the first seven panels. There are an additional four panels used for defining additional uses of the taxonomy. These four panels are available to both custom taxonomies and those created by other plugins or WordPress itself.**

The main purpose of the plugin is to be able to set the very many parameters available to the user when defining a Taxonomy.

They are all set within this section. This appears in the middle of the initial Settings screen. It also appears as the entire screen for modification.

Because there are so many parameters, the window has been divided into a number of panels rather than supporting a very large page.

The names, in English, associated with the fields correspond to the variables that are being set. There is no point here trying to restate their usage or purpose.

See the [WordPress Documentation](https://developer.wordpress.org/reference/functions/register_taxonomy/) for details of their impact.

You can move from panel to panel reviewing the parameters - but they need to be stored into the database by clicking the Add or Update Taxonomy before they will take effect.

## Custom Main

This will be titled *Add Taxonomy* when adding a taxonomy or *Modify Taxonomy* (with its name) when updating it.
![Main Panel](../images/AddTaxMain.png)

The name **must** be completed in order to add the taxonomy.

The last fields *Display Terms with Posts* is not related to WordPress standard functionality. It allows you to request that the taxonomy terms are added to posts are displayed with the post content and/or excerpt. When used, the list of terms can be prefixed and suffixed by the user-defined Before and After texts.

These fields may also be output directly using a shortcode `staxo_post_terms` or a block editor block `simple-taxonomy-refreshed/post-terms`. These will by default add the terms for all custom taxonomies (using the before and after text). If only one taxonomy is required, then add a parameter `tax='taxonomy_slug'`.

## External Main

![Main Panel](../images/ExternalExtraOpts.png)

These are optional entries that may be added to a taxonomy defined elsewhere - and the corresponding element, say for WPGraphQL may already be set up. Clearly this should not then be defined here.

## Visibility

![Visibility Panel](../images/AddTaxVis.png)

This panel contains all the fields that control how the taxonomy is used within WordPress.

## Labels

![Labels Panel](../images/AddTaxLabl.png)

There are many labels that can be used for a taxonomy, any and all can be changed here.

Scroll down the page using the plugin to see them all.

A label "No Term" has been created for use with the plugin. See "Term Control" for details.

The plugin uses core WordPress default values. These depend on whether the taxonomy is hierarchical (Category) or not (Tag).

When creating a non-hierarchical taxonomy, it is recommended to set the hierarchical value to False on the main tab (the labels appear as Category type) and save the taxonomy.

Then modify the taxonomy. Tha labels will appear as Tag type and can be modified appropriately.

## Rewrite URL

![Rewrite URL Panel](../images/AddTaxRewr.png)

Enables the taxonomy to be a selection criterion.

## Permissions

![Permissions Panel](../images/AddTaxPerm.png)

Sets the capabilities required to manage the taxonomy. These need to be existing capabilities.

## REST

![REST Panel](../images/AddTaxRest.png)

If non-standard processing is required for REST processing, the code routines can be declared here.

## Other

![Other Panel](../images/AddTaxOthr.png)

Various other parameters are managed here.

## WPGraphQL

![WPGraphQL Panel](../images/AddTaxGrQL.png)

This is an optional set of parameters and is provided as a convenience to avoid user coding when WPGraphQL is installed.

## Admin List Filter

![Admin List Filter Panel](../images/AddTaxAdmin.png)

This is an optional set of parameters and allows the user to use the taxonomy as a dropdown filter for selected post types on the admin list screens.

## Term Count

![Term Count Panel](../images/AddTaxTerms1.png)

This is an optional set of parameters and allows the user to modify the standard term count of published posts only and to select which post statuses should be included.

Two options are supported - either to count all statuses except trash; and the other allows the statuses to be individually specified.

![Term Count Panel showing statuses](../images/AddTaxTerms2.png)

## Term Control

![Term Control Panel](../images/AddTaxControl.png)

This is an optional set of parameters that allows the user to require a minimum and/or maximum number of terms in the taxonomy to be attached to the post and to select the post statuses when the test is to be applied.

If the taxonomy applies to more than one post type it is possible to choose which types to apply the control. when no type is selected here, then this means all post types are subject to term control.

Since a user may be able to edit the post, but not add or delete terms, this can be notified to the user that the number is outside the required bounds, but the edit is allowed.

The option "When post is saved" means that the test is done during save post processing - and updates will be rejected if outside the bounds.

The option "As terms are changed and when post is changed" means the bounds are checked during editing as well. This functionality is mostly complete, but it is possible that there are some holes. To ensure incorrect data is not saved, the "when post is saved" processing is also invoked.

When the taxonomy is defined as hierarchical, the terms will normally be displayed as a set of checkboxes. When the maximum number of terms is set as 1, i.e. only one term is possible, the checkboxes are changed to radio buttons by the plugin. This will also be the case if there is no minimum (or is set to 0). To support this, a virtual term "No term" has been created (see "Labels" above) that can be selected, but no term of the taxonomy will be attached to the post.