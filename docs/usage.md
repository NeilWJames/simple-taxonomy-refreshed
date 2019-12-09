# Use of the Plugin

## Custom Taxonomies Definition

When the plugin is activated, the configuration is available in the administration screen under *Settings* -> *Custom Taxonomies*

The screen has three sections :
* Taxonomy List

This gives a list of the previous entered Custom Taxonomies which is empty when first used.

![Empty Taxonomy List](../images/CustTaxEmpty.png)

The taxonomy label name will be used to identify the taxonomy used in the plugin screens.

A number of significant properties of each taxonomy is shown in this list.

Once there are some taxonomies, the list will be populated.

![Taxonomy Listing](../images/AddTaxList.png)

Some functional options will appear in the space under each taxonomy label when the pointer is over the area.


| Option | Processing |
| ---------------- | ----------------------------------------- |
|Modify          | Modify data - in a new panel [Add/Modify Taxonomy](./addmod.md)
|Export PHP      | Creates a download file containing the parameters |
|Delete          | Delete the parameter entries; leaves the term data |
|Flush & Delete  | Deletes the parameter entries and the term data |

See the [example page](./example.md) for an example download file.

* Add a new taxonomy

This part of the form is available to add a new Custom Taxonomy at any time. It contains default values for the taxonomies.

When modifying an existing taxonomy a panel with only this section is displayed with the existing data held.

See [Add/Modify Taxonomy](./addmod.md) for further information of the process.

* Export/Import

This section allows the entire Custom Taxonomy definitions to be exported and/or imported to the browser.

The data is held in JSON format and is incompatible with data stored by the plugin [Simple Taxonomy](https://github.com/herewithme/simple-taxonomy/)

![Export/Import](../images/ExportImport.png)

## Tools

When the plugin is activated, two tools are available in the administration menu under *Tools*. They are *Terms migrate* and *Terms import*.

### Terms migrate

This tool enables the existing terms held against a taxonomy to be copied to another within the database.

![Terms migrate screen](../images/MigScreen1.png)

The user selects the Source and Destination taxonomies - which may be standard (built-in) or custom ones.

Once selected the entire set of terms held in the Source taxonomy are extracted from the database and entered into the screen ready for loading with the [Terms Import](./TermsImp.md) function.

This gives the opportunity to edit this list to choose subsets, etc.

If both of these are Hierarchical, then the terms will be output in a format to be loaded as a hierarchy, otherwise a simple list will be produced.

See [example page](./example.md) to see a worked example of its usage.


### Terms import

This tool allows terms to be entered in bulk into a selected taxonomy.

The user selects the Taxonomy and also whether the data to be entered as non-hierarchical, hierarchical with leading spaces or tabs.  

For the hierarchical data entry, the number of spaces or tabs denote the hierarchy. Clearly the number of leading spaces or tabs can only br one greater than the immediately preceding line. 

Finally there is a text area where the terms are entered.

The terms are entered one per line. For hierarchical taxonomies it may be necessary to  enter existing terms in order to give the correct context for a sub-term to be entered. Existing term will not be updated.

As only term text is entered, the slug is generated from the term text and no description is created.

This data can be entered or amended in the appropriate Taxonomy maintenance screen. 

See the [example page](./example.md) for a specific example of the function usage.
