(function(a,b,c,d,e){const{registerBlockType:f,createBlock:g}=wp.blocks,{createElement:h}=wp.element,{InspectorControls:i}=wp.blockEditor,{PanelBody:j,RadioControl:k,RangeControl:l,SelectControl:m,TextControl:n,ToggleControl:o}=wp.components,{__:p}=wp.i18n,{useSelect:q}=wp.data;f("simple-taxonomy-refreshed/cloud-widget",{title:p("Taxonomy Cloud","simple-taxonomy-refreshed"),description:p("Display a Taxonomy Cloud.","simple-taxonomy-refreshed"),icon:"admin-page",attributes:{title:{type:"string"},taxonomy:{type:"string"},disptype:{type:"string",default:"cloud"},small:{type:"number",default:50},big:{type:"number",default:150},alignment:{type:"string",default:"justify"},orderby:{type:"string",default:"name"},ordering:{type:"string",default:"ASC"},showcount:{type:"boolean",default:!1},numdisp:{type:"number",default:0},minposts:{type:"number",default:0},align:{type:"string"},backgroundColor:{type:"string"},linkColor:{type:"string"},textColor:{type:"string"},gradient:{type:"string"},fontSize:{type:"string"},style:{type:"object"}},supports:{align:!0,color:{gradients:!0,link:!0},spacing:{margin:!0,padding:!0},typography:{fontSize:!0,lineHeight:!0}},edit(a){const b=a.attributes,c=a.setAttributes;var d=[];for(key in staxo_data)d.push({label:staxo_data[key],value:key});return h("div",{},[h(e,{block:"simple-taxonomy-refreshed/cloud-widget",attributes:b}),h(i,{},[h(j,{title:p("Taxonomy Cloud Settings","simple-taxonomy-refreshed"),initialOpen:!0},[h(n,{value:b.title,label:p("Title","simple-taxonomy-refreshed"),onChange:function(a){c({title:a})}}),h(k,{label:p("Taxonomy","simple-taxonomy-refreshed"),selected:b.taxonomy,options:d,onChange:function(a){c({taxonomy:a})}}),h(k,{label:p("Display Type","simple-taxonomy-refreshed"),selected:b.disptype,options:[{label:p("Cloud","simple-taxonomy-refreshed"),value:"cloud"},{label:p("List","simple-taxonomy-refreshed"),value:"list"}],onChange:function(a){c({disptype:a})}}),h(l,{value:b.small,label:p("Tag size - Smallest","simple-taxonomy-refreshed"),onChange:function(a){c({small:parseInt(a)})},min:40,max:100}),h(l,{value:b.big,label:p("Tag size - Largest","simple-taxonomy-refreshed"),onChange:function(a){c({big:parseInt(a)})},min:100,max:160}),h(k,{label:p("Text Alignment","simple-taxonomy-refreshed"),selected:b.alignment,options:[{label:p("Centre","simple-taxonomy-refreshed"),value:"center"},{label:p("Left","simple-taxonomy-refreshed"),value:"left"},{label:p("Right","simple-taxonomy-refreshed"),value:"right"},{label:p("Justify","simple-taxonomy-refreshed"),value:"justify"}],onChange:function(a){c({alignment:a})}}),h(k,{label:p("Order choice","simple-taxonomy-refreshed"),selected:b.orderby,options:[{label:p("Name","simple-taxonomy-refreshed"),value:"name"},{label:p("Count","simple-taxonomy-refreshed"),value:"count"}],onChange:function(a){c({orderby:a})}}),h(k,{label:p("Order sequence","simple-taxonomy-refreshed"),selected:b.ordering,options:[{label:p("Ascending","simple-taxonomy-refreshed"),value:"ASC"},{label:p("Descending","simple-taxonomy-refreshed"),value:"DESC"},{label:p("Random","simple-taxonomy-refreshed"),value:"RAND"}],onChange:function(a){c({ordering:a})}}),h(o,{type:"boolean",checked:b.showcount,label:p("Show the number of posts for each term?","wp-document-revisions"),help:p("Setting this on will give the number of posts linked to each term.","simple-taxonomy-refreshed"),onChange:function(a){c({showcount:a})}}),h(l,{value:b.numdisp,label:p("Maximum number of terms to display","simple-taxonomy-refreshed"),onChange:function(a){c({numdisp:parseInt(a)})},min:1,max:100}),h(l,{value:b.minposts,label:p("Minimum count of posts for term to be shown","simple-taxonomy-refreshed"),help:p("Set to 1 to remove empty terms.","simple-taxonomy-refreshed"),onChange:function(a){c({minposts:parseInt(a)})},min:0})])])])},save(){return null},transforms:{from:[{type:"block",blocks:["core/legacy-widget"],isMatch:({idBase:a,instance:b})=>!!b?.raw&&"staxonomy"===a,transform:({instance:a})=>g("simple-taxonomy-refreshed/cloud-widget",{name:a.raw.name})}]}})})(window.wp.blocks,window.wp.element,window.wp.blockEditor,window.wp.components,window.wp.serverSideRender,window.wp.i18n,window.wp.data);