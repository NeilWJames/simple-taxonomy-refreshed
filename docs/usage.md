# Use of the Plugin

## Custom Taxonomies Definition

When the plugin is activated, the configuration is available in the administration screen under *Settings* -> *Custom Taxonomies*

The screen has three sections :
* Taxonomy List

This gives a list of the previous entered Custom Taxonomies which is empty when first used.
![Empty Taxonomy List](./CustTaxEmpty.png)

The taxonomy label name will be used to identify the taxonly used in the plugin screens.

A number of significant properties of each taxonomy is shown in this list. 

* Add a new taxonomy

This part of the form is available to add a new Custom Taxonomy at any time. It contains default values for the taxonomies.

When modifying an existing taxonomy a panel with only this section is displayed with the existing data held.

See [Add/Modify Taxonomy](./addmod.md) for further information of the process.

* Export/Import

This section allows the entire Custom Taxonomy definitions to be exported and/or imported to the browser.

The data is held in JSON format and is incompatible with data stored by the plugin [Simple Taxonomy]{https://github.com/herewithme/simple-taxonomy/}

![Empty Taxonomy List](./CustTaxEmpty.png)

## Tools

When the plugin is activated, two tools are available in the administration screen under *Tools*. They are *Terms migrate* and *Terms import*.

### Terms migrate

This allows you to extract the existing terms held against a taxonomy and prepare them for importing into another taxonomy.

See [Terms migrate]{./TermsMig.md} for more information.

Its output is a pre-filled Terms import screen - allowing for further editing before the actual import.

### Terms import

This allows you to import new terms into a taxonomy.

See [Terms import]{./TermsImp.md} for more information.
