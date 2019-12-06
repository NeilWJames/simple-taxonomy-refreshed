# Add/Modify a Taxonomy

The main purpose of the plug-in is to set the very many parameters available to the user when defining a Taxonomy.

They are all set within this section. This appears in the middle of the initial Settings screen. It also appears as the entire screen for modification.

Because there are so many parameters rather than supporting a very large page, the window has been divided into a number of panels.

The names, in English, associated with the fields correspond to the variables that are being set. There is no point here second guessing their usage or purpose.

See the [WordPress Documentation](https://developer.wordpress.org/reference/functions/register_taxonomy/) for their detail.

You can move from panel to panel reviewing the parameters - but they need to be stored into the database by clicking the Add or Update Taxonomy before taking effect.

## Main

![Main Panel](../images/AddTaxMain.png)

The name **must** be completed in order to add the taxonomy.

The last field *Display Terms with Posts* is not related to a parameter. It allows you to request that the taxonomy terms added to posts are displayed with the post content and/or excerpt.

## Visibility

![Visibility Panel](../images/AddTaxVis.png)

This panel contains all the fields that control how the taxonomy is used within WordPress.

## Labels

![Labels Panel](../images/AddTaxLabl.png)

There are many labels that can be used for a taxonomy, any and all can be changed here.

## Rewrite URL

![Rewrite URL Panel](../images/AddTaxRewr.png)

Enables the taxonomy to be a selection criterion.

## Permissions

![Permissions Panel](../images/AddTaxPerm.png)

Sets the capabilities required to manage the taxonomy. These need to be existing capabilities.

## REST

![REST Panel](../images/AddTaxRest.png)

If non-standard processing is required for RST processing, the code routines can be declared here.

## Other

![Other Panel](../images/AddTaxOthr.png)

Various other parameters are managed here.

## WPGraphQL

![PWPGraphQL Panel](../images/AddTaxGrQL.png)

This is an optional set of parameters and is provided as a convenience to avoid user codong if WPGraphQL is installed.


